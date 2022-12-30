<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaigns_reports_export
 *
 * Handles the actions for exporting campaign reports
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.7.3
 */

if (!class_exists('Campaign_reports_exportController', false) && defined('MW_ROOT_PATH')) {
    require_once MW_ROOT_PATH . '/apps/customer/controllers/Campaign_reports_exportController.php';
}

class Campaigns_reports_exportController extends Campaign_reports_exportController
{
    /**
     * @throws CException
     *
     * @return void
     */
    public function init()
    {
        $campaign_uid = (string)request()->getQuery('campaign_uid', '');
        $session      = session();
        if (!isset($session['campaign_reports_access_' . $campaign_uid])) {
            $this->redirect(['campaigns_reports/login', 'campaign_uid' => $campaign_uid]);
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByUid($campaign_uid);
        if (empty($campaign)) {
            unset($session['campaign_reports_access_' . $campaign_uid]);
            $this->redirect(['campaigns_reports/login', 'campaign_uid' => $campaign_uid]);
            return;
        }
        $this->customerId = (int)$campaign->customer_id;

        parent::init();
    }
}
