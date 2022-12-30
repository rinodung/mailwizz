<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign_overview_widgetsController
 *
 * Handles the actions that fetch the widgets for the campaigns overview page
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.4
 */

class Campaign_overview_widgetsController extends Controller
{

    /**
     * Get the campaigns overview html for the "overview" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionIndex($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.campaign-tracking.CampaignOverviewWidget', [
                'campaign' => $campaign,
            ], true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "counter boxes" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionCounter_boxes($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.campaign-tracking.CampaignOverviewCounterBoxesWidget', [
                'campaign' => $campaign,
            ], true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "rates boxes" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionRate_boxes($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.campaign-tracking.CampaignOverviewRateBoxesWidget', [
                'campaign' => $campaign,
            ], true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "daily performance graph" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionDaily_performance($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->renderPartial('common.views.campaign_overview_widgets._daily-performance', [
                'campaign' => $campaign,
            ], true, true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "top domains opens and clicks graph" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionTop_domains_opens_clicks_graph($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->renderPartial('common.views.campaign_overview_widgets._top-domains-opens-clicks-graph', [
                'campaign' => $campaign,
            ], true, true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "geo opens graph" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionGeo_opens($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->renderPartial('common.views.campaign_overview_widgets._geo-opens', [
                'campaign' => $campaign,
            ], true, true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "open user agents graph" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionOpen_user_agents($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->renderPartial('common.views.campaign_overview_widgets._open-user-agents', [
                'campaign' => $campaign,
            ], true, true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "tracking top clicked links box" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionTracking_top_clicked_links($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.campaign-tracking.CampaignTrackingTopClickedLinksWidget', [
                'campaign'        => $campaign,
                'showDetailLinks' => false,
            ], true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "tracking latest clicked links box" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionTracking_latest_clicked_links($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.campaign-tracking.CampaignTrackingLatestClickedLinksWidget', [
                'campaign'        => $campaign,
                'showDetailLinks' => false,
            ], true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "tracking subscribers with most opens box" widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionTracking_subscribers_with_most_opens($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.campaign-tracking.CampaignTrackingSubscribersWithMostOpensWidget', [
                'campaign'        => $campaign,
                'showDetailLinks' => false,
            ], true),
        ]);
    }

    /**
     * Get the campaigns overview html for the "tracking latest opens" box widget
     *
     * @param string $campaign_uid
     * @return void
     * @throws CException
     */
    public function actionTracking_latest_opens($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'html' => '',
            ]);
            return;
        }

        /**
         * Make sure the campaign is available on the controller in case it is
         * checked and accessed during request processing
         */
        $this->setData('campaign', $campaign);

        $this->renderJson([
            'html' => $this->widget('customer.components.web.widgets.campaign-tracking.CampaignTrackingLatestOpensWidget', [
                'campaign'        => $campaign,
                'showDetailLinks' => false,
            ], true),
        ]);
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
     * @param CAction $action
     *
     * @return bool
     * @throws CException
     */
    protected function beforeAction($action)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return false;
        }

        return parent::beforeAction($action);
    }
}
