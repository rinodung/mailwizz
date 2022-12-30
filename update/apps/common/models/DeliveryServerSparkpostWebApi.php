<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerSparkpostWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.6
 *
 */

class DeliveryServerSparkpostWebApi extends DeliveryServer
{
    /**
     * US region
     */
    const REGION_US = 'us';

    /**
     * EU region
     */
    const REGION_EU = 'eu';

    /**
     * @var string
     */
    public $ip_pool = '';

    /**
     * @var string
     */
    public $region = self::REGION_US;

    /**
     * @var string
     */
    protected $serverType = 'sparkpost-web-api';

    /**
     * @var string
     */
    protected $_initStatus;

    /**
     * @var string
     */
    protected $_preCheckError = '';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://www.sparkpost.com/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['password', 'required'],
            ['password', 'length', 'max' => 255],

            ['ip_pool', 'length', 'max' => 255],
            ['region', 'in', 'range' => array_keys($this->getRegionsList())],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'password' => t('servers', 'Api key'),
            'ip_pool'  => t('servers', 'Ip pool'),
            'region'   => t('servers', 'Api region'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'password' => t('servers', 'One of your sparkpost api keys.'),
            'ip_pool'  => t('servers', 'Your dedicated IP Pool, only if you have any.'),
            'region'   => t('servers', 'Mailgun api geo region.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'password' => t('servers', 'Api key'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerSparkpostWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerSparkpostWebApi $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     * @throws CException
     */
    public function sendEmail(array $params = []): array
    {
        /** @var array $params */
        $params = (array)hooks()->applyFilters('delivery_server_before_send_email', $this->getParamsArray($params), $this);

        if (!ArrayHelper::hasKeys($params, ['from', 'to', 'subject', 'body'])) {
            return [];
        }

        [$toEmail, $toName]     = $this->getMailer()->findEmailAndName($params['to']);
        [$fromEmail, $fromName] = $this->getMailer()->findEmailAndName($params['from']);

        if (!empty($params['fromName'])) {
            $fromName = $params['fromName'];
        }

        $replyToEmail = $replyToName = null;
        if (!empty($params['replyTo'])) {
            [$replyToEmail, $replyToName] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $headers = [];
        if (!empty($params['headers'])) {
            $headers = $this->parseHeadersIntoKeyValue($params['headers']);
        }

        $campaignId = StringHelper::randomSha1();
        $metaData   = [];
        if (!empty($params['campaignUid'])) {
            $metaData['campaign_uid'] = $campaignId = $params['campaignUid'];
        }
        if (!empty($params['subscriberUid'])) {
            $metaData['subscriber_uid'] = $params['subscriberUid'];
        }

        $sent = [];

        try {
            if (!$this->preCheckWebHook()) {
                throw new Exception($this->_preCheckError);
            }

            $sendParams = [
                'campaign_id' => $campaignId,
                'metadata'    => (object)$metaData,
                'recipients'  => [
                    [
                        'address' => [
                            'email' => $toEmail,
                            'name'  => $toName,
                        ],
                        'metadata'  => (object)$metaData,
                    ],
                ],
                'content'   => [
                    'from' => [
                        'email' => $fromEmail,
                        'name'  => $fromName,
                    ],
                    'subject'  => $params['subject'],
                    'reply_to' => !empty($replyToEmail) ? $replyToEmail : $fromEmail,
                    'headers'  => !empty($headers) ? $headers : new StdClass(),
                    'text'     => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
                    'html'     => $params['body'],
                ],
            ];

            // 1.3.7
            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_unique($params['attachments']);
                $sendParams['content']['attachments'] = [];
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $sendParams['content']['attachments'][] = [
                            'type' => 'application/octet-stream',
                            'name' => basename($attachment),
                            'data' => base64_encode((string)file_get_contents($attachment)),
                        ];
                    }
                }
            }
            //

            // 1.4.5
            if (!empty($this->ip_pool)) {
                $sendParams['options']['ip_pool'] = $this->ip_pool;
            }

            // unset the html content
            if ($onlyPlainText) {
                unset($sendParams['content']['html']);
            }

            $resp = $this->getClient()->post('transmissions', [
                'json' => $sendParams,
            ]);
            if ((int)$resp->getStatusCode() !== 200) {
                throw new Exception($resp->getReasonPhrase());
            }
            /** @var stdClass $response */
            $response = json_decode((string)$resp->getBody());

            if (!empty($response->errors)) {
                $errors = [];
                foreach ($response->errors as $error) {
                    $errors[] = $error->message . (!empty($error->description) ? ' - ' . $error->description : '');
                }
                throw new Exception(implode('<br />', $errors));
            }

            if (empty($response->results)) {
                throw new Exception(print_r($response, true));
            }

            $this->getMailer()->addLog('OK');
            $sent = ['message_id' => $response->results->id];
        } catch (Exception $e) {
            $this->getMailer()->addLog($e->getMessage());
        }

        if ($sent) {
            $this->logUsage();
        }

        hooks()->doAction('delivery_server_after_send_email', $params, $this, $sent);

        return (array)$sent;
    }

