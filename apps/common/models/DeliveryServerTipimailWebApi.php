<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerTipimailWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.3
 *
 */

class DeliveryServerTipimailWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'tipimail-web-api';

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
    protected $_providerUrl = 'https://www.tipimail.com/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['username, password', 'required'],
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
            'username'   => t('servers', 'SMTP username'),
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
            'username' => t('servers', 'Your smtp username'),
            'password' => t('servers', 'Your api key'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'username'   => 'dd623d60cc62d890cabb00c4cb716333',
            'password'   => '123a15725f4b676fd79d746c7d9d0b21',
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
        /** @var DeliveryServerTipimailWebApi $model */
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

        $metaData = [];
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

            $message = new Tipimail\Messages\Message();

            $subject = sprintf('=?%s?B?%s?=', strtolower(app()->charset), base64_encode((string)$params['subject']));

            $message->addTo($toEmail, sprintf('=?%s?B?%s?=', strtolower(app()->charset), base64_encode((string)$toName)));
            $message->setFrom($fromEmail, sprintf('=?%s?B?%s?=', strtolower(app()->charset), base64_encode((string)$fromName)));
            $message->setSubject($subject);

            if ($replyToEmail) {
                $message->setReplyTo($replyToEmail, $replyToName);
            }

            $message->setText(!empty($params['plainText']) ? (string)$params['plainText'] : CampaignHelper::htmlToText((string)$params['body']));
            $message->setApiKey($this->password);

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $_attachments = array_unique($params['attachments']);
                foreach ($_attachments as $attachment) {
                    if (is_file($attachment)) {
                        $fileName = basename($attachment);
                        $message->addAttachmentFromFile($attachment, $fileName);
                    }
                }
            }

            if (!$onlyPlainText) {
                $message->setHtml($params['body']);
            }

            $message->setMeta($metaData);

            $this->getClient()->getMessagesService()->send($message);

            $this->getMailer()->addLog('OK');
            $sent = ['message_id' => StringHelper::random(60)];
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
     * @return Tipimail\Tipimail
     */
    public function getClient(): Tipimail\Tipimail
    {
        static $clients = [];
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }

        return $clients[$id] = new Tipimail\Tipimail($this->username, $this->password);
    }

    /**
     * @inheritDoc
     */
    public function getParamsArray(array $params = []): array
    {
        $params['transport'] = self::TRANSPORT_TIPIMAIL_WEB_API;
        return parent::getParamsArray($params);
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
        $this->hostname    = 'web-api.tipimail.com';
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
        return true;
    }
}
