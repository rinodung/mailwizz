<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerAmazonSesWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.8
 *
 */

class DeliveryServerAmazonSesWebApi extends DeliveryServerSmtpAmazon
{

    /**
     * @var string
     */
    public $topic_arn;

    /**
     * @var string
     */
    public $subscription_arn;
    /**
     * @var string
     */
    protected $serverType = 'amazon-ses-web-api';

    /**
     * @var string
     */
    protected $_initStatus;

    /**
     * @var string
     */
    protected $_preCheckSesSnsError;

    /**
     * @var string
     */
    protected $_providerUrl = 'https://aws.amazon.com/ses/';

    /**
     * @var array
     */
    protected $notificationTypes = ['Bounce', 'Complaint'];

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerAmazonSesWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerAmazonSesWebApi $model */
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

        if (empty($fromName)) {
            $fromName = $params['fromName'];
        }

        $sent = [];

        try {
            if (!$this->preCheckSesSns()) {
                throw new Exception($this->_preCheckSesSnsError);
            }

            $message = [
                'Source'       => sprintf('=?%s?B?%s?= <%s>', strtolower(app()->charset), base64_encode((string)$fromName), $fromEmail),
                'Destinations' => [sprintf('=?%s?B?%s?= <%s>', strtolower(app()->charset), base64_encode((string)$toName), $toEmail)],
                'RawMessage' => [
                    'Data' => $this->getMailer()->getEmailMessage($params),
                ],
            ];

            $response = $this->getSesClient()->sendRawEmail($message);

            if ($response['MessageId']) {
                $sent = ['message_id' => $response['MessageId']];
                $this->getMailer()->addLog('OK');
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
        $params = parent::getParamsArray($params);
        $params['transport'] = self::TRANSPORT_AMAZON_SES_WEB_API;
        return $params;
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'username'  => t('servers', 'Access Key ID'),
            'password'  => t('servers', 'Secret Access Key'),
        ];

        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'username'   => t('servers', 'Your Amazon SES SMTP username, something like: i.e: AKIAIYYYYYYYYYYUBBFQ. Please make sure this user has enough rights for SES but also for SNS'),
            'force_from' => t('servers', 'When to force the FROM address. Please note that if you set this option to Never and you send from a unverified domain, all your emails will fail delivery. It is best to leave this option as is unless you really know what you are doing.'),
        ];

        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return string
     */
    public function getRegionFromHostname(): string
    {
        $parts = explode('.', str_replace('.amazonaws.com', '', $this->hostname));
        return (string)array_pop($parts);
    }

    /**
     * @return Aws\Ses\SesClient
     */
    public function getSesClient(): Aws\Ses\SesClient
    {
        static $clients = [];
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }

        return $clients[$id] =  new Aws\Ses\SesClient([
            'region'  => $this->getRegionFromHostname(),
            'version' => '2010-12-01',
            'credentials' => [
                'key'     => trim((string)$this->username),
                'secret'  => trim((string)$this->password),
            ],
        ]);
    }

    /**
     * @return Aws\Sns\SnsClient
     */
    public function getSnsClient(): Aws\Sns\SnsClient
    {
        static $clients = [];
        $id = (int)$this->server_id;
        if (!empty($clients[$id])) {
            return $clients[$id];
        }

        return $clients[$id] = new Aws\Sns\SnsClient([
            'region'      => $this->getRegionFromHostname(),
            'version'     => '2010-03-31',
            'credentials' => [
                'key'    => trim((string)$this->username),
                'secret' => trim((string)$this->password),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'port'                    => null,
            'protocol'                => null,
            'timeout'                 => null,
            'max_connection_messages' => null,
            'bounce_server_id'        => null,
        ], $fields));
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->_initStatus      = $this->status;
        $this->topic_arn        = $this->modelMetaData->getModelMetaData()->itemAt('topic_arn');
        $this->subscription_arn = $this->modelMetaData->getModelMetaData()->itemAt('subscription_arn');
        $this->force_from       = self::FORCE_FROM_ALWAYS;
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->_initStatus      = $this->status;
        $this->topic_arn        = $this->modelMetaData->getModelMetaData()->itemAt('topic_arn');
        $this->subscription_arn = $this->modelMetaData->getModelMetaData()->itemAt('subscription_arn');
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('topic_arn', $this->topic_arn);
        $this->modelMetaData->getModelMetaData()->add('subscription_arn', $this->subscription_arn);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        try {
            $this->getSesClient()->setIdentityFeedbackForwardingEnabled([
                'Identity'          => $this->from_email,
                'ForwardingEnabled' => true,
            ]);
            foreach ($this->notificationTypes as $type) {
                $this->getSesClient()->setIdentityNotificationTopic([
                    'Identity'          => $this->from_email,
                    'NotificationType'  => $type,
                ]);
            }
            if (!empty($this->subscription_arn)) {
                $this->getSnsClient()->unsubscribe(['SubscriptionArn' => $this->subscription_arn]);
            }
        } catch (Exception $e) {
        }
        parent::afterDelete();
    }

    /**
     * @return bool
     */
    protected function preCheckSesSns(): bool
    {
        if (is_cli() || $this->getIsNewRecord() || $this->_initStatus !== self::STATUS_INACTIVE) {
            return true;
        }

        try {
            $this->getSesClient()->setIdentityFeedbackForwardingEnabled([
                'Identity'          => $this->from_email,
                'ForwardingEnabled' => true,
            ]);
            foreach ($this->notificationTypes as $type) {
                $this->getSesClient()->setIdentityNotificationTopic([
                    'Identity'          => $this->from_email,
                    'NotificationType'  => $type,
                ]);
            }

            if (!empty($this->subscription_arn)) {
                try {
                    $this->getSnsClient()->unsubscribe(['SubscriptionArn' => $this->subscription_arn]);
                } catch (Exception $e) {
                }
            }

            $result          = $this->getSnsClient()->createTopic(['Name' => 'MWZSESHANDLER' . (int)$this->server_id]);
            $this->topic_arn = (string)$result->get('TopicArn');
            $subscribeUrl    = $this->getDswhUrl();

            $result = $this->getSnsClient()->subscribe([
                'TopicArn' => $this->topic_arn,
                'Protocol' => stripos($subscribeUrl, 'https') === 0 ? 'https' : 'http',
                'Endpoint' => $subscribeUrl,
            ]);
            if (stripos((string)$result->get('SubscriptionArn'), 'pending') === false) {
                $this->subscription_arn = (string)$result->get('SubscriptionArn');
            }

            foreach ($this->notificationTypes as $type) {
                $this->getSesClient()->setIdentityNotificationTopic([
                    'Identity'          => $this->from_email,
                    'NotificationType'  => $type,
                    'SnsTopic'          => $this->topic_arn,
                ]);
            }

            $this->getSesClient()->setIdentityFeedbackForwardingEnabled([
                'Identity'          => $this->from_email,
                'ForwardingEnabled' => false,
            ]);
        } catch (Exception $e) {
            $this->_preCheckSesSnsError = $e->getMessage();
            return false;
        }

        return (bool)$this->save(false);
    }
}
