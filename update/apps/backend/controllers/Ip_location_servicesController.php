<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Ip_location_servicesController
 *
 * Handles the actions for ip location services related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.2
 */

class Ip_location_servicesController extends Controller
{
    /**
     * Display available services
     *
     * @return void
     */
    public function actionIndex()
    {
        $model = new IpLocationServicesList();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('ip_location', 'Ip location services'),
            'pageHeading'     => t('ip_location', 'Ip location services'),
            'pageBreadcrumbs' => [
                t('ip_location', 'Ip location services'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
