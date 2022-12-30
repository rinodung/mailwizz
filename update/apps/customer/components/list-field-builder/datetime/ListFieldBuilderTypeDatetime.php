<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldBuilderTypeDatetime
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
 * @property ListFieldBuilderTypeDatetimeCrud $_crud
 * @property ListFieldBuilderTypeDatetimeSubscriber $_subscriber
 */
class ListFieldBuilderTypeDatetime extends ListFieldBuilderType
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
        clientScript()->registerCssFile(apps()->getAppUrl('frontend', 'assets/js/datetimepicker/css/bootstrap-datetimepicker.min.css', false, true));
        clientScript()->registerScriptFile(apps()->getAppUrl('frontend', 'assets/js/datetimepicker/js/bootstrap-datetimepicker.min.js', false, true));

        /** @var array $languagesPaths */
        $languagesPaths = [
            (string)Yii::getPathOfAlias('root.assets.js.datetimepicker.js.locales.bootstrap-datetimepicker'),
        ];

        if ($language = $this->detectLanguage($languagesPaths)) {
            clientScript()->registerScriptFile(apps()->getAppUrl('frontend', 'assets/js/datetimepicker/js/locales/bootstrap-datetimepicker.' . $language . '.js', false, true));
        }

        parent::run();
    }
}
