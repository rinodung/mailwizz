<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerMailgunWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.9
 *
 */

class DeliveryServerMailgunWebApi extends DeliveryServer
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
     * @var array
     */
    public $webhooks = [];

    /**
     * @var string
     */
    public $region = self::REGION_US;

    /**
     * @var string
     */
    protected $serverType = 'mailgun-web-api';

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
    protected $_providerUrl = 'https://www.mailgun.com/';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['password', 'required'],
            ['password', 'length', 'max' => 255],
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
            'hostname' => t('servers', 'Domain name'),
            'password' => t('servers', 'Api key'),
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
            'hostname'  => t('servers', 'Mailgun verified domain name.'),
            'password'  => t('servers', 'Mailgun api key.'),
            'region'    => t('servers', 'Mailgun api geo region.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'hostname'  => t('servers', 'Domain name'),
            'password'  => t('servers', 'Api key'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerElasticemailWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerElasticemailWebApi $model */
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
        if (!empty($params['replyTo'])) {
            [$replyToEmail] = $this->getMailer()->findEmailAndName($params['replyTo']);
        }

        $headers = [];
        if (!empty($params['headers'])) {
            $headers = $this->parseHeadersIntoKeyValue($params['headers']);
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

            $message = [
                'from'       => sprintf('=?%s?B?%s?= <%s>', strtolower(app()->charset), base64_encode((string)$fromName), $fromEmail),
                'to'         => sprintf('=?%s?B?%s?= <%s>', strtolower(app()->charset), base64_encode((string)$toName), $toEmail),
                'subject'    => $params['subject'],
                'text'       => !empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']),
                'html'       => $params['body'],
                'o:tag'      => ['bulk-mail'],
                'v:metadata' => json_encode($metaData),
            ];

            // since 1.5.2
            foreach ($headers as $headerName => $headerValue) {
                $message['h:' . $headerName] = $headerValue;
            }

            if (!empty($replyToEmail)) {
                $message['h:Reply-To'] = $replyToEmail;
            }

            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_filter(array_unique($params['attachments']));
                $message['attachment'] = [];
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $message['attachment'][] = [
                            'filePath' => $attachment,
                            'filename' => basename($attachment),
                        ];
                    }
                }
            }

            // since 2.1.10
            if (!$onlyPlainText && !empty($params['embedImages']) && is_array($params['embedImages'])) {
                $message['inline'] = [];
                foreach ($params['embedImages'] as $imageData) {
                    if (!isset($imageData['path'], $imageData['cid'])) {
                        continue;
                    }
                    if (!is_file($imageData['path'])) {
                        continue;
                    }
                    $message['inline'][] = [
                        'filePath' => $imageData['path'],
                        'filename' => $imageData['cid'],
                    ];
                }
            }

            if ($onlyPlainText) {
                unset($message['html']);
            }

            $result = $this->getClient()->messages()->send($this->hostname, $message);
            if (is_object($result) && $result->getId()) {
                $this->getMailer()->addLog('OK');
                $sent = ['message_id' => str_replace(['<', '>'], '', $result->getId())];
            } else {
                throw new Exception(t('servers', 'Unable to make the delivery!') . print_r($result, true));
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
        $params['transport'] = self::TRANSPORT_MAILGUN_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @return Mailgun\Mailgun
     */
    public function getClient(): Mailgun\Mailgun
    {
        static $clients = [];
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }

        // since 1.6.3
        $endpoint = 'https://api.mailgun.net';
        if ($this->region === self::REGION_EU) {
            $endpoint = 'https://api.eu.mailgun.net';
        }
        //

        return $clients[$id] = Mailgun\Mailgun::create($this->password, $endpoint);
    }

    /**
     * @inheritDoc
     */
    public function getCanEmbedImages(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form = new CActiveForm();
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'username'                => null,
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'signing_enabled'         => null,
            'max_connection_messages' => null,
            'bounce_server_id'        => null,
            'force_sender'            => null,
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
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initStatus = $this->status;
        $this->webhooks    = (array)$this->modelMetaData->getModelMetaData()->itemAt('webhooks');
        $this->region      = (string)$this->modelMetaData->getModelMetaData()->itemAt('region');
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->_initStatus = $this->status;
        $this->webhooks    = (array)$this->modelMetaData->getModelMetaData()->itemAt('webhooks');
        $this->region      = (string)$this->modelMetaData->getModelMetaData()->itemAt('region');
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('webhooks', (array)$this->webhooks);
        $this->modelMetaData->getModelMetaData()->add('region', (string)$this->region);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        if (!empty($this->webhooks)) {
            foreach ($this->webhooks as $name => $url) {
                try {
                    $this->getClient()->webhooks()->delete($this->hostname, (string)$name);
                } catch (Exception $e) {
                }
            }
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

        if (!is_array($this->webhooks)) {
            $this->webhooks = [];
        }

        foreach (['permanent_fail', 'temporary_fail', 'complained', 'unsubscribed'] as $webhook) {
            try {
                $this->getClient()->webhooks()->delete($this->hostname, $webhook);
            } catch (Exception $e) {
            }

            try {
                $this->getClient()->webhooks()->create($this->hostname, $webhook, $this->getDswhUrl());
            } catch (Exception $e) {
                $this->_preCheckError = t('servers', 'Cannot create the {name} webhook, reason: {reason}', [
                    '{name}'   => $webhook,
                    '{reason}' => $e->getMessage(),
                ]);
            }

            if ($this->_preCheckError) {
                break;
            }

            $this->webhooks[$webhook] = $this->getDswhUrl();
        }

        if ($this->_preCheckError) {
            return false;
        }

        return (bool)$this->save(false);
    }
}
