<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldsStatsWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

class SurveyFieldsStatsWidget extends CWidget
{
    /**
     * @var Survey
     */
    public $survey;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $survey = $this->survey;
        $criteria = new CDbCriteria();
        $criteria->compare('survey_id', $survey->survey_id);
        $criteria->order = 'sort_order ASC';
        $fields = SurveyField::model()->findAll($criteria);

        foreach ($fields as $field) {
            $className  = 'SurveyFieldsStatsType' . ucfirst($field->type->identifier);
            $classAlias = 'customer.components.web.widgets.survey-fields-stats.' . $className . 'Widget';

            if (!is_file((string)Yii::getPathOfAlias($classAlias) . '.php')) {
                continue;
            }

            $this->getController()->widget($classAlias, [
                'survey' => $survey,
                'field'  => $field,
            ]);
        }
    }
}
