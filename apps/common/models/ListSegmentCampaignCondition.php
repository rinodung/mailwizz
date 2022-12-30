<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSegmentCampaignCondition
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.9.12
 */

/**
 * This is the model class for table "{{list_segment_campaign_condition}}".
 *
 * The followings are the available columns in table '{{list_segment_campaign_condition}}':
 * @property integer $condition_id
 * @property integer $segment_id
 * @property integer $campaign_id
 * @property string $action
 * @property string $action_click_url_id
 * @property integer $time_value
 * @property string $time_unit
 * @property string $time_comparison_operator
 * @property string $date_added
 * @property string $last_updated
 *
 * The followings are the available model relations:
 * @property CampaignTrackUrl $actionClickUrl
 * @property Campaign $campaign
 * @property ListSegment $segment
 */
class ListSegmentCampaignCondition extends ActiveRecord
{
    /**
     * Flags
     */
    const ACTION_OPEN   = 'open';
    const ACTION_CLICK  = 'click';

    /**
     * Flags
     */
    const TIME_UNIT_DAY     = 'day';
    const TIME_UNIT_MONTH   = 'month';
    const TIME_UNIT_YEAR    = 'year';

    /**
     * Flags
     */
    const OPERATOR_LTE = 'lte';
    const OPERATOR_LT  = 'lt';
    const OPERATOR_GTE = 'gte';
    const OPERATOR_GT  = 'gt';
    const OPERATOR_EQ  = 'eq';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_segment_campaign_condition}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['action, segment_id, time_unit, time_value, time_comparison_operator', 'required'],

            ['segment_id, campaign_id', 'numerical', 'integerOnly' => true],
            ['segment_id', 'exist', 'className' => 'ListSegment'],
            ['campaign_id', 'exist', 'className' => 'Campaign'],
            ['time_value', 'numerical', 'integerOnly' => true, 'min' => 0, 'max' => 10000],
            ['action', 'length', 'max' => 255],
            ['action', 'in', 'range' => array_keys($this->getActionsList())],
            ['action_click_url_id', 'numerical', 'integerOnly' => true, 'min' => 1],
            ['action_click_url_id', 'exist', 'className' => 'CampaignTrackUrl'],
            ['time_unit, time_comparison_operator', 'length', 'max' => 20],
            ['time_unit', 'in', 'range' => array_keys($this->getTimeUnitsList())],
            ['time_comparison_operator', 'in', 'range' => array_keys($this->getTimeComparisonOperatorsList())],
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
            'actionClickUrl'    => [self::BELONGS_TO, 'CampaignTrackUrl', 'action_click_url_id'],
            'campaign'          => [self::BELONGS_TO, 'Campaign', 'campaign_id'],
            'segment'           => [self::BELONGS_TO, 'ListSegment', 'segment_id'],
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
            'condition_id'              => t('list_segments', 'Condition'),
            'segment_id'                => t('list_segments', 'Segment'),
            'campaign_id'               => t('list_segments', 'Campaign'),
            'action'                    => t('list_segments', 'Campaign action'),
            'action_click_url_id'       => t('list_segments', 'Click url'),
            'time_value'                => t('list_segments', 'Time value'),
            'time_unit'                 => t('list_segments', 'Time unit'),
            'time_comparison_operator'  => t('list_segments', 'Comparison'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSegmentCampaignCondition the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSegmentCampaignCondition $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getActionsList(): array
    {
        return [
            self::ACTION_OPEN  => t('list_segments', 'Open'),
            self::ACTION_CLICK => t('list_segments', 'Click'),
        ];
    }

    /**
     * @return array
     */
    public function getTimeUnitsList(): array
    {
        return [
            self::TIME_UNIT_DAY     => t('list_segments', 'Day(s)'),
            self::TIME_UNIT_MONTH   => t('list_segments', 'Month(s)'),
            self::TIME_UNIT_YEAR    => t('list_segments', 'Year(s)'),
        ];
    }

    /**
     * @return string[]
     */
    public function getTimeComparisonOperatorsList(): array
    {
        return [
            self::OPERATOR_LTE => t('list_segments', 'Less then or equal'),
            self::OPERATOR_LT  => t('list_segments', 'Less then'),
            self::OPERATOR_GTE => t('list_segments', 'Greater than or equal'),
            self::OPERATOR_GT  => t('list_segments', 'Greater than'),
            self::OPERATOR_EQ  => t('list_segments', 'Equal'),
        ];
    }

    /**
     * @param int $listId
     *
     * @return array
     */
    public function getCampaignsList($listId): array
    {
        $statuses = [
            Campaign::STATUS_SENT,
            Campaign::STATUS_SENDING,
            Campaign::STATUS_PROCESSING,
            Campaign::STATUS_PAUSED,
        ];

        $criteria = new CDbCriteria();
        $criteria->compare('list_id', (int)$listId);
        $criteria->addInCondition('status', $statuses);
        $models = Campaign::model()->findAll($criteria);

        $campaigns = [
            ''  => t('list_segments', 'Any list campaign'),
        ];
        foreach ($models as $model) {
            $campaigns[$model->campaign_id] = sprintf('%s: %s', $model->name, $model->campaign_uid);
        }

        return $campaigns;
    }

    /**
     * @return bool
     */
    public function getIsOpenAction(): bool
    {
        return $this->action === self::ACTION_OPEN;
    }

    /**
     * @return bool
     */
    public function getIsClickAction(): bool
    {
        return $this->action === self::ACTION_CLICK;
    }

    /**
     * @return string
     */
    public function getTimeComparisonAliasForDb(): string
    {
        return sprintf('segmentCampaignCondition%s%d', ucfirst($this->action), (int)$this->condition_id);
    }

    /**
     * @return string
     */
    public function getTimeComparisonOperatorForDb(): string
    {
        if ($this->time_comparison_operator === self::OPERATOR_LTE) {
            return '<=';
        }

        if ($this->time_comparison_operator === self::OPERATOR_LT) {
            return '<';
        }

        if ($this->time_comparison_operator === self::OPERATOR_GTE) {
            return '>=';
        }

        if ($this->time_comparison_operator === self::OPERATOR_GT) {
            return '>';
        }

        return '=';
    }

    /**
     * @return string
     */
    public function getTimeComparisonForDb(): string
    {
        $comparison = ' 0 = 1 ';
        if ($this->time_comparison_operator === self::OPERATOR_LTE || $this->time_comparison_operator === self::OPERATOR_LT) {
            $comparison = sprintf(
                'DATE(DATE_SUB(NOW(), INTERVAL %d %s)) %s DATE(%s.date_added)',
                $this->time_value,
                $this->time_unit,
                $this->getTimeComparisonOperatorForDb(),
                $this->getTimeComparisonAliasForDb()
            );
        } elseif ($this->time_comparison_operator === self::OPERATOR_GTE || $this->time_comparison_operator === self::OPERATOR_GT) {
            $comparison = sprintf(
                'DATE(DATE_SUB(NOW(), INTERVAL %d %s)) %s DATE(%s.date_added)',
                $this->time_value,
                $this->time_unit,
                $this->getTimeComparisonOperatorForDb(),
                $this->getTimeComparisonAliasForDb()
            );
        } elseif ($this->time_comparison_operator === self::OPERATOR_EQ) {
            $comparison = sprintf(
                'DATE(DATE_SUB(NOW(), INTERVAL %d %s)) %s DATE(%s.date_added)',
                $this->time_value,
                $this->time_unit,
                $this->getTimeComparisonOperatorForDb(),
                $this->getTimeComparisonAliasForDb()
            );
        }
        return $comparison;
    }
}
