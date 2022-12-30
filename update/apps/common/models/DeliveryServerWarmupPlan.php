<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerWarmupPlan
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

/**
 * This is the model class for table "{{delivery_server_warmup_plan}}".
 *
 * The followings are the available columns in table '{{delivery_server_warmup_plan}}':
 * @property integer $plan_id
 * @property integer|string|null $customer_id
 * @property string $name
 * @property string $description
 * @property string $status
 * @property integer $sending_limit
 * @property integer $sendings_count
 * @property string $sending_quota_type
 * @property integer $sending_increment_ratio
 * @property string $sending_strategy
 * @property string $sending_limit_type
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * @property DeliveryServerWarmupPlanSchedule[] $schedules
 * @property DeliveryServerWarmupPlanScheduleLog[] $serverSchedules
 * @property Customer $customer
 */
class DeliveryServerWarmupPlan extends ActiveRecord
{
    /**
     * Flags for the quota type
     */
    const SENDING_QUOTA_TYPE_HOURLY = 'hourly';
    const SENDING_QUOTA_TYPE_DAILY = 'daily';
    const SENDING_QUOTA_TYPE_MONTHLY = 'monthly';

    /**
     * Flags for the sending strategy
     */
    const SENDING_STRATEGY_EXPONENTIAL = 'exponential';
    const SENDING_STRATEGY_LINEAR = 'linear';

    /**
     * Flags for the sending limit type
     */
    const SENDING_LIMIT_TYPE_TOTAL = 'total';
    const SENDING_LIMIT_TYPE_TARGETED = 'targeted';

    /**
     * Flag for status draft
     */
    const STATUS_DRAFT = 'draft';

    /**
     * The maximum period in days that the warmup plan can last
     */
    const MAX_ALLOWED_WARMUP_PLAN_DURATION_DAYS = 30;

    /**
     * @var array
     */
    protected $_schedules = [];

    /**
     * @var array
     */
    protected $_isValidPlan = [];

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{delivery_server_warmup_plan}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, sending_limit, sendings_count, sending_quota_type, sending_strategy, sending_limit_type', 'required'],
            ['name', 'length', 'max' => 50],
            ['description', 'length', 'max' => 255],

            ['sending_limit', 'numerical', 'integerOnly' => true, 'min' => 1],
            ['sendings_count', 'numerical', 'integerOnly' => true, 'min' => 1],
            ['sending_increment_ratio', '_validateSendingIncrementRatio'],
            ['sending_increment_ratio', 'length', 'min'=> 1, 'max' => 3],
            ['sending_increment_ratio', 'in', 'range' => array_keys($this->getSendingIncrementRatioArray())],
            ['sending_increment_ratio', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 99],

            ['sending_quota_type', 'in', 'range' => array_keys($this->getSendingQuotaTypeOptions())],
            ['sending_strategy', 'in', 'range' => array_keys($this->getSendingStrategyOptions())],
            ['sending_limit_type', 'in', 'range' => array_keys($this->getSendingLimitTypeOptions())],

            ['sending_limit, sendings_count, sending_increment_ratio', '_validateGeneratedSchedule'],

            ['customer_id', 'exist', 'className' => Customer::class, 'attributeName' => 'customer_id', 'allowEmpty' => true],

