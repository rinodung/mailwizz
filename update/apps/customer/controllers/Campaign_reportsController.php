<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign_reportsController
 *
 * Handles the actions for campaign reports related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class Campaign_reportsController extends Controller
{
    /**
     * @var array
     */
    public $localPageScripts = [
        'campaign-reports.js',
    ];

    /**
     * @var array
     */
    public $actionView = [
        'delivery'                      => 'customer.views.campaign_reports.delivery',
        'bounce'                        => 'customer.views.campaign_reports.bounce',
        'open'                          => 'customer.views.campaign_reports.open',
        'open_unique'                   => 'customer.views.campaign_reports.open-unique',
        'open_by_subscriber'            => 'customer.views.campaign_reports.open-by-subscriber',
        'click'                         => 'customer.views.campaign_reports.click',
        'click_url'                     => 'customer.views.campaign_reports.click-url',
        'click_by_subscriber'           => 'customer.views.campaign_reports.click-by-subscriber',
        'click_by_subscriber_unique'    => 'customer.views.campaign_reports.click-by-subscriber-unique',
        'unsubscribe'                   => 'customer.views.campaign_reports.unsubscribe',
        'complain'                      => 'customer.views.campaign_reports.complain',
        'forward_friend'                => 'customer.views.campaign_reports.forward-friend',
        'abuse_reports'                 => 'customer.views.campaign_reports.abuse-reports',
    ];

    /**
     * @var string
     */
    public $campaignOverviewRoute = 'campaigns/overview';

    /**
     * @var string
     */
    public $campaignReportsController = 'campaign_reports';

    /**
     * @var string
     */
    public $campaignReportsExportController = 'campaign_reports_export';

    /**
     * @var string
     */
    public $campaignsListRoute = 'campaigns/index';

    /**
     * @var string
     */
    protected $campaignsListUrl = 'javascript:;';

    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        foreach ($this->localPageScripts as $script) {
            $this->addPageScript(['src' => AssetsUrl::js($script)]);
        }

        if (!empty($this->campaignsListRoute)) {
            $this->campaignsListUrl = createUrl($this->campaignsListRoute);
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            $this->redirect(['dashboard/index']);
        }

        parent::init();
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + delete_opens, delete_clicks',
        ], parent::filters());
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelivery($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // 1.4.4
        if ($campaign->option->processed_count >= 0) {
            notify()->addInfo(t('campaigns', 'Delivery report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $className = $campaign->getDeliveryLogsArchived() ? CampaignDeliveryLogArchive::class : CampaignDeliveryLog::class;
        $deliveryLogs = new $className('customer-search');
        $deliveryLogs->unsetAttributes();
        $deliveryLogs->attributes   = (array)request()->getQuery($deliveryLogs->getModelName(), []);
        $deliveryLogs->campaign_id  = (int)$campaign->campaign_id;

        $subscriber  = new ListSubscriber();
        $bulkActions = $subscriber->getBulkActionsList();
        foreach ($bulkActions as $value => $name) {
            if (!empty($value) && $value != ListSubscriber::BULK_DELETE) {
                unset($bulkActions[$value]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Sent emails report'),
            'pageHeading'     => t('campaign_reports', 'Sent emails report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Sent emails report'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render($this->actionView['delivery'], compact('campaign', 'deliveryLogs', 'bulkActions'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionBounce($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // 1.7.9
        if ($campaign->option->bounces_count >= 0) {
            notify()->addInfo(t('campaigns', 'Bounce report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $bounceLogs = new CampaignBounceLog('customer-search');
        $bounceLogs->unsetAttributes();
        $bounceLogs->attributes     = (array)request()->getQuery($bounceLogs->getModelName(), []);
        $bounceLogs->campaign_id    = (int)$campaign->campaign_id;

        $subscriber  = new ListSubscriber();
        $bulkActions = $subscriber->getBulkActionsList();
        foreach ($bulkActions as $value => $name) {
            if (!empty($value) && $value != ListSubscriber::BULK_DELETE) {
                unset($bulkActions[$value]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Bounce report'),
            'pageHeading'     => t('campaign_reports', 'Bounce report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Bounce report'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render($this->actionView['bounce'], compact('campaign', 'bounceLogs', 'bulkActions'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionOpen($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // 1.7.9
        if ($campaign->option->opens_count >= 0) {
            notify()->addInfo(t('campaigns', 'Open report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $model = new CampaignTrackOpen('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);
        $criteria->compare('t.date_added', $model->date_added);
        $criteria->order = 't.id DESC';

        if ($countryCode = request()->getQuery('country_code')) {
            $criteria->with['ipLocation'] = [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ];
            $criteria->compare('ipLocation.country_code', $countryCode);
        }

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Opens report'),
            'pageHeading'     => t('campaign_reports', 'Opens report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Opens report'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        // 1.7.3
        $this->setData('canDeleteStats', true);

        $this->render($this->actionView['open'], compact('campaign', 'model', 'dataProvider'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionOpen_unique($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // 1.7.9
        if ($campaign->option->opens_count >= 0) {
            notify()->addInfo(t('campaigns', 'Open report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $model = new CampaignTrackOpen('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->select = 't.*, COUNT(*) AS counter';
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);
        $criteria->compare('t.date_added', $model->date_added);
        $criteria->group = 't.subscriber_id';
        $criteria->order = 'counter DESC';

        if ($countryCode = request()->getQuery('country_code')) {
            $criteria->with['ipLocation'] = [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ];
            $criteria->compare('ipLocation.country_code', $countryCode);
        }

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Unique opens report'),
            'pageHeading'     => t('campaign_reports', 'Unique opens report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Unique opens report'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        // 1.7.3
        $this->setData('canDeleteStats', true);

        $this->render($this->actionView['open_unique'], compact('campaign', 'model', 'dataProvider'));
    }

    /**
     * @param string $campaign_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionOpen_by_subscriber($campaign_uid, $subscriber_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$campaign->list->list_id, (string)$subscriber_uid);

        // 1.7.9
        if ($campaign->option->opens_count >= 0) {
            notify()->addInfo(t('campaigns', 'Open report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $model = new CampaignTrackOpen();
        $model->unsetAttributes();

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', (int)$campaign->campaign_id);
        $criteria->compare('subscriber_id', (int)$subscriber->subscriber_id);
        $criteria->order = 'id DESC';

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Opens report by subscriber'),
            'pageHeading'     => t('campaign_reports', 'Opens report by subscriber'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Opens report by subscriber'),
            ],
        ]);

        $this->render($this->actionView['open_by_subscriber'], compact('campaign', 'subscriber', 'model', 'dataProvider'));
    }

    /**
     * Delete campaign opens
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete_opens($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // run the delete action
        CampaignTrackOpen::model()->deleteAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);

        $campaign->getStats()->deleteOpensCountCache();
        $campaign->getStats()->deleteUniqueOpensCountCache();

        notify()->addSuccess(t('campaign_reports', 'Your reports were successfully deleted!'));

        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            $redirect = request()->getPost('returnUrl', ['campaign_reports/open_unique', 'campaign_uid' => $campaign->campaign_uid]);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller'    => $this,
            'campaign'      => $campaign,
            'redirect'      => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param string $campaign_uid
     * @param mixed $show
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionClick($campaign_uid, $show = null)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // 1.7.9
        if ($campaign->option->clicks_count >= 0) {
            notify()->addInfo(t('campaigns', 'Click report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        if ($campaign->option->url_tracking != CampaignOption::TEXT_YES) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $showActions = ['latest', 'top'];
        if (!empty($show) && !in_array($show, $showActions)) {
            $show = null;
        }

        $model = new CampaignUrl();
        $model->unsetAttributes();

        $subSelect = '(SELECT COUNT(*) FROM {{campaign_track_url}} WHERE url_id = t.url_id)';
        $params    = [];

        $clickStartDate = (string)request()->getQuery('click_start_date', '');
        if (!empty($clickStartDate)) {
            $subSelect = '(SELECT COUNT(*) FROM {{campaign_track_url}} WHERE url_id = t.url_id AND date_added >= :da)';
            $params[':da'] = $clickStartDate;
        }

        $criteria = new CDbCriteria();
        $criteria->params = $params;
        $criteria->select = sprintf('t.*, %s AS counter', $subSelect);
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);

        if ($show == 'latest' || $show == 'top') {
            $criteria->addCondition(sprintf('%s > 0', $subSelect));
        }

        if ($show == 'latest') {
            $criteria->order = 't.date_added DESC';
        } else {
            $criteria->order = 'counter DESC';
        }

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $heading = t('campaign_reports', 'Clicks report');
        if ($show == 'top') {
            $heading = t('campaign_reports', 'Top clicks report');
        } elseif ($show == 'latest') {
            $heading = t('campaign_reports', 'Latest clicks report');
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $heading,
            'pageHeading'     => $heading,
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                $heading,
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        // 1.7.3
        $this->setData('canDeleteStats', true);

        $this->render($this->actionView['click'], compact('campaign', 'model', 'dataProvider', 'show', 'clickStartDate'));
    }

    /**
     * @param string $campaign_uid
     * @param int $url_id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionClick_url($campaign_uid, $url_id)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // 1.7.9
        if ($campaign->option->clicks_count >= 0) {
            notify()->addInfo(t('campaigns', 'Click report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        if ($campaign->option->url_tracking != CampaignOption::TEXT_YES) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        $url = $this->loadUrlModel((int)$campaign->campaign_id, (int)$url_id);

        $model = new CampaignTrackUrl('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $criteria = new CDbCriteria();
        $criteria->select = 't.*, COUNT(*) AS counter';
        $criteria->compare('t.url_id', (int)$url->url_id);
        $criteria->compare('t.date_added', $model->date_added);
        $criteria->with = [
            'subscriber' => [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ],
        ];
        $criteria->order = 'counter DESC';
        $criteria->group = 't.subscriber_id';

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Url clicks report'),
            'pageHeading'     => t('campaign_reports', 'Url clicks report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Url clicks report'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render($this->actionView['click_url'], compact('campaign', 'url', 'model', 'dataProvider'));
    }

    /**
     * @param string $campaign_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionClick_by_subscriber($campaign_uid, $subscriber_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // 1.7.9
        if ($campaign->option->clicks_count >= 0) {
            notify()->addInfo(t('campaigns', 'Click report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        if ($campaign->option->url_tracking != CampaignOption::TEXT_YES) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$campaign->list->list_id, (string)$subscriber_uid);

        $model = new CampaignTrackUrl();
        $model->unsetAttributes();

        $criteria = new CDbCriteria();
        $criteria->compare('t.subscriber_id', (int)$subscriber->subscriber_id);

        $criteria->with = [
            'url' => [
                'select'    => 'url.url_id, url.destination',
                'together'    => true,
                'joinType'    => 'INNER JOIN',
                'condition'    => 'url.campaign_id = :cid',
                'params'    => [':cid' => $campaign->campaign_id],
            ],
        ];
        $criteria->order = 't.id DESC';

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Clicks report by subscriber'),
            'pageHeading'     => t('campaign_reports', 'Clicks report by subscriber'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Clicks report by subscriber'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render($this->actionView['click_by_subscriber'], compact('campaign', 'subscriber', 'model', 'dataProvider'));
    }

    /**
     * @param string $campaign_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionClick_by_subscriber_unique($campaign_uid, $subscriber_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // 1.7.9
        if ($campaign->option->clicks_count >= 0) {
            notify()->addInfo(t('campaigns', 'Click report stats are not available for this campaign!'));
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        if ($campaign->option->url_tracking != CampaignOption::TEXT_YES) {
            $this->redirect(['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
        }

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$campaign->list->list_id, (string)$subscriber_uid);

        $model = new CampaignTrackUrl();
        $model->unsetAttributes();

        $criteria = new CDbCriteria();
        $criteria->select = 't.*, COUNT(*) AS counter';
        $criteria->compare('t.subscriber_id', (int)$subscriber->subscriber_id);

        $criteria->with = [
            'url' => [
                'select'    => 'url.url_id, url.destination',
                'together'  => true,
                'joinType'  => 'INNER JOIN',
                'condition' => 'url.campaign_id = :cid',
                'params'    => [':cid' => $campaign->campaign_id],
            ],
        ];
        $criteria->group = 't.url_id';
        $criteria->order = 'counter DESC';

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Clicks report by subscriber'),
            'pageHeading'     => t('campaign_reports', 'Clicks report by subscriber'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Clicks report by subscriber'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render($this->actionView['click_by_subscriber_unique'], compact('campaign', 'subscriber', 'model', 'dataProvider'));
    }

    /**
     * Delete campaign clicks
     *
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete_clicks($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        // run the delete action
        $urls = CampaignUrl::model()->findAllByAttributes([
            'campaign_id' => $campaign->campaign_id,
        ]);
        if (!empty($urls)) {
            $ids = [];
            foreach ($urls as $url) {
                $ids[] = (int)$url->url_id;
            }
            $criteria = new CDbCriteria();
            $criteria->addInCondition('url_id', $ids);
            CampaignTrackUrl::model()->deleteAll($criteria);

            $campaign->getStats()->deleteClicksCountCache();
            $campaign->getStats()->deleteUniqueClicksCountCache();
        }

        notify()->addSuccess(t('campaign_reports', 'Your reports were successfully deleted!'));

        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            $redirect = request()->getPost('returnUrl', ['campaign_reports/click', 'campaign_uid' => $campaign->campaign_uid]);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller'    => $this,
            'campaign'      => $campaign,
            'redirect'      => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUnsubscribe($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        $model = new CampaignTrackUnsubscribe('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', (int)$campaign->campaign_id);
        $criteria->compare('date_added', $model->date_added);
        $criteria->order = 'id DESC';

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $subscriber  = new ListSubscriber();
        $bulkActions = $subscriber->getBulkActionsList();
        foreach ($bulkActions as $value => $name) {
            if (!empty($value) && $value != ListSubscriber::BULK_DELETE) {
                unset($bulkActions[$value]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Unsubscribes report'),
            'pageHeading'     => t('campaign_reports', 'Unsubscribes report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Unsubscribes report'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render($this->actionView['unsubscribe'], compact('campaign', 'model', 'dataProvider', 'bulkActions'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionComplain($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        $model = new CampaignComplainLog('search');
        $model->unsetAttributes();
        $model->attributes = (array)request()->getQuery($model->getModelName(), []);

        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', (int)$campaign->campaign_id);
        $criteria->compare('date_added', $model->date_added);
        $criteria->order = 'log_id DESC';

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$model->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Complaints report'),
            'pageHeading'     => t('campaign_reports', 'Complaints report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Complaints report'),
            ],
        ]);

        // 1.3.5.9
        $this->setData('canExportStats', ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render($this->actionView['complain'], compact('campaign', 'model', 'dataProvider'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionForward_friend($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        $forward = new CampaignForwardFriend('search');
        $forward->attributes  = (array)request()->getQuery($forward->getModelName(), []);
        $forward->campaign_id = (int)$campaign->campaign_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Forward to a friend report'),
            'pageHeading'     => t('campaign_reports', 'Forward to a friend report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Forward to a friend report'),
            ],
        ]);

        $this->render($this->actionView['forward_friend'], compact('campaign', 'forward'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionAbuse_reports($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);

        $reports = new CampaignAbuseReport('search');
        $reports->attributes  = (array)request()->getQuery($reports->getModelName(), []);
        $reports->campaign_id = (int)$campaign->campaign_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Abuse reports'),
            'pageHeading'     => t('campaign_reports', 'Abuse reports'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => $this->campaignsListUrl,
                $campaign->name . ' ' => createUrl($this->campaignOverviewRoute, ['campaign_uid' => $campaign_uid]),
                t('campaign_reports', 'Abuse reports'),
            ],
        ]);

        $this->render($this->actionView['abuse_reports'], compact('campaign', 'reports'));
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @return Campaign
     * @throws CHttpException
     */
    public function loadCampaignModel(string $campaign_uid): Campaign
    {
        $criteria = new CDbCriteria();
        $criteria->with = [
            'customer' => [
                'together'  => true,
                'joinType'  => 'INNER JOIN',
            ],
            'list' => [
                'together'  => true,
                'joinType'  => 'INNER JOIN',
            ],
        ];
        $criteria->compare('t.campaign_uid', $campaign_uid);
        $statuses = [
            Campaign::STATUS_DRAFT, Campaign::STATUS_PENDING_DELETE, Campaign::STATUS_PENDING_SENDING,
        ];
        $criteria->addNotInCondition('t.status', $statuses);

        /** @var Campaign|null $model */
        $model = Campaign::model()->find($criteria);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param int $list_id
     * @param string $subscriber_uid
     *
     * @return ListSubscriber
     * @throws CHttpException
     */
    public function loadSubscriberModel(int $list_id, string $subscriber_uid): ListSubscriber
    {
        $model = ListSubscriber::model()->findByAttributes([
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => $list_id,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param int $campaign_id
     * @param int $url_id
     *
     * @return CampaignUrl
     * @throws CHttpException
     */
    public function loadUrlModel(int $campaign_id, int $url_id): CampaignUrl
    {
        $model = CampaignUrl::model()->findByAttributes([
            'url_id'        => $url_id,
            'campaign_id'   => $campaign_id,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
