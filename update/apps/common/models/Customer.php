<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Customer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "customer".
 *
 * The followings are the available columns in table 'customer':
 * @property integer $customer_id
 * @property string $customer_uid
 * @property integer|string $parent_id
 * @property integer $group_id
 * @property integer $language_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $timezone
 * @property string $avatar
 * @property string $removable
 * @property string $confirmation_key
 * @property integer $oauth_uid
 * @property string $oauth_provider
 * @property string $status
 * @property string|null $birth_date
 * @property string $phone
 * @property string $twofa_enabled
 * @property string $twofa_secret
 * @property integer $twofa_timestamp
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 * @property string $last_login
 * @property string|null $inactive_at
 *
 * The followings are the available model relations:
 * @property BounceServer[] $bounceServers
 * @property Campaign[] $campaigns
 * @property CustomerCampaignTag[] $campaignTags
 * @property CustomerMessage[] $messages
 * @property CampaignGroup[] $campaignGroups
 * @property CampaignSendGroup[] $campaignSendGroups
 * @property CustomerGroup $group
 * @property CustomerApiKey[] $apiKeys
 * @property CustomerCompany $company
 * @property CustomerAutoLoginToken[] $autoLoginTokens
 * @property CustomerEmailTemplate[] $emailTemplates
 * @property CustomerEmailTemplateCategory[] $emailTemplateCategories
 * @property CustomerActionLog[] $actionLogs
 * @property CustomerQuotaMark[] $quotaMarks
 * @property DeliveryServer[] $deliveryServers
 * @property DeliveryServerWarmupPlan[] $warmupPlans
 * @property FeedbackLoopServer[] $fblServers
 * @property Language $language
 * @property Customer $parent
 * @property Customer[] $customers
 * @property DeliveryServerUsageLog[] $usageLogs
 * @property Lists[] $lists
 * @property PricePlanOrder[] $pricePlanOrders
 * @property PricePlanOrderNote[] $pricePlanOrderNotes
 * @property TrackingDomain[] $trackingDomains
 * @property SendingDomain[] $sendingDomains
 * @property TransactionalEmail[] $transactionalEmails
 * @property CustomerEmailBlacklist[] $blacklistedEmails
 * @property CustomerSuppressionList[] $suppressionLists
 *
 * @property CustomerActionLogBehavior $logAction
 * @property CustomerActionLogBehavior $__logAction
 */
class Customer extends ActiveRecord
{
    /**
     * Statuses list
     */
    const STATUS_PENDING_CONFIRM = 'pending-confirm';
    const STATUS_PENDING_ACTIVE = 'pending-active';
    const STATUS_PENDING_DELETE = 'pending-delete';
    const STATUS_PENDING_DISABLE = 'pending-disable';
    const STATUS_DISABLED = 'disabled';

    /**
     * @var string
     */
    public $fake_password;

    /**
     * @var string
     */
    public $confirm_password;

    /**
     * @var string
     */
    public $confirm_email;

    /**
     * @var string
     */
    public $tc_agree;

    /**
     * @var string
     */
    public $newsletter_consent;

    /**
     * @var int
     */
    public $sending_quota_usage;

    /**
     * @var string
     */
    public $company_name;

    /**
     * @var string
     */
    public $new_avatar;

    /**
     * @var string
     */
    public $countUsageFromQuotaMarkCachePattern = 'Customer::countUsageFromQuotaMark:cid:%d:date_added:%s';

    /**
     * @var string
     */
    public $countHourlyUsageCachePattern = 'Customer::countHourlyUsage:cid:%d:date_added:%s:hourly_quota:%d';

    /**
     * @var string
     */
    public $email_details = 'no';

    /**
     * @var CustomerQuotaMark
     */
    protected $_lastQuotaMark;

    /**
     * @var int
     */
    protected $_lastQuotaCheckTime = 0;

    /**
     * @var int
     */
    protected $_lastQuotaCheckTimeDiff = 30;

    /**
     * @var int
     */
    protected $_lastQuotaCheckMaxDiffCounter = 500;

    /**
     * @var bool
     */
    protected $_lastQuotaCheckTimeOverQuota = false;

    /**
     * @var string
     */
    protected $birthDateInit;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $avatarMimes = null;
        if (CommonHelper::functionExists('finfo_open')) {

            /** @var FileExtensionMimes $extensionMimes */
            $extensionMimes = app()->getComponent('extensionMimes');

            /** @var array $avatarMimes */
            $avatarMimes = $extensionMimes->get(['png', 'jpg', 'jpeg', 'gif'])->toArray();
        }

