<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldNumber
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * Class SurveyFieldNumber
 */
class SurveyFieldNumber extends SurveyField
{
    /**
     * Flag for integer and float
     */
    const VALUE_TYPE_INTEGER_AND_FLOAT = 0;

    /**
     * Flag for integer only
     */
    const VALUE_TYPE_INTEGER_ONLY = 1;

    /**
     * Scenario for integer and float
     */
    const SCENARIO_INTEGER_AND_FLOAT = 'integer-and-float';

    /**
     * Scenario for integer only
     */
    const SCENARIO_INTEGER_ONLY = 'integer-only';

    /**
     * Max int and float value
     */
    const MAX_VALUE = 99999999;

    /**
     * @var int
     */
    public $min_value = 1;

    /**
     * @var int
     */
    public $max_value = self::MAX_VALUE;

    /**
     * @var int
     */
    public $step_size = 1;

    /**
     * @var bool
     */
    public $integer_only = false;

    /**
     * @return void
     */
    public function init()
    {
        $this->setScenario(self::SCENARIO_INTEGER_AND_FLOAT);
        if ($this->integer_only) {
            $this->setScenario(self::SCENARIO_INTEGER_ONLY);
        }

        parent::init();
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['min_value, max_value, step_size, integer_only', 'required'],
            ['integer_only', 'in', 'range' => array_keys($this->getValuesTypeList())],
            ['min_value, max_value, step_size', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => self::MAX_VALUE, 'on' => self::SCENARIO_INTEGER_ONLY],
            ['min_value, max_value, step_size', 'numerical', 'min' => 1, 'max' => self::MAX_VALUE, 'on' => self::SCENARIO_INTEGER_AND_FLOAT],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'min_value'    => t('survey_fields', 'Minimum value'),
            'max_value'    => t('survey_fields', 'Maximum value'),
            'step_size'    => t('survey_fields', 'Step size'),
            'integer_only' => t('survey_fields', 'Value type'),
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
            'min_value' => t('survey_fields', 'Minimum value of the number input'),
            'max_value' => t('survey_fields', 'Maximum value of the number input'),
            'step_size' => t('survey_fields', 'Step size of the number input'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function getValuesTypeList(): array
    {
        return [
            self::VALUE_TYPE_INTEGER_AND_FLOAT => t('survey_fields', 'Integer and float'),
            self::VALUE_TYPE_INTEGER_ONLY      => t('survey_fields', 'Integer only'),
        ];
    }

    /**
     * @return float|int
     */
    public function getMinValue()
    {
        return $this->integer_only ? (int)$this->min_value : (float)$this->min_value;
    }

    /**
     * @return float|int
     */
    public function getMaxValue()
    {
        return $this->integer_only ? (int)$this->max_value : (float)$this->max_value;
    }

    /**
     * @return int|string
     */
    public function getStepSize()
    {
        return $this->integer_only ? (int)$this->step_size : 'any';
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('min_value', $this->getMinValue());
        $this->modelMetaData->getModelMetaData()->add('max_value', $this->getMaxValue());
        $this->modelMetaData->getModelMetaData()->add('step_size', $this->step_size);
        $this->modelMetaData->getModelMetaData()->add('integer_only', (bool)$this->integer_only);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->min_value    = $this->modelMetaData->getModelMetaData()->itemAt('min_value');
        $this->max_value    = $this->modelMetaData->getModelMetaData()->itemAt('max_value');
        $this->step_size    = $this->modelMetaData->getModelMetaData()->itemAt('step_size');
        $this->integer_only = (bool)$this->modelMetaData->getModelMetaData()->itemAt('integer_only');

        parent::afterFind();
    }
}
