<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSegmentCondition
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_segment_condition".
 *
 * The followings are the available columns in table 'list_segment_condition':
 * @property integer|null $condition_id
 * @property integer|null $segment_id
 * @property integer $operator_id
 * @property integer $field_id
 * @property string $value
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property ListSegmentOperator $operator
 * @property ListSegment $segment
 * @property ListField $field
 */
class ListSegmentCondition extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_segment_condition}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['field_id, operator_id, value', 'required'],
            ['field_id, operator_id', 'numerical', 'integerOnly' => true],
            ['value', 'length', 'max'=>255],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'operator'  => [self::BELONGS_TO, ListSegmentOperator::class, 'operator_id'],
            'segment'   => [self::BELONGS_TO, ListSegment::class, 'segment_id'],
            'field'     => [self::BELONGS_TO, ListField::class, 'field_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'condition_id'  => t('list_segments', 'Condition'),
            'segment_id'    => t('list_segments', 'Segment'),
            'operator_id'   => t('list_segments', 'Operator'),
            'field_id'      => t('list_segments', 'Field'),
            'value'         => t('list_segments', 'Value'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSegmentCondition the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSegmentCondition $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getOperatorsDropDownArray(): array
    {
        static $_options = [];
        if (!empty($_options)) {
            return $_options;
        }

        $operators = ListSegmentOperator::model()->findAll();
        foreach ($operators as $operator) {
            $_options[$operator->operator_id] = t('list_segments', $operator->name);
        }

        return $_options;
    }

    /**
     * @return mixed
     */
    public function getParsedValue()
    {
        $tags  = self::getValueTags();
        $value = trim((string)$this->value);
        foreach ($tags as $data) {
            $value = call_user_func_array($data['callback'], [$data, $value, $this]);
        }
        return $value;
    }

    /**
     * @return array
     */
    public static function getValueTags(): array
    {
        static $tags;
        if ($tags === null) {
            $tags = [
                [
                    'tag'         => '[EMPTY]',
                    'description' => t('list_segments', 'It will be transformed into an empty value'),
                    'callback'    => [__CLASS__, '_parseEmptyValueTag'],
                ],
                [
                    'tag'         => '[DATETIME]',
                    'description' => t('list_segments', 'It will be transformed into the current date/time in the format of Y-m-d H:i:s (i.e: {datetime})', ['{datetime}' => date('Y-m-d H:i:s')]),
                    'callback'    => [__CLASS__, '_parseDatetimeValueTag'],
                ],
                [
                    'tag'         => '[DATE]',
                    'description' => t('list_segments', 'It will be transformed into the current date in the format of Y-m-d (i.e: {date})', ['{date}' => date('Y-m-d')]),
                    'callback'    => [__CLASS__, '_parseDateValueTag'],
                ],
                [
                    'tag'         => '[PAST_DAYS_X]',
                    'description' => t('list_segments', 'It will rewind the current date by X days and use that as a comparison date'),
                    'callback'    => [__CLASS__, '_parsePastDaysValueTag'],
                ],
                [
                    'tag'         => '[FUTURE_DAYS_X]',
                    'description' => t('list_segments', 'It will forward the current date by X days and use that as a comparison date'),
                    'callback'    => [__CLASS__, '_parseFutureDaysValueTag'],
                ],
                [
                    'tag'         => '[BIRTHDAY]',
                    'description' => t('list_segments', 'It requires the birthday custom field value to be in the format of Y-m-d (i.e: {date}) in order to work properly', ['{date}' => date('Y-m-d')]),
                    'callback'    => [__CLASS__, '_parseBirthDateValueTag'],
                ],
                [
                    'tag'         => '[BIRTHDAY_FUTURE_DAYS_X]',
                    'description' => t('list_segments', 'It will forward the birthday by X days relative to the current date and use that as a comparison date.'),
                    'callback'    => [__CLASS__, '_parseFutureBirthDateValueTag'],
                ],
            ];
            $tags = (array)hooks()->applyFilters('list_segment_condition_value_tags', $tags);
            /**
             * @var int $index
             * @var array $data
             */
            foreach ($tags as $index => $data) {
                if (!isset($data['tag'], $data['description'], $data['callback']) || !is_callable($data['callback'], false)) {
                    unset($tags[$index]);
                }
            }
            ksort($tags);
        }
        return is_array($tags) ? $tags : [];
    }

    /**
     * @param array $data
     * @param string $value
     * @param ListSegmentCondition $condition
     *
     * @return string
     */
    public static function _parseEmptyValueTag(array $data, string $value, ListSegmentCondition $condition): string
    {
        if ($data['tag'] != $value) {
            return $value;
        }
        return str_replace($data['tag'], '', $value);
    }

    /**
     * @param array $data
     * @param string $value
     * @param ListSegmentCondition $condition
     *
     * @return string
     */
    public static function _parseDatetimeValueTag(array $data, string $value, ListSegmentCondition $condition): string
    {
        if ($data['tag'] != $value) {
            return $value;
        }
        return str_replace($data['tag'], date('Y-m-d H:i:s'), $value);
    }

    /**
     * @param array $data
     * @param string $value
     * @param ListSegmentCondition $condition
     *
     * @return string
     */
    public static function _parseDateValueTag(array $data, string $value, ListSegmentCondition $condition): string
    {
        if ($data['tag'] != $value) {
            return $value;
        }
        return str_replace($data['tag'], date('Y-m-d'), $value);
    }

    /**
     * @param array $data
     * @param string $value
     * @param ListSegmentCondition $condition
     *
     * @return string
     */
    public static function _parsePastDaysValueTag(array $data, string $value, ListSegmentCondition $condition): string
    {
        if (strpos($value, '[PAST_DAYS_') === false) {
            return $value;
        }

        if (!preg_match('/\[PAST_DAYS_(\d+)\]/', $value, $matches)) {
            return $value;
        }

        if (empty($matches[1])) {
            return $value;
        }

        return date('Y-m-d', (int)strtotime(sprintf('-%d days', (int)$matches[1])));
    }

    /**
     * @param array $data
     * @param string $value
     * @param ListSegmentCondition $condition
     *
     * @return string
     */
    public static function _parseFutureDaysValueTag(array $data, string $value, ListSegmentCondition $condition): string
    {
        if (strpos($value, '[FUTURE_DAYS_') === false) {
            return $value;
        }

        if (!preg_match('/\[FUTURE_DAYS_(\d+)\]/', $value, $matches)) {
            return $value;
        }

        if (empty($matches[1])) {
            return $value;
        }

        return date('Y-m-d', (int)strtotime(sprintf('+%d days', (int)$matches[1])));
    }

    /**
     * @param array $data
     * @param string $value
     * @param ListSegmentCondition $condition
     *
     * @return string
     */
    public static function _parseBirthDateValueTag(array $data, string $value, ListSegmentCondition $condition): string
    {
        if ($data['tag'] != $value) {
            return $value;
        }
        if (in_array($condition->operator->slug, [ListSegmentOperator::IS, ListSegmentOperator::ENDS_WITH])) {
            $condition->operator->slug = ListSegmentOperator::ENDS_WITH;
            return str_replace($data['tag'], date('m-d'), $value);
        }
        if (in_array($condition->operator->slug, [ListSegmentOperator::IS_NOT, ListSegmentOperator::NOT_ENDS_WITH])) {
            $condition->operator->slug = ListSegmentOperator::NOT_ENDS_WITH;
            return str_replace($data['tag'], date('m-d'), $value);
        }
        return $value;
    }

    /**
     * @param array $data
     * @param string $value
     * @param ListSegmentCondition $condition
     *
     * @return string
     */
    public static function _parseFutureBirthDateValueTag(array $data, string $value, ListSegmentCondition $condition): string
    {
        if (strpos($value, '[BIRTHDAY_FUTURE_DAYS_') === false) {
            return $value;
        }

        if (!preg_match('/\[BIRTHDAY_FUTURE_DAYS_(\d+)\]/', $value, $matches)) {
            return $value;
        }

        if (empty($matches[1])) {
            return $value;
        }

        $daysCount = (int)$matches[1] * (24 * 3600);

        if (in_array($condition->operator->slug, [ListSegmentOperator::IS, ListSegmentOperator::ENDS_WITH])) {
            $condition->operator->slug = ListSegmentOperator::ENDS_WITH;
            $value = date('m-d', time() + $daysCount);
        } elseif (in_array($condition->operator->slug, [ListSegmentOperator::IS_NOT, ListSegmentOperator::NOT_ENDS_WITH])) {
            $condition->operator->slug = ListSegmentOperator::NOT_ENDS_WITH;
            $value = date('m-d', time() + $daysCount);
        }

        return $value;
    }
}