    /**
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport'] = self::TRANSPORT_SPARKPOST_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @return GuzzleHttp\Client
     */
    public function getClient(): GuzzleHttp\Client
    {
        static $client;
        if ($client !== null) {
            return $client;
        }

        $apiUrl = 'https://api.sparkpost.com/api/v1/';
        if ($this->region === self::REGION_EU) {
            $apiUrl = 'https://api.eu.sparkpost.com/api/v1/';
        }

        return $client = new GuzzleHttp\Client([
            'timeout'  => (int)$this->timeout,
            'base_uri' => $apiUrl,
            'headers'  => [
                'Authorization' => $this->password,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    public function createWebhook(): void
    {
        $resp = $this->getClient()->post('webhooks', [
            'json' => [
                'name'       => 'MWZ-#' . $this->server_id,
                'target'     => $this->getDswhUrl(),
                'auth_token' => $this->password,
                'events'     => ['bounce', 'spam_complaint', 'list_unsubscribe', 'link_unsubscribe'],
            ],
        ]);
        if ((int)$resp->getStatusCode() !== 200) {
            throw new Exception($resp->getReasonPhrase());
        }
        /** @var stdClass $response */
        $response = json_decode((string)$resp->getBody());

        if (!empty($response->errors)) {
            $errors = [];
            foreach ($response->errors as $error) {
                $errors[] = $error->message . (!empty($error->description) ? ' - ' . $error->description : '');
            }
            $error = t('servers', 'When creating the webhooks, we got following errors: {error}', [
                '{error}' => implode('<br />', $errors),
            ]);
            throw new Exception($error);
        }
    }

    /**
     * @throws Exception
     */
    public function deleteWebhook(): void
    {
        $resp = $this->getClient()->get('webhooks');
        if ((int)$resp->getStatusCode() !== 200) {
            throw new Exception($resp->getReasonPhrase());
        }
        /** @var stdClass $response */
        $response = json_decode((string)$resp->getBody());

        $url = $this->getDswhUrl();
        $ids = [];

        if (!empty($response->results)) {
            foreach ($response->results as $result) {
                if ($result->target == $url) {
                    $ids[] = $result->id;
                }
            }
        }

        foreach ($ids as $id) {
            $endpoint = 'webhooks/' . $id;
            $response = $this->getClient()->delete($endpoint);
            if ((int)$response->getStatusCode() !== 200) {
                throw new Exception($response->getReasonPhrase());
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form = new CActiveForm();
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'username'                => null,
            'hostname'                => null,
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'signing_enabled'         => null,
            'max_connection_messages' => null,
            'bounce_server_id'        => null,
            'force_sender'            => null,
            'ip_pool'                 => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'ip_pool', $this->fieldDecorator->getHtmlOptions('ip_pool')),
            ],
            'region'                  => [
                'visible'   => true,
                'fieldHtml' => $form->dropDownList($this, 'region', $this->getRegionsList(), $this->fieldDecorator->getHtmlOptions('region')),
            ],
        ], $fields));
    }

    /**
     * @return array
     */
    public function getRegionsList(): array
    {
        return [
            self::REGION_US => 'US',
            self::REGION_EU => 'EU',
        ];
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('ip_pool', (string)$this->ip_pool);
        $this->modelMetaData->getModelMetaData()->add('region', (string)$this->region);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->ip_pool     = (string)$this->modelMetaData->getModelMetaData()->itemAt('ip_pool');
        $this->region      = (string)$this->modelMetaData->getModelMetaData()->itemAt('region');
        $this->_initStatus = $this->status;
        $this->hostname    = 'web-api.sparkpost.com';
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->ip_pool     = (string)$this->modelMetaData->getModelMetaData()->itemAt('ip_pool');
        $this->region      = (string)$this->modelMetaData->getModelMetaData()->itemAt('region');
        $this->_initStatus = $this->status;
        parent::afterFind();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        try {
            $this->deleteWebhook();
        } catch (Exception $e) {
        }

        parent::afterDelete();
    }

    /**
     * @return bool
     */
    protected function preCheckWebHook(): bool
    {
        if (is_cli() || $this->getIsNewRecord() || $this->_initStatus !== self::STATUS_INACTIVE) {
            return true;
        }

        try {
            $this->deleteWebhook();
        } catch (Exception $e) {
        }

        try {
            $this->createWebhook();
        } catch (Exception $e) {
            $this->_preCheckError = $e->getMessage();
        }

        if ($this->_preCheckError) {
            return false;
        }

        return $this->save(false);
    }
}
