<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

if (!class_exists('SurveyFieldsStatsTypeCheckboxlistWidget', false)) {
    require_once dirname(__FILE__) . '/SurveyFieldsStatsTypeCheckboxlistWidget.php';
}

/**
 * SurveyFieldsStatsTypeDropdownWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

class SurveyFieldsStatsTypeDropdownWidget extends SurveyFieldsStatsTypeCheckboxlistWidget
{
}
