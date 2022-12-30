<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerInboxroadWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.9.17
 *
 */

class DeliveryServerInboxroadWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'inboxroad-web-api';

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
    protected $_providerUrl = 'https://inboxroad.com/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['password', 'required'],
            ['password', 'length', 'max' => 255],
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
            'password'  => t('servers', 'One of your inboxroad api key.'),
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

        $replyToEmail = '';
        if (!empty($params['replyTo'])) {
            [$replyToEmail] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $headers = [];
        if (!empty($params['headers'])) {
            $headers = $this->parseHeadersIntoKeyValue($params['headers']);
        }

        $sent = [];

        try {
            if (!$this->preCheckWebHook()) {
                throw new Exception($this->_preCheckError);
            }

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;

            $message = (new \Inboxroad\Models\Message())
                ->setFromEmail($fromEmail)
                ->setFromName(!empty($fromName) ? $fromName : '')
                ->setToEmail($toEmail)
                ->setToName(!empty($toName) ? $toName : '')
                ->setReplyToEmail(!empty($replyToEmail) ? $replyToEmail : $fromEmail)
                ->setSubject($params['subject'])
                ->setText(!empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']))
                ->setHtml($onlyPlainText ? '' : $params['body']);

            if (!empty($headers)) {
                foreach ($headers as $key => $value) {
                    $message->getHeaders()->add(new \Inboxroad\Models\MessageHeader($key, $value));
                }
            }

            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_filter(array_unique($params['attachments']));
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $message->getAttachments()->add(
                            new \Inboxroad\Models\MessageAttachment(basename($attachment), (string)file_get_contents($attachment), 'application/octet-stream')
                        );
                    }
                }
            }

            try {
                $response = $this->getMessagesClient()->send($message);
            } catch (\Inboxroad\Exception\RequestException $e) {
                throw new Exception($e->getMessage());
            }

            if (!$response->getMessageId()) {
                throw new Exception($response->getBody());
            }

            $sent = [
                'message_id' => $response->getMessageId(),
            ];
        } catch (Throwable $e) {
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
        $params['transport'] = self::TRANSPORT_INBOXROAD_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @return \Inboxroad\Api\Messages
     * @throws ErrorException
     */
    public function getMessagesClient(): Inboxroad\Api\Messages
    {
        static $client;
        if ($client !== null) {
            return $client;
        }

        return $client = new \Inboxroad\Api\Messages(
            new \Inboxroad\HttpClient\HttpClient((string)$this->password)
        );
    }

    /**
     * @inheritDoc
     */
    public function getDswhUrl(): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url = $optionUrl->getFrontendUrl('dswh/inboxroad');
        if (is_cli()) {
            return $url;
        }
        if (request()->getIsSecureConnection() && parse_url($url, PHP_URL_SCHEME) == 'http') {
            $url = substr_replace($url, 'https', 0, 4);
        }
        return $url;
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
        $this->hostname    = 'web-api.inboxroad.com';
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

        if ($this->_preCheckError) {
            return false;
        }

        return true;
    }
}