            // The following rule is used by search().
            ['name, status, sending_limit, sendings_count, sending_quota_type, sending_strategy, sending_limit_type, customer_id', 'safe', 'on'=>'search'],
            ['description', 'safe'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'schedules'      => [self::HAS_MANY, DeliveryServerWarmupPlanSchedule::class, 'plan_id'],
            'severSchedules' => [self::HAS_MANY, DeliveryServerWarmupPlanScheduleLog::class, 'plan_id'],
            'customer'       => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'plan_id'                 => t('warmup_plans', 'Delivery server warmup plan'),
            'customer_id'             => t('warmup_plans', 'Customer'),
            'name'                    => t('warmup_plans', 'Name'),
            'description'             => t('warmup_plans', 'Description'),
            'sending_limit'           => t('warmup_plans', 'Sending limit'),
            'sendings_count'          => t('warmup_plans', 'Sendings count'),
            'sending_quota_type'      => t('warmup_plans', 'Sending quota type'),
            'sending_increment_ratio' => t('warmup_plans', 'Sending increment percentage'),
            'sending_strategy'        => t('warmup_plans', 'Sending strategy'),
            'sending_limit_type'      => t('warmup_plans', 'Sending limit type'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array help text for attributes
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name'                    => t('warmup_plans', 'The warmup plan name'),
            'description'             => t('warmup_plans', 'The warmup plan description'),
            'sending_limit'           => t('warmup_plans', 'The warmup plan sending limit, meaning the number of emails to be sent. Based on the limit type chosen this number can be the total number of emails sent throughout all the sendings, or the last sending will have that exact number'),
            'sendings_count'          => t('warmup_plans', 'The warmup plan sendings count. This is the number of generated schedules, based on which the delivery server quota will apply.'),
            'sending_quota_type'      => t('warmup_plans', 'The warmup plan sending quota type. The kind of quota against which the generated schedule quota value will be applied. If hourly, we will take into consideration applying the delivery server hourly quota'),
            'sending_increment_ratio' => t('warmup_plans', 'The warmup plan sending increment ratio. If the sending strategy is exponential, this values represents the increment percentage from a schedule to another.'),
            'sending_strategy'        => t('warmup_plans', 'The warmup plan sending strategy. Can be exponential or incremental. For incremental we will use the ratio between sending_limit and sendings_count to calculate the growth factor. Depending on the sending limit type chosen we can send maximum the value of the growth factor per sending (for total) or the growth factor added to the previous sending value per sending (for targeted).'),
            'sending_limit_type'      => t('warmup_plans', 'The warmup plan sending limit type. Based on this selection, we will send either a number of emails calculated throughout all the schedules (total) equal with the sending limit, or the last of the sendings will reach the sending limit value.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
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
        $criteria->compare('name', $this->name, true);
        $criteria->compare('sending_limit', $this->sending_limit, true);
        $criteria->compare('sendings_count', $this->sendings_count, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('sending_quota_type', $this->sending_quota_type);
        $criteria->compare('sending_strategy', $this->sending_strategy);
        $criteria->compare('sending_limit_type', $this->sending_limit_type);

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'plan_id'     => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerWarmupPlan the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerWarmupPlan $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getFormattedSendingsCount(): string
    {
        return formatter()->formatNumber($this->sendings_count);
    }

    /**
     * @return string
     */
    public function getFormattedSendingLimit(): string
    {
        return formatter()->formatNumber($this->sending_limit);
    }

    /**
     * @return bool
     */
    public function getSendingQuotaTypeIsHourly(): bool
    {
        return $this->sending_quota_type === self::SENDING_QUOTA_TYPE_HOURLY;
    }

    /**
     * @return bool
     */
    public function getSendingQuotaTypeIsDaily(): bool
    {
        return $this->sending_quota_type === self::SENDING_QUOTA_TYPE_DAILY;
    }

    /**
     * @return bool
     */
    public function getSendingQuotaTypeIsMonthly(): bool
    {
        return $this->sending_quota_type === self::SENDING_QUOTA_TYPE_MONTHLY;
    }

    /**
     * @return array
     */
    public function getSendingIncrementRatioArray(): array
    {
        $options = ['0' => t('app', 'Please select an option.')];
        for ($i = 1; $i < 100; ++$i) {
            $options[$i] = $i . ' %';
        }
        return $options;
    }

    /**
     * @return array
     */
    public function getSendingQuotaTypeOptions(): array
    {
        return [
            self::SENDING_QUOTA_TYPE_HOURLY  => ucfirst(t('warmup_plans', self::SENDING_QUOTA_TYPE_HOURLY)),
            self::SENDING_QUOTA_TYPE_DAILY   => ucfirst(t('warmup_plans', self::SENDING_QUOTA_TYPE_DAILY)),
            self::SENDING_QUOTA_TYPE_MONTHLY => ucfirst(t('warmup_plans', self::SENDING_QUOTA_TYPE_MONTHLY)),
        ];
    }

    /**
     * @return array
     */
    public function getStatusesOptions(): array
    {
        return [
            self::STATUS_ACTIVE  => ucfirst(t('app', self::STATUS_ACTIVE)),
            self::STATUS_DRAFT   => ucfirst(t('app', self::STATUS_DRAFT)),
        ];
    }

    /**
     * @return array
     */
    public function getSendingStrategyOptions(): array
    {
        return [
            self::SENDING_STRATEGY_LINEAR      => ucfirst(t('warmup_plans', self::SENDING_STRATEGY_LINEAR)),
            self::SENDING_STRATEGY_EXPONENTIAL => ucfirst(t('warmup_plans', self::SENDING_STRATEGY_EXPONENTIAL)),
        ];
    }

    /**
     * @return array
     */
    public function getSendingLimitTypeOptions(): array
    {
        return [
            self::SENDING_LIMIT_TYPE_TOTAL    => ucfirst(t('warmup_plans', self::SENDING_LIMIT_TYPE_TOTAL)),
            self::SENDING_LIMIT_TYPE_TARGETED => ucfirst(t('warmup_plans', self::SENDING_LIMIT_TYPE_TARGETED)),
        ];
    }

    /**
     * @return array
     */
    public function getSendingsCountLimitPerQuotaTypeMapping(): array
    {
        return [
            self::SENDING_QUOTA_TYPE_HOURLY  => self::MAX_ALLOWED_WARMUP_PLAN_DURATION_DAYS * 24,
            self::SENDING_QUOTA_TYPE_DAILY   => self::MAX_ALLOWED_WARMUP_PLAN_DURATION_DAYS,
            self::SENDING_QUOTA_TYPE_MONTHLY => (int)(self::MAX_ALLOWED_WARMUP_PLAN_DURATION_DAYS / 30),
        ];
    }

    /**
     * @return bool
     */
    public function getIsLinear(): bool
    {
        return $this->sending_strategy === self::SENDING_STRATEGY_LINEAR;
    }

    /**
     * @return bool
     */
    public function getIsExponential(): bool
    {
        return $this->sending_strategy === self::SENDING_STRATEGY_EXPONENTIAL;
    }

    /**
     * @return bool
     */
    public function getIsTargeted(): bool
    {
        return $this->sending_limit_type === self::SENDING_LIMIT_TYPE_TARGETED;
    }

    /**
     * @return bool
     */
    public function getIsActive(): bool
    {
        return $this->getStatusIs(self::STATUS_ACTIVE);
    }

    /**
     * @return string
     */
    public function getNameWithCustomer(): string
    {
        return sprintf('%s (%s)', $this->name, $this->customer_id ? $this->customer->getFullName() : t('app', 'System'));
    }

    /**
     * @return float
     */
    public function getSendingIncrementRatio(): float
    {
        return (float)(1 + (int)$this->sending_increment_ratio / 100);
    }

    /**
     * @return float
     */
    public function getIncrementFactor(): float
    {
        if (empty($this->sendings_count)) {
            return 1.00;
        }

        if ($this->getIsLinear()) {
            return $this->sending_limit / $this->sendings_count;
        }

        if ($this->getSendingIncrementRatio() === 1.00) {
            return 1.00;
        }

        if ($this->getIsExponential()) {
            return (int)$this->sending_limit * (1 - $this->getSendingIncrementRatio()) / (1 - pow($this->getSendingIncrementRatio(), (int)$this->sendings_count));
        }

        return 1.00;
    }

    /**
     * @return string
     */
    public function getPlanHash(): string
    {
        return sha1(sprintf('sending_count_%s_sending_limit_%s_sending_strategy_%s_sending_ratio_%s', (string)$this->sendings_count, (string)$this->sending_limit, (string)$this->sending_strategy, $this->sending_increment_ratio));
    }

    /**
     * @return array
     */
    public function getSchedulesArray(): array
    {
        $hash = $this->getPlanHash();
        if (!empty($this->_schedules[$hash])) {
            return $this->_schedules[$hash];
        }
        return $this->_schedules[$hash] = $this->generateSchedulesArray();
    }

    /**
     * @return bool
     */
    public function getIsValidPlan(): bool
    {
        $hash = $this->getPlanHash();
        if (!array_key_exists($hash, $this->_isValidPlan)) {
            $this->getSchedulesArray();
        }
        return (bool)($this->_isValidPlan[$hash] ?? false);
    }

    /**
     * @return array
     */
    public function generateSchedulesArray(): array
    {
        $schedules = [];
        $this->_isValidPlan[$this->getPlanHash()] = false;

        if (empty($this->sendings_count)) {
            return $schedules;
        }

        if (!($incrementFactor = $this->getIncrementFactor())) {
            return $schedules;
        }

        $valid = true;
        for ($i = 1; $i <= $this->sendings_count; $i++) {
            $increment = 0;
            if ($this->getIsExponential()) {
                $increment = (int)round($incrementFactor * pow($this->getSendingIncrementRatio(), ($i - 1)));
            }
            if ($this->getIsLinear()) {
                $increment = (int)round($incrementFactor);
            }
            $schedule = [
                'id'        => $i,
                'increment' => $increment,
                'valid'     => ($increment !== 0),
            ];
            $valid = $valid && $schedule['valid'];

            if ($i === 1) {
                $schedule['quota'] = $increment;
                $schedules[]       = $schedule;
                continue;
            }

            $schedule['quota'] = $schedules[$i - 2]['quota'] + $increment;

            $schedules[] = $schedule;
        }

        $this->_isValidPlan[$this->getPlanHash()] = $valid;

        //Adjust the last schedule values to overcome the rounding differences.
        $incrementSum = 0;
        $lastSchedule = [
            'id'        => 0,
            'increment' => 0,
            'quota'     => 0,
            'valid'     => false,
        ];
        if (!empty($schedules)) {
            $incrementSum = (int)array_sum(array_column($schedules, 'increment'));
            $lastSchedule = (array)end($schedules);
        }

        if ($incrementSum && $incrementSum !== $this->sending_limit) {
            $lastSchedule['increment'] += $this->sending_limit - $incrementSum;
        }
        if (!empty($lastSchedule['quota']) && $lastSchedule['quota'] != $this->sending_limit) {
            $lastSchedule['quota'] = $this->sending_limit;
        }
        if ($lastSchedule['id']) {
            array_pop($schedules);
            $schedules[] = $lastSchedule;
        }

        return $schedules;
    }

    /**
     * @return DeliveryServerWarmupPlanSchedule[]
     */
    public function getSchedulesModels(): array
    {
        $schedulesArray = $this->getSchedulesArray();

        $models = [];
        foreach ($schedulesArray as $scheduleItem) {
            $model = new DeliveryServerWarmupPlanSchedule();
            $model->plan_id    = $this->plan_id;
            $model->attributes = $scheduleItem;

            $models[] = $model;
        }

        return $models;
    }

    /**
     * @return bool
     */
    public function createSchedules(): bool
    {
        if ($this->getIsNewRecord()) {
            return false;
        }

        DeliveryServerWarmupPlanSchedule::model()->deleteAllByAttributes(['plan_id' => (int)$this->plan_id]);

        $models = $this->getSchedulesModels();
        foreach ($models as $model) {
            if (!$model->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return DeliveryServerWarmupPlanSchedule
     */
    public function getScheduleSearchModel(): DeliveryServerWarmupPlanSchedule
    {
        $schedule = new DeliveryServerWarmupPlanSchedule('search');
        $schedule->plan_id = $this->plan_id;

        return $schedule;
    }

    /**
     * @param int $serverId
     *
     * @return bool
     */
    public function getIsDeliveryServerProcessing(int $serverId): bool
    {
        $all = (int)DeliveryServerWarmupPlanScheduleLog::model()->countByAttributes([
            'plan_id'   => $this->plan_id,
            'server_id' => $serverId,
        ]);

        if ($all === 0) {
            return false;
        }

        $processing = (int)DeliveryServerWarmupPlanScheduleLog::model()->countByAttributes([
            'plan_id'   => $this->plan_id,
            'server_id' => $serverId,
            'status'    => DeliveryServerWarmupPlanScheduleLog::STATUS_PROCESSING,
        ]);

        return $processing > 0;
    }

    /**
     * @param int $serverId
     *
     * @return bool
     */
    public function getIsDeliveryServerCompleted(int $serverId): bool
    {
        $completed = (int)DeliveryServerWarmupPlanScheduleLog::model()->countByAttributes([
            'plan_id'   => $this->plan_id,
            'server_id' => $serverId,
            'status'    => DeliveryServerWarmupPlanScheduleLog::STATUS_COMPLETED,
        ]);

        if ($completed === 0) {
            return false;
        }

        $all = (int)DeliveryServerWarmupPlanScheduleLog::model()->countByAttributes([
            'plan_id'   => $this->plan_id,
            'server_id' => $serverId,
        ]);

        return $completed === $all;
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateGeneratedSchedule(string $attribute, array $params = []): void
    {
        if ($this->hasErrors()) {
            return;
        }

        if ($this->sendings_count > $this->sending_limit) {
            $this->addError('sendings_count', t('warmup_plans', 'The value of the sendings count should be lower than the sending limit'));
            return;
        }

        $mapping = $this->getSendingsCountLimitPerQuotaTypeMapping();
        $mappingSendingCount = $mapping[$this->sending_quota_type] ?? 0;

        if ($this->sending_quota_type && $this->sendings_count > $mappingSendingCount) {
            $this->addError('sendings_count', t('warmup_plans', 'The value of the sendings count should be lower than 1 month period. Please lower this value or change the sending quota type.'));
            $this->addError('sending_quota_type', t('warmup_plans', 'Please change the sending quota type to accommodate a period lower than one month.'));
            return;
        }

        if (!$this->getIsValidPlan()) {
            $this->addError('sending_limit', t('warmup_plans', 'The generated schedules are having empty quotas. That means the combination of your sending limit, sendings count and increment ratio is not correct.'));
            $this->addError('sendings_count', t('warmup_plans', 'The generated schedules are having empty quotas.'));
            $this->addError('sending_increment_ratio', t('warmup_plans', 'Having a big increment ratio against with not so many emails to be sent can lead to this error'));
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateSendingIncrementRatio(string $attribute, array $params = []): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if ($this->getIsExponential() && empty($this->$attribute)) {
            $this->addError('sending_increment_ratio', t('warmup_plans', 'This field is required for exponential sending type'));
        }
    }
}
