<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerMailerqWebApi
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 *
 */

class DeliveryServerMailerqWebApi extends DeliveryServer
{

    /**
     * @var string
     */
    public $vhost = '/';

    /**
     * @var string
     */
    public $exchange = 'mailerq';

    /**
     * @var string
     */
    public $exchange_type = 'direct';

    /**
     * @var string
     */
    public $queue = 'outbox';

    /**
     * @var string
     */
    public $assigned_ips = '';

    /**
     * @var string
     */
    public $ip_to_domains = '';

    /**
     * @var bool
     */
    public $canConfirmDelivery = true;
    /**
     * @var string
     */
    protected $serverType = 'mailerq-web-api';

    /**
     * @var bool
     */
    protected $hasConnection = false;

    /**
     * Close any opened connection
     */
    public function __destruct()
    {
        if ($this->hasConnection) {
            try {
                $this->getConnection()->close();
            } catch (Exception $e) {
            }
        }
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['username, password, port, vhost, exchange, exchange_type, queue', 'required'],
            ['username, password, port, vhost, exchange, exchange_type, queue', 'length', 'max' => 255],
            ['ip_to_domains, assigned_ips', 'safe'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'vhost'         => t('servers', 'Virtual host'),
            'exchange'      => t('servers', 'Exchange name'),
            'exchange_type' => t('servers', 'Exchange type'),
            'queue'         => t('servers', 'Queue name'),
            'assigned_ips'  => t('servers', 'Assigned ips'),
            'ip_to_domains' => t('servers', 'Ip to domains'),
        ];
        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'bounce_server_id'  => t('servers', 'The server that will handle bounce emails for this Mailerq server.'),
            'hostname'          => '',
            'username'          => '',
            'port'              => '',
            'password'          => '',
            'vhost'             => '',
            'exchange'          => '',
            'exchange_type'     => '',
            'queue'             => '',
            'assigned_ips'      => '',
            'ip_to_domains'     => '',
        ];
        return CMap::mergeArray(parent::attributeHelpTexts(), $texts);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'hostname'      => 'mailerq.domain.com',
            'username'      => '',
            'password'      => '',
            'port'          => 5672,
            'vhost'         => '/',
            'exchange'      => 'mailerq',
            'exchange_type' => 'direct',
            'queue'         => 'outbox',
            'assigned_ips'  => '123.123.123.123, 12.12.12.12, 100.1.100.1',
            'ip_to_domains' => json_encode([
                '11.11.11.11' => ['yahoo.*', 'gmail.*'],
                '11.11.11.12' => ['hotmail.*', 'outlook.*'],
            ]),
        ];
        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerMailerqWebApi the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerMailerqWebApi $model */
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

        [$toEmail]   = $this->getMailer()->findEmailAndName($params['to']);
        [$fromEmail] = $this->getMailer()->findEmailAndName($params['from']);

        $sent = [];

        try {
            $channel = $this->getConnection()->channel();

            $channel->queue_declare($this->queue, false, true, false, false);
            $channel->exchange_declare($this->exchange, $this->exchange_type, false, true, false);
            $channel->queue_bind($this->queue, $this->exchange);

            $message    = $this->getMailer()->getEmailMessage($params);
            $domainName = explode('@', $toEmail);
            $domainName = $domainName[1];

            $ips = explode(',', $this->assigned_ips);
            $ips = array_map('trim', $ips);
            $ips = array_unique($ips);
            foreach ($ips as $index => $ip) {
                if (!FilterVarHelper::ip($ip)) {
                    unset($ips[$index]);
                }
            }

            $ipToDomains = $this->getIpToDomains();
            $tempIps = [];
            foreach ($ipToDomains as $ip => $domainsRegex) {
                if (!FilterVarHelper::ip($ip)) {
                    continue;
                }
                foreach ($domainsRegex as $domainRegex) {
                    if (preg_match('#' . preg_quote($domainRegex, '/') . '#six', $domainName)) {
                        $tempIps[] = $ip;
                    }
                }
            }
            if (!empty($tempIps)) {
                $ips = array_unique($tempIps);
            }

            $sendData = [
                'domain'    => $domainName,
                'key'	    => $this->getMailer()->getEmailMessageId(),
                'keepmime'  => 1,
                'envelope'  => $fromEmail,
                'recipient' => $toEmail,
                'mime'      => $message,
                'ips'       => $ips,
            ];

            if (!empty($params['campaignUid'])) {
                $sendData['campaign_uid'] = $params['campaignUid'];
            }
            if (!empty($params['subscriberUid'])) {
                $sendData['subscriber_uid'] = $params['subscriberUid'];
            }
            if (!empty($params['customerUid'])) {
                $sendData['customer_uid'] = $params['customerUid'];
            }

            if (
                empty($sendData['customer_uid']) && !is_cli() && app()->hasComponent('customer') &&
                customer()->getId() && ($_customer = customer()->getModel())
            ) {
                $sendData['customer_uid'] = (string)$_customer->customer_uid;
            }

            foreach ($sendData as $key => $val) {
                if (empty($val)) {
                    unset($sendData[$key]);
                }
            }

            /** @var array $messageParams */
            $messageParams = ['content_type' => 'application/json'];

            $msg = new PhpAmqpLib\Message\AMQPMessage((string)json_encode($sendData), $messageParams);

            $channel->basic_publish($msg, $this->exchange, $this->queue);
            $channel->close();

            $sent = ['message_id' => StringHelper::random(60)];
        } catch (Exception $e) {
            $sent = [];
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
        $params['transport'] = self::TRANSPORT_MAILERQ_WEB_API;
        return parent::getParamsArray($params);
    }

    /**
     * @return array
     */
    public function getIpToDomains(): array
    {
        static $data = [];
        $id = (int)$this->server_id;
        if (!empty($data[$id])) {
            return $data[$id];
        }
        return $data[$id] = (
            !empty($this->ip_to_domains) &&
            ($results = json_decode($this->ip_to_domains, true)) &&
            is_array($results)
        ) ? $results : [];
    }

    /**
     * @return PhpAmqpLib\Connection\AMQPStreamConnection
     */
    public function getConnection(): PhpAmqpLib\Connection\AMQPStreamConnection
    {
        static $data = [];
        $id = (int)$this->server_id;
        if (!empty($data[$id])) {
            $this->hasConnection = true;
            return $data[$id];
        }

        $connection = new PhpAmqpLib\Connection\AMQPStreamConnection(
            (string)$this->hostname,
            (string)$this->port,
            (string)$this->username,
            (string)$this->password,
            (string)$this->vhost
        );

        $this->hasConnection = true;
        return $data[$id] = $connection;
    }

    /**
     * @inheritDoc
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form = new CActiveForm();
        return parent::getFormFieldsDefinition(CMap::mergeArray([
            'timeout'                 => null,
            'max_connection_messages' => null,
            'force_sender'            => null,
            'protocol'                => null,
            'vhost' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'vhost', $this->fieldDecorator->getHtmlOptions('vhost')),
            ],
            'queue' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'queue', $this->fieldDecorator->getHtmlOptions('queue')),
            ],
            'exchange' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'exchange', $this->fieldDecorator->getHtmlOptions('exchange')),
            ],
            'exchange_type' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'exchange_type', $this->fieldDecorator->getHtmlOptions('exchange_type')),
            ],
            'assigned_ips' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'assigned_ips', $this->fieldDecorator->getHtmlOptions('assigned_ips')),
            ],
            'ip_to_domains' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'ip_to_domains', $this->fieldDecorator->getHtmlOptions('ip_to_domains')),
            ],
            'must_confirm_delivery' => [
                'visible'   => $this->canConfirmDelivery,
                'fieldHtml' => $form->dropDownList($this, 'must_confirm_delivery', $this->getYesNoOptions(), $this->fieldDecorator->getHtmlOptions('must_confirm_delivery')),
            ],
        ], $fields));
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        parent::afterConstruct();
        $this->port = 5672;
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->vhost         = $this->modelMetaData->getModelMetaData()->itemAt('vhost');
        $this->queue         = $this->modelMetaData->getModelMetaData()->itemAt('queue');
        $this->exchange      = $this->modelMetaData->getModelMetaData()->itemAt('exchange');
        $this->exchange_type = $this->modelMetaData->getModelMetaData()->itemAt('exchange_type');
        $this->assigned_ips  = $this->modelMetaData->getModelMetaData()->itemAt('assigned_ips');
        $this->ip_to_domains = $this->modelMetaData->getModelMetaData()->itemAt('ip_to_domains');
        parent::afterFind();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $results = json_decode($this->ip_to_domains, true);
        $this->ip_to_domains = '';
        if (is_array($results)) {
            foreach ($results as $ipAddress => $domains) {
                if (!FilterVarHelper::ip($ipAddress) || !is_array($domains)) {
                    unset($results[$ipAddress]);
                    continue;
                }
                foreach ($domains as $domain) {
                    if (!is_string($domain)) {
                        unset($domains[$domain]);
                    }
                }
                if (empty($domains)) {
                    unset($results[$ipAddress]);
                    continue;
                }
            }
            $this->ip_to_domains = (string)json_encode($results);
        }

        $this->modelMetaData->getModelMetaData()->add('vhost', $this->vhost);
        $this->modelMetaData->getModelMetaData()->add('queue', $this->queue);
        $this->modelMetaData->getModelMetaData()->add('exchange', $this->exchange);
        $this->modelMetaData->getModelMetaData()->add('exchange_type', $this->exchange_type);
        $this->modelMetaData->getModelMetaData()->add('assigned_ips', $this->assigned_ips);
        $this->modelMetaData->getModelMetaData()->add('ip_to_domains', $this->ip_to_domains);
        return parent::beforeSave();
    }
}
