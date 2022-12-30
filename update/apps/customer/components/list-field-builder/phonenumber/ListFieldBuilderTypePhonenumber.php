<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldBuilderTypePhonenumber
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.8.1
 */

/**
 * The followings are the available behaviors:
 * @property ListFieldBuilderTypePhonenumberCrud $_crud
 * @property ListFieldBuilderTypePhonenumberSubscriber $_subscriber
 */
class ListFieldBuilderTypePhonenumber extends ListFieldBuilderType
{
    /**
     * @inheritDoc
     */
    public function run()
    {
        /** @var Controller|null $controller */
        $controller = app()->getController();

        // since this is a widget always running inside a controller, there is no reason for this to not be set.
        if (empty($controller)) {
            return;
        }

        /** register assets */
        clientScript()->registerCssFile(apps()->getAppUrl('frontend', 'assets/js/intl-tel-input/css/intlTelInput.css', false, true));
        clientScript()->registerScriptFile(apps()->getAppUrl('frontend', 'assets/js/intl-tel-input/js/intlTelInput-jquery.js', false, true));

        parent::run();
    }
}
