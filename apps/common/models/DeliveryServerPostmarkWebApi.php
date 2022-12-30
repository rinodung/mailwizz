<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerPostmarkWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.2
 *
 */

class DeliveryServerPostmarkWebApi extends DeliveryServer
{
    /**
     * @var string
     */
    protected $serverType = 'postmark-web-api';

    /**
     * @var string
     */
    protected $_providerUrl = 'https://postmarkapp.com/';

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
            'password' => t('servers', 'Server api token'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'password' => t('servers', 'The server api token from your postmark account'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'password' => t('servers', 'Server api token'),
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

        $replyToEmail = $replyToName = null;
        if (!empty($params['replyTo'])) {
            [$replyToEmail, $replyToName] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $headers = [];
        if (!empty($params['headers'])) {
            $headers = $this->parseHeadersIntoKeyValue($params['headers']);
        }

        $sent = [];
        $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;

        try {
            $sendParams = [
                'To'            => $toEmail,
                'From'          => $fromEmail,
                'ReplyTo'       => $replyToEmail,
                'Headers'       => $headers,
                'Subject'       => $params['subject'],
                'TextBody'      => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
            ];

            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_unique($params['attachments']);
                $sendParams['Attachments'] = [];
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $sendParams['Attachments'][] = [
                            'Name'          => basename($attachment),
                            'Content'       => base64_encode((string)file_get_contents($attachment)),
                            'ContentType'   => 'application/octet-stream',
                        ];
                    }
                }
            }

            if (!$onlyPlainText) {
                $sendParams['HtmlBody'] = $params['body'];
            }

            /** @var array $response */
            $response = $this->getClient()->sendEmailBatch([$sendParams]);
            if (empty($response[0]) || empty($response[0]['MessageID'])) {
                throw new Exception((string)json_encode($response));
            }

            $this->getMailer()->addLog('OK');
            $sent = ['message_id' => $response[0]['MessageID']];
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
        $params['transport'] = self::TRANSPORT_POSTMARK_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @return Postmark\PostmarkClient
     */
    public function getClient(): Postmark\PostmarkClient
    {
        static $clients = [];
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }

        return $clients[$id] = new Postmark\PostmarkClient($this->password);
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
        $this->hostname = 'web-api.postmark.com';
    }
}
