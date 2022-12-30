<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignsController
 *
 * Handles the actions for campaigns related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CampaignsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->addPageScript(['src' => AssetsUrl::js('campaigns.js')]);
        $this->onBeforeAction = [$this, '_registerJuiBs'];
    }

    /**
     * @return array
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + delete, pause_unpause, resume_sending, approve, disapprove, block_unblock, pause_unpause,
            marksent, resend_giveups, bulk_action, 
            ',
        ], parent::filters());
    }

    /**
     * List available campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $campaign = new Campaign('search');
        $campaign->unsetAttributes();

        // 1.4.4
        $campaign->stickySearchFilters->setStickySearchFilters();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaigns'),
            'pageHeading'     => t('campaigns', 'Campaigns'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('index', compact('campaign'));
    }

    /**
     * List available regular campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionRegular()
    {
        $campaign = new Campaign('search');
        $campaign->unsetAttributes();

        // 1.4.4
        $campaign->stickySearchFilters->setStickySearchFilters();
        $campaign->type = Campaign::TYPE_REGULAR;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaigns') . ' | ' . t('campaigns', 'Regular campaigns'),
            'pageHeading'     => t('campaigns', 'Regular campaigns'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Regular campaigns') => createUrl('campaigns/regular'),
                t('app', 'View all'),
            ],
        ]);

        $this->render($campaign->type, compact('campaign'));
    }

    /**
     * List available autoresponder campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionAutoresponder()
    {
        $campaign = new Campaign('search');
        $campaign->unsetAttributes();
        $campaign->addRelatedRecord('option', new CampaignOption(), false);

        // 1.4.4
        $campaign->stickySearchFilters->setStickySearchFilters();
        $campaign->type = Campaign::TYPE_AUTORESPONDER;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaigns') . ' | ' . t('campaigns', 'Autoresponders'),
            'pageHeading'     => t('campaigns', 'Autoresponders'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Autoresponders') => createUrl('campaigns/autoresponder'),
                t('app', 'View all'),
            ],
        ]);

        $this->render($campaign->type, compact('campaign'));
    }

    /**
     * Show the overview for a campaign
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionOverview($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if (!$campaign->getAccessOverview()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        if ($campaign->getIsRecurring()) {
            $cron = new JQCron($campaign->getRecurringCronjob());
            $this->setData('recurringInfo', $cron->getText(LanguageHelper::getAppLanguageCode()));
        }

        $this->addPageStyle(['src' => apps()->getBaseUrl('assets/css/placeholder-loading.css')]);

        // since 1.3.5.9
        if ($campaign->getIsBlocked() && !empty($campaign->option->blocked_reason)) {
            $message = [];
            $message[] = t('campaigns', 'This campaign is blocked because following reasons:');
            $reasons = explode('|', $campaign->option->blocked_reason);
            foreach ($reasons as $reason) {
                $message[] = t('campaigns', $reason);
            }
            $message[] = CHtml::link(t('campaigns', 'Click here to unblock it!'), createUrl('campaigns/block_unblock', ['campaign_uid' => $campaign_uid]));
            notify()->addInfo($message);
        }
        //

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);
        $webVersionUrl = $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaign overview'),
            'pageHeading'     => t('campaigns', 'Campaign overview'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                $campaign->name . ' ' => createUrl('campaigns/overview', ['campaign_uid' => $campaign_uid]),
                t('campaigns', 'Overview'),
            ],
        ]);

        $this->render('overview', compact('campaign', 'webVersionUrl'));
    }

    /**
     * Delete campaign, will remove all campaign related data
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if ($campaign->getRemovable()) {
            $campaign->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['campaigns/' . $campaign->type]);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $campaign,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Allows to approve a campaign
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionApprove($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if ($campaign->getCanBeApproved()) {
            $campaign->saveStatus(Campaign::STATUS_PENDING_SENDING);

            /** @var OptionCommon $common */
            $common = container()->get(OptionCommon::class);

            /** @var OptionUrl $url */
            $url = container()->get(OptionUrl::class);

            $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                'campaign-pending-approval-approved',
                [
                    'subject' => t('campaigns', 'Your campaign has been approved!'),
                ],
                [
                    '[CAMPAIGN_OVERVIEW_URL]' => $url->getCustomerUrl('campaigns/' . $campaign->campaign_uid . '/overview'),
                ]
            );

            $email = new TransactionalEmail();
            $email->sendDirectly = false;
            $email->to_name      = $campaign->customer->getFullName();
            $email->to_email     = $campaign->customer->email;
            $email->from_name    = $common->getSiteName();
            $email->subject      = $params['subject'];
            $email->body         = $params['body'];
            $email->save();
        }

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * Allows to disapprove a campaign
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionDisapprove($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if ($campaign->getCanBeApproved()) {
            $campaign->saveStatus(Campaign::STATUS_DRAFT);

            /** @var OptionCommon $common */
            $common = container()->get(OptionCommon::class);

            /** @var OptionUrl $url */
            $url = container()->get(OptionUrl::class);

            $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                'campaign-pending-approval-disapproved',
                [
                    'subject' => t('campaigns', 'Your campaign has been disapproved!'),
                ],
                [
                    '[CAMPAIGN_OVERVIEW_URL]' => $url->getCustomerUrl('campaigns/' . $campaign->campaign_uid . '/overview'),
                    '[DISAPPROVED_MESSAGE]'   => nl2br(html_encode((string)request()->getPost('message', ''))),
                ]
            );

            $email = new TransactionalEmail();
            $email->sendDirectly = false;
            $email->to_name      = $campaign->customer->getFullName();
            $email->to_email     = $campaign->customer->email;
            $email->from_name    = $common->getSiteName();
            $email->subject      = $params['subject'];
            $email->body         = $params['body'];
            $email->save();
        }

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * Allows to block/unblock a campaign
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionBlock_unblock($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        $campaign->blockUnblock();

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * Allows to pause/unpause the sending of a campaign
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionPause_unpause($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        $campaign->pauseUnpause();

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * Allows to resume sending of a stuck campaign
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionResume_sending($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if ($campaign->getIsProcessing()) {
            $campaign->saveStatus(Campaign::STATUS_SENDING);
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * Allows to mark a campaign as sent
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionMarksent($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);
        $campaign->markAsSent();

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('campaigns', 'Your campaign was successfully changed!'));
            $this->redirect(request()->getPost('returnUrl', ['campaigns/' . $campaign->type]));
        }
    }

    /**
     * Allows to resend the giveups for a campaign
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionResend_giveups($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if (!request()->getIsAjaxRequest() || !request()->getIsPostRequest()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        if (!$campaign->getIsSent()) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'Resending to giveups only works for sent campaigns!'),
            ]);
        }

        if (empty($campaign->option->giveup_count)) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'It seems this campaign has no giveups!'),
            ]);
        }

        $queued = CampaignResendGiveupQueue::model()->countByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);

        if ($queued) {
            $this->renderJson([
                'result'  => 'error',
                'message' =>  t('campaigns', 'It seems this campaign has already been queued to resend to giveups!'),
            ]);
        }

        $queue = new CampaignResendGiveupQueue();
        $queue->campaign_id = (int)$campaign->campaign_id;
        $queue->save(false);

        $this->renderJson([
            'result'  => 'success',
            'message' =>  t('campaigns', 'The campaigns has been queued successfully, it will start sending in a few minutes!'),
        ]);
    }

    /**
     * Run a bulk action against the campaigns
     *
     * @param string $type
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionBulk_action($type = '')
    {
        $action = request()->getPost('bulk_action');
        /** @var string[] $items */
        $items = array_unique((array)request()->getPost('bulk_item', []));

        $returnRoute = ['campaigns/index'];
        $campaign = new Campaign();
        if (in_array($type, $campaign->getTypesList())) {
            $returnRoute = ['campaigns/' . $type];
        }

        if ($action == Campaign::BULK_ACTION_DELETE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                /** @var Campaign|null $campaign */
                $campaign = $this->loadCampaignByUid($item);

                if (empty($campaign)) {
                    continue;
                }

                if (!$campaign->getRemovable()) {
                    continue;
                }

                $campaign->delete();
                $affected++;

                /** @var Customer $customer */
                $customer = $campaign->customer;

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->campaignDeleted($campaign);
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_COPY && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid($item))) {
                    continue;
                }
                $customer = $campaign->customer;
                if (($maxCampaigns = (int)$customer->getGroupOption('campaigns.max_campaigns', -1)) > -1) {
                    $criteria = new CDbCriteria();
                    $criteria->compare('customer_id', (int)$customer->customer_id);
                    $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);
                    $campaignsCount = Campaign::model()->count($criteria);
                    if ($campaignsCount >= $maxCampaigns) {
                        continue;
                    }
                }
                if (!$campaign->copy()) {
                    continue;
                }
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_PAUSE_UNPAUSE && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid($item))) {
                    continue;
                }
                $campaign->pauseUnpause();
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_MARK_SENT && count($items)) {
            $affected = 0;
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid($item))) {
                    continue;
                }
                if (!$campaign->markAsSent()) {
                    continue;
                }
                $affected++;
            }
            if ($affected) {
                notify()->addSuccess(t('app', 'The action has been successfully completed!'));
            }
        } elseif ($action == Campaign::BULK_ACTION_SHARE_CAMPAIGN_CODE && count($items)) {
            $affected     = 0;
            $success      = false;
            $campaignsIds = [];

            // Collect the campaign ids
            foreach ($items as $item) {
                if (!($campaign = $this->loadCampaignByUid($item))) {
                    continue;
                }
                $campaignsIds[] = (int)$campaign->campaign_id;
            }

            /** @var CampaignShareCode $campaignShareCode */
            $campaignShareCode = new CampaignShareCode();

            if (!empty($campaignsIds)) {
                $transaction = db()->beginTransaction();

                try {
                    if (!$campaignShareCode->save()) {
                        throw new Exception(t('campaigns', 'Could not save the sharing code'));
                    }

                    foreach ($campaignsIds as $campaignId) {
                        $campaignShareCodeToCampaign              = new CampaignShareCodeToCampaign();
                        $campaignShareCodeToCampaign->code_id     = (int)$campaignShareCode->code_id;
                        $campaignShareCodeToCampaign->campaign_id = (int)$campaignId;

                        if (!$campaignShareCodeToCampaign->save()) {
                            throw new Exception(t('campaigns', 'Could not save the sharing code to campaign'));
                        }

                        $affected++;
                    }

                    $transaction->commit();
                    $success = true;
                } catch (Exception $e) {
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                    $transaction->rollback();
                }
            }

            if ($success) {
                notify()->addSuccess(t('campaigns', 'The sharing code is: {code}', [
                    '{code}' => sprintf('<strong>%s</strong>', $campaignShareCode->code_uid),
                ]));
            }
        }

        $defaultReturn = request()->getServer('HTTP_REFERER', $returnRoute);
        $this->redirect(request()->getPost('returnUrl', $defaultReturn));
    }

    /**
     * Quick view campaign details
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionQuick_view($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel($campaign_uid);

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['campaigns/' . $campaign->type]);
        }

        $abTest = null;
        if ($campaign->getCanDoAbTest()) {
            $abTest = CampaignAbtest::model()->findByAttributes([
                'campaign_id'   => $campaign->campaign_id,
                'enabled'       => CampaignAbtest::TEXT_YES,
                'status'        => [CampaignAbtest::STATUS_ACTIVE, CampaignAbtest::STATUS_COMPLETE],
            ]);
        }

        $this->renderPartial('_quick-view', compact('campaign', 'abTest'));
    }

    /**
     * Compare campaigns
     *
     * @return void
     * @throws CException
     */
    public function actionCompare()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['campaigns/index']);
        }

        $items = array_unique((array)request()->getPost('bulk_item', []));
        $items = array_slice($items, 0, 5);

        $criteria = new CDbCriteria();
        $criteria->addInCondition('campaign_uid', $items);
        $criteria->order = 'campaign_id ASC';
        $campaigns = Campaign::model()->findAll($criteria);

        $this->renderPartial('_compare', compact('campaigns'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign|null
     * @throws CHttpException
     */
    public function loadCampaignModel(string $campaign_uid): ?Campaign
    {
        /** @var Campaign|null $model */
        $model = $this->loadCampaignByUid($campaign_uid);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($model->getIsPendingDelete()) {
            $this->redirect(['campaigns/' . $model->type]);
        }

        return $model;
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign|null
     */
    public function loadCampaignByUid(string $campaign_uid): ?Campaign
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_uid', $campaign_uid);
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE]);

        /** @var Campaign|null $model */
        $model = Campaign::model()->find($criteria);

        return $model;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['index'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
