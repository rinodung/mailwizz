<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerSendinblueWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.3
 *
 */

class DeliveryServerSendinblueWebApi extends DeliveryServer
{

    /**
     * @var array
     */
    public $webhook = [];
    /**
     * @var string
     */
    protected $serverType = 'sendinblue-web-api';

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
    protected $_providerUrl = 'https://www.sendinblue.com//';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['password', 'length', 'max' => 255],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'password'   => t('servers', 'Api key'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'password' => t('servers', 'Your sendinblue api key'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'password'   => 'xkeysib-...',
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerSendinblueWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerSendinblueWebApi $model */
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

        $replyToEmail = null;
        $replyToName  = null;
        if (!empty($params['replyTo'])) {
            [$replyToEmail, $replyToName] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $headers = [];
        if (!empty($params['headers'])) {
            $headers = $this->parseHeadersIntoKeyValue($params['headers']);
        }
        $headers['Reply-To'] = $replyToEmail;

        $metaData   = [];
        if (!empty($params['campaignUid'])) {
            $metaData['campaign_uid'] = $params['campaignUid'];
        }
        if (!empty($params['subscriberUid'])) {
            $metaData['subscriber_uid'] = $params['subscriberUid'];
        }

        $sent = [];

        try {
            if (!$this->preCheckWebHook()) {
                throw new Exception($this->_preCheckError);
            }

            $message = (new SendinBlue\Client\Model\SendSmtpEmail())
                ->setSender(new SendinBlue\Client\Model\SendSmtpEmailSender([
                    'name' => $fromName,
                    'email'=> $fromEmail,
                ]))
                ->setTo([
                    new SendinBlue\Client\Model\SendSmtpEmailTo([
                        'name' => $toName,
                        'email'=> $toEmail,
                    ]),
                ])
                ->setSubject($params['subject'])
                ->setHtmlContent($params['body'])
                ->setTextContent(!empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']));

            $message->setHeaders((object)$headers);

            if (!empty($metaData)) {
                $message->setParams((object)$metaData);
            }

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = [];
                $_attachments = array_unique($params['attachments']);
                foreach ($_attachments as $attachment) {
                    if (!is_file($attachment)) {
                        continue;
                    }
                    $attachments[] = new SendinBlue\Client\Model\SendSmtpEmailAttachment([
                        'name'    => basename($attachment),
                        'content' => base64_encode((string)file_get_contents($attachment)),
                    ]);
                }
                $message->setAttachment($attachments);
            }

            if ($replyToEmail) {
                $message->setReplyTo(new SendinBlue\Client\Model\SendSmtpEmailReplyTo([
                    'name'  => $replyToName,
                    'email' => $replyToEmail,
                ]));
            }

            if ($onlyPlainText) {
                $message->setHtmlContent($message->getTextContent());
            }

            $client = new SendinBlue\Client\Api\TransactionalEmailsApi(null, $this->getClientConfiguration());
            $response = $client->sendTransacEmail($message);
            if (!$response->getMessageId()) {
                throw new Exception('Upstream response: ' . json_encode($response));
            }

            $this->getMailer()->addLog('OK');
            $sent = ['message_id' => $response->getMessageId()];
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
     * @return SendinBlue\Client\Configuration
     */
    public function getClientConfiguration(): SendinBlue\Client\Configuration
    {
        $config = SendinBlue\Client\Configuration::getDefaultConfiguration();
        $config->setApiKey('api-key', (string)$this->password);

        return $config;
    }

    /**
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport'] = self::TRANSPORT_SENDINBLUE_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
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
        ], $fields));
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initStatus = $this->status;
        $this->hostname    = 'web-api.sendinblue.com';
        $this->webhook     = (array)$this->modelMetaData->getModelMetaData()->itemAt('webhook');
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->_initStatus = $this->status;
        $this->webhook     = (array)$this->modelMetaData->getModelMetaData()->itemAt('webhook');
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('webhook', (array)$this->webhook);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        if (!empty($this->webhook['id'])) {
            try {
                $client = new SendinBlue\Client\Api\WebhooksApi(null, $this->getClientConfiguration());
                $client->deleteWebhook((int)$this->webhook['id']);
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
            $this->webhook = [];
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
            $client = new SendinBlue\Client\Api\WebhooksApi(null, $this->getClientConfiguration());
            if (!empty($this->webhook['id'])) {
                /** @var SendinBlue\Client\Model\GetWebhook $response */
                $response = $client->getWebhook((int)$this->webhook['id']);
                if ($response->getUrl() == $this->getDswhUrl()) {
                    return true;
                }
                $client->deleteWebhook((int)$this->webhook['id']);
                $this->webhook = [];
            }

            /** @var SendinBlue\Client\Model\CreateModel $response */
            $response = $client->createWebhook(new SendinBlue\Client\Model\CreateWebhook([
                'url'         => $this->getDswhUrl(),
                'description' => 'Notifications Webhook - DO NOT ALTER THIS IN ANY WAY!',
                'events'      => [
                    SendinBlue\Client\Model\CreateWebhook::EVENTS_HARD_BOUNCE,
                    SendinBlue\Client\Model\CreateWebhook::EVENTS_SOFT_BOUNCE,
                    SendinBlue\Client\Model\CreateWebhook::EVENTS_BLOCKED,
                    SendinBlue\Client\Model\CreateWebhook::EVENTS_SPAM,
                    SendinBlue\Client\Model\CreateWebhook::EVENTS_INVALID,
                    SendinBlue\Client\Model\CreateWebhook::EVENTS_UNSUBSCRIBED,
                ],
            ]));

            if (!$response->getId()) {
                throw new Exception((string)json_encode($response));
            }

            $this->webhook = ['id' => $response->getId()];
        } catch (Exception $e) {
            $this->_preCheckError = $e->getMessage();
        }

        if ($this->_preCheckError) {
            return false;
        }

        return (bool)$this->save(false);
    }
}
