<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldsStatsTypeBaseWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

abstract class SurveyFieldsStatsTypeBaseWidget extends CWidget
{
    /**
     * @var Survey|null
     */
    public $survey;

    /**
     * @var SurveyField|null
     */
    public $field;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        if (empty($this->survey) || empty($this->field)) {
            return;
        }

        $field     = $this->field;
        $chartData = $this->getData();

        if (empty($chartData)) {
            return;
        }

        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/flot/jquery.flot.pie.min.js'));
        clientScript()->registerScriptFile(apps()->getBaseUrl('assets/js/survey-fields-stats.js'));

        $viewName = 'field-type';

        if (is_file(dirname(__FILE__) . '/views/field-type-' . $field->type->identifier . '.php')) {
            $viewName = 'field-type-' . $field->type->identifier;
        }

        $this->render($viewName, compact('field', 'chartData'));
    }

    /**
     * @return array
     */
    abstract protected function getData(): array;
}
