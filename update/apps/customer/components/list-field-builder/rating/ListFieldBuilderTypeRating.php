<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListFieldBuilderTypeRating
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
 * @property ListFieldBuilderTypeRatingCrud $_crud
 * @property ListFieldBuilderTypeRatingSubscriber $_subscriber
 */
class ListFieldBuilderTypeRating extends ListFieldBuilderType
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

        clientScript()->registerScriptFile(apps()->getAppUrl('frontend', 'assets/js/bootstrap-rating-input/bootstrap-rating-input.min.js', false, true));

        parent::run();
    }
}
