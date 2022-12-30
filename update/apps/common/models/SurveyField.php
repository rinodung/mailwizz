<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyField
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * This is the model class for table "{{survey_field}}".
 *
 * The followings are the available columns in table '{{survey_field}}':
 * @property integer|null $field_id
 * @property integer $type_id
 * @property integer|null $survey_id
 * @property string $label
 * @property string $default_value
 * @property string $help_text
 * @property string $description
 * @property string $required
 * @property string $visibility
 * @property string $meta_data
 * @property integer $sort_order
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Survey $survey
 * @property SurveyFieldType $type
 * @property SurveyFieldOption[] $options
 * @property SurveyFieldOption[] $option
 * @property SurveyFieldValue[] $values
 * @property SurveyFieldValue[] $value
 * @property SurveySegmentCondition[] $segmentConditions
 */
class SurveyField extends ActiveRecord
{
    /**
     * Visibility flags
     */
    const VISIBILITY_VISIBLE = 'visible';
    const VISIBILITY_HIDDEN = 'hidden';
    const VISIBILITY_NONE = 'none';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{survey_field}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['type_id, label, required, visibility, sort_order', 'required'],

            ['type_id', 'numerical', 'integerOnly' => true, 'min' => 1],
            ['type_id', 'exist', 'className' => SurveyFieldType::class],
            ['label, help_text, description, default_value', 'length', 'min' => 1, 'max' => 255],
            ['required', 'in', 'range' => array_keys($this->getRequiredOptionsArray())],
            ['visibility', 'in', 'range' => array_keys($this->getVisibilityOptionsArray())],
            ['sort_order', 'numerical', 'min' => -100, 'max' => 100],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'survey'            => [self::BELONGS_TO, Survey::class, 'survey_id'],
            'type'              => [self::BELONGS_TO, SurveyFieldType::class, 'type_id'],
            'options'           => [self::HAS_MANY, SurveyFieldOption::class, 'field_id'],
            'option'            => [self::HAS_ONE, SurveyFieldOption::class, 'field_id'],
            'values'            => [self::HAS_MANY, SurveyFieldValue::class, 'field_id'],
            'value'             => [self::HAS_ONE, SurveyFieldValue::class, 'field_id'],
            'segmentConditions' => [self::HAS_MANY, SurveySegmentCondition::class, 'field_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'field_id'      => t('survey_fields', 'Field'),
            'type_id'       => t('survey_fields', 'Type'),
            'survey_id'     => t('survey_fields', 'List'),
            'label'         => t('survey_fields', 'Label'),
            'default_value' => t('survey_fields', 'Default value'),
            'help_text'     => t('survey_fields', 'Help text'),
            'Description'   => t('survey_fields', 'Description'),
            'required'      => t('survey_fields', 'Required'),
            'visibility'    => t('survey_fields', 'Visibility'),
            'sort_order'    => t('survey_fields', 'Sort order'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SurveyField the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var SurveyField $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'label'         => t('survey_fields', 'This is what your responders will see above the input field.'),
            'default_value' => t('survey_fields', 'In case this field is not required and you need a default value for it.'),
            'help_text'     => t('survey_fields', 'If you need to describe this field to your responders.'),
            'description'   => t('survey_fields', 'Additional description for this field to show to your responders.'),
            'required'      => t('survey_fields', 'Whether this field must be filled in in order to submit the subscription form.'),
            'visibility'    => t('survey_fields', 'Hidden fields are rendered in the form but hidden, while None fields are simply not rendered in the form at all.'),
            'sort_order'    => t('survey_fields', 'Decide the order of the fields shown in the form.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getRequiredOptionsArray()
    {
        return [
            self::TEXT_YES   => t('app', 'Yes'),
            self::TEXT_NO    => t('app', 'No'),
        ];
    }

    /**
     * @return array
     */
    public function getVisibilityOptionsArray()
    {
        return [
            self::VISIBILITY_VISIBLE  => t('app', 'Visible'),
            self::VISIBILITY_HIDDEN   => t('app', 'Hidden'),
            self::VISIBILITY_NONE     => t('app', 'None'),
        ];
    }

    /**
     * @return array
     */
    public function getSortOrderOptionsArray()
    {
        static $_opts = [];
        if (!empty($_opts)) {
            return $_opts;
        }

        for ($i = -100; $i <= 100; ++$i) {
            $_opts[$i] = $i;
        }

        return $_opts;
    }

    /**
     * @param int $surveyId
     *
     * @return array
     */
    public static function getAllBySurveyId(int $surveyId): array
    {
        static $fields = [];
        if (!isset($fields[$surveyId])) {
            $fields[$surveyId] = [];
            $criteria = new CDbCriteria();
            $criteria->select = 't.field_id, t.label';
            $criteria->compare('t.survey_id', $surveyId);
            $models = self::model()->findAll($criteria);
            foreach ($models as $model) {
                $fields[$surveyId][] = $model->getAttributes(['field_id', 'label']);
            }
        }
        return $fields[$surveyId];
    }

    /**
     * @return string
     */
    public function getTag(): string
    {
        return 'field-' . $this->field_id;
    }

    /**
     * @return bool
     */
    public function getVisibilityIsVisible(): bool
    {
        return (string)$this->visibility === self::VISIBILITY_VISIBLE;
    }

    /**
     * @return bool
     */
    public function getVisibilityIsHidden(): bool
    {
        return (string)$this->visibility === self::VISIBILITY_HIDDEN;
    }

    /**
     * @return bool
     */
    public function getVisibilityIsNone(): bool
    {
        return (string)$this->visibility === self::VISIBILITY_NONE;
    }

    /**
     * @param SurveyResponder|null $responder
     *
     * @return array
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     * @throws CException
     */
    public static function getDefaultValueTags(?SurveyResponder $responder = null): array
    {
        $ip = $userAgent = '';

        if (!is_cli()) {
            $ip        = (string)request()->getUserHostAddress();
            $userAgent = StringHelper::truncateLength((string)request()->getUserAgent(), 255);
        }

        $geoCountry = $geoCity = $geoState = '';
        if (!empty($ip) && ($location = IpLocation::findByIp($ip))) {
            $geoCountry = $location->country_name;
            $geoCity    = $location->city_name;
            $geoState   = $location->zone_name;
        }

        $tags = [
            '[DATETIME]'                       => date('Y-m-d H:i:s'),
            '[DATE]'                           => date('Y-m-d'),
            '[RESPONDER_IP]'                  => $ip,
            '[RESPONDER_USER_AGENT]'          => $userAgent,
            '[RESPONDER_GEO_COUNTRY]'         => $geoCountry,
            '[RESPONDER_GEO_STATE]'           => $geoState,
            '[RESPONDER_GEO_CITY]'            => $geoCity,
        ];

        if (!empty($responder->subscriber_id) && !empty($responder->subscriber)) {
            $subscriberFields = $responder->subscriber->getAllCustomFieldsWithValues();
            foreach ($subscriberFields as $key => $value) {
                $key = str_replace('[', '[SUBSCRIBER_', $key);
                $tags[$key] = $value;
            }
        }

        return (array)hooks()->applyFilters('survey_field_get_default_value_tags', $tags);
    }

    /**
     * @param string $value
     * @param SurveyResponder|null $responder
     *
     * @return string
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public static function parseDefaultValueTags(string $value, ?SurveyResponder $responder = null): string
    {
        if (empty($value)) {
            return $value;
        }
        $tags = self::getDefaultValueTags($responder);

        return (string)str_replace(array_keys($tags), array_values($tags), $value);
    }
}
