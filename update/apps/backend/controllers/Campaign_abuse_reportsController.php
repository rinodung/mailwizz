<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign_abuse_reportsController
 *
 * Handles the actions for campaign abuse reports tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

class Campaign_abuse_reportsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->addPageScript(['src' => AssetsUrl::js('campaign-abuse-reports.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, blacklist',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all abuse reports for all campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $reports = new CampaignAbuseReport('search');
        $reports->unsetAttributes();
        $reports->attributes = (array)request()->getQuery($reports->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'View campaign abuse reports'),
            'pageHeading'     => t('campaigns', 'View campaign abuse reports'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaign abuse reports'),
            ],
        ]);

        $this->render('index', compact('reports'));
    }

    /**
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($id)
    {
        $report = CampaignAbuseReport::model()->findByPk((int)$id);

        if (empty($report)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $report->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['campaign_abuse_reports/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $report,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Blacklist campaign abuse email
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionBlacklist($id)
    {
        $report = CampaignAbuseReport::model()->findByPk((int)$id);

        if (empty($report)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $reason = t('campaigns', 'Campaign abuse report') . '#' . $report->report_id . ': ' . $report->reason;
        EmailBlacklist::addToBlacklist($report->subscriber_info, $reason);

        $report->addLog(t('campaigns', 'Subscriber email has been blacklisted!'))->save(false);

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('campaigns', 'The email has been successfully blacklisted!'));
            $this->redirect(request()->getPost('returnUrl', ['campaign_abuse_reports/index']));
        }

        $this->renderJson([
            'status'  => 'success',
            'message' => t('campaigns', 'The email has been successfully blacklisted!'),
        ]);
    }

    /**
     * Run a bulk action against the campaign abuse reports
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action()
    {
        $action = request()->getPost('bulk_action');
        $items  = array_unique(array_map('intval', (array)request()->getPost('bulk_item', [])));

        if ($action == CampaignAbuseReport::BULK_ACTION_BLACKLIST && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $report = CampaignAbuseReport::model()->findByPk((int)$item);

                if (empty($report)) {
                    continue;
                }

                $reason = t('campaigns', 'Campaign abuse report') . '#' . $report->report_id . ': ' . $report->reason;
                EmailBlacklist::addToBlacklist($report->subscriber_info, $reason);

                $report->addLog(t('campaigns', 'Subscriber email has been blacklisted!'))->save(false);

                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == CampaignAbuseReport::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                $report = CampaignAbuseReport::model()->findByPk((int)$item);

                if (empty($report)) {
                    continue;
                }

                $report->delete();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', ['campaign_abuse_reports/index']);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }
}
