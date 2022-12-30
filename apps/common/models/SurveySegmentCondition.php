<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveySegmentCondition
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * This is the model class for table "survey_segment_condition".
 *
 * The followings are the available columns in table 'survey_segment_condition':
 * @property integer|null $condition_id
 * @property integer $segment_id
 * @property integer $operator_id
 * @property integer $field_id
 * @property string $value
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property SurveySegmentOperator $operator
 * @property SurveySegment $segment
 * @property SurveyField $field
 */
class SurveySegmentCondition extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{survey_segment_condition}}';
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
            'operator'  => [self::BELONGS_TO, SurveySegmentOperator::class, 'operator_id'],
            'segment'   => [self::BELONGS_TO, SurveySegment::class, 'segment_id'],
            'field'     => [self::BELONGS_TO, SurveyField::class, 'field_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'condition_id'  => t('survey_segments', 'Condition'),
            'segment_id'    => t('survey_segments', 'Segment'),
            'operator_id'   => t('survey_segments', 'Operator'),
            'field_id'      => t('survey_segments', 'Field'),
            'value'         => t('survey_segments', 'Value'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SurveySegmentCondition the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var SurveySegmentCondition $model */
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

        $operators = SurveySegmentOperator::model()->findAll();
        foreach ($operators as $operator) {
            $_options[$operator->operator_id] = t('survey_segments', $operator->name);
        }

        return $_options;
    }

    /**
     * @return string
     */
    public function getParsedValue(): string
    {
        $tags  = self::getValueTags();
        $value = trim((string)$this->value);
        foreach ($tags as $data) {
            $value = call_user_func_array($data['callback'], [$data, $value, $this]);
        }
        return (string)$value;
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
                    'description' => t('survey_segments', 'It will be transformed into an empty value'),
                    'callback'    => [__CLASS__, '_parseEmptyValueTag'],
                ],
                [
                    'tag'         => '[DATETIME]',
                    'description' => t('survey_segments', 'It will be transformed into the current date/time in the format of Y-m-d H:i:s (i.e: {datetime})', ['{datetime}' => date('Y-m-d H:i:s')]),
                    'callback'    => [__CLASS__, '_parseDatetimeValueTag'],
                ],
                [
                    'tag'         => '[DATE]',
                    'description' => t('survey_segments', 'It will be transformed into the current date in the format of Y-m-d (i.e: {date})', ['{date}' => date('Y-m-d')]),
                    'callback'    => [__CLASS__, '_parseDateValueTag'],
                ],
            ];
            /** @var array $tags */
            $tags = (array)hooks()->applyFilters('survey_segment_condition_value_tags', $tags);
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
     * @param SurveySegmentCondition $condition
     *
     * @return string
     */
    public static function _parseEmptyValueTag(array $data, string $value, SurveySegmentCondition $condition): string
    {
        if ($data['tag'] != $value) {
            return $value;
        }
        return (string)str_replace($data['tag'], '', $value);
    }

    /**
     * @param array $data
     * @param string $value
     * @param SurveySegmentCondition $condition
     *
     * @return string
     */
    public static function _parseDatetimeValueTag(array $data, string $value, SurveySegmentCondition $condition): string
    {
        if ($data['tag'] != $value) {
            return $value;
        }
        return (string)str_replace($data['tag'], date('Y-m-d H:i:s'), $value);
    }

    /**
     * @param array $data
     * @param string $value
     * @param SurveySegmentCondition $condition
     *
     * @return string
     */
    public static function _parseDateValueTag(array $data, string $value, SurveySegmentCondition $condition): string
    {
        if ($data['tag'] != $value) {
            return $value;
        }
        return (string)str_replace($data['tag'], date('Y-m-d'), $value);
    }
}
