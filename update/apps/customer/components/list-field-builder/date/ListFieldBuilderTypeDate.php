<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldBuilderTypeDate
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

/**
 * The followings are the available behaviors:
 * @property ListFieldBuilderTypeDateCrud $_crud
 * @property ListFieldBuilderTypeDateSubscriber $_subscriber
 */
class ListFieldBuilderTypeDate extends ListFieldBuilderType
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
        clientScript()->registerCssFile(apps()->getAppUrl('frontend', 'assets/js/datepicker/css/datepicker3.css', false, true));
        clientScript()->registerScriptFile(apps()->getAppUrl('frontend', 'assets/js/datepicker/js/bootstrap-datepicker.js', false, true));

        /** @var array $languagesPaths */
        $languagesPaths = [
            (string)Yii::getPathOfAlias('root.assets.js.datepicker.js.locales.bootstrap-datepicker'),
        ];

        if ($language = $this->detectLanguage($languagesPaths)) {
            clientScript()->registerScriptFile(apps()->getAppUrl('frontend', 'assets/js/datepicker/js/locales/bootstrap-datepicker.' . $language . '.js', false, true));
        }

        parent::run();
    }
}
