<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaigns_abuse_complaintsController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @version 1.0
 * @since 1.9.19
 */

class Campaigns_abuse_complaintsController extends Controller
{
    /**
     * List all campaign abuse complaints
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $model = new CampaignComplainLog('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        /** @var Customer $customer */
        $customer = customer()->getModel();
        if (is_subaccount()) {
            /** @var Customer $customer */
            $customer = subaccount()->customer();
        }

        $model->customer_id = (int)$customer->customer_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Abuse complaints'),
            'pageHeading'     => t('campaigns', 'Abuse complaints'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Abuse complaints') => createUrl('campaigns_abuse_complaints/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('model'));
    }
}
