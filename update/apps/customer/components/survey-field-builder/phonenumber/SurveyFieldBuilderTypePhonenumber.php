<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SurveyFieldBuilderTypePhonenumber
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.7.8
 */

/**
 * The followings are the available behaviors:
 * @property SurveyFieldBuilderTypePhonenumberCrud $_crud
 * @property SurveyFieldBuilderTypePhonenumberResponder $_responder
 */
class SurveyFieldBuilderTypePhonenumber extends SurveyFieldBuilderType
{
    /**
     * @return void
     */
    public function run()
    {
        /** @var Controller|null $controller */
        $controller = app()->getController();

        if (empty($controller)) {
            return;
        }

        // register assets
        clientScript()->registerCssFile(apps()->getAppUrl('frontend', 'assets/js/intl-tel-input/css/intlTelInput.css', false, true));
        clientScript()->registerScriptFile(apps()->getAppUrl('frontend', 'assets/js/intl-tel-input/js/intlTelInput-jquery.js', false, true));

        parent::run();
    }
}
