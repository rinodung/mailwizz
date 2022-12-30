<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerSendgridWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.9
 *
 */

class DeliveryServerSendgridWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'sendgrid-web-api';

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
    protected $_providerUrl = 'https://sendgrid.com/';

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
        $texts = [
            'password'  => t('servers', 'Api key'),
        ];

        return CMap::mergeArray(parent::attributeLabels(), $texts);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'username'  => t('servers', 'Your sendgrid username.'),
            'password'  => t('servers', 'One of your sendgrid api key.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerSendgridWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerSendgridWebApi $model */
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

        $customArgs = [
            'date' => date('Y-m-d H:i:s'),
        ];

        if (!empty($params['campaignUid'])) {
            $customArgs['campaign_uid'] = $params['campaignUid'];
        }
        if (!empty($params['subscriberUid'])) {
            $customArgs['subscriber_uid'] = $params['subscriberUid'];
        }

        $sent = [];

        try {
            if (!$this->preCheckWebHook()) {
                throw new Exception($this->_preCheckError);
            }

            $data = [
                'personalizations' => [
                    [
                        'subject' => (string)$params['subject'],
                        'to' => [
                            [
                                'email' => (string)$toEmail,
                                'name'  => sprintf('=?%s?B?%s?=', strtolower(app()->charset), base64_encode((string)$toName)),
                            ],
                        ],
                        'custom_args' => $customArgs,
                        'headers'     => !empty($headers) ? $headers : new StdClass(),
                    ],
                ],
                'from' => [
                    'email' => (string)$fromEmail,
                    'name'  => (string)$fromName,
                ],
                'reply_to' => [
                    'email' => (string)$replyToEmail,
                    'name'  => (string)$replyToName,
                ],
                'content' => [],
            ];

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_unique($params['attachments']);
                $data['attachments'] = [];
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $data['attachments'][] = [
                            'content'    => base64_encode((string)file_get_contents($attachment)),
                            'type'       => pathinfo($attachment, PATHINFO_EXTENSION),
                            'filename'   => basename($attachment),
                            'content_id' => StringHelper::random(20),
                        ];
                    }
                }
            }

            $data['content'][] = [
                'type'  => 'text/plain',
                'value' => !empty($params['plainText']) ? (string)$params['plainText'] : CampaignHelper::htmlToText($params['body']),
            ];

            if (!$onlyPlainText) {
                $data['content'][] = [
                    'type'  => 'text/html',
                    'value' => (string)$params['body'],
                ];
            }

            /** @var SendGrid\Client $client */
            $client = $this->getClient()->client;

            /** @var SendGrid\Client $send */
            $send = $client->mail()->send();

            /** @var SendGrid\Response $result */
            $result = $send->post($data);

            if ($result->statusCode() >= 200 && $result->statusCode() < 300) {
                $this->getMailer()->addLog('OK');
                $sent = ['message_id' => StringHelper::random(60)];
            } elseif ($result->body()) {
                throw new Exception($result->body());
            } else {
                throw new Exception(t('servers', 'Unable to make the delivery!'));
            }
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
        $params['transport'] = self::TRANSPORT_SENDGRID_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @return SendGrid
     */
    public function getClient(): SendGrid
    {
        static $clients = [];
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }

        return $clients[$id] = new SendGrid($this->password, [
            'turn_off_ssl_verification' => true,
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
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initStatus = $this->status;
        $this->hostname    = 'web-api.sendgrid.com';
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->_initStatus = $this->status;
        parent::afterFind();
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

            /** @var SendGrid\Client $client */
            $client = $this->getClient()->client->user();

            /** @var SendGrid\Client $client */
            $webhook = $client->webhooks()->event()->settings();

            /** @var SendGrid\Response $result */
            $result = $webhook->patch([
                'enabled'           => true,
                'url'               => $this->getDswhUrl(),
                'group_resubscribe' => false,
                'delivered'         => false,
                'spam_report'       => true,
                'bounce'            => true,
                'deferred'          => true,
                'unsubscribe'       => false,
                'processed'         => false,
                'open'              => false,
                'click'             => false,
                'dropped'           => true,
            ]);

            if ((int)$result->statusCode() !== 200) {
                throw new Exception((string)$result->body());
            }

            /** @var stdClass|null $resp */
            $resp = json_decode((string)$result->body());
            if (empty($resp) || empty($resp->url) || $resp->url != $this->getDswhUrl()) {
                throw new Exception((string)$result->body());
            }
        } catch (Exception $e) {
            $this->_preCheckError = $e->getMessage();

            if (empty($this->_preCheckError)) {
                $this->_preCheckError = t('servers', 'Unknown error!');
            }
        }

        if ($this->_preCheckError) {
            return false;
        }

        return (bool)$this->save(false);
    }
}