        $rules = [
            // when new customer is created by a user.
            ['first_name, last_name, email, confirm_email, fake_password, confirm_password, timezone, birthDate, status', 'required', 'on' => 'insert'],

            // when new subaccount is created by a customer.
            ['first_name, last_name, email, confirm_email, fake_password, confirm_password, timezone, birthDate, status', 'required', 'on' => 'insert-subaccount'],

            // when a customer is updated by a user
            ['first_name, last_name, email, confirm_email, timezone, birthDate, status', 'required', 'on' => 'update'],

            // when a subaccount is updated by a customer
            ['first_name, last_name, email, confirm_email, timezone, birthDate, status', 'required', 'on' => 'update-subaccount'],

            // when a customer updates his profile
            ['first_name, last_name, email, confirm_email, timezone, birthDate', 'required', 'on' => 'update-profile'],

            // when a customer registers
            ['first_name, last_name, email, confirm_email, fake_password, confirm_password, timezone, birthDate, tc_agree', 'required', 'on' => 'register'],

            ['group_id', 'numerical', 'integerOnly' => true],
            ['group_id', 'exist', 'className' => CustomerGroup::class],
            ['language_id', 'numerical', 'integerOnly' => true],
            ['language_id', 'exist', 'className' => Language::class],
            ['parent_id', 'numerical', 'integerOnly' => true],
            ['parent_id', 'exist', 'className' => Customer::class, 'attributeName' => 'customer_id'],
            ['parent_id', '_validateParentId'],
            ['first_name, last_name', 'length', 'min' => 1, 'max' => 100],
            ['email, confirm_email', 'length', 'min' => 4, 'max' => 100],
            ['email, confirm_email', 'email', 'validateIDN' => true],
            ['timezone', 'in', 'range' => array_keys(DateTimeHelper::getTimeZones())],
            ['fake_password, confirm_password', 'length', 'min' => 6, 'max' => 100],
            ['confirm_password', 'compare', 'compareAttribute' => 'fake_password'],
            ['confirm_email', 'compare', 'compareAttribute' => 'email'],
            ['email', 'unique'],
            ['email_details', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['birthDate', 'type', 'dateFormat' => 'yyyy-MM-dd'],
            ['birthDate', '_validateMinimumAge'],
            ['phone', 'length', 'max' => 32],
            ['phone', 'match', 'pattern' => '/[0-9\s\-]+/'],
            ['inactiveAt', 'date', 'format' => 'yyyy-mm-dd hh:mm:ss'],
            ['inactiveAt', '_validateInactiveAt'],
            ['status', 'in', 'range' => array_keys($this->getStatusesArray())],
            ['status', 'in', 'range' => array_keys($this->getSubaccountsStatusesArray()), 'on' => 'insert-subaccount, update-subaccount'],

            // avatar
            ['new_avatar', 'file', 'types' => ['png', 'jpg', 'jpeg', 'gif'], 'mimeTypes' => $avatarMimes, 'allowEmpty' => true],

            // unsafe
            ['group_id, parent_id, status, email_details, inactiveAt', 'unsafe', 'on' => 'update-profile, register'],
            ['group_id, parent_id', 'unsafe', 'on' => 'insert-subaccount, update-subaccount'],

            // mark them as safe for search
            ['customer_uid, first_name, last_name, email, group_id, parent_id, status, company_name', 'safe', 'on' => 'search'],
            ['customer_uid, first_name, last_name, email, status', 'safe', 'on' => 'search-subaccounts'],

            ['newsletter_consent', 'safe'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'bounceServers'             => [self::HAS_MANY, BounceServer::class, 'customer_id'],
            'campaigns'                 => [self::HAS_MANY, Campaign::class, 'customer_id'],
            'campaignGroups'            => [self::HAS_MANY, CampaignGroup::class, 'customer_id'],
            'campaignSendGroups'        => [self::HAS_MANY, CampaignSendGroup::class, 'customer_id'],
            'campaignTags'              => [self::HAS_MANY, CustomerCampaignTag::class, 'customer_id'],
            'messages'                  => [self::HAS_MANY, CustomerMessage::class, 'customer_id'],
            'group'                     => [self::BELONGS_TO, CustomerGroup::class, 'group_id'],
            'apiKeys'                   => [self::HAS_MANY, CustomerApiKey::class, 'customer_id'],
            'company'                   => [self::HAS_ONE, CustomerCompany::class, 'customer_id'],
            'autoLoginTokens'           => [self::HAS_MANY, CustomerAutoLoginToken::class, 'customer_id'],
            'emailTemplates'            => [self::HAS_MANY, CustomerEmailTemplate::class, 'customer_id'],
            'emailTemplateCategories'   => [self::HAS_MANY, CustomerEmailTemplateCategory::class, 'customer_id'],
            'actionLogs'                => [self::HAS_MANY, CustomerActionLog::class, 'customer_id'],
            'quotaMarks'                => [self::HAS_MANY, CustomerQuotaMark::class, 'customer_id'],
            'deliveryServers'           => [self::HAS_MANY, DeliveryServer::class, 'customer_id'],
            'warmupPlans'               => [self::HAS_MANY, DeliveryServerWarmupPlan::class, 'customer_id'],
            'fblServers'                => [self::HAS_MANY, FeedbackLoopServer::class, 'customer_id'],
            'language'                  => [self::BELONGS_TO, Language::class, 'language_id'],
            'parent'                    => [self::BELONGS_TO, Customer::class, 'parent_id'],
            'customers'                 => [self::HAS_MANY, Customer::class, 'parent_id'],
            'usageLogs'                 => [self::HAS_MANY, DeliveryServerUsageLog::class, 'customer_id'],
            'lists'                     => [self::HAS_MANY, Lists::class, 'customer_id'],
            'pricePlanOrders'           => [self::HAS_MANY, PricePlanOrder::class, 'customer_id'],
            'pricePlanOrderNotes'       => [self::HAS_MANY, PricePlanOrderNote::class, 'customer_id'],
            'trackingDomains'           => [self::HAS_MANY, TrackingDomain::class, 'customer_id'],
            'sendingDomains'            => [self::HAS_MANY, SendingDomain::class, 'customer_id'],
            'transactionalEmails'       => [self::HAS_MANY, TransactionalEmail::class, 'customer_id'],
            'blacklistedEmails'         => [self::HAS_MANY, CustomerEmailBlacklist::class, 'customer_id'],
            'suppressionLists'          => [self::HAS_MANY, CustomerSuppressionList::class, 'customer_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'customer_id'   => $this->t('ID'),
            'customer_uid'  => $this->t('Unique ID'),
            'parent_id'     => $this->t('Parent account'),
            'group_id'      => $this->t('Group'),
            'language_id'   => $this->t('Language'),
            'first_name'    => $this->t('First name'),
            'last_name'     => $this->t('Last name'),
            'email'         => $this->t('Email'),
            'password'      => $this->t('Password'),
            'timezone'      => $this->t('Timezone'),
            'avatar'        => $this->t('Avatar'),
            'new_avatar'    => $this->t('New avatar'),
            'removable'     => $this->t('Removable'),

            'confirm_email'         => $this->t('Confirm email'),
            'fake_password'         => $this->t('Password'),
            'confirm_password'      => $this->t('Confirm password'),
            'tc_agree'              => $this->t('Terms and conditions'),
            'sending_quota_usage'   => $this->t('Sending quota usage'),
            'company_name'          => $this->t('Company'),

            'email_details'         => $this->t('Send details via email'),
            'birth_date'            => $this->t('Birth date'),
            'birthDate'             => $this->t('Birth date'),
            'phone'                 => $this->t('Phone'),
            'inactive_at'           => $this->t('Inactivate at'),
            'inactiveAt'            => $this->t('Inactivate at'),

            'newsletter_consent' => t('settings', 'Newsletter'),

            'twofa_enabled' => $this->t('2FA enabled'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'twofa_enabled' => $this->t('Please make sure you scan the QR code in your authenticator application before enabling this feature, otherwise you will be locked out from your account'),
            'inactive_at'   => $this->t('Leave it empty for no future inactivation'),
            'inactiveAt'    => $this->t('Leave it empty for no future inactivation'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        $criteria->compare('t.customer_uid', $this->customer_uid, true);
        $criteria->compare('t.first_name', $this->first_name, true);
        $criteria->compare('t.last_name', $this->last_name, true);
        $criteria->compare('t.email', $this->email, true);
        $criteria->compare('t.group_id', $this->group_id);
        $criteria->compare('t.status', $this->status);

        if (!empty($this->parent_id)) {
            $parentId = (string)$this->parent_id;
            if (is_numeric($parentId)) {
                $criteria->compare('t.parent_id', $parentId);
            } else {
                $criteria->with['parent'] = [
                    'condition' => 'parent.email LIKE :name OR parent.first_name LIKE :name OR parent.last_name LIKE :name',
                    'params'    => [':name' => '%' . $parentId . '%'],
                ];
            }
        }

        if ($this->company_name) {
            $criteria->with['company'] = [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ];
            $criteria->compare('company.name', $this->company_name, true);
        }

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort' => [
                'defaultOrder' => 't.customer_id DESC',
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Customer the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Customer $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getTranslationCategory(): string
    {
        return 'customers';
    }

    /**
     * @return bool
     */
    public function getIsRemovable(): bool
    {
        if ((string)$this->removable !== self::TEXT_YES) {
            return false;
        }

        if ($this->getStatusIs(self::STATUS_PENDING_DELETE)) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        // @phpstan-ignore-next-line
        return collect([$this->first_name, $this->last_name])
            ->filter()
            ->whenEmpty(function (Illuminate\Support\Collection $collection) {
                return $collection->push((string)$this->email);
            })
            ->implode(' ');
    }

    /**
     * @return string|null
     */
    public function getInactiveAt()
    {
        if (empty($this->inactive_at)) {
            return null;
        }
        return $this->inactive_at;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    public function setInactiveAt(string $value)
    {
        if (empty($value)) {
            $this->inactive_at = null;
            return;
        }
        $this->inactive_at = date('Y-m-d H:i:s', (int)strtotime($value));
    }

    /**
     * @return array
     */
    public function getStatusesArray(): array
    {
        return [
            self::STATUS_ACTIVE          => t('app', 'Active'),
            self::STATUS_INACTIVE        => t('app', 'Inactive'),
            self::STATUS_PENDING_CONFIRM => t('app', 'Pending confirm'),
            self::STATUS_PENDING_ACTIVE  => t('app', 'Pending active'),
            self::STATUS_PENDING_DELETE  => t('app', 'Pending delete'),
            self::STATUS_PENDING_DISABLE => t('app', 'Pending disable'),
            self::STATUS_DISABLED        => t('app', 'Disabled'),
        ];
    }

    /**
     * @return array
     */
    public function getSubaccountsStatusesArray(): array
    {
        return [
            self::STATUS_ACTIVE     => t('app', 'Active'),
            self::STATUS_INACTIVE   => t('app', 'Inactive'),
            self::STATUS_DISABLED   => t('app', 'Disabled'),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTimeZonesArray(): array
    {
        return DateTimeHelper::getTimeZones();
    }

    /**
     * @param string $customer_uid
     *
     * @return Customer|null
     */
    public function findByUid(string $customer_uid): ?self
    {
        return self::model()->findByAttributes([
            'customer_uid' => $customer_uid,
        ]);
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->customer_uid;
    }

    /**
     * For compatibility with the Customer component
     *
     * @return int
     */
    public function getId(): int
    {
        return (int)$this->customer_id;
    }

    /**
     * @return array
     */
    public function getAvailableDeliveryServers(): array
    {
        static $deliveryServers;
        if ($deliveryServers !== null) {
            return $deliveryServers;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'server_id, hostname, name';
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addInCondition('status', [DeliveryServer::STATUS_ACTIVE, DeliveryServer::STATUS_IN_USE]);
        // since 1.3.5
        $criteria->addInCondition('use_for', [DeliveryServer::USE_FOR_ALL, DeliveryServer::USE_FOR_CAMPAIGNS]);

        //
        $deliveryServers = DeliveryServer::model()->findAll($criteria);

        // merge with existing customer servers, but avoid duplicates
        if (!empty($this->group_id)) {

            // 1.5.5
            $deliveryServersIds = [];
            if (!empty($deliveryServers)) {
                foreach ($deliveryServers as $deliveryServer) {
                    $deliveryServersIds[] = (int)$deliveryServer->server_id;
                }
            }

            // 1.5.5
            $criteria = new CDbCriteria();
            $criteria->compare('group_id', (int)$this->group_id);
            if (!empty($deliveryServersIds)) {
                $criteria->addNotInCondition('server_id', $deliveryServersIds);
            }

            $groupServerIds = [];
            $groupServers   = DeliveryServerToCustomerGroup::model()->findAll($criteria);
            foreach ($groupServers as $group) {
                $groupServerIds[] = (int)$group->server_id;
            }

            if (!empty($groupServerIds)) {
                $criteria = new CDbCriteria();
                $criteria->select = 'server_id, hostname, name';
                $criteria->addInCondition('server_id', $groupServerIds);
                $criteria->addCondition('customer_id IS NULL');
                $criteria->addInCondition('status', [DeliveryServer::STATUS_ACTIVE, DeliveryServer::STATUS_IN_USE]);

                // since 1.3.5
                $criteria->addInCondition('use_for', [DeliveryServer::USE_FOR_ALL, DeliveryServer::USE_FOR_CAMPAIGNS]);

                //
                $models = DeliveryServer::model()->findAll($criteria);

                // since 1.5.5
                if (!empty($models)) {
                    foreach ($models as $model) {
                        $deliveryServers[] = $model;
                    }
                }
            }
        }

        if (empty($deliveryServers) && $this->getGroupOption('servers.can_send_from_system_servers', 'yes') == 'yes') {
            $criteria = new CDbCriteria();
            $criteria->select = 'server_id, hostname, name';
            $criteria->addCondition('customer_id IS NULL');
            $criteria->addInCondition('status', [DeliveryServer::STATUS_ACTIVE, DeliveryServer::STATUS_IN_USE]);
            // since 1.3.5
            $criteria->addInCondition('use_for', [DeliveryServer::USE_FOR_ALL, DeliveryServer::USE_FOR_CAMPAIGNS]);
            //
            $deliveryServers = DeliveryServer::model()->findAll($criteria);
        }

        return $deliveryServers;
    }

    /**
     * @return int
     */
    public function getHourlyQuota(): int
    {
        static $cache = [];
        if (isset($cache[$this->customer_id])) {
            return (int)$cache[$this->customer_id];
        }
        return $cache[$this->customer_id] = (int)$this->getGroupOption('sending.hourly_quota', 0);
    }

    /**
     * @return bool
     */
    public function getCanHaveHourlyQuota(): bool
    {
        return $this->getHourlyQuota() > 0;
    }

    /**
     * @return int
     */
    public function countHourlyUsage(): int
    {
        if (!$this->getCanHaveHourlyQuota()) {
            return 0;
        }

        $dateAdded = date('Y-m-d H:00:00');
        $cacheKey  = sha1(sprintf($this->countHourlyUsageCachePattern, (int)$this->customer_id, (string)$dateAdded, (int)$this->getHourlyQuota()));

        if (!mutex()->acquire($cacheKey, 60)) {
            return PHP_INT_MAX;
        }

        if (($count = cache()->get($cacheKey)) !== false) {
            mutex()->release($cacheKey);
            return (int)$count;
        }

        $count = PHP_INT_MAX;
        try {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$this->customer_id);
            $criteria->compare('customer_countable', self::TEXT_YES);
            $criteria->addCondition('`date_added` >= :startDateTime');
            $criteria->params[':startDateTime'] = $dateAdded;
            $count = (int)DeliveryServerUsageLog::model()->count($criteria);
        } catch (Exception $e) {
        }

        cache()->set($cacheKey, $count);
        mutex()->release($cacheKey);

        return (int)$count;
    }

    /**
     * @return int
     */
    public function getHourlyQuotaLeft(): int
    {
        if (!$this->getCanHaveHourlyQuota()) {
            return PHP_INT_MAX;
        }

        $maxHourlyQuota = $this->getHourlyQuota();
        $hourlyUsage    = (int)$this->countHourlyUsage();
        $hourlyLeft     = $maxHourlyQuota - $hourlyUsage;

        return $hourlyLeft < 0 ? 0 : $hourlyLeft;
    }

    /**
     * @param int $by
     */
    public function increaseHourlyUsageCached(int $by = 1): void
    {
        if (!$this->getCanHaveHourlyQuota()) {
            return;
        }

        $dateAdded = date('Y-m-d H:00:00');
        $cacheKey  = sha1(sprintf($this->countHourlyUsageCachePattern, (int)$this->customer_id, (string)$dateAdded, (int)$this->getHourlyQuota()));

        if (!mutex()->acquire($cacheKey, 60)) {
            return;
        }

        $count  = (int)cache()->get($cacheKey);
        $count += (int)$by;

        cache()->set($cacheKey, $count);
        mutex()->release($cacheKey);
    }

    /**
     * @return string
     */
    public function getSendingQuotaUsageDisplay(): string
    {
        $_allowed  = (int)$this->getGroupOption('sending.quota', -1);
        $_count    = (int)$this->countUsageFromQuotaMark();
        $allowed   = !$_allowed ? 0 : ($_allowed == -1 ? '&infin;' : formatter()->formatNumber($_allowed));
        $count     = formatter()->formatNumber($_count);
        $percent   = ($_allowed < 1 ? 0 : ($_count > $_allowed ? 100 : round(($_count / $_allowed) * 100, 2)));

        return sprintf('%s (%s/%s)', $percent . '%', $count, $allowed);
    }

    /**
     * @return void
     */
    public function resetSendingQuota(): void
    {
        // 1.3.7.3
        $this->removeOption('sending_quota.last_notification');
        CustomerQuotaMark::model()->deleteAllByAttributes(['customer_id' => (int)$this->customer_id]);

        // reset the hourly quota, if any
        $dateAdded = date('Y-m-d H:00:00');
        $cacheKey  = sha1(sprintf($this->countHourlyUsageCachePattern, (int)$this->customer_id, (string)$dateAdded, (int)$this->getHourlyQuota()));
        if (mutex()->acquire($cacheKey, 60)) {
            cache()->set($cacheKey, 0);
            mutex()->release($cacheKey);
        }
        //
    }

    /**
     * @return bool
     */
    public function getIsOverQuota(): bool
    {
        if ($this->getIsNewRecord()) {
            return false;
        }

        // since 1.3.5.5
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_CUSTOMER_QUOTA_CHECK') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_CUSTOMER_QUOTA_CHECK
        ) {
            return false;
        }

        // since 1.3.9.7 - max number of emails customer is able to send in one hour
        if ($this->getCanHaveHourlyQuota() && !$this->getHourlyQuotaLeft()) {
            return true;
        }

        $timeNow = time();
        if ($this->_lastQuotaCheckTime > 0 && ($this->_lastQuotaCheckTime + $this->_lastQuotaCheckTimeDiff) > $timeNow) {
            return $this->_lastQuotaCheckTimeOverQuota;
        }
        $this->_lastQuotaCheckTime = $timeNow;

        $quota     = (int)$this->getGroupOption('sending.quota', -1);
        $timeValue = (int)$this->getGroupOption('sending.quota_time_value', -1);

        if ($quota == 0 || $timeValue == 0) {
            $this->_lastQuotaCheckTime += $timeNow;
            return $this->_lastQuotaCheckTimeOverQuota = true;
        }

        if ($quota == -1 && $timeValue == -1) {
            $this->_lastQuotaCheckTime += $timeNow;
            return $this->_lastQuotaCheckTimeOverQuota = false;
        }

        $timestamp = 0;
        if ($timeValue > 0) {
            $timeUnit  = (string)$this->getGroupOption('sending.quota_time_unit', 'month');
            $seconds   = (int)strtotime(sprintf('+ %d %s', $timeValue, ($timeValue == 1 ? $timeUnit : $timeUnit . 's')), $timeNow) - $timeNow;
            $quotaMark = $this->getLastQuotaMark();
            $timestamp = (int)strtotime((string)$quotaMark->date_added) + $seconds;

            if ($timeNow >= $timestamp) {
                $bmStart = microtime(true);
                $this->_takeQuotaAction();
                $bmEnd = microtime(true);

                try {
                    $this->logTakeQuotaAction([
                        'line'          => __LINE__,
                        'quota'         => $quota,
                        'timeNow'       => $timeNow,
                        'duration'      => $bmEnd - $bmStart,
                        'timeValue'     => $timeValue,
                        'timeUnit'      => $timeUnit,
                        'seconds'       => $seconds,
                        'timestamp'     => $timestamp,
                        'quota_mark'    => $quotaMark->date_added,
                    ]);
                } catch (Exception $e) {
                }

                // SINCE 1.3.5.9
                if ($this->getGroupOption('sending.action_quota_reached') == 'reset') {
                    return $this->_lastQuotaCheckTimeOverQuota = false;
                }
                //
                return $this->_lastQuotaCheckTimeOverQuota = true; // keep an eye on it
            }
        }

        if ($quota == -1) {
            $this->_lastQuotaCheckTime += $timeNow;
            return $this->_lastQuotaCheckTimeOverQuota = false;
        }

        $currentUsage = $this->countUsageFromQuotaMark();

        if ($currentUsage >= $quota) {
            // force waiting till end of ts
            if ($this->getGroupOption('sending.quota_wait_expire', 'yes') == 'yes' && $timeNow <= $timestamp) {
                $this->_lastQuotaCheckTime += $timeNow;
                return $this->_lastQuotaCheckTimeOverQuota = true;
            }

            $bmStart = microtime(true);
            $this->_takeQuotaAction();
            $bmEnd = microtime(true);

            try {
                $this->logTakeQuotaAction([
                    'line'          => __LINE__,
                    'quota'         => $quota,
                    'timeNow'       => $timeNow,
                    'duration'      => $bmEnd - $bmStart,
                    'timeValue'     => $timeValue,
                    'timestamp'     => $timestamp,
                    'currentUsage'  => $currentUsage,
                ]);
            } catch (Exception $e) {
            }

            return $this->_lastQuotaCheckTimeOverQuota = true;
        }

        if (($quota - $currentUsage) > $this->_lastQuotaCheckMaxDiffCounter) {
            $this->_lastQuotaCheckTime += $timeNow;
            return $this->_lastQuotaCheckTimeOverQuota = false;
        }

        return $this->_lastQuotaCheckTimeOverQuota = false;
    }

    /**
     * @return int
     */
    public function countUsageFromQuotaMark(): int
    {
        $quotaMark = $this->getLastQuotaMark();
        $cacheKey  = sha1(sprintf($this->countUsageFromQuotaMarkCachePattern, (int)$this->customer_id, (string)$quotaMark->date_added));

        if (!mutex()->acquire($cacheKey, 60)) {
            return 0;
        }

        if (($count = cache()->get($cacheKey)) !== false) {
            mutex()->release($cacheKey);
            return (int)$count;
        }

        $count = 0;

        try {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$this->customer_id);
            $criteria->compare('customer_countable', self::TEXT_YES);
            $criteria->addCondition('`date_added` >= :startDateTime');
            $criteria->params[':startDateTime'] = $quotaMark->date_added;

            $count = DeliveryServerUsageLog::model()->count($criteria);
        } catch (Exception $e) {
        }

        cache()->set($cacheKey, $count);
        mutex()->release($cacheKey);

        return (int)$count;
    }

    /**
     * @param int $by
     */
    public function increaseLastQuotaMarkCachedUsage(int $by = 1): void
    {
        $quotaMark = $this->getLastQuotaMark();
        $cacheKey  = sha1(sprintf($this->countUsageFromQuotaMarkCachePattern, (int)$this->customer_id, (string)$quotaMark->date_added));

        if (!mutex()->acquire($cacheKey, 60)) {
            return;
        }

        $count  = (int)cache()->get($cacheKey);
        $count += (int)$by;

        cache()->set($cacheKey, $count);
        mutex()->release($cacheKey);
    }

    /**
     * @return CustomerQuotaMark
     */
    public function getLastQuotaMark(): CustomerQuotaMark
    {
        if ($this->_lastQuotaMark !== null) {
            return $this->_lastQuotaMark;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->order = 'mark_id DESC';
        $criteria->limit = 1;
        $quotaMark = CustomerQuotaMark::model()->find($criteria);
        if (empty($quotaMark)) {
            $quotaMark = $this->createQuotaMark(false);
        }
        return $this->_lastQuotaMark = $quotaMark;
    }

    /**
     * @param bool $deleteOlder
     * @return CustomerQuotaMark
     */
    public function createQuotaMark(bool $deleteOlder = true): CustomerQuotaMark
    {
        if ($deleteOlder) {
            $this->resetSendingQuota();
        }

        $quotaMark = new CustomerQuotaMark();
        $quotaMark->customer_id = (int)$this->customer_id;
        $quotaMark->save(false);
        $quotaMark->refresh(); // because of date_added being an expression

        return $this->_lastQuotaMark = $quotaMark;
    }

    /**
     * @return bool
     */
    public function getHasGroup(): bool
    {
        if (!$this->hasAttribute('group_id') || !$this->group_id) {
            return false;
        }
        return !empty($this->group);
    }

    /**
     * @param string $option
     * @param mixed $defaultValue
     * @return mixed
     */
    public function getGroupOption(string $option, $defaultValue = null)
    {
        static $loaded = [];

        if (!isset($loaded[$this->customer_id])) {
            $loaded[$this->customer_id] = [];
        }

        if (strpos($option, 'system.customer_') !== 0) {
            $option = 'system.customer_' . $option;
        }

        if (array_key_exists($option, $loaded[$this->customer_id])) {
            return $loaded[$this->customer_id][$option];
        }

        if (!$this->getHasGroup()) {
            return $loaded[$this->customer_id][$option] = options()->get($option, $defaultValue);
        }

        return $loaded[$this->customer_id][$option] = $this->group->getOptionValue($option, $defaultValue);
    }

    /**
     * @param int $size
     * @return string
     */
    public function getGravatarUrl(int $size = 50): string
    {
        $gravatar = sprintf('//www.gravatar.com/avatar/%s?s=%d', md5(strtolower(trim((string)$this->email))), (int)$size);
        return (string)hooks()->applyFilters('customer_get_gravatar_url', $gravatar, $this, $size);
    }

    /**
     * @param int $width
     * @param int $height
     * @param bool $forceSize
     * @return string
     */
    public function getAvatarUrl(int $width = 50, int $height = 50, bool $forceSize = false): string
    {
        if (empty($this->avatar)) {
            return $this->getGravatarUrl($width);
        }
        return (string)ImageHelper::resize($this->avatar, $width, $height, $forceSize);
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->getStatusIs(self::STATUS_ACTIVE);
    }

    /**
     * @return array
     */
    public function getAllListsIds(): array
    {
        static $ids = [];
        if (isset($ids[$this->customer_id])) {
            return $ids[$this->customer_id];
        }
        $ids[$this->customer_id] = [];

        $criteria = new CDbCriteria();
        $criteria->select    = 'list_id';
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);

        return $ids[$this->customer_id] = ListsCollection::findAll($criteria)->map(function (Lists $list) {
            return $list->list_id;
        })->all();
    }

    /**
     * @return array
     */
    public function getAllListsIdsNotArchived(): array
    {
        static $ids = [];
        if (isset($ids[$this->customer_id])) {
            return $ids[$this->customer_id];
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'list_id';
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);

        return $ids[$this->customer_id] = ListsCollection::findAll($criteria)->map(function (Lists $list) {
            return $list->list_id;
        })->all();
    }

    /**
     * @return array
     */
    public function getAllSurveysIds(): array
    {
        static $ids = [];
        if (isset($ids[$this->customer_id])) {
            return $ids[$this->customer_id];
        }
        $ids[$this->customer_id] = [];

        $criteria = new CDbCriteria();
        $criteria->select    = 'survey_id';
        $criteria->condition = 'customer_id = :cid AND `status` != :st';
        $criteria->params    = [
            ':cid' => (int)$this->customer_id,
            ':st' => Survey::STATUS_PENDING_DELETE,
        ];

        return $ids[$this->customer_id] = SurveyCollection::findAll($criteria)->map(function (Survey $survey) {
            return $survey->survey_id;
        })->all();
    }

    /**
     * @since 1.3.6.2
     * @param PricePlan $pricePlan
     *
     * @return CAttributeCollection
     * @throws CException
     */
    public function isOverPricePlanLimits(PricePlan $pricePlan): CAttributeCollection
    {
        $default = new CAttributeCollection([
            'overLimit' => false,
            'object'    => '',
            'limit'     => 0,
            'count'     => 0,
        ]);

        $in = clone $default;
        $in->add('overLimit', true);

        $kp  = 'system.customer_';
        $grp = $pricePlan->customerGroup;

        $limit = (int)$grp->getOptionValue($kp . 'servers.max_bounce_servers', 0);
        if ($limit > 0) {
            $in->add('limit', $limit);
            $in->add('count', BounceServer::model()->countByAttributes(['customer_id' => (int)$this->customer_id]));
            $in->add('object', 'bounce servers');
            if ((int)$in->itemAt('count') > (int)$in->itemAt('limit')) {
                return $in;
            }
        }

        $limit = (int)$grp->getOptionValue($kp . 'servers.max_delivery_servers', 0);
        if ($limit > 0) {
            $in->add('limit', $limit);
            $in->add('count', (int)DeliveryServer::model()->countByAttributes(['customer_id' => (int)$this->customer_id]));
            $in->add('object', 'delivery servers');
            if ((int)$in->itemAt('count') > (int)$in->itemAt('limit')) {
                return $in;
            }
        }

        $limit = (int)$grp->getOptionValue($kp . 'servers.max_fbl_servers', 0);
        if ($limit > 0) {
            $in->add('limit', $limit);
            $in->add('count', (int)FeedbackLoopServer::model()->countByAttributes(['customer_id' => (int)$this->customer_id]));
            $in->add('object', 'feedback loop servers');
            if ((int)$in->itemAt('count') > (int)$in->itemAt('limit')) {
                return $in;
            }
        }

        $limit = (int)$grp->getOptionValue($kp . 'campaigns.max_campaigns', 0);
        if ($limit > 0) {
            $in->add('limit', $limit);
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$this->customer_id);
            $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);
            $in->add('count', (int)Campaign::model()->count($criteria));
            $in->add('object', 'campaigns');
            if ((int)$in->itemAt('count') > (int)$in->itemAt('limit')) {
                return $in;
            }
        }

        $limit = (int)$grp->getOptionValue($kp . 'lists.max_subscribers', 0);
        if ($limit > 0) {
            $in->add('limit', $limit);
            $criteria = new CDbCriteria();
            $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';
            $criteria->addInCondition('t.list_id', $this->getAllListsIds());
            $in->add('count', (int)ListSubscriber::model()->count($criteria));
            $in->add('object', 'subscribers');
            if ((int)$in->itemAt('count') > (int)$in->itemAt('limit')) {
                return $in;
            }
        }

        $limit = (int)$grp->getOptionValue($kp . 'lists.max_lists', 0);
        if ($limit > 0) {
            $in->add('limit', $limit);
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$this->customer_id);
            $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);
            $in->add('count', (int)Lists::model()->count($criteria));
            $in->add('object', 'lists');
            if ((int)$in->itemAt('count') > (int)$in->itemAt('limit')) {
                return $in;
            }
        }

        $limit = (int)$grp->getOptionValue($kp . 'sending_domains.max_sending_domains', 0);
        if ($limit > 0) {
            $in->add('limit', $limit);
            $in->add('count', (int)SendingDomain::model()->countByAttributes(['customer_id' => (int)$this->customer_id]));
            $in->add('object', 'sending domains');
            if ((int)$in->itemAt('count') > (int)$in->itemAt('limit')) {
                return $in;
            }
        }

        return $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return Customer
     */
    public function setOption(string $key, $value): self
    {
        options()->set('customers.' . (int)$this->customer_id . '.' . $key, $value);
        return $this;
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getOption(string $key, $default = null)
    {
        return options()->get('customers.' . (int)$this->customer_id . '.' . $key, $default);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function removeOption(string $key)
    {
        return options()->remove('customers.' . (int)$this->customer_id . '.' . $key);
    }

    /**
     * @return void
     */
    public function updateLastLogin(): void
    {
        if (!array_key_exists('last_login', $this->getAttributes())) {
            return;
        }
        $columns = ['last_login' => MW_DATETIME_NOW];
        $params  = [':id' => $this->customer_id];
        db()->createCommand()->update($this->tableName(), $columns, 'customer_id = :id', $params);
        $this->last_login = date('Y-m-d H:i:s');
    }

    /**
     * @return string
     */
    public function getBirthDate(): string
    {
        if (empty($this->birth_date) || $this->birth_date == '0000-00-00') {
            return '';
        }
        return $this->birth_date = date('Y-m-d', (int)strtotime($this->birth_date));
    }

    /**
     * @param string $value
     */
    public function setBirthDate(string $value = ''): void
    {
        if (empty($value)) {
            $this->birth_date = null;
            return;
        }
        $this->birth_date = date('Y-m-d', (int)strtotime($value));
    }

    /**
     * @return string
     */
    public function getDatePickerFormat(): string
    {
        return 'yy-mm-dd';
    }

    /**
     * @return string
     */
    public function getDatePickerLanguage(): string
    {
        $language = app()->getLanguage();
        if (strpos($language, '_') === false) {
            return $language;
        }
        $language = explode('_', $language);
        return $language[0];
    }

    /**
     * @return bool
     */
    public function getTwoFaEnabled(): bool
    {
        return (string)$this->twofa_enabled === self::TEXT_YES;
    }

    /**
     * @return IBehavior
     */
    public function getLogAction(): IBehavior
    {
        if (!$this->asa('__logAction')) {
            $this->attachBehavior('__logAction', [
                'class' => 'customer.components.behaviors.CustomerActionLogBehavior',
            ]);
        }

        /** @var IBehavior $log */
        $log = $this->asa('__logAction');

        return $log;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateParentId(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute) || empty($this->customer_id)) {
            return;
        }

        if ((int)$this->customer_id === (int)$this->getAttribute($attribute)) {
            $this->addError($attribute, $this->t('Please select a different parent value'));
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateMinimumAge(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $currentYear  = (int)date('Y');
        $selectedYear = (int)date('Y', (int)strtotime($this->$attribute));

        if ($selectedYear >= $currentYear) {
            $this->addError($attribute, $this->t('Please select a past date!'));
            return;
        }

        /** @var OptionCustomerRegistration $optionCustomerRegistration */
        $optionCustomerRegistration = container()->get(OptionCustomerRegistration::class);

        $minimum = $optionCustomerRegistration->getMinimumAge();
        if (($age = $currentYear - $selectedYear) < $minimum) {
            $this->addError($attribute, $this->t('Age is {age} but minimum is {min}!', [
                '{age}' => $age,
                '{min}' => $minimum,
            ]));
            return;
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateInactiveAt(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }
        if (!$this->$attribute) {
            return;
        }
        $currentDate  = date('Y-m-d H:i:s');
        $selectedDate = date('Y-m-d H:i:s', strtotime($this->$attribute));
        if ($selectedDate <= $currentDate) {
            $this->addError($attribute, $this->t('Please select a future date!'));
            return;
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function logTakeQuotaAction(array $data = []): bool
    {
        static $logger;
        if ($logger === null) {
            $fileName = sprintf('customer-take-quota-action-%s.log', date('Y-m-d'));
            $filePath = (string)Yii::getPathOfAlias('common.runtime') . '/' . $fileName;
            $logger = new Monolog\Logger('customer-take-quota-action');
            $logger->pushHandler(new Monolog\Handler\StreamHandler($filePath, Monolog\Logger::DEBUG));
        }

        $data = CMap::mergeArray($data, [
            'customer_id'   => (int)$this->customer_id,
            'log_timestamp' => time(),
            'is_cli'        => is_cli(),
            'is_ajax'       => is_ajax(),
            'ip_address'    => is_cli() ? '' : (string)request()->getUserHostAddress(),
            // 'backtrace'  => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
        ]);

        $logger->info(json_encode($data));
        return true;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function handleSubaccountsIfGroupForbidThem(): void
    {
        // If the subaccounts are allowed and the customer is active, there is nothing to do
        if (!$this->getMustCheckSubaccountsPermissions()) {
            return;
        }

        $subaccounts = Customer::model()->findAllByAttributes([
            'parent_id' => $this->customer_id,
            'status'    => Customer::STATUS_ACTIVE,
        ]);

        // No subaccounts, nothing to handle
        if (empty($subaccounts)) {
            return;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        // There are subaccounts, but the group that this customer belongs to is not allowing them, so we need to make them inactive
        $subaccountsList = [];
        foreach ($subaccounts as $subaccount) {
            $subaccount->saveStatus(Customer::STATUS_INACTIVE);
            $subaccountsList[] = CHtml::link($subaccount->getFullName(), $optionUrl->getBackendUrl(sprintf('customers/update/id/%s', (string)$subaccount->customer_id)));
        }

        $message = new UserMessage();
        $message->title   = 'Subaccounts inactivation due to parent account inactivation or revoked permissions.';
        $message->message = 'The following subaccounts were inactivated, because the parent account {customer} is no longer allowed to manage subaccounts, or it became inactive: <br /><b>{subaccounts}</b>.';
        $message->message_translation_params = [
            '{customer}'    => $this->getFullName(),
            '{subaccounts}' => implode('<br />', $subaccountsList),
        ];
        $message->broadcast();
    }

    /**
     * @return bool
     */
    public function getMustCheckSubaccountsPermissions(): bool
    {
        return empty($this->parent_id) &&
            (
                $this->getGroupOption('subaccounts.enabled', 'no') === 'no' ||
                (int)$this->getGroupOption('subaccounts.max_subaccounts', -1) === 0 ||
                !$this->getIsActive()
            );
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function afterValidate()
    {
        parent::afterValidate();
        $this->handleUploadedAvatar();
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if (empty($this->customer_uid)) {
            $this->customer_uid = $this->generateUid();
        }

        if (!empty($this->fake_password)) {
            $this->password = passwordHasher()->hash($this->fake_password);
        }

        if ((string)$this->removable === self::TEXT_NO) {
            $this->status = self::STATUS_ACTIVE;
        }

        if (empty($this->confirmation_key)) {
            $this->confirmation_key = sha1($this->customer_uid . StringHelper::uniqid());
        }

        if (empty($this->timezone)) {
            $this->timezone = 'UTC';
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function beforeDelete()
    {
        if ((string)$this->removable !== self::TEXT_YES) {
            return false;
        }

        // since 1.3.5
        if (!$this->getStatusIs(self::STATUS_PENDING_DELETE)) {
            $this->saveStatus(self::STATUS_PENDING_DELETE);
            return false;
        }

        return parent::beforeDelete();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        if (!empty($this->customer_uid)) {
            // clean customer files, if any.
            $storagePath   = (string)Yii::getPathOfAlias('root.frontend.files.customer');
            $customerFiles = $storagePath . '/' . $this->customer_uid;
            if (file_exists($customerFiles) && is_dir($customerFiles)) {
                FileSystemHelper::deleteDirectoryContents($customerFiles, true, 1);
            }
        }

        parent::afterDelete();
    }

    /**
     * @return void
     */
    protected function handleUploadedAvatar(): void
    {
        if ($this->hasErrors()) {
            return;
        }

        /** @var CUploadedFile|null $avatar */
        $avatar = CUploadedFile::getInstance($this, 'new_avatar');

        if (!$avatar) {
            return;
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.avatars');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            if (!mkdir($storagePath, 0777, true)) {
                $this->addError('new_avatar', $this->t('The avatars storage directory({path}) does not exists and cannot be created!', [
                    '{path}' => $storagePath,
                ]));
                return;
            }
        }

        $newAvatarName = StringHelper::random(8, true) . '-' . $avatar->getName();
        if (!$avatar->saveAs($storagePath . '/' . $newAvatarName)) {
            $this->addError('new_avatar', $this->t('Cannot move the avatar into the correct storage folder!'));
            return;
        }

        $this->avatar = '/frontend/assets/files/avatars/' . $newAvatarName;
    }

    /**
     * @return bool
     */
    protected function _takeQuotaAction(): bool
    {
        $quotaAction = $this->getGroupOption('sending.action_quota_reached', '');
        if (empty($quotaAction)) {
            return true;
        }

        $this->createQuotaMark();

        if ($quotaAction != 'move-in-group') {
            return true;
        }

        $moveInGroupId = (int)$this->getGroupOption('sending.move_to_group_id', '');
        if (empty($moveInGroupId)) {
            return true;
        }

        $group = CustomerGroup::model()->findByPk($moveInGroupId);
        if (empty($group)) {
            return true;
        }

        $this->group_id = (int)$group->group_id;
        $this->addRelatedRecord('group', $group, false);
        $this->save(false);

        return true;
    }
}
