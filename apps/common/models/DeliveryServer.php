<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "delivery_server".
 *
 * The followings are the available columns in table 'delivery_server':
 * @property integer|null $server_id
 * @property integer|string $customer_id
 * @property integer $bounce_server_id
 * @property integer $tracking_domain_id
 * @property integer|null $warmup_plan_id
 * @property string $type
 * @property string $name
 * @property string $hostname
 * @property string $username
 * @property string $password
 * @property integer|null $port
 * @property string $protocol
 * @property integer|null $timeout
 * @property string $from_email
 * @property string $from_name
 * @property string $reply_to_email
 * @property integer $probability
 * @property integer $hourly_quota
 * @property integer $daily_quota
 * @property integer $monthly_quota
 * @property integer $pause_after_send
 * @property string $meta_data
 * @property string $confirmation_key
 * @property string $locked
 * @property string $use_for
 * @property string $signing_enabled
 * @property string $force_from
 * @property string $force_reply_to
 * @property string $force_sender
 * @property string $must_confirm_delivery
 * @property integer $max_connection_messages
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign[] $campaigns
 * @property BounceServer $bounceServer
 * @property TrackingDomain $trackingDomain
 * @property DeliveryServerWarmupPlan $warmupPlan
 * @property DeliveryServerWarmupPlanScheduleLog[] $warmupSchedules
 * @property Customer $customer
 * @property DeliveryServerUsageLog[] $usageLogs
 * @property DeliveryServerDomainPolicy[] $domainPolicies
 * @property CustomerGroup[] $customerGroups
 */
class DeliveryServer extends ActiveRecord
{
    /**
     * Transport flags
     */
    const TRANSPORT_SMTP = 'smtp';
    const TRANSPORT_SMTP_AMAZON = 'smtp-amazon';
    const TRANSPORT_SMTP_POSTMASTERY = 'smtp-postmastery';
    const TRANSPORT_SMTP_POSTAL = 'smtp-postal';
    const TRANSPORT_SMTP_MYSMTPCOM = 'smtp-mysmtpcom';
    const TRANSPORT_SMTP_PMTA = 'smtp-pmta';
    const TRANSPORT_SMTP_INBOXROAD = 'smtp-inboxroad';
    const TRANSPORT_SENDMAIL = 'sendmail';
    const TRANSPORT_PICKUP_DIRECTORY = 'pickup-directory';
    const TRANSPORT_AMAZON_SES_WEB_API = 'amazon-ses-web-api';
    const TRANSPORT_MAILGUN_WEB_API = 'mailgun-web-api';
    const TRANSPORT_SENDGRID_WEB_API = 'sendgrid-web-api';
    const TRANSPORT_INBOXROAD_WEB_API = 'inboxroad-web-api';
    const TRANSPORT_ELASTICEMAIL_WEB_API = 'elasticemail-web-api';
    const TRANSPORT_DYN_WEB_API = 'dyn-web-api';
    const TRANSPORT_SPARKPOST_WEB_API = 'sparkpost-web-api';
    const TRANSPORT_PEPIPOST_WEB_API = 'pepipost-web-api';
    const TRANSPORT_POSTMARK_WEB_API = 'postmark-web-api';
    const TRANSPORT_MAILJET_WEB_API = 'mailjet-web-api';
    const TRANSPORT_MAILERQ_WEB_API = 'mailerq-web-api';
    const TRANSPORT_SENDINBLUE_WEB_API = 'sendinblue-web-api';
    const TRANSPORT_TIPIMAIL_WEB_API = 'tipimail-web-api';
    const TRANSPORT_NEWSMAN_WEB_API = 'newsman-web-api';
    const TRANSPORT_POSTAL_WEB_API = 'postal-web-api';

    /**
     * Delivery flags
     */
    const DELIVERY_FOR_SYSTEM = 'system';
    const DELIVERY_FOR_CAMPAIGN_TEST = 'campaign-test';
    const DELIVERY_FOR_TEMPLATE_TEST = 'template-test';
    const DELIVERY_FOR_CAMPAIGN = 'campaign';
    const DELIVERY_FOR_LIST = 'list';
    const DELIVERY_FOR_TRANSACTIONAL = 'transactional';

    /**
     * Usage Flags
     */
    const USE_FOR_ALL = 'all';
    const USE_FOR_TRANSACTIONAL = 'transactional';
    const USE_FOR_CAMPAIGNS = 'campaigns';
    const USE_FOR_EMAIL_TESTS = 'email-tests';
    const USE_FOR_REPORTS = 'reports';
    const USE_FOR_LIST_EMAILS = 'list-emails';
    const USE_FOR_INVOICES = 'invoices';

    /**
     * Status flags
     */
    const STATUS_IN_USE = 'in-use';
    const STATUS_HIDDEN = 'hidden';
    const STATUS_DISABLED = 'disabled';
    const STATUS_PENDING_DELETE = 'pending-delete';

    /**
     * Various force flags
     */
    const FORCE_FROM_WHEN_NO_SIGNING_DOMAIN = 'when no valid signing domain';
    const FORCE_FROM_ALWAYS = 'always';
    const FORCE_FROM_NEVER = 'never';
    const FORCE_REPLY_TO_ALWAYS = 'always';
    const FORCE_REPLY_TO_NEVER = 'never';

    /**
     * Cache flags
     */
    const QUOTA_CACHE_SECONDS = 300;

    /**
     * List of additional headers to send for this server
     * @var array
     */
    public $additional_headers = [];

    /**
     * @var bool
     */
    public $canConfirmDelivery = false;

    /**
     * @var string
     */
    protected $serverType = 'smtp';

    /**
     * Flag to mark what kind of delivery we are making
     *
     * @var string
     */
    protected $_deliveryFor = 'system';

    /**
     * What do we deliver
     *
     * @var mixed
     */
    protected $_deliveryObject;

    /**
     * @var Mailer
     */
    protected $_mailer;

    /**
     * @var int
     */
    protected $_hourlySendingsLeft;

    /**
     * @var int
     */
    protected $_monthlySendingsLeft;

    /**
     * @var bool
     */
    protected $_logUsage = true;

    /**
     * @var array
     */
    protected $_campaignQueueEmails = [];

    /**
     * @var string
     */
    protected $_hourlyQuotaAccessKey = 'DeliveryServerGetHourlyQuotaLeft::%d';

    /**
     * @var string
     */
    protected $_dailyQuotaAccessKey = 'DeliveryServerGetDailyQuotaLeft::%d';

    /**
     * @var string
     */
    protected $_monthlyQuotaAccessKey = 'DeliveryServerGetMonthlyQuotaLeft::%d';

    /**
     * @var int
     */
    protected $_initHourlyQuota  = 0;

    /**
     * @var int
     */
    protected $_initDailyQuota   = 0;

    /**
     * @var int
     */
    protected $_initMonthlyQuota = 0;

