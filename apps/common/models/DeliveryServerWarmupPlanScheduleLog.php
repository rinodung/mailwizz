<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerWarmupPlanScheduleLog
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.10
 */

/**
 * This is the model class for table "{{delivery_server_warmup_plan_schedule_log}}".
 *
 * The followings are the available columns in table '{{delivery_server_warmup_plan_schedule_log}}':
 * @property integer $server_id
 * @property integer $schedule_id
 * @property integer $plan_id
 * @property integer $allowed_quota
 * @property integer $used_quota
 * @property mixed $started_at
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property DeliveryServer $server
 * @property DeliveryServerWarmupPlanSchedule $schedule
 * @property DeliveryServerWarmupPlan $plan
 */
class DeliveryServerWarmupPlanScheduleLog extends ActiveRecord
{
    /**
     * Flag for pending
     */
    const STATUS_PENDING = 'pending';

    /**
     * Flag for pending
     */
    const STATUS_PROCESSING = 'processing';

    /**
     * Flag for pending
     */
    const STATUS_COMPLETED = 'completed';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{delivery_server_warmup_plan_schedule_log}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['allowed_quota, used_quota, started_at, status', 'safe'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'server'   => [self::BELONGS_TO, DeliveryServer::class, 'server_id'],
            'schedule' => [self::BELONGS_TO, DeliveryServerWarmupPlanSchedule::class, 'schedule_id'],
            'plan'     => [self::BELONGS_TO, DeliveryServerWarmupPlan::class, 'plan_id'],

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
            'server_id'     => t('warmup_plans', 'Server'),
            'schedule_id'   => t('warmup_plans', 'Schedule'),
            'plan_id'       => t('warmup_plans', 'Warmup plan'),
            'allowed_quota' => t('warmup_plans', 'Allowed quota'),
            'used_quota'    => t('warmup_plans', 'Used quota'),
            'started_at'    => t('warmup_plans', 'Started at'),
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
        $criteria->compare('t.server_id', $this->server_id);
        $criteria->compare('t.schedule_id', $this->schedule_id);
        $criteria->compare('t.status', $this->status);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize' => DeliveryServerWarmupPlan::MAX_ALLOWED_WARMUP_PLAN_DURATION_DAYS * 24, // We want to show them all
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.schedule_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerWarmupPlanScheduleLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerWarmupPlanScheduleLog $model */
        $model = parent::model($className);

        return $model;
    }
}
