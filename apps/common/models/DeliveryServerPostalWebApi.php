<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerPostalWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.6
 *
 */

class DeliveryServerPostalWebApi extends DeliveryServer
{
    /**
     * Flag for http scheme
     */
    const HOSTNAME_SCHEME_HTTP = 'http';

    /**
     * Flag for https scheme
     */
    const HOSTNAME_SCHEME_HTTPS = 'https';

    /**
     * @var string
     */
    public $hostname_scheme = self::HOSTNAME_SCHEME_HTTP;

    /**
     * @var string
     */
    protected $serverType = 'postal-web-api';

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
    protected $_providerUrl = 'https://postal.atech.media';

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['password, hostname_scheme', 'required'],
            ['password', 'length', 'max' => 255],
            ['hostname_scheme', 'in', 'range' => array_keys($this->getHostnameSchemes())],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'password'        => t('servers', 'Api key'),
            'hostname_scheme' => t('servers', 'Hostname scheme'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'hostname'        => t('servers', 'Your Postal server host'),
            'password'        => t('servers', 'One of your Postal api keys'),
            'hostname_scheme' => t('servers', 'If you are accessing your Postal dashboard using HTTPS, then select HTTPS, otherwise use HTTP'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'hostname' => 'postal.example.com',
            'password' => t('servers', 'Api key'),
        ];

        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerPostalWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerPostalWebApi $model */
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

        try {
            $onlyPlainText = !empty($params['onlyPlainText']) && $params['onlyPlainText'] === true;
            $message       = new Postal\SendMessage($this->getClient());

            $message->to(sprintf('%s <%s>', $toName, $toEmail));
            $message->from(sprintf('%s <%s>', $fromName, $fromEmail));
            $message->replyTo($replyToEmail);
            $message->subject($params['subject']);

            $message->plainBody(!empty($params['plainText']) ? $params['plainText'] : CampaignHelper::htmlToText($params['body']));
            if (!$onlyPlainText) {
                $message->htmlBody($params['body']);
            }

            foreach ($headers as $name => $value) {
                $message->header($name, $value);
            }

            if (!$onlyPlainText && !empty($params['attachments']) && is_array($params['attachments'])) {
                $attachments = array_unique($params['attachments']);
                foreach ($attachments as $attachment) {
                    if (is_file($attachment)) {
                        $message->attach(basename($attachment), 'application/octet-stream', file_get_contents($attachment));
                    }
                }
            }

            $result = $message->send();

            foreach ($result->recipients() as $message) {
                if ($message->token()) {
                    $this->getMailer()->addLog('OK');
                    $sent = ['message_id' => $message->token()];
                    break;
                }
                throw new Exception((string)json_encode($message));
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
        $params['transport'] = self::TRANSPORT_POSTAL_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @return Postal\Client
     */
    public function getClient(): Postal\Client
    {
        static $clients = [];
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }

        return $clients[$id] = new Postal\Client($this->getApiUrl(), $this->password);
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form = new CActiveForm();
        $fields = parent::getFormFieldsDefinition(CMap::mergeArray([
            'username'                => null,
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'signing_enabled'         => null,
            'max_connection_messages' => null,
            'bounce_server_id'        => null,
            'force_sender'            => null,
        ], $fields));

        $newFields = [];
        foreach ($fields as $id => $definition) {
            if ($id === 'hostname') {
                $newFields['hostname_scheme'] = [
                    'visible'   => true,
                    'fieldHtml' => $form->dropDownList($this, 'hostname_scheme', $this->getHostnameSchemes(), $this->fieldDecorator->getHtmlOptions('hostname_scheme')),
                ];
            }
            $newFields[$id] = $definition;
        }
        return $newFields;
    }

    /**
     * @return array
     */
    public function getHostnameSchemes(): array
    {
        return [
            self::HOSTNAME_SCHEME_HTTP  => t('servers', 'HTTP'),
            self::HOSTNAME_SCHEME_HTTPS => t('servers', 'HTTPS'),
        ];
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return sprintf('%s://%s', $this->hostname_scheme, $this->hostname);
    }

    /**
     * @inheritDoc
     */
    public function getDswhUrl(): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url = $optionUrl->getFrontendUrl('dswh/postal');
        if (is_cli()) {
            return $url;
        }
        if (request()->getIsSecureConnection() && parse_url($url, PHP_URL_SCHEME) == 'http') {
            $url = substr_replace($url, 'https', 0, 4);
        }
        return $url;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('hostname_scheme', (string)$this->hostname_scheme);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->hostname_scheme = (string)$this->modelMetaData->getModelMetaData()->itemAt('hostname_scheme');
        $this->_initStatus     = $this->status;
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->hostname_scheme = (string)$this->modelMetaData->getModelMetaData()->itemAt('hostname_scheme');
        $this->_initStatus     = $this->status;
        parent::afterFind();
    }
}
