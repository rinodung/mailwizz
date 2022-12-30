<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerWarmupPlanSchedule
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

/**
 * This is the model class for table "{{delivery_server_warmup_plan_schedule}}".
 *
 * The followings are the available columns in table '{{delivery_server_warmup_plan_schedule}}':
 * @property integer $schedule_id
 * @property integer $plan_id
 * @property integer $quota
 * @property integer $increment
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property DeliveryServerWarmupPlan $plan
 * @property DeliveryServerWarmupPlanScheduleLog[] $logs
 */
class DeliveryServerWarmupPlanSchedule extends ActiveRecord
{
    /**
     * @var int
     */
    private static $_scheduleLogServerId = 0;

    /**
     * @var array
     */
    private $_serverScheduleLogs = [];

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{delivery_server_warmup_plan_schedule}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['quota, increment', 'safe'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'plan'  => [self::BELONGS_TO, DeliveryServerWarmupPlan::class, 'plan_id'],
            'logs'  => [self::HAS_MANY, DeliveryServerWarmupPlanScheduleLog::class, 'schedule_id'],
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
            'schedule_id' => t('warmup_plans', 'Schedule'),
            'plan_id'     => t('warmup_plans', 'Warmup plan'),
            'quota'       => t('warmup_plans', 'Quota'),
            'increment'   => t('warmup_plans', 'Increment'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [];
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
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        $criteria->compare('t.plan_id', $this->plan_id);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => false,
            'sort'          => [
                'defaultOrder' => [
                    't.schedule_id' => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerWarmupPlanSchedule the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerWarmupPlanSchedule $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return int
     */
    public function getPlanQuota(): int
    {
        return $this->plan->getIsTargeted() ? (int)$this->quota : (int)$this->increment;
    }

    /**
     * @param int $serverId
     *
     * @return void
     */
    public static function setScheduleLogServerId(int $serverId)
    {
        self::$_scheduleLogServerId = $serverId;
    }

    /**
     * @return int
     */
    public function getScheduleLogServerId(): int
    {
        return self::$_scheduleLogServerId;
    }

    /**
     * @param int $serverId
     *
     * @return DeliveryServerWarmupPlanScheduleLog|null
     */
    public function getScheduleLogByServerId(int $serverId): ?DeliveryServerWarmupPlanScheduleLog
    {
        if (array_key_exists($serverId, $this->_serverScheduleLogs)) {
            /** @var DeliveryServerWarmupPlanScheduleLog|null $log */
            $log = $this->_serverScheduleLogs[$serverId];

            return $log;
        }

        /** @var DeliveryServerWarmupPlanScheduleLog|null $log */
        $log = DeliveryServerWarmupPlanScheduleLog::model()->findByAttributes([
            'schedule_id'   => $this->schedule_id,
            'server_id'     => $serverId,
        ]);

        return $this->_serverScheduleLogs[$serverId] = $log;
    }

    /**
     * @param int $serverId
     *
     * @return string
     * @throws Exception
     */
    public function getScheduleLogStartAtByServerId(int $serverId): string
    {
        $log = $this->getScheduleLogByServerId($serverId);

        if (empty($log) || empty($log->started_at)) {
            return t('app', 'Not set');
        }

        return $this->dateTimeFormatter->formatLocalizedDateTime($log->started_at);
    }

    /**
     * @param int $serverId
     *
     * @return string
     * @throws Exception
     */
    public function getScheduleLogStatusByServerId(int $serverId): string
    {
        $log = $this->getScheduleLogByServerId($serverId);

        if (empty($log) || empty($log->status)) {
            return t('app', ucfirst(DeliveryServerWarmupPlanScheduleLog::STATUS_PENDING));
        }

        return t('app', ucfirst($log->status));
    }
}
