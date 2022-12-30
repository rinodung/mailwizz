<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Payment_gatewaysController
 *
 * Handles the actions for payment gateways related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

class Payment_gatewaysController extends Controller
{
    /**
     * Display available gateways
     *
     * @return void
     */
    public function actionIndex()
    {
        $model = new PaymentGatewaysList();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('payment_gateways', 'Payment gateways'),
            'pageHeading'     => t('payment_gateways', 'Payment gateways'),
            'pageBreadcrumbs' => [
                t('payment_gateways', 'Payment gateways'),
            ],
        ]);

        $this->render('index', compact('model'));
    }
}
