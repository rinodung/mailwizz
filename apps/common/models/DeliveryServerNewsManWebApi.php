<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerNewsManWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 *
 */

class DeliveryServerNewsManWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'newsman-web-api';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://www.newsmanapp.com/';

    /**
     * @var string
     */
    protected $_initStatus;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['username, password', 'required'],
            ['username, password', 'length', 'max' => 255],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'username'  => t('servers', 'Account ID'),
            'password'  => t('servers', 'Api key'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'username'  => t('servers', 'Account ID'),
            'password'  => t('servers', 'Api key'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'username'  => t('servers', 'Account ID'),
            'password'  => t('servers', 'Api key'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServer the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServer $model */
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

        $sent = [];

        try {
            $sendParams = [
                'key'        => $this->password,
                'account_id' => $this->username,
                'message'    => [
                    'from_name'  => $fromName,
                    'from_email' => $fromEmail,
                    'html'       => $params['body'],
                    'text'       => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
                    'headers'    => [],
                    'subject'    => $params['subject'],
                    'template_engine' => 'handlebars',
                ],
                'recipients' => [
                    [
                        'email' => $toEmail,
                        'name'  => $toName,
                    ],
                ],
            ];

            // 1.3.7
            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_unique($params['attachments']);
                $sendParams['message']['attachments'] = [];
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $sendParams['message']['attachments'][] = [
                            'name'          => basename($attachment),
                            'content_type'  => 'application/octet-stream',
                            'data'          => base64_encode((string)file_get_contents($attachment)),
                        ];
                    }
                }
            }
            //

            if ($onlyPlainText) {
                unset($sendParams['message']['html']);
            }

            $resp = $this->getClient()->post('message.send', [
                'json' => $sendParams,
            ]);
            if ((int)$resp->getStatusCode() !== 200) {
                throw new Exception($resp->getReasonPhrase());
            }

            /** @var array $response */
            $response = (array)json_decode((string)$resp->getBody(), true);

            if (!empty($response['err'])) {
                throw new Exception((string)$response['err']);
            }

            if (empty($response[0]) || $response[0]['status'] != 'queued') {
                throw new Exception(print_r($response, true));
            }

            $this->getMailer()->addLog('OK');
            $sent = ['message_id' => $response[0]['send_id']];
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
        $params['transport'] = self::TRANSPORT_NEWSMAN_WEB_API;
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

        return $client = new GuzzleHttp\Client([
            'timeout'  => (int)$this->timeout,
            'base_uri' => 'https://cluster.newsmanapp.com/api/1.0/',
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'hostname'                => null,
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'signing_enabled'         => null,
            'max_connection_messages' => null,
            'bounce_server_id'        => null,
            'force_sender'            => null,
        ], $fields));
    }

    /**
     * @inheritDoc
     */
    public function getDswhUrl(): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url = $optionUrl->getFrontendUrl('dswh/newsman');
        if (is_cli()) {
            return $url;
        }
        if (request()->getIsSecureConnection() && parse_url($url, PHP_URL_SCHEME) == 'http') {
            $url = substr_replace($url, 'https', 0, 4);
        }
        return $url;
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initStatus = $this->status;
        $this->hostname    = 'web-api.newsmansmtp.ro';
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->_initStatus = $this->status;
        parent::afterFind();
    }
}
