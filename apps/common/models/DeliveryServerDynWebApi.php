<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerDynWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.3
 *
 */

class DeliveryServerDynWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'dyn-web-api';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://dyn.com/email/';

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
        $labels = [
            'password' => t('servers', 'Api key'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'password' => t('servers', 'One of your dyn.com api keys.'),
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

        [$fromEmail, $fromName] = $this->getMailer()->findEmailAndName($params['from']);
        [$toEmail, $toName]     = $this->getMailer()->findEmailAndName($params['to']);

        if (!empty($params['fromName'])) {
            $fromName = $params['fromName'];
        }

        $replyToEmail = $replyToName = null;
        if (!empty($params['replyTo'])) {
            [$replyToEmail, $replyToName] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $sent = [];

        try {
            $mm   = new Dyn\MessageManagement($this->password);
            $mail = new Dyn\MessageManagement\Mail();

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            $fromEmail     = (!empty($fromEmail) ? $fromEmail : $this->from_email);
            $fromName      = (!empty($fromName) ? $fromName : $this->from_name);
            $replyToEmail  = (!empty($replyToEmail) ? $replyToEmail : $this->from_email);
            $replyToName   = (!empty($replyToName) ? $replyToName : $this->from_name);
            $senderEmail   = (!empty($fromEmail) ? $fromEmail : $this->from_email);
            $senderName    = (!empty($fromName) ? $fromName : $this->from_name);

            $mail
                ->setEncoding(strtoupper(app()->charset))
                ->setFrom($fromEmail, $fromName)
                ->setTo($toEmail, $toName)
                ->setSubject($params['subject'])
                ->setSender($senderEmail, $senderName)
                ->addReplyTo($replyToEmail, $replyToName);

            if (!$onlyPlainText) {
                $mail->setHtmlBody(!empty($params['body']) ? $params['body'] : '');
            }

            $mail->setTextBody(!empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']));

            if (!empty($params['headers'])) {
                $headers = $this->parseHeadersIntoKeyValue($params['headers']);
                foreach ($headers as $name => $value) {
                    if (substr($name, 0, 2) !== 'X-') {
                        continue;
                    }
                    $mail->setXHeader($name, $value);
                }
            }

            // since 2.0.33 - For this delivery server type, there is no other way than using headers...
            if (!empty($params['campaignUid'])) {
                $mail->setXHeader(sprintf('%sCampaign-Uid', (string)app_param('email.custom.header.prefix', '')), $params['campaignUid']);
            }
            if (!empty($params['subscriberUid'])) {
                $mail->setXHeader(sprintf('%sSubscriber-Uid', (string)app_param('email.custom.header.prefix', '')), $params['subscriberUid']);
            }

            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $_attachments = array_unique($params['attachments']);
                foreach ($_attachments as $attachment) {
                    if (is_file($attachment)) {
                        $mimePart = new Zend\Mime\Part(fopen($attachment, 'r'));
                        $mimePart->type = 'application/octet-stream';

                        /** @var Zend\Mime\Message $body */
                        $body = $mail->getBody();
                        $body->addPart($mimePart);
                    }
                }
            }

            // send it
            if ($sent = $mm->send($mail)) {
                $this->getMailer()->addLog('OK');
                $sent = ['message_id' => StringHelper::random(60)];
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
        $params['transport'] = self::TRANSPORT_DYN_WEB_API;
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
        $this->hostname = 'web-api.email.dynect.net';
    }
}
