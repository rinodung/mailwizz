<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * MaxmindController
 *
 * Handles the actions for maxmind related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */

class MaxmindController extends Controller
{
    /**
     * Maxmind DB info
     *
     * @return void
     */
    public function actionIndex()
    {
        $model = new MaxmindDatabase();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('ip_location', 'MaxMind.com database'),
            'pageHeading'     => t('ip_location', 'MaxMind.com database'),
            'pageBreadcrumbs' => [
                t('ip_location', 'MaxMind.com database'),
            ],
        ]);

        MaxmindDatabase::addNotifyErrorIfMissingDbFile();

        $this->render('index', compact('model'));
    }
}
