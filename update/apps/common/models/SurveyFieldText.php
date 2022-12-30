<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldText
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * Class SurveyFieldText
 */
class SurveyFieldText extends SurveyField
{
    /**
     * @var int
     */
    public $min_length = 1;

    /**
     * @var int
     */
    public $max_length = 255;

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['min_length, max_length', 'required'],
            ['min_length, max_length', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => 255],
            ['min_length', 'compare', 'compareAttribute' => 'max_length', 'operator' => '<'],
            ['max_length', 'compare', 'compareAttribute' => 'min_length', 'operator' => '>'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'min_length' => t('survey_fields', 'Minimum length'),
            'max_length' => t('survey_fields', 'Maximum length'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return SurveyFieldText the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var SurveyFieldText $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'min_length' => t('survey_fields', 'Minimum length of the text'),
            'max_length' => t('survey_fields', 'Maximum length of the text'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->modelMetaData->getModelMetaData()->add('min_length', (int)$this->min_length);
        $this->modelMetaData->getModelMetaData()->add('max_length', (int)$this->max_length);
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        $this->min_length = (int)$this->modelMetaData->getModelMetaData()->itemAt('min_length');
        $this->max_length = (int)$this->modelMetaData->getModelMetaData()->itemAt('max_length');
        parent::afterFind();
    }
}