    /**
     * @var string
     */
    protected $_providerUrl = '';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{delivery_server}}';
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return CMap::mergeArray([
            'passwordHandler' => [
                'class' => 'common.components.db.behaviors.RemoteServerPasswordHandlerBehavior',
            ],
        ], parent::behaviors());
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['hostname, from_email', 'required'],

            ['type', 'length', 'min' => 2, 'max' => 20],
            ['name, hostname, username, from_email, from_name, reply_to_email', 'length', 'min' => 2, 'max'=>255],
            ['password', 'length', 'min' => 2, 'max'=>150],
            ['port, probability, timeout', 'numerical', 'integerOnly'=>true],
            ['port', 'length', 'min'=> 2, 'max' => 5],
            ['probability', 'length', 'min'=> 1, 'max' => 3],
            ['probability', 'in', 'range' => array_keys($this->getProbabilityArray())],
            ['timeout', 'numerical', 'min' => 5, 'max' => 120],
            ['from_email, reply_to_email', 'email', 'validateIDN' => true],
            ['from_email', '_validateFromEmail'],
            ['protocol', 'in', 'range' => array_keys($this->getProtocolsArray())],
            ['hourly_quota, daily_quota, monthly_quota, pause_after_send', 'numerical', 'integerOnly' => true, 'min' => 0],
            ['hourly_quota, daily_quota, monthly_quota, pause_after_send', 'length', 'max' => 11],
            ['bounce_server_id', 'exist', 'className' => BounceServer::class, 'attributeName' => 'server_id', 'allowEmpty' => true],
            ['tracking_domain_id', 'exist', 'className' => TrackingDomain::class, 'attributeName' => 'domain_id', 'allowEmpty' => true],
            ['hostname, username, from_email, type, status, customer_id', 'safe', 'on' => 'search'],
            ['additional_headers', '_validateAdditionalHeaders'],
            ['customer_id', 'exist', 'className' => Customer::class, 'attributeName' => 'customer_id', 'allowEmpty' => true],
            ['warmup_plan_id', 'exist', 'className' => DeliveryServerWarmupPlan::class, 'attributeName' => 'plan_id', 'allowEmpty' => true],
            ['locked, signing_enabled, force_sender', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['use_for', 'in', 'range' => array_keys($this->getUseForOptions())],
            ['force_from', 'in', 'range' => array_keys($this->getForceFromOptions())],
            ['force_reply_to', 'in', 'range' => array_keys($this->getForceReplyToOptions())],
            ['must_confirm_delivery', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['max_connection_messages', 'numerical', 'integerOnly' => true, 'min' => 1],
            ['max_connection_messages', 'length', 'max' => 11],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaigns'         => [self::MANY_MANY, Campaign::class, '{{campaign_to_delivery_server}}(server_id, campaign_id)'],
            'bounceServer'      => [self::BELONGS_TO, BounceServer::class, 'bounce_server_id'],
            'trackingDomain'    => [self::BELONGS_TO, TrackingDomain::class, 'tracking_domain_id'],
            'warmupPlan'        => [self::BELONGS_TO, DeliveryServerWarmupPlan::class, 'warmup_plan_id'],
            'customer'          => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'warmupSchedules'   => [self::HAS_MANY, DeliveryServerWarmupPlanScheduleLog::class, 'server_id'],
            'usageLogs'         => [self::HAS_MANY, DeliveryServerUsageLog::class, 'server_id'],
            'domainPolicies'    => [self::HAS_MANY, DeliveryServerDomainPolicy::class, 'server_id'],
            'customerGroups'    => [self::MANY_MANY, CustomerGroup::class, 'delivery_server_to_customer_group(server_id, group_id)'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'server_id'                     => t('servers', 'ID'),
            'customer_id'                   => t('servers', 'Customer'),
            'bounce_server_id'              => t('servers', 'Bounce server'),
            'tracking_domain_id'            => t('servers', 'Tracking domain'),
            'warmup_plan_id'                => t('servers', 'Warmup plan'),
            'type'                          => t('servers', 'Type'),
            'name'                          => t('servers', 'Name'),
            'hostname'                      => t('servers', 'Hostname'),
            'username'                      => t('servers', 'Username'),
            'password'                      => t('servers', 'Password'),
            'port'                          => t('servers', 'Port'),
            'protocol'                      => t('servers', 'Protocol'),
            'timeout'                       => t('servers', 'Timeout'),
            'from_email'                    => t('servers', 'From email'),
            'from_name'                     => t('servers', 'From name'),
            'reply_to_email'                => t('servers', 'Reply-To email'),
            'probability'                   => t('servers', 'Probability'),
            'hourly_quota'                  => t('servers', 'Hourly quota'),
            'daily_quota'                   => t('servers', 'Daily quota'),
            'monthly_quota'                 => t('servers', 'Monthly quota'),
            'meta_data'                     => t('servers', 'Meta data'),
            'additional_headers'            => t('servers', 'Additional headers'),
            'locked'                        => t('servers', 'Locked'),
            'use_for'                       => t('servers', 'Use for'),
            'signing_enabled'               => t('servers', 'Signing enabled'),
            'force_from'                    => t('servers', 'Force FROM'),
            'force_reply_to'                => t('servers', 'Force Reply-To'),
            'force_sender'                  => t('servers', 'Force Sender'),
            'must_confirm_delivery'         => t('servers', 'Must confirm delivery'),
            'max_connection_messages'       => t('servers', 'Max. connection messages'),
            'pause_after_send'              => t('servers', 'Pause after send'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'bounce_server_id'          => t('servers', 'The server that will handle bounce emails for this SMTP server.'),
            'tracking_domain_id'        => t('servers', 'The domain that will be used for tracking purposes, must be a DNS CNAME of the master domain.'),
            'warmup_plan_id'            => t('servers', 'The warmup plan that is attached to this delivery server. The quota of this server will be calculated based on the warmup plan.'),
            'name'                      => t('servers', 'The name of this server to make a distinction if having multiple servers with same hostname.'),
            'hostname'                  => t('servers', 'The hostname of your SMTP server, usually something like smtp.domain.com.'),
            'username'                  => t('servers', 'The username of your SMTP server, usually something like you@domain.com.'),
            'password'                  => t('servers', 'The password of your SMTP server, used in combination with your username to authenticate your request.'),
            'port'                      => t('servers', 'The port of your SMTP server, usually this is 25, but 465 and 587 are also valid choices for some of the servers depending on the security protocol they are using. If unsure leave it to 25.'),
            'protocol'                  => t('servers', 'The security protocol used to access this server. If unsure, leave it blank or select TLS if blank does not work for you.'),
            'timeout'                   => t('servers', 'The maximum number of seconds we should wait for the server to respond to our request. 30 seconds is a proper value.'),
            'from_email'                => t('servers', 'The default email address used in the FROM header when nothing is specified'),
            'from_name'                 => t('servers', 'The default name used in the FROM header, together with the FROM email when nothing is specified'),
            'reply_to_email'            => t('servers', 'The default email address used in the Reply-To header when nothing is specified'),
            'probability'               => t('servers', 'When having multiple servers from where you send, the probability helps to choose one server more than another. This is useful if you are using servers with various quota limits. A lower probability means a lower sending rate using this server.'),
            'hourly_quota'              => t('servers', 'In case there are limits that apply for sending with this server, you can set a hourly quota for it and it will only send in one hour as many emails as you set here. Set it to 0 in order to not apply any hourly limit.'),
            'daily_quota'               => t('servers', 'In case there are limits that apply for sending with this server, you can set a daily quota for it and it will only send in one day as many emails as you set here. Set it to 0 in order to not apply any daily limit.'),
            'monthly_quota'             => t('servers', 'In case there are limits that apply for sending with this server, you can set a monthly quota for it and it will only send in one monthly as many emails as you set here. Set it to 0 in order to not apply any monthly limit.'),
            'locked'                    => t('servers', 'Whether this server is locked and assigned customer cannot change or delete it'),
            'use_for'                   => t('servers', 'For which type of sending can this server be used for'),
            'signing_enabled'           => t('servers', 'Whether signing is enabled when sending emails through this delivery server'),
            'force_from'                => t('servers', 'When to force the FROM email address'),
            'force_reply_to'            => t('servers', 'When to force the Reply-To email address'),
            'force_sender'              => t('servers', 'Whether to force the Sender header, if unsure, leave this disabled'),
            'must_confirm_delivery'     => t('servers', 'Whether the server can and must confirm the actual delivery. Leave as is if not sure.'),
            'max_connection_messages'   => t('servers', 'The maximum number of messages to send through a single smtp connection'),
            'pause_after_send'          => t('servers', 'The number of microseconds to pause after an email is sent. A microsecond is one millionth of a second, so to pause for two seconds you would enter: 2000000'),
        ];

        // since 1.3.6.3
        if (stripos($this->type, 'web-api') !== false || in_array($this->type, ['smtp-amazon'])) {
            $texts['force_from'] = t('servers', 'When to force the FROM address. Please note that if you set this option to Never and you send from a unverified domain, all your emails will fail delivery. It is best to leave this option as is unless you really know what you are doing.');
        }

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'hostname'          => t('servers', 'smtp.your-server.com'),
            'username'          => t('servers', 'you@domain.com'),
            'password'          => t('servers', 'your smtp account password'),
            'from_email'        => t('servers', 'you@domain.com'),
            'reply_to_email'    => t('servers', 'you@domain.com'),
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
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        if (!empty($this->customer_id)) {
            $customerId = (string)$this->customer_id;
            if (is_numeric($customerId)) {
                $criteria->compare('t.customer_id', $customerId);
            } else {
                $criteria->with = [
                    'customer' => [
                        'joinType'  => 'INNER JOIN',
                        'condition' => 'CONCAT(customer.first_name, " ", customer.last_name) LIKE :name',
                        'params'    => [
                            ':name'    => '%' . $customerId . '%',
                        ],
                    ],
                ];
            }
        }
        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.hostname', $this->hostname, true);
        $criteria->compare('t.username', $this->username, true);
        $criteria->compare('t.from_email', $this->from_email, true);
        $criteria->compare('t.type', $this->type);

        if (empty($this->status)) {
            $criteria->addNotInCondition('t.status', [self::STATUS_HIDDEN, self::STATUS_PENDING_DELETE]);
        } else {
            $criteria->compare('t.status', $this->status);
        }

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder'  => [
                    't.server_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * @since 1.3.5.9
     * @param array $headers
     * @return array
     */
    public function parseHeadersFormat(array $headers = []): array
    {
        if (!is_array($headers) || empty($headers)) {
            return [];
        }
        $_headers = [];

        foreach ($headers as $k => $v) {
            // pre 1.3.5.9 format
            if (is_string($k) && is_string($v)) {
                $_headers[] = ['name' => $k, 'value' => $v];
                continue;
            }
            // post 1.3.5.9 format
            if (is_numeric($k) && is_array($v) && array_key_exists('name', $v) && array_key_exists('value', $v)) {
                $_headers[] = ['name' => $v['name'], 'value' => $v['value']];
            }
        }

        return $_headers;
    }

    /**
     * @since 1.3.5.9
     * @param array $headers
     * @return array
     */
    public function parseHeadersIntoKeyValue(array $headers = []): array
    {
        $_headers = [];

        foreach ($headers as $k => $v) {
            if (is_string($k) && (is_string($v) || is_numeric($v))) {
                $_headers[$k] = $v;
                continue;
            }
            if (is_numeric($k) && is_array($v) && array_key_exists('name', $v) && array_key_exists('value', $v)) {
                $_headers[$v['name']] = $v['value'];
            }
        }

        return $_headers;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws Exception
     */
    public function sendEmail(array $params = []): array
    {
        throw new Exception(__METHOD__ . ' has not been implemented!');
    }

    /**
     * @return Mailer
     */
    public function getMailer(): Mailer
    {
        if ($this->_mailer === null) {
            $this->_mailer = clone mailer();
        }
        return $this->_mailer;
    }

    /**
     * @return array
     */
    public function getBounceServersArray(): array
    {
        static $_options = [];
        if (!empty($_options)) {
            return $_options;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'server_id, name, hostname, username, service';

        if (apps()->isAppName('backend')) {
            $criteria->addCondition('customer_id = :cid OR customer_id IS NULL');
        } else {
            $criteria->addCondition('customer_id = :cid OR server_id = :sid');
            $criteria->params[':sid'] = (int)$this->bounce_server_id;
        }
        $criteria->params[':cid'] = (int)$this->customer_id;

        $criteria->addInCondition('status', [BounceServer::STATUS_ACTIVE, BounceServer::STATUS_CRON_RUNNING]);
        $criteria->order = 'server_id DESC';
        $models = BounceServer::model()->findAll($criteria);

        $_options[''] = t('app', 'Choose');
        foreach ($models as $model) {
            $_options[$model->server_id] = $model->getDisplayName();
        }

        return $_options;
    }

    /**
     * @return string
     */
    public function getDisplayBounceServer(): string
    {
        if (empty($this->bounceServer)) {
            return '';
        }

        $model = $this->bounceServer;

        return sprintf('%s - %s(%s)', strtoupper((string)$model->service), $model->hostname, $model->username);
    }

    /**
     * @return array
     */
    public function getBounceServerNotSupportedTypes(): array
    {
        $types = [
            self::TRANSPORT_AMAZON_SES_WEB_API,
            self::TRANSPORT_MAILGUN_WEB_API,
            self::TRANSPORT_SENDGRID_WEB_API,
            self::TRANSPORT_INBOXROAD_WEB_API,
            self::TRANSPORT_ELASTICEMAIL_WEB_API,
            self::TRANSPORT_DYN_WEB_API,
            self::TRANSPORT_SPARKPOST_WEB_API,
            self::TRANSPORT_PEPIPOST_WEB_API,
            self::TRANSPORT_POSTMARK_WEB_API,
            self::TRANSPORT_NEWSMAN_WEB_API,
            self::TRANSPORT_MAILJET_WEB_API,
            self::TRANSPORT_SENDINBLUE_WEB_API,
            self::TRANSPORT_TIPIMAIL_WEB_API,
            self::TRANSPORT_POSTAL_WEB_API,
            self::TRANSPORT_SMTP_INBOXROAD,
        ];

        return (array)hooks()->applyFilters('delivery_servers_get_bounce_server_not_supported_types', $types);
    }

    /**
     * @return bool
     */
    public function getBounceServerNotSupported(): bool
    {
        return in_array($this->type, $this->getBounceServerNotSupportedTypes());
    }

    /**
     * @return array
     */
    public function getSigningSupportedTypes(): array
    {
        $types = [
            self::TRANSPORT_PICKUP_DIRECTORY,
            self::TRANSPORT_SENDMAIL,
            self::TRANSPORT_SMTP,
            self::TRANSPORT_SMTP_PMTA,
            self::TRANSPORT_SMTP_POSTAL,
            self::TRANSPORT_SMTP_POSTMASTERY,
            self::TRANSPORT_MAILERQ_WEB_API,
        ];
        return (array)hooks()->applyFilters('delivery_servers_get_signing_supported_types', $types);
    }

    /**
     * @return array
     */
    public function getTrackingDomainsArray(): array
    {
        static $_options = [];
        if (!empty($_options)) {
            return $_options;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'domain_id, name';
        $criteria->compare('verified', TrackingDomain::TEXT_YES);

        if (apps()->isAppName('backend')) {
            $criteria->addCondition('customer_id = :cid OR customer_id IS NULL');
        } else {
            $criteria->addCondition('customer_id = :cid OR domain_id = :did');
            $criteria->params[':did'] = (int)$this->tracking_domain_id;
        }
        $criteria->params[':cid'] = (int)$this->customer_id;

        $criteria->order = 'domain_id DESC';
        $models = TrackingDomain::model()->findAll($criteria);

        $_options[''] = t('app', 'Choose');
        foreach ($models as $model) {
            $_options[$model->domain_id] = $model->name;
        }

        return $_options;
    }

    /**
     * @return array
     */
    public function getProtocolsArray(): array
    {
        return [
            ''          => t('app', 'Choose'),
            'tls'       => 'TLS',
            'ssl'       => 'SSL',
            'starttls'  => 'STARTTLS',
        ];
    }

    /**
     * @return string
     */
    public function getProtocolName(): string
    {
        return $this->getProtocolsArray()[$this->protocol] ?? t('app', 'Default');
    }

    /**
     * @return array
     */
    public function getProbabilityArray(): array
    {
        $options = ['' => t('app', 'Choose')];
        for ($i = 5; $i <= 100; ++$i) {
            if ($i % 5 == 0) {
                $options[$i] = $i . ' %';
            }
        }
        return $options;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws CException
     */
    public function getParamsArray(array $params = []): array
    {
        $deliveryObject = null;

        /** @var Customer|null $customer */
        $customer = isset($params['customer']) && is_object($params['customer']) ? $params['customer'] : null;

        if ($deliveryObject = $this->getDeliveryObject()) {
            if (empty($customer) && is_object($deliveryObject) && $deliveryObject instanceof Campaign) {

                /** @var Customer|null $customer */
                $customer = $deliveryObject->customer;
            }
            if (
                empty($customer) && is_object($deliveryObject) && $deliveryObject instanceof Lists &&
                !empty($deliveryObject->default)
            ) {

                /** @var Customer|null $customer */
                $customer = $deliveryObject->customer;
            }
        }

        if (!empty($customer)) {
            $hlines = (string)$customer->getGroupOption('servers.custom_headers', '');
        } else {
            /** @var OptionCustomerServers $optionCustomerServers */
            $optionCustomerServers = container()->get(OptionCustomerServers::class);
            $hlines = (string)$optionCustomerServers->getCustomHeaders();
        }
        $defaultHeaders = DeliveryServerHelper::getOptionCustomerCustomHeadersArrayFromString($hlines);

        foreach ((array)$this->additional_headers as $header) {
            if (!isset($header['name'], $header['value'])) {
                continue;
            }
            foreach ($defaultHeaders as $index => $dheader) {
                if ($dheader['name'] == $header['name']) {
                    unset($defaultHeaders[$index]);
                    continue;
                }
            }
        }

        foreach ((array)$this->additional_headers as $header) {
            if (!isset($header['name'], $header['value'])) {
                continue;
            }
            $defaultHeaders[] = $header;
        }

        // reindex
        $defaultHeaders = array_values($defaultHeaders);

        // default params
        $defaultParams = CMap::mergeArray([
            'server_id'             => (int)$this->server_id,
            'transport'             => self::TRANSPORT_SMTP,
            'hostname'              => '',
            'username'              => '',
            'password'              => '',
            'port'                  => 25,
            'timeout'               => 30,
            'protocol'              => '',
            'probability'           => 100,
            'headers'               => $defaultHeaders,
            'from'                  => (string)$this->from_email,
            'fromName'              => (string)$this->from_name,
            'sender'                => (string)$this->from_email,
            'returnPath'            => (string)$this->from_email,
            'replyTo'               => !empty($this->reply_to_email) ? (string)$this->reply_to_email : (string)$this->from_email,
            'to'                    => '',
            'subject'               => '',
            'body'                  => '',
            'plainText'             => '',
            'trackingEnabled'       => (bool)$this->getTrackingEnabled(), // changed from 1.3.5.3
            'signingEnabled'        => (bool)$this->getSigningEnabled(),
            'forceFrom'             => (string)$this->force_from,
            'forcedFromEmail'       => '',
            'forceReplyTo'          => (string)$this->force_reply_to,
            'forceSender'           => (string)$this->force_sender == self::TEXT_YES, // 1.3.7.1
            'sendingDomain'         => null, // 1.3.7.1
            'dkimPrivateKey'        => '',
            'dkimDomain'            => '',
            'dkimSelector'          => SendingDomain::getDkimSelector(),
            'maxConnectionMessages' => !empty($this->max_connection_messages) ? (int)$this->max_connection_messages : 1,
            'abSubject'             => null, // since 2.0.34
            'campaignUid'           => '', // since 2.0.34
            'subscriberUid'         => '', // since 2.0.34
            'customerUid'           => '', // since 2.0.34
        ], $this->attributes);

        // avoid merging arrays recursive ending up with multiple arrays when we expect only one.
        $uniqueKeys = ['from', 'sender', 'returnPath', 'replyTo', 'to'];
        foreach ($uniqueKeys as $key) {
            if (array_key_exists($key, $params) && array_key_exists($key, $defaultParams)) {
                unset($defaultParams[$key]);
            }
        }

        //
        if (!empty($params['headers'])) {
            foreach ($params['headers'] as $header) {
                if (!isset($header['name'], $header['value'])) {
                    continue;
                }
                foreach ($defaultParams['headers'] as $idx => $h) {
                    if (!isset($h['name'], $h['value'])) {
                        continue;
                    }
                    if (strtolower((string)$h['name']) == strtolower((string)$header['name'])) {
                        unset($defaultParams['headers'][$idx]);
                    }
                }
            }
        }

        // merge them all now
        $params      = CMap::mergeArray($defaultParams, $params);
        $customer_id = null;
        $fromEmail   = $this->from_email;

        if (is_object($deliveryObject) && $deliveryObject instanceof Campaign) {
            $_fromName   = !empty($params['fromNameCustom']) ? $params['fromNameCustom'] : (string)$deliveryObject->from_name;
            $_fromEmail  = !empty($params['fromEmailCustom']) ? $params['fromEmailCustom'] : (string)$deliveryObject->from_email;
            $_replyEmail = !empty($params['replyToCustom']) ? $params['replyToCustom'] : (string)$deliveryObject->reply_to;

            $params['fromName'] = $_fromName;
            $params['from']     = [$_fromEmail => $_fromName];
            $params['sender']   = [$_fromEmail => $_fromName];
            $params['replyTo']  = [$_replyEmail => $_fromName];

            $customer_id = (int)$deliveryObject->customer_id;
            $fromEmail   = $_fromEmail;
        }

        if (is_object($deliveryObject) && $deliveryObject instanceof Lists && !empty($deliveryObject->default)) {
            $_fromName   = !empty($params['fromNameCustom']) ? $params['fromNameCustom'] : (string)$deliveryObject->default->from_name;
            $_fromEmail  = !empty($params['fromEmailCustom']) ? $params['fromEmailCustom'] : (string)$deliveryObject->default->from_email;
            $_replyEmail = !empty($params['replyToCustom']) ? $params['replyToCustom'] : (string)$deliveryObject->default->reply_to;

            $params['fromName'] = $_fromName;
            $params['from']     = [$_fromEmail => $_fromName];
            $params['sender']   = [$_fromEmail => $_fromName];
            $params['replyTo']  = [$_replyEmail => $_fromName];

            $customer_id = (int)$deliveryObject->customer_id;
            $fromEmail   = $_fromEmail;
        }

        if ($params['forceReplyTo'] == self::FORCE_REPLY_TO_ALWAYS) {
            $params['replyTo'] = !empty($this->reply_to_email) ? $this->reply_to_email : $this->from_email;
        }

        if ($params['forceFrom'] == self::FORCE_FROM_ALWAYS) {
            $fromEmail = $this->from_email;
        }

        if (!empty($params['signingEnabled'])) {
            $sendingDomain = null;
            if (!empty($this->bounce_server_id) && !empty($this->bounceServer)) {
                $returnPathEmail = !empty($this->bounceServer->email) ? $this->bounceServer->email : $this->bounceServer->username;
                $sendingDomain   = SendingDomain::model()->findVerifiedByEmail($returnPathEmail, $customer_id, true);
            }
            if (empty($sendingDomain)) {
                $sendingDomain = SendingDomain::model()->findVerifiedByEmail($fromEmail, $customer_id, true);
            }

            if (!empty($sendingDomain)) {
                $params['dkimPrivateKey'] = (string)$sendingDomain->dkim_private_key;
                $params['dkimDomain']     = (string)$sendingDomain->name;
            }
        }

        if (
            $params['forceFrom'] == self::FORCE_FROM_ALWAYS ||
            ($params['forceFrom'] == self::FORCE_FROM_WHEN_NO_SIGNING_DOMAIN && empty($params['dkimDomain']))
        ) {
            $fromEmail = $this->from_email;
            if (!empty($params['from'])) {
                if (is_array($params['from'])) {
                    $value = null;
                    foreach ($params['from'] as $value) {
                        break;
                    }
                    $params['from']   = [$fromEmail => $value];
                    $params['sender'] = [$fromEmail => $value];
                } else {
                    $params['from']   = $fromEmail;
                    $params['sender'] = $fromEmail;
                }
            }
        }

        $hasBounceServer = false;
        if (!empty($this->bounce_server_id) && !empty($this->bounceServer)) {
            if (!empty($this->bounceServer->email)) {
                $params['returnPath'] = $this->bounceServer->email;
                $hasBounceServer      = true;
            } elseif (FilterVarHelper::email($this->bounceServer->username)) {
                $params['returnPath'] = $this->bounceServer->username;
                $hasBounceServer      = true;
            }
        }

        // 1.3.7.1
        if (!$hasBounceServer) {
            [$_fromEmail] = $this->getMailer()->findEmailAndName($params['from']);
            if (!empty($_fromEmail) && FilterVarHelper::email($_fromEmail)) {
                $sendingDomain = SendingDomain::model()->findVerifiedByEmail($_fromEmail, $customer_id);
                if (!empty($sendingDomain)) {
                    $params['returnPath'] = $_fromEmail;
                }
            }
        }
        //

        // changed since 1.3.5.3
        if (!empty($params['trackingEnabled'])) {
            // since 1.3.5.4 - we disabled the action hook in the favor of the direct method.
            $params = $this->_handleTrackingDomain($params);
        }

        // since 1.3.5.9
        foreach ($params['headers'] as $index => $header) {
            if (!isset($header['name'], $header['value'])) {
                continue;
            }
            if (strtolower((string)$header['name']) == 'x-force-return-path') {
                $header['value'] = preg_replace('#\[([a-z0-9\_]+)\](\-)?#six', '', $header['value']);
                $header['value'] = trim((string)$header['value'], '- ');
                $header['value'] = (string)str_replace('-@', '@', $header['value']);

                $params['headers'][$index]['value'] = $header['value'];
                $params['returnPath'] = $header['value'];
                break;
            }
        }
        //

        // and trigger the attached filters
        return (array)hooks()->applyFilters('delivery_server_get_params_array', $params);
    }

    /**
     * @return string
     */
    public function getFromEmail(): string
    {
        return (string)$this->from_email;
    }

    /**
     * @return string
     */
    public function getFromName(): string
    {
        return (string)$this->from_name;
    }

    /**
     * @return string
     */
    public function getSenderEmail(): string
    {
        return (string)$this->from_email;
    }

    /**
     * @return string
     */
    public function requirementsFailedMessage(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getTypeName(): string
    {
        return self::getNameByType($this->type);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    public static function getNameByType(string $type): string
    {
        $mapping = self::getTypesMapping();
        if (!isset($mapping[$type])) {
            return '';
        }
        if ($type == self::TRANSPORT_SMTP_MYSMTPCOM) {
            return 'SMTP mySMTP.com';
        }
        return ucwords(str_replace(['-'], ' ', t('servers', $type)));
    }

    /**
     * @return array
     */
    public static function getTypesMapping(): array
    {
        static $mapping;
        if ($mapping !== null) {
            return (array)$mapping;
        }

        $mapping = [
            self::TRANSPORT_MAILGUN_WEB_API      => DeliveryServerMailgunWebApi::class,
            self::TRANSPORT_SPARKPOST_WEB_API    => DeliveryServerSparkpostWebApi::class,
            self::TRANSPORT_SENDGRID_WEB_API     => DeliveryServerSendgridWebApi::class,
            self::TRANSPORT_INBOXROAD_WEB_API    => DeliveryServerInboxroadWebApi::class,
            self::TRANSPORT_POSTAL_WEB_API       => DeliveryServerPostalWebApi::class,

            self::TRANSPORT_ELASTICEMAIL_WEB_API => DeliveryServerElasticemailWebApi::class,
            self::TRANSPORT_AMAZON_SES_WEB_API   => DeliveryServerAmazonSesWebApi::class,
            self::TRANSPORT_PEPIPOST_WEB_API     => DeliveryServerPepipostWebApi::class,

            self::TRANSPORT_MAILJET_WEB_API      => DeliveryServerMailjetWebApi::class,
            self::TRANSPORT_SENDINBLUE_WEB_API   => DeliveryServerSendinblueWebApi::class,
            self::TRANSPORT_NEWSMAN_WEB_API      => DeliveryServerNewsManWebApi::class,

            self::TRANSPORT_SMTP                 => DeliveryServerSmtp::class,
            self::TRANSPORT_SMTP_AMAZON          => DeliveryServerSmtpAmazon::class,
            self::TRANSPORT_SMTP_POSTMASTERY     => DeliveryServerSmtpPostmastery::class,
            self::TRANSPORT_SMTP_POSTAL          => DeliveryServerSmtpPostal::class,
            self::TRANSPORT_SMTP_MYSMTPCOM       => DeliveryServerSmtpMySmtpCom::class,
            self::TRANSPORT_SMTP_PMTA            => DeliveryServerSmtpPmta::class,
            self::TRANSPORT_SMTP_INBOXROAD       => DeliveryServerSmtpInboxroad::class,

            self::TRANSPORT_DYN_WEB_API          => DeliveryServerDynWebApi::class,
            self::TRANSPORT_TIPIMAIL_WEB_API     => DeliveryServerTipimailWebApi::class,

            self::TRANSPORT_POSTMARK_WEB_API     => DeliveryServerPostmarkWebApi::class,
            self::TRANSPORT_MAILERQ_WEB_API      => DeliveryServerMailerqWebApi::class,

            self::TRANSPORT_SENDMAIL             => DeliveryServerSendmail::class,
            self::TRANSPORT_PICKUP_DIRECTORY     => DeliveryServerPickupDirectory::class,
        ];

        $mapping = (array)hooks()->applyFilters('delivery_servers_get_types_mapping', $mapping);

        foreach ($mapping as $type => $class) {

            /** @var DeliveryServer $server */
            $server = new $class();

            if ($server->requirementsFailedMessage()) {
                unset($mapping[$type]);
            }
        }

        return $mapping;
    }

    /**
     * @param Customer|null $customer
     *
     * @return array
     */
    public static function getCustomerTypesMapping(?Customer $customer = null): array
    {
        static $mapping;
        if ($mapping !== null) {
            return (array)$mapping;
        }

        $mapping = self::getTypesMapping();
        if (!$customer) {
            /** @var OptionCustomerServers $optionCustomerServers */
            $optionCustomerServers = container()->get(OptionCustomerServers::class);
            $allowed = $optionCustomerServers->getAllowedServerTypes();
        } else {
            $allowed = (array)$customer->getGroupOption('servers.allowed_server_types', []);
        }

        foreach ($mapping as $type => $name) {
            if (!in_array($type, $allowed)) {
                unset($mapping[$type]);
                continue;
            }
        }

        return $mapping = (array)hooks()->applyFilters('delivery_servers_get_customer_types_mapping', $mapping);
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_ACTIVE     => ucfirst(t('app', self::STATUS_ACTIVE)),
            self::STATUS_IN_USE     => ucfirst(t('app', self::STATUS_IN_USE)),
            self::STATUS_INACTIVE   => ucfirst(t('app', self::STATUS_INACTIVE)),
            self::STATUS_DISABLED   => ucfirst(t('app', self::STATUS_DISABLED)),
        ];
    }

    /**
     * @return array
     */
    public static function getTypesList(): array
    {
        $list = [];
        foreach (self::getTypesMapping() as $key => $value) {
            $list[$key] = self::getNameByType($key);
        }
        return $list;
    }

    /**
     * @return array
     */
    public static function getCustomerTypesList(): array
    {
        $list = [];
        foreach (self::getCustomerTypesMapping() as $key => $value) {
            $list[$key] = self::getNameByType($key);
        }
        return $list;
    }

    /**
     * @return array
     */
    public static function getDeliveryForList(): array
    {
        return [
            self::DELIVERY_FOR_CAMPAIGN      => ucfirst(t('app', self::DELIVERY_FOR_CAMPAIGN)),
            self::DELIVERY_FOR_CAMPAIGN_TEST => ucfirst(t('app', self::DELIVERY_FOR_CAMPAIGN_TEST)),
            self::DELIVERY_FOR_LIST          => ucfirst(t('app', self::DELIVERY_FOR_LIST)),
            self::DELIVERY_FOR_SYSTEM        => ucfirst(t('app', self::DELIVERY_FOR_SYSTEM)),
            self::DELIVERY_FOR_TEMPLATE_TEST => ucfirst(t('app', self::DELIVERY_FOR_TEMPLATE_TEST)),
            self::DELIVERY_FOR_TRANSACTIONAL => ucfirst(t('app', self::DELIVERY_FOR_TRANSACTIONAL)),

        ];
    }

    /**
     * @param mixed $object
     * @return DeliveryServer
     */
    public function setDeliveryObject($object): self
    {
        $this->_deliveryObject = $object;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDeliveryObject()
    {
        return $this->_deliveryObject;
    }

    /**
     * @param string $deliveryFor
     *
     * @return $this
     */
    public function setDeliveryFor(string $deliveryFor)
    {
        $this->_deliveryFor = $deliveryFor;
        return $this;
    }

    /**
     * @return string
     */
    public function getDeliveryFor(): string
    {
        return (string)$this->_deliveryFor;
    }

    /**
     * @param string $for
     * @return bool
     */
    public function isDeliveryFor(string $for)
    {
        return (string)$this->_deliveryFor === (string)$for;
    }

    /**
     * @return bool
     */
    public function getCanLogUsage(): bool
    {
        // since 1.3.5.5
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_DS_LOG_USAGE') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_DS_LOG_USAGE
        ) {
            return false;
        }

        // since 1.3.5
        if (!$this->_logUsage) {
            return false;
        }

        return $this->getCanHaveQuota() ||
               ($this->getCustomerByDeliveryObject() && $this->getDeliveryIsCountableForCustomer());
    }

    /**
     * @return DeliveryServerUsageLog|null
     */
    public function logUsage(): ?DeliveryServerUsageLog
    {
        // since 1.3.5.5
        if (!$this->getCanLogUsage()) {
            return null;
        }

        $log = new DeliveryServerUsageLog();
        $log->server_id = (int)$this->server_id;
        $log->addRelatedRecord('server', $this, false);

        if ($customer = $this->getCustomerByDeliveryObject()) {
            $log->customer_id = (int)$customer->customer_id;

            // 1.3.9.5
            $log->addRelatedRecord('customer', $customer, false);

            if (!$this->getDeliveryIsCountableForCustomer()) {
                $log->customer_countable = DeliveryServerUsageLog::TEXT_NO;
            }
        }

        $log->delivery_for = $this->getDeliveryFor();
        if (!$log->save(false)) {
            return null;
        }

        $this->decreaseHourlyQuota();
        $this->decreaseDailyQuota();
        $this->decreaseMonthlyQuota();

        if ($log->getIsCustomerCountable()) {
            $log->customer->increaseLastQuotaMarkCachedUsage();

            // since 1.3.9.7
            if ($log->customer->getCanHaveHourlyQuota()) {
                $log->customer->increaseHourlyUsageCached();
            }
        }

        return $log;
    }

    /**
     * @param DeliveryServerUsageLog $log
     *
     * @return bool
     */
    public function undoLogUsage(DeliveryServerUsageLog $log): bool
    {
        // since 1.3.5.5
        if (!$this->getCanLogUsage()) {
            return false;
        }

        if (empty($log->log_id)) {
            return false;
        }

        if (!DeliveryServerUsageLog::model()->deleteAllByAttributes(['log_id' => (int)$log->log_id])) {
            return false;
        }
        $this->decreaseHourlyQuota(-1);
        $this->decreaseDailyQuota(-1);
        $this->decreaseMonthlyQuota(-1);

        // 1.3.9.5
        if ($log->getIsCustomerCountable()) {
            $log->customer->increaseLastQuotaMarkCachedUsage(-1);

            // since 1.3.9.7
            if ($log->customer->getCanHaveHourlyQuota()) {
                $log->customer->increaseHourlyUsageCached(-1);
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function getDeliveryIsCountableForCustomer(): bool
    {
        if (!($deliveryObject = $this->getDeliveryObject())) {
            return false;
        }

        if (!($customer = $this->getCustomerByDeliveryObject())) {
            return false;
        }

        $trackableDeliveryFor = [
            self::DELIVERY_FOR_CAMPAIGN,
            self::DELIVERY_FOR_CAMPAIGN_TEST,
            self::DELIVERY_FOR_TEMPLATE_TEST,
            self::DELIVERY_FOR_LIST,
            self::DELIVERY_FOR_TRANSACTIONAL,
        ];

        if (!in_array($this->getDeliveryFor(), $trackableDeliveryFor)) {
            return false;
        }

        if ($deliveryObject instanceof Campaign) {
            if (
                $this->isDeliveryFor(self::DELIVERY_FOR_CAMPAIGN) &&
                $customer->getGroupOption('quota_counters.campaign_emails', self::TEXT_YES) == self::TEXT_YES
            ) {
                return true;
            }
            if (
                $this->isDeliveryFor(self::DELIVERY_FOR_CAMPAIGN_TEST) &&
                $customer->getGroupOption('quota_counters.campaign_test_emails', self::TEXT_YES) == self::TEXT_YES
            ) {
                return true;
            }
            return false;
        }

        if ($deliveryObject instanceof CustomerEmailTemplate) {
            if (
                $this->isDeliveryFor(self::DELIVERY_FOR_TEMPLATE_TEST) &&
                $customer->getGroupOption('quota_counters.template_test_emails', self::TEXT_YES) == self::TEXT_YES
            ) {
                return true;
            }
            return false;
        }

        if ($deliveryObject instanceof Lists) {
            if (
                $this->isDeliveryFor(self::DELIVERY_FOR_LIST) &&
                $customer->getGroupOption('quota_counters.list_emails', self::TEXT_YES) == self::TEXT_YES
            ) {
                return true;
            }
            return false;
        }

        if ($deliveryObject instanceof TransactionalEmail) {
            if (
                $this->isDeliveryFor(self::DELIVERY_FOR_TRANSACTIONAL) &&
                $customer->getGroupOption('quota_counters.transactional_emails', self::TEXT_YES) == self::TEXT_YES
            ) {
                return true;
            }
            return false;
        }

        return false;
    }

    /**
     * @return int
     */
    public function countHourlyUsage(): int
    {
        $count = 0;
        try {
            $criteria = new CDbCriteria();
            $criteria->compare('server_id', (int)$this->server_id);
            $criteria->addCondition('
                `date_added` BETWEEN DATE_FORMAT(NOW(), "%Y-%m-%d %H:00:00") AND 
                DATE_FORMAT(NOW() + INTERVAL 1 HOUR, "%Y-%m-%d %H:00:00")
            ');
            $count = (int)DeliveryServerUsageLog::model()->count($criteria);
        } catch (Exception $e) {
        }

        return $count;
    }

    /**
     * @return bool
     */
    public function getCanHaveHourlyQuota(): bool
    {
        return !$this->getIsNewRecord() && $this->hourly_quota > 0;
    }

    /**
     * @return int
     */
    public function countDailyUsage(): int
    {
        $criteria = new CDbCriteria();
        $criteria->compare('server_id', (int)$this->server_id);
        $criteria->addCondition('
            `date_added` BETWEEN DATE_FORMAT(NOW(), "%Y-%m-%d 00:00:00") AND 
        	DATE_FORMAT(NOW() + INTERVAL 1 DAY, "%Y-%m-%d 00:00:00")
        ');
        return (int)DeliveryServerUsageLog::model()->count($criteria);
    }

    /**
     * @return bool
     */
    public function getCanHaveDailyQuota(): bool
    {
        return !$this->getIsNewRecord() && $this->daily_quota > 0;
    }

    /**
     * @return int
     */
    public function countMonthlyUsage(): int
    {
        $criteria = new CDbCriteria();
        $criteria->compare('server_id', (int)$this->server_id);
        $criteria->addCondition('
            `date_added` BETWEEN DATE_FORMAT(NOW(), "%Y-%m-01 00:00:00") AND 
            DATE_FORMAT(NOW() + INTERVAL 1 MONTH, "%Y-%m-01 00:00:00")
        ');
        return (int)DeliveryServerUsageLog::model()->count($criteria);
    }

    /**
     * @return bool
     */
    public function getCanHaveMonthlyQuota(): bool
    {
        return !$this->getIsNewRecord() && $this->monthly_quota > 0;
    }

    /**
     * @param bool $useMutex
     * @return int
     */
    public function getHourlyQuotaLeft(bool $useMutex = true): int
    {
        if (!$this->getCanHaveHourlyQuota()) {
            return PHP_INT_MAX;
        }

        $accessKey = sha1(sprintf($this->_hourlyQuotaAccessKey, (int)$this->server_id));

        if ($useMutex && !mutex()->acquire($accessKey, 5)) {
            return 0;
        }

        if (($sendingsLeft = cache()->get($accessKey)) !== false) {
            if ($useMutex) {
                mutex()->release($accessKey);
            }
            return (int)$sendingsLeft;
        }

        $sendingsLeft = $this->hourly_quota - $this->countHourlyUsage();
        $sendingsLeft = $sendingsLeft > 0 ? $sendingsLeft : 0;

        cache()->set($accessKey, $sendingsLeft, self::QUOTA_CACHE_SECONDS);
        if ($useMutex) {
            mutex()->release($accessKey);
        }

        return (int)$sendingsLeft;
    }

    /**
     * @param int $by
     * @param bool $useMutex
     * @return int
     */
    public function decreaseHourlyQuota(int $by = 1, bool $useMutex = true): int
    {
        if (!$this->getCanHaveHourlyQuota()) {
            return PHP_INT_MAX;
        }

        $accessKey = sha1(sprintf($this->_hourlyQuotaAccessKey, (int)$this->server_id));

        if ($useMutex && !mutex()->acquire($accessKey, 60)) {
            return 0;
        }

        $sendingsLeft = $this->getHourlyQuotaLeft(!$useMutex) - (int)$by;
        $sendingsLeft = $sendingsLeft > 0 ? $sendingsLeft : 0;

        cache()->set($accessKey, $sendingsLeft, self::QUOTA_CACHE_SECONDS);

        if ($useMutex) {
            mutex()->release($accessKey);
        }

        return (int)$sendingsLeft;
    }

    /**
     * @param bool $useMutex
     * @return int
     */
    public function getDailyQuotaLeft(bool $useMutex = true): int
    {
        if (!$this->getCanHaveDailyQuota()) {
            return PHP_INT_MAX;
        }

        $accessKey = sha1(sprintf($this->_dailyQuotaAccessKey, (int)$this->server_id));

        if ($useMutex && !mutex()->acquire($accessKey, 5)) {
            return 0;
        }

        if (($sendingsLeft = cache()->get($accessKey)) !== false) {
            if ($useMutex) {
                mutex()->release($accessKey);
            }
            return (int)$sendingsLeft;
        }

        $sendingsLeft = $this->daily_quota - $this->countDailyUsage();
        $sendingsLeft = $sendingsLeft > 0 ? $sendingsLeft : 0;

        cache()->set($accessKey, $sendingsLeft, self::QUOTA_CACHE_SECONDS);
        if ($useMutex) {
            mutex()->release($accessKey);
        }

        return (int)$sendingsLeft;
    }

    /**
     * @param int $by
     * @param bool $useMutex
     * @return int
     */
    public function decreaseDailyQuota(int $by = 1, bool $useMutex = true): int
    {
        if (!$this->getCanHaveDailyQuota()) {
            return PHP_INT_MAX;
        }

        $accessKey = sha1(sprintf($this->_dailyQuotaAccessKey, (int)$this->server_id));

        if ($useMutex && !mutex()->acquire($accessKey, 60)) {
            return 0;
        }

        $sendingsLeft = $this->getDailyQuotaLeft(!$useMutex) - (int)$by;
        $sendingsLeft = $sendingsLeft > 0 ? $sendingsLeft : 0;

        cache()->set($accessKey, $sendingsLeft, self::QUOTA_CACHE_SECONDS);

        if ($useMutex) {
            mutex()->release($accessKey);
        }

        return (int)$sendingsLeft;
    }

    /**
     * @param bool $useMutex
     * @return int
     */
    public function getMonthlyQuotaLeft(bool $useMutex = true): int
    {
        if (!$this->getCanHaveMonthlyQuota()) {
            return PHP_INT_MAX;
        }

        $accessKey = sha1(sprintf($this->_monthlyQuotaAccessKey, (int)$this->server_id));

        if ($useMutex && !mutex()->acquire($accessKey, 5)) {
            return 0;
        }

        if (($sendingsLeft = cache()->get($accessKey)) !== false) {
            if ($useMutex) {
                mutex()->release($accessKey);
            }
            return (int)$sendingsLeft;
        }

        $sendingsLeft = $this->monthly_quota - $this->countMonthlyUsage();
        $sendingsLeft = $sendingsLeft > 0 ? $sendingsLeft : 0;

        cache()->set($accessKey, $sendingsLeft, self::QUOTA_CACHE_SECONDS);

        if ($useMutex) {
            mutex()->release($accessKey);
        }

        return (int)$sendingsLeft;
    }

    /**
     * @param int $by
     * @param bool $useMutex
     * @return int
     */
    public function decreaseMonthlyQuota(int $by = 1, bool $useMutex = true): int
    {
        if (!$this->getCanHaveMonthlyQuota()) {
            return PHP_INT_MAX;
        }

        $accessKey = sha1(sprintf($this->_monthlyQuotaAccessKey, (int)$this->server_id));

        if ($useMutex && !mutex()->acquire($accessKey, 60)) {
            return 0;
        }

        $sendingsLeft = $this->getMonthlyQuotaLeft(!$useMutex) - (int)$by;
        $sendingsLeft = $sendingsLeft > 0 ? $sendingsLeft : 0;

        cache()->set($accessKey, $sendingsLeft, self::QUOTA_CACHE_SECONDS);

        if ($useMutex) {
            mutex()->release($accessKey);
        }

        return (int)$sendingsLeft;
    }

    /**
     * @since 1.5.8
     * @return bool
     */
    public function getCanHaveQuota(): bool
    {
        // since 1.3.5.5
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_DS_QUOTA_CHECK') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_DS_QUOTA_CHECK
        ) {
            return false;
        }

        if ($this->getIsNewRecord()) {
            return false;
        }

        return $this->getCanHaveHourlyQuota() || $this->getCanHaveDailyQuota() || $this->getCanHaveMonthlyQuota();
    }

    /**
     * @return bool
     */
    public function getIsOverQuota(): bool
    {
        // since 1.3.5.5
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_DS_QUOTA_CHECK') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_DS_QUOTA_CHECK
        ) {
            return false;
        }

        if ($this->getIsNewRecord()) {
            return false;
        }

        if ($this->getHourlyQuotaLeft() == 0) {
            return true;
        }

        if ($this->getDailyQuotaLeft() == 0) {
            return true;
        }

        if ($this->getMonthlyQuotaLeft() == 0) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getCanBeDeleted(): bool
    {
        return !in_array($this->status, [self::STATUS_IN_USE]);
    }

    /**
     * @return bool
     */
    public function getCanBeUpdated(): bool
    {
        return !in_array($this->status, [self::STATUS_IN_USE, self::STATUS_HIDDEN, self::STATUS_PENDING_DELETE]);
    }

    /**
     * @param bool $refresh
     *
     * @return $this
     * @throws Exception
     */
    public function setIsInUse(bool $refresh = true)
    {
        if ($this->getIsInUse()) {
            return $this;
        }

        $this->saveStatus(self::STATUS_IN_USE);

        if ($refresh) {
            $this->refresh();
        }

        return $this;
    }

    /**
     * @param bool $refresh
     *
     * @return $this
     * @throws Exception
     */
    public function setIsNotInUse(bool $refresh = true)
    {
        if (!$this->getIsInUse()) {
            return $this;
        }

        $this->saveStatus(self::STATUS_ACTIVE);

        if ($refresh) {
            $this->refresh();
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsInUse(): bool
    {
        return $this->getStatusIs(self::STATUS_IN_USE);
    }

    /**
     * @return bool
     */
    public function getIsLocked(): bool
    {
        return (string)$this->locked === self::TEXT_YES;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return empty($this->name) ? $this->hostname : $this->name;
    }

    /**
     * @return bool
     */
    public function getIsPendingDelete(): bool
    {
        return $this->getStatusIs(self::STATUS_PENDING_DELETE);
    }

    /**
     * @param string $emailAddress
     * @return bool
     */
    public function canSendToDomainOf(string $emailAddress): bool
    {
        // since 1.3.5.5
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_DS_CAN_SEND_TO_DOMAIN_OF_CHECK') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_DS_CAN_SEND_TO_DOMAIN_OF_CHECK
        ) {
            return true;
        }
        return DeliveryServerDomainPolicy::canSendToDomainOf((int)$this->server_id, $emailAddress);
    }

    /**
     * @return array
     */
    public function getNeverAllowedHeaders(): array
    {
        $neverAllowed = [
            'From', 'To', 'Subject', 'Date', 'Return-Path', 'Sender',
            'Reply-To', 'Message-Id', 'List-Unsubscribe',
            'Content-Type', 'Content-Transfer-Encoding', 'Content-Length', 'MIME-Version',
            'X-Sender', 'X-Receiver', 'X-Report-Abuse', 'List-Id',
        ];

        return (array)hooks()->applyFilters('delivery_server_never_allowed_headers', $neverAllowed);
    }

    /**
     * @return Customer|null
     */
    public function getCustomerByDeliveryObject(): ?Customer
    {
        return self::parseDeliveryObjectForCustomer($this->getDeliveryObject());
    }

    /**
     * @param mixed $deliveryObject
     * @return Customer|null
     */
    public static function parseDeliveryObjectForCustomer($deliveryObject): ?Customer
    {
        $customer = null;
        if ($deliveryObject && is_object($deliveryObject)) {
            if ($deliveryObject instanceof Customer) {
                $customer = $deliveryObject;
            } elseif ($deliveryObject instanceof Campaign) {
                $customer = !empty($deliveryObject->list) && !empty($deliveryObject->list->customer) ? $deliveryObject->list->customer : null;
            } elseif ($deliveryObject instanceof Lists) {
                $customer = !empty($deliveryObject->customer) ? $deliveryObject->customer : null;
            } elseif ($deliveryObject instanceof CustomerEmailTemplate) {
                $customer = !empty($deliveryObject->customer) ? $deliveryObject->customer : null;
            } elseif ($deliveryObject instanceof TransactionalEmail && !empty($deliveryObject->customer_id)) {
                $customer = !empty($deliveryObject->customer) ? $deliveryObject->customer : null;
            }
        }
        if (!$customer && apps()->isAppName('customer') && app()->hasComponent('customer') && customer()->getId() > 0) {
            $customer = customer()->getModel();
        }
        return $customer;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateAdditionalHeaders(string $attribute, array $params = []): void
    {
        $headers = $this->$attribute;
        if (empty($headers) || !is_array($headers)) {
            $headers = [];
        }

        $this->$attribute   = [];
        $_headers           = [];

        $notAllowedHeaders  = (array)$this->getNeverAllowedHeaders();
        $notAllowedHeaders  = array_map('strtolower', $notAllowedHeaders);

        // try to be a bit restrictive
        $namePattern        = '/([a-z0-9\-\_])*/i';
        $valuePattern       = '/.*/i';

        foreach ($headers as $index => $header) {
            if (!is_array($header) || !isset($header['name'], $header['value'])) {
                unset($headers[$index]);
                continue;
            }

            $name   = preg_replace('/:\s/', '', trim((string)$header['name']));
            $value  = trim((string)$header['value']);

            if (empty($name) || in_array(strtolower((string)$name), $notAllowedHeaders) || !preg_match($namePattern, $name)) {
                unset($headers[$index]);
                continue;
            }

            if (empty($value) || !preg_match($valuePattern, $value)) {
                unset($headers[$index]);
                continue;
            }

            $_headers[] = ['name' => $name, 'value' => $value];
        }

        $this->$attribute = $_headers;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateFromEmail(string $attribute, array $params = []): void
    {
        if (empty($this->customer_id) || empty($this->from_email)) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('from_email', $this->from_email);
        $criteria->addNotInCondition('server_id', [(int)$this->server_id]);
        $criteria->addNotInCondition('customer_id', [(int)$this->customer_id]);
        $criteria->addNotInCondition('status', [DeliveryServer::STATUS_PENDING_DELETE]);
        $count = (int)self::model()->count($criteria);

        if ($count === 0) {
            return;
        }

        $this->addError($attribute, t('servers', 'This email address cannot be used.'));
    }

    /**
     * @param int $currentServerId
     * @param mixed $deliveryObject
     * @param array $params
     *
     * @return DeliveryServer|null
     * @throws CException
     */
    public static function pickServer(int $currentServerId = 0, $deliveryObject = null, array $params = []): ?self
    {
        // since 1.3.6.3
        if (!isset($params['excludeServers']) || !is_array($params['excludeServers'])) {
            $params['excludeServers'] = [];
        }

        if (!empty($currentServerId)) {
            $params['excludeServers'][] = $currentServerId;
        }

        $params['excludeServers'] = array_filter(array_unique(array_map('intval', $params['excludeServers'])));
        //

        // 1.4.2
        static $excludeServers = [];
        foreach ($params['excludeServers'] as $srvId) {
            $excludeServers[] = $srvId;
        }
        $excludeServers = array_filter(array_unique(array_map('intval', $excludeServers)));
        //

        if ($customer = self::parseDeliveryObjectForCustomer($deliveryObject)) {
            $checkQuota = (bool)($params['customerCheckQuota'] ?? true);
            if ($checkQuota && $customer->getIsOverQuota()) {

                // 1.4.2
                if (empty($params['__afterExcludeServers']) && count($excludeServers)) {
                    $excludeServers = [];
                    $params['excludeServers']        = [];
                    $params['__afterExcludeServers'] = true;
                    return self::pickServer((int)$currentServerId, $deliveryObject, $params);
                }
                //

                return null;
            }

            // load the servers for this customer only
            $serverIds = [];
            $criteria  = new CDbCriteria();
            $criteria->select = 't.server_id, t.monthly_quota, t.daily_quota, t.hourly_quota';
            $criteria->compare('t.customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('t.server_id', $excludeServers);
            $criteria->addInCondition('t.status', [self::STATUS_ACTIVE, self::STATUS_IN_USE]);
            $servers = self::model()->findAll($criteria);

            // remove the ones over quota
            foreach ($servers as $server) {
                if (!$server->getIsOverQuota()) {
                    $serverIds[] = (int)$server->server_id;
                }
            }

            // if we have any left, we pass them further
            if (!empty($serverIds)) {
                $criteria = new CDbCriteria();
                $criteria->addInCondition('t.server_id', $serverIds);

                $pickData = self::processPickServerCriteria($criteria, (int)$currentServerId, $deliveryObject, $params);
                if (!empty($pickData['server'])) {
                    return $pickData['server'];
                }
                if (!$pickData['continue']) {

                    // 1.4.2
                    if (empty($params['__afterExcludeServers']) && count($excludeServers)) {
                        $excludeServers = [];
                        $params['excludeServers']        = [];
                        $params['__afterExcludeServers'] = true;
                        return self::pickServer((int)$currentServerId, $deliveryObject, $params);
                    }
                    //

                    return null;
                }
            }
            //

            if (!empty($customer->group_id)) {

                // local cache
                static $groupServers = [];

                if (!isset($groupServers[$customer->group_id])) {
                    $groupServers[$customer->group_id] = [];
                    $criteria = new CDbCriteria();
                    $criteria->select = 't.server_id';
                    $criteria->compare('t.group_id', (int)$customer->group_id);
                    $criteria->addNotInCondition('t.server_id', $excludeServers);
                    $models = DeliveryServerToCustomerGroup::model()->findAll($criteria);
                    foreach ($models as $model) {
                        $groupServers[$customer->group_id][] = (int)$model->server_id;
                    }
                }

                if (!empty($groupServers[$customer->group_id])) {

                    // load the servers assigned to this group alone
                    $serverIds = [];
                    $servers   = self::model()->findAll([
                        'select'    => 't.server_id, t.monthly_quota, t.daily_quota, t.hourly_quota',
                        'condition' => 't.server_id IN(' . implode(', ', array_map('intval', $groupServers[$customer->group_id])) . ') AND 
                                        t.`status` IN("' . self::STATUS_ACTIVE . '", "' . self::STATUS_IN_USE . '") AND 
                                        t.customer_id IS NULL',
                    ]);

                    // remove the ones over quota
                    foreach ($servers as $server) {
                        if (!$server->getIsOverQuota()) {
                            $serverIds[] = (int)$server->server_id;
                        }
                    }

                    // use what is left, if any
                    if (!empty($serverIds)) {
                        $criteria = new CDbCriteria();
                        $criteria->addInCondition('t.server_id', $serverIds);

                        // since 1.8.4
                        // This flag should not allow campaigns to use other servers than the ones selected at setup time.
                        //
                        // This avoids a issue where you select a delivery server for a campaign
                        // and then if this server hits the quota and there are servers assigned to the customer group, those
                        // servers would be used as a fallback.
                        // This would trick the customer, campaign the server is selected for a good reason and we should respect that.
                        $params['customerGroupServerIds'] = $serverIds;

                        $pickData = self::processPickServerCriteria($criteria, (int)$currentServerId, $deliveryObject, $params);
                        if (!empty($pickData['server'])) {
                            return $pickData['server'];
                        }
                        if (!$pickData['continue']) {

                            // 1.4.2
                            if (empty($params['__afterExcludeServers']) && count($excludeServers)) {
                                $excludeServers = [];
                                $params['excludeServers']        = [];
                                $params['__afterExcludeServers'] = true;
                                return self::pickServer((int)$currentServerId, $deliveryObject, $params);
                            }
                            //

                            return null;
                        }
                    }
                }
            }

            if ($customer->getGroupOption('servers.can_send_from_system_servers', 'yes') != 'yes') {
                $excludeServers = []; // reset this
                return null;
            }
        }

        // load all system servers
        $serverIds = [];
        $criteria  = new CDbCriteria();
        $criteria->select = 't.server_id, t.monthly_quota, t.daily_quota, t.hourly_quota';
        $criteria->addCondition('t.customer_id IS NULL');
        $criteria->addInCondition('t.status', [self::STATUS_ACTIVE, self::STATUS_IN_USE]);
        $criteria->addNotInCondition('t.server_id', $excludeServers);
        $servers   = self::model()->findAll($criteria);

        // remove the ones over quota
        foreach ($servers as $server) {
            if (!$server->getIsOverQuota()) {
                $serverIds[] = (int)$server->server_id;
            }
        }

        // use what's left, if any
        if (!empty($serverIds)) {
            $criteria = new CDbCriteria();
            $criteria->addInCondition('t.server_id', $serverIds);

            $pickData = self::processPickServerCriteria($criteria, (int)$currentServerId, $deliveryObject, $params);
            if (!empty($pickData['server'])) {
                return $pickData['server'];
            }
            if (!$pickData['continue']) {

                // 1.4.2
                if (empty($params['__afterExcludeServers']) && count($excludeServers)) {
                    $excludeServers = [];
                    $params['excludeServers']        = [];
                    $params['__afterExcludeServers'] = true;
                    return self::pickServer((int)$currentServerId, $deliveryObject, $params);
                }
                //

                return null;
            }
        }
        //

        // 1.4.2
        if (empty($params['__afterExcludeServers']) && count($excludeServers)) {
            $excludeServers = [];
            $params['excludeServers']        = [];
            $params['__afterExcludeServers'] = true;
            return self::pickServer((int)$currentServerId, $deliveryObject, $params);
        }
        //

        return null;
    }

    /**
     * @param array $params
     * @return array
     */
    public function _handleTrackingDomain(array $params = []): array
    {
        $trackingDomainModel = null;
        if (!empty($params['trackingDomainModel'])) {
            $trackingDomainModel = $params['trackingDomainModel'];
        } elseif (!empty($this->tracking_domain_id) && !empty($this->trackingDomain) && $this->trackingDomain->getIsVerified()) {
            $params['trackingDomainModel'] = $trackingDomainModel = $this->trackingDomain;
        }

        if (empty($trackingDomainModel)) {
            return $params;
        }

        if (!empty($params['body']) || !empty($params['plainText'])) {
            /** @var OptionUrl $optionUrl */
            $optionUrl = container()->get(OptionUrl::class);

            $currentDomainName  = parse_url($optionUrl->getFrontendUrl(), PHP_URL_HOST);
            $trackingDomainName = strpos($trackingDomainModel->name, 'http') !== 0 ? 'http://' . $trackingDomainModel->name : $trackingDomainModel->name;
            $trackingDomainName = parse_url($trackingDomainName, PHP_URL_HOST);

            if (!empty($currentDomainName) && !empty($trackingDomainName)) {
                $searchReplace = [
                    'https://www.' . $currentDomainName => 'http://' . $trackingDomainName,
                    'http://www.' . $currentDomainName  => 'http://' . $trackingDomainName,
                    'https://' . $currentDomainName     => 'http://' . $trackingDomainName,
                    'http://' . $currentDomainName      => 'http://' . $trackingDomainName,
                ];

                // since 1.5.8
                if (!empty($trackingDomainModel->scheme) && $trackingDomainModel->scheme ==  TrackingDomain::SCHEME_HTTPS) {
                    foreach ($searchReplace as $key => $value) {
                        $searchReplace[$key] = (string)str_replace('http://', 'https://', $value);
                    }
                }

                // since 1.3.5.9
                if (stripos($trackingDomainName, $currentDomainName) === false) {
                    $searchReplace[$currentDomainName] = $trackingDomainName;
                }

                $searchFor   = array_keys($searchReplace);
                $replaceWith = array_values($searchReplace);

                $params['body']      = (string)str_replace($searchFor, $replaceWith, (string)($params['body'] ?? ''));
                $params['plainText'] = (string)str_replace($searchFor, $replaceWith, (string)($params['plainText'] ?? ''));

                if (!empty($params['headers']) && is_array($params['headers'])) {
                    foreach ($params['headers'] as $idx => $header) {
                        if (strpos($header['value'], $currentDomainName) !== false) {
                            $params['headers'][$idx]['value'] = (string)str_replace($searchFor, $replaceWith, $header['value']);
                        }
                    }
                }
                $params['trackingDomain'] = $trackingDomainName;
                $params['currentDomain']  = $currentDomainName;
            }
        }
        return $params;
    }

    /**
     * @return DeliveryServer|null
     * @throws CException
     */
    public function copy(): ?self
    {
        $copied = null;

        if ($this->getIsNewRecord()) {
            return null;
        }

        $transaction = db()->beginTransaction();

        try {
            $server = clone $this;
            $server->setIsNewRecord(true);
            $server->server_id    = null;
            $server->status       = $this->getIsActive() || $this->getIsInUse() ? self::STATUS_DISABLED : $this->status;
            $server->date_added   = MW_DATETIME_NOW;
            $server->last_updated = MW_DATETIME_NOW;

            if (!empty($server->name)) {
                if (preg_match('/\#(\d+)$/', $server->name, $matches)) {
                    $counter = (int)$matches[1];
                    $counter++;
                    $server->name = (string)preg_replace('/#(\d+)$/', '#' . $counter, $server->name);
                } else {
                    $server->name .= ' #1';
                }
            }

            if (!$server->save(false)) {
                throw new CException($server->shortErrors->getAllAsString());
            }

            if (!empty($this->domainPolicies)) {
                foreach ($this->domainPolicies as $policy) {
                    $policy = clone $policy;
                    $policy->setIsNewRecord(true);
                    $policy->domain_id = null;
                    $policy->server_id = (int)$server->server_id;
                    $policy->date_added   = MW_DATETIME_NOW;
                    $policy->last_updated = MW_DATETIME_NOW;
                    $policy->save(false);
                }
            }

            $transaction->commit();
            $copied = $server;
        } catch (Exception $e) {
            $transaction->rollback();
        }

        return $copied;
    }

    /**
     * @return bool
     */
    public function getIsDisabled(): bool
    {
        return $this->getStatusIs(self::STATUS_DISABLED);
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->getStatusIs(self::STATUS_ACTIVE);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function enable(): bool
    {
        if (!$this->getIsDisabled()) {
            return false;
        }

        $saved = $this->saveStatus(self::STATUS_ACTIVE);

        // since 2.1.10
        if ($saved) {
            $this->handleWarmupPlanScheduleLogs();
        }

        return $saved;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function disable(): bool
    {
        if (!$this->getIsActive()) {
            return false;
        }
        return $this->saveStatus(self::STATUS_DISABLED);
    }

    /**
     * @return array
     */
    public function getForceFromOptions(): array
    {
        return [
            self::FORCE_FROM_NEVER => ucfirst(t('servers', self::FORCE_FROM_NEVER)),
            self::FORCE_FROM_ALWAYS => ucfirst(t('servers', self::FORCE_FROM_ALWAYS)),
            self::FORCE_FROM_WHEN_NO_SIGNING_DOMAIN => ucfirst(t('servers', self::FORCE_FROM_WHEN_NO_SIGNING_DOMAIN)),
        ];
    }

    /**
     * @return array
     */
    public function getForceReplyToOptions(): array
    {
        return [
            self::FORCE_REPLY_TO_NEVER  => ucfirst(t('servers', self::FORCE_REPLY_TO_NEVER)),
            self::FORCE_REPLY_TO_ALWAYS => ucfirst(t('servers', self::FORCE_REPLY_TO_ALWAYS)),
        ];
    }

    /**
     * @return array
     */
    public function getUseForOptions(): array
    {
        return [
            self::USE_FOR_ALL           => ucfirst(t('servers', self::USE_FOR_ALL)),
            self::USE_FOR_CAMPAIGNS     => ucfirst(t('servers', self::USE_FOR_CAMPAIGNS)),
            self::USE_FOR_TRANSACTIONAL => ucfirst(t('servers', self::USE_FOR_TRANSACTIONAL . ' emails')),
            self::USE_FOR_EMAIL_TESTS   => t('servers', 'Email tests'),
            self::USE_FOR_REPORTS       => t('servers', 'Reports'),
            self::USE_FOR_LIST_EMAILS   => t('servers', 'List emails'),
            self::USE_FOR_INVOICES      => t('servers', 'Invoices'),
        ];
    }

    /**
     * @param string $for
     *
     * @return bool
     */
    public function getUseFor(string $for): bool
    {
        return in_array($this->use_for, [self::USE_FOR_ALL, $for]);
    }

    /**
     * @return bool
     */
    public function getUseForCampaigns(): bool
    {
        return $this->getUseFor(self::USE_FOR_CAMPAIGNS);
    }

    /**
     * @return bool
     */
    public function getUseForTransactional(): bool
    {
        return $this->getUseFor(self::USE_FOR_TRANSACTIONAL);
    }

    /**
     * @return bool
     */
    public function getUseForEmailTests(): bool
    {
        return $this->getUseFor(self::USE_FOR_EMAIL_TESTS);
    }

    /**
     * @return bool
     */
    public function getUseForReports(): bool
    {
        return $this->getUseFor(self::USE_FOR_REPORTS);
    }

    /**
     * @return bool
     */
    public function getUseForListEmails(): bool
    {
        return $this->getUseFor(self::USE_FOR_LIST_EMAILS);
    }

    /**
     * @return $this
     */
    public function enableLogUsage()
    {
        $this->_logUsage = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function disableLogUsage()
    {
        $this->_logUsage = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSigningEnabled(): bool
    {
        return (string)$this->signing_enabled === self::TEXT_YES &&
               in_array($this->type, $this->getSigningSupportedTypes());
    }

    /**
     * @return bool
     */
    public function getTrackingEnabled(): bool
    {
        return !empty($this->tracking_domain_id) && !empty($this->trackingDomain) && !empty($this->trackingDomain->name);
    }

    /**
     * @return array
     */
    public function getImportExportAllowedAttributes(): array
    {
        $allowedAttributes = [
            'type',
            'name',
            'hostname',
            'username',
            'password',
            'port',
            'protocol',
            'timeout',
            'from_email',
            'from_name',
            'reply_to_email',
            'hourly_quota',
            'daily_quota',
            'monthly_quota',
            'pause_after_send',
        ];
        return (array)hooks()->applyFilters('delivery_servers_get_import_export_allowed_attributes', $allowedAttributes);
    }

    /**
     * @return string
     */
    public function getDswhUrl(): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $url = $optionUrl->getFrontendUrl(sprintf('dswh/%d', $this->server_id));
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
    public function getCanEmbedImages(): bool
    {
        return false;
    }

    /**
     * @param array $fields
     *
     * @return array
     * @throws CException
     */
    public function getFormFieldsDefinition(array $fields = []): array
    {
        $form     = new CActiveForm();
        $defaults = [
            'name' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'name', $this->fieldDecorator->getHtmlOptions('name')),
            ],
            'hostname' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'hostname', $this->fieldDecorator->getHtmlOptions('hostname')),
            ],
            'username' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'username', $this->fieldDecorator->getHtmlOptions('username')),
            ],
            'password' => [
                'visible'   => true,
                'fieldHtml' => $form->passwordField($this, 'password', $this->fieldDecorator->getHtmlOptions('password')),
            ],
            'port' => [
                'visible'   => true,
                'fieldHtml' => $form->numberField($this, 'port', $this->fieldDecorator->getHtmlOptions('port')),
            ],
            'protocol' => [
                'visible'   => true,
                'fieldHtml' => $form->dropDownList($this, 'protocol', $this->getProtocolsArray(), $this->fieldDecorator->getHtmlOptions('protocol')),
            ],
            'timeout' => [
                'visible'   => true,
                'fieldHtml' => $form->numberField($this, 'timeout', $this->fieldDecorator->getHtmlOptions('timeout')),
            ],
            'from_email' => [
                'visible'   => true,
                'fieldHtml' => $form->emailField($this, 'from_email', $this->fieldDecorator->getHtmlOptions('from_email')),
            ],
            'from_name' => [
                'visible'   => true,
                'fieldHtml' => $form->textField($this, 'from_name', $this->fieldDecorator->getHtmlOptions('from_name')),
            ],
            'probability' => [
                'visible'   => true,
                'fieldHtml' => $form->dropDownList($this, 'probability', $this->getProbabilityArray(), $this->fieldDecorator->getHtmlOptions('probability')),
            ],
            'hourly_quota' => [
                'visible'   => true,
                'fieldHtml' => $form->numberField($this, 'hourly_quota', $this->fieldDecorator->getHtmlOptions('hourly_quota', [
                    'disabled' => !empty($this->warmup_plan_id) && !empty($this->warmupPlan) && !$this->warmupPlan->getIsDeliveryServerCompleted((int)$this->server_id),
                    'readonly' => !empty($this->warmup_plan_id) && !empty($this->warmupPlan) && !$this->warmupPlan->getIsDeliveryServerCompleted((int)$this->server_id),
                    'class'    => 'form-control has-help-text delivery-server-quota-form-field',
                ])),
            ],
            'daily_quota' => [
                'visible'   => true,
                'fieldHtml' => $form->numberField($this, 'daily_quota', $this->fieldDecorator->getHtmlOptions('daily_quota', [
                    'disabled' => !empty($this->warmup_plan_id) && !empty($this->warmupPlan) && !$this->warmupPlan->getIsDeliveryServerCompleted((int)$this->server_id),
                    'readonly' => !empty($this->warmup_plan_id) && !empty($this->warmupPlan) && !$this->warmupPlan->getIsDeliveryServerCompleted((int)$this->server_id),
                    'class'    => 'form-control has-help-text delivery-server-quota-form-field',
                ])),
            ],
            'monthly_quota' => [
                'visible'   => true,
                'fieldHtml' => $form->numberField($this, 'monthly_quota', $this->fieldDecorator->getHtmlOptions('monthly_quota', [
                    'disabled' => !empty($this->warmup_plan_id) && !empty($this->warmupPlan) && !$this->warmupPlan->getIsDeliveryServerCompleted((int)$this->server_id),
                    'readonly' => !empty($this->warmup_plan_id) && !empty($this->warmupPlan) && !$this->warmupPlan->getIsDeliveryServerCompleted((int)$this->server_id),
                    'class'    => 'form-control has-help-text delivery-server-quota-form-field',
                ])),
            ],
            'pause_after_send' => [
                'visible'   => true,
                'fieldHtml' => $form->numberField($this, 'pause_after_send', $this->fieldDecorator->getHtmlOptions('pause_after_send', [
                    'disabled' => !empty($this->warmup_plan_id) && !empty($this->warmupPlan) && !$this->warmupPlan->getIsDeliveryServerCompleted((int)$this->server_id),
                    'readonly' => !empty($this->warmup_plan_id) && !empty($this->warmupPlan) && !$this->warmupPlan->getIsDeliveryServerCompleted((int)$this->server_id),
                ])),
            ],
            'bounce_server_id' => [
                'visible'   => true,
                'fieldHtml' => $form->dropDownList($this, 'bounce_server_id', $this->getBounceServersArray(), $this->fieldDecorator->getHtmlOptions('bounce_server_id')),
            ],
            'tracking_domain_id'  => [
                'visible'   => true,
                'fieldHtml' => $form->dropDownList($this, 'tracking_domain_id', $this->getTrackingDomainsArray(), $this->fieldDecorator->getHtmlOptions('tracking_domain_id')),
            ],
            'use_for' => [
                'visible'   => true,
                'fieldHtml' => $form->dropDownList($this, 'use_for', $this->getUseForOptions(), $this->fieldDecorator->getHtmlOptions('use_for')),
            ],
            'signing_enabled' => [
                'visible'    => true,
                'fieldHtml'  => $form->dropDownList($this, 'signing_enabled', $this->getYesNoOptions(), $this->fieldDecorator->getHtmlOptions('signing_enabled')),
            ],
            'force_from' => [
                'visible'   => true,
                'fieldHtml' => $form->dropDownList($this, 'force_from', $this->getForceFromOptions(), $this->fieldDecorator->getHtmlOptions('force_from')),
            ],
            'force_sender' => [
                'visible'    => true,
                'fieldHtml'  => $form->dropDownList($this, 'force_sender', $this->getYesNoOptions(), $this->fieldDecorator->getHtmlOptions('force_sender')),
            ],
            'reply_to_email' => [
                'visible'    => true,
                'fieldHtml'  => $form->emailField($this, 'reply_to_email', $this->fieldDecorator->getHtmlOptions('reply_to_email')),
            ],
            'force_reply_to' => [
                'visible'   => true,
                'fieldHtml' => $form->dropDownList($this, 'force_reply_to', $this->getForceReplyToOptions(), $this->fieldDecorator->getHtmlOptions('force_reply_to')),
            ],
            'max_connection_messages' => [
                'visible'   => true,
                'fieldHtml' => $form->numberField($this, 'max_connection_messages', $this->fieldDecorator->getHtmlOptions('max_connection_messages')),
            ],
        ];

        foreach ($fields as $fieldName => $props) {
            if ((!is_array($props) || empty($props)) && array_key_exists($fieldName, $defaults)) {
                unset($defaults[$fieldName], $fields[$fieldName]);

                continue;
            }
        }

        $fields = (array)CMap::mergeArray($defaults, $fields);
        $fields = (array)hooks()->applyFilters('delivery_server_form_fields_definition', $fields, $this);

        foreach ($fields as $fieldName => $props) {
            if (!is_array($props) || empty($props) || empty($props['fieldHtml']) || empty($props['visible'])) {
                unset($fields[$fieldName]);
                continue;
            }
        }

        return $fields;
    }

    /**
     * @return string
     */
    public function getProviderUrl(): string
    {
        if (!app_param('delivery_servers.show_provider_url', true)) {
            return '';
        }

        $url = $this->_providerUrl;
        foreach (self::loadRemoteServers() as $server) {
            if ($server->type == $this->type) {
                if (!empty($server->provider_url)) {
                    $url = $server->provider_url;
                }
                break;
            }
        }

        $url = (string)hooks()->applyFilters('delivery_server_get_provider_url', $url, $this);

        return !empty($url) && FilterVarHelper::url($url) ? $url : '';
    }

    /**
     * @return bool
     */
    public function getIsRecommended(): bool
    {
        foreach (self::loadRemoteServers() as $server) {
            if ($server->type == $this->type) {
                return !empty($server->recommended);
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public static function loadRemoteServers(): array
    {
        static $servers;
        if (!empty($servers) && is_array($servers)) {
            return $servers;
        }

        $cacheTtl = 3600 * 24;
        $cacheKey = sha1(__METHOD__);
        if (($servers = cache()->get($cacheKey)) !== false) {
            return (array)$servers;
        }

        try {
            $response = (new GuzzleHttp\Client())->get('https://www.mailwizz.com/api/delivery-servers/index', [
                'timeout' => 10,
            ]);
        } catch (Exception $e) {
            $response = null;
        }

        if (empty($response) || (int)$response->getStatusCode() !== 200) {
            cache()->set($cacheKey, [], $cacheTtl);
            return [];
        }

        $servers = json_decode((string)$response->getBody());
        if (empty($servers) || !is_array($servers)) {
            cache()->set($cacheKey, [], $cacheTtl);
            return [];
        }
        cache()->set($cacheKey, (array)$servers, $cacheTtl);

        return (array)$servers;
    }

    /**
     * This is used so that we can enter the first log so that we don't have to wait for the hourly cron job to run
     *
     * @since 2.1.10
     * @return void
     * @throws CDbException
     */
    public function handleWarmupPlanScheduleLogs(): void
    {
        if (empty($this->warmup_plan_id) || !$this->getIsActive()) {
            return;
        }
        DeliveryServerWarmupPlanHelper::handleServerWarmupPlanScheduleLogs($this);
    }

    /**
     * @return void
     */
    protected function afterConstruct()
    {
        $this->additional_headers = $this->parseHeadersFormat((array)$this->modelMetaData->getModelMetaData()->itemAt('additional_headers'));
        $this->_deliveryFor       = self::DELIVERY_FOR_SYSTEM;
        $this->type               = $this->serverType;

        // since 1.3.6.3 default always
        $this->force_from = self::FORCE_FROM_ALWAYS;

        parent::afterConstruct();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->additional_headers = $this->parseHeadersFormat((array)$this->modelMetaData->getModelMetaData()->itemAt('additional_headers'));
        $this->_deliveryFor       = self::DELIVERY_FOR_SYSTEM;

        // since 1.5.0
        $this->_initHourlyQuota  = $this->hourly_quota;
        $this->_initDailyQuota   = $this->daily_quota;
        $this->_initMonthlyQuota = $this->monthly_quota;

        parent::afterFind();
    }

    /**
     * @return void
     */
    protected function afterValidate()
    {
        if (!$this->getIsNewRecord() && !is_cli()) {
            if (empty($this->customer_id)) {
                $this->locked = self::TEXT_NO;
            }

            $model = self::model()->findByPk((int)$this->server_id);
            $keys = ['hostname', 'username', 'password', 'port', 'protocol', 'from_email'];
            if (!empty($this->bounce_server_id)) {
                array_push($keys, 'bounce_server_id');
            }
            foreach ($keys as $key) {
                if ($model->$key !== $this->$key) {
                    $this->status = self::STATUS_INACTIVE;
                    break;
                }
            }
        }
        parent::afterValidate();
    }

    /**
     * @return bool
     * @throws CException
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('additional_headers', (array)$this->additional_headers);
        if (empty($this->type)) {
            $this->type = $this->serverType;
        }

        if (empty($this->use_for)) {
            $this->use_for = self::USE_FOR_ALL;
        }

        return parent::beforeSave();
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function afterSave()
    {
        // since 2.1.10
        $this->handleWarmupPlanScheduleLogs();

        // since 1.5.0
        if ((int)$this->hourly_quota != (int)$this->_initHourlyQuota) {
            cache()->delete(sha1(sprintf($this->_hourlyQuotaAccessKey, (int)$this->server_id)));
        }
        if ((int)$this->daily_quota != (int)$this->_initDailyQuota) {
            cache()->delete(sha1(sprintf($this->_dailyQuotaAccessKey, (int)$this->server_id)));
        }
        if ((int)$this->monthly_quota != (int)$this->_initMonthlyQuota) {
            cache()->delete(sha1(sprintf($this->_monthlyQuotaAccessKey, (int)$this->server_id)));
        }
        $this->_initHourlyQuota  = $this->hourly_quota;
        $this->_initDailyQuota   = $this->daily_quota;
        $this->_initMonthlyQuota = $this->monthly_quota;
        //

        parent::afterSave();
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function beforeDelete()
    {
        if (!$this->getCanBeDeleted()) {
            return false;
        }

        if (!$this->getIsPendingDelete()) {
            $this->saveStatus(self::STATUS_PENDING_DELETE);

            return false;
        }

        return parent::beforeDelete();
    }

    /**
     * @param CDbCriteria $criteria
     * @param int $currentServerId
     * @param mixed $deliveryObject
     * @param array $params
     *
     * @return array
     * @throws CException
     */
    protected static function processPickServerCriteria(CDbCriteria $criteria, int $currentServerId = 0, $deliveryObject = null, array $params = []): array
    {
        static $campaignServers = [];
        static $campaignHasAssignedServers = [];
        $campaign_id = !empty($deliveryObject) && $deliveryObject instanceof Campaign ? (int)$deliveryObject->campaign_id : 0;

        if ($campaign_id > 0 && !isset($campaignServers[$campaign_id])) {
            $campaignServers[$campaign_id] = [];
            $campaignHasAssignedServers[$campaign_id] = false;

            /** @var Customer $customer */
            $customer  = $deliveryObject->customer; // @phpstan-ignore-line
            $canSelect = $customer->getGroupOption('servers.can_select_delivery_servers_for_campaign', 'no') == 'yes';

            $_campaignServers = CampaignToDeliveryServer::model()->findAllByAttributes([
                'campaign_id' => $deliveryObject->campaign_id, // @phpstan-ignore-line
            ]);

            // 1.3.6.7
            $_serverIds = [];
            foreach ($_campaignServers as $mdl) {
                $_serverIds[] = (int)$mdl->server_id;
            }

            $_campaignServers = [];
            if (!empty($_serverIds)) {
                $_criteria = new CDbCriteria();
                $_criteria->select = 't.server_id, t.hourly_quota, t.daily_quota, t.monthly_quota';
                $_criteria->addInCondition('t.server_id', $_serverIds);
                $_criteria->addInCondition('t.status', [self::STATUS_ACTIVE, self::STATUS_IN_USE]);
                $_campaignServers = self::model()->findAll($_criteria);
                $campaignHasAssignedServers[$campaign_id] = !empty($_campaignServers);
            }
            //

            if ($canSelect) {
                foreach ($_campaignServers as $server) {
                    $checkQuota = is_array($params) && isset($params['serverCheckQuota']) ? $params['serverCheckQuota'] : true;
                    if ($checkQuota && !$server->getIsOverQuota()) {
                        $campaignServers[$campaign_id][] = (int)$server->server_id;
                    } elseif (!$checkQuota) {
                        $campaignServers[$campaign_id][] = (int)$server->server_id;
                    }
                }

                // if there are campaign servers specified but there are no valid servers, we stop!
                if ((is_countable($_campaignServers) ? count($_campaignServers) : 0) > 0 && empty($campaignServers[$campaign_id])) {
                    return ['server' => null, 'continue' => true];
                }
                unset($_campaignServers);
            }
        }

        $_criteria = new CDbCriteria();
        $_criteria->select = 't.server_id, t.type';
        if ($campaign_id > 0 && !empty($campaignHasAssignedServers[$campaign_id])) {
            // since 1.3.6.6
            if (empty($campaignServers[$campaign_id])) {
                $_criteria->compare('t.server_id', 0);
            } else {
                $_criteria->addInCondition('t.server_id', $campaignServers[$campaign_id]);
            }

            // since 1.8.4 - reset group servers if any
            if (!empty($params['customerGroupServerIds'])) {
                $criteria = new CDbCriteria();
            }
            //
        }
        $_criteria->addInCondition('t.status', [self::STATUS_ACTIVE, self::STATUS_IN_USE]);

        // since 1.3.5
        if (!empty($params['useFor']) && is_array($params['useFor']) && array_search(self::USE_FOR_ALL, $params['useFor']) === false) {
            $_criteria->addInCondition('t.use_for', array_merge([self::USE_FOR_ALL], $params['useFor']));
        }
        //

        $_criteria->order = 't.probability DESC';
        $_criteria->mergeWith($criteria);

        $_servers = self::model()->findAll($_criteria);
        if (empty($_servers)) {
            return ['server' => null, 'continue' => true];
        }

        $mapping = self::getTypesMapping();
        foreach ($_servers as $index => $srv) {
            if (!isset($mapping[$srv->type])) {
                unset($_servers[$index]);
                continue;
            }

            // since 1.3.6.2
            // this avoids issues when different configs from cli/web
            if ($failMessage = self::model($mapping[$srv->type])->requirementsFailedMessage()) {
                Yii::log((string)$failMessage, CLogger::LEVEL_ERROR);
                unset($_servers[$index]);
                continue;
            }

            $_servers[$index] = self::model($mapping[$srv->type])->findByPk($srv->server_id);
        }

        if (empty($_servers)) {
            return ['server' => null, 'continue' => true];
        }

        // 1.4.4
        // reset the indexes
        $_servers = array_values($_servers);

        $probabilities  = [];
        foreach ($_servers as $srv) {
            if (!isset($probabilities[$srv->probability])) {
                $probabilities[$srv->probability] = [];
            }
            $probabilities[$srv->probability][] = $srv;
        }

        $server                 = null;
        $probabilitySum         = array_sum(array_keys($probabilities));
        $probabilityPercentage  = [];
        $cumulative             = [];

        foreach ($probabilities as $probability => $probabilityServers) {
            $probabilityPercentage[$probability] = ((int)$probability / (int)$probabilitySum) * 100;
        }
        asort($probabilityPercentage);

        foreach ($probabilityPercentage as $probability => $percentage) {
            $cumulative[$probability] = end($cumulative) + $percentage;
        }
        asort($cumulative);

        $lowest      = floor((float)current($cumulative));
        $probability = rand((int)$lowest, 100);

        foreach ($cumulative as $key => $value) {
            if ($value > $probability) {
                $_keys  = array_keys($probabilities[$key]);
                shuffle($_keys);
                $rand   = $_keys[0];
                $server = $probabilities[$key][$rand];
                break;
            }
        }

        if (empty($server)) {
            $_keys  = array_keys($_servers);
            shuffle($_keys);
            $rand   = $_keys[0];
            $server = $_servers[$rand];
        }

        if (count($_servers) > 1 && $currentServerId > 0 && $server->server_id == $currentServerId) {
            return self::processPickServerCriteria($criteria, (int)$server->server_id, $deliveryObject, $params);
        }

        $server->getMailer()->reset();

        if (empty($deliveryObject)) {
            $server->setDeliveryFor(self::DELIVERY_FOR_SYSTEM);
        } elseif ($deliveryObject instanceof Campaign) {
            $server->setDeliveryFor(self::DELIVERY_FOR_CAMPAIGN);
        } elseif ($deliveryObject instanceof Lists) {
            $server->setDeliveryFor(self::DELIVERY_FOR_LIST);
        } elseif ($deliveryObject instanceof CustomerEmailTemplate) {
            $server->setDeliveryFor(self::DELIVERY_FOR_TEMPLATE_TEST);
        }

        return ['server' => $server, 'continue' => true];
    }
}
