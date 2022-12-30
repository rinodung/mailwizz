<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DashboardController
 *
 * Handles the actions for dashboard related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class DashboardController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        $this->addPageScripts([
            ['src' => AssetsUrl::js('dashboard.js')],
        ]);
        parent::init();
    }

    /**
     * Display dashboard information
     *
     * @return void
     * @throws Exception
     */
    public function actionIndex()
    {
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('dashboard', 'Dashboard'),
            'pageHeading'     => t('dashboard', 'Dashboard'),
            'pageBreadcrumbs' => [
                t('dashboard', 'Dashboard'),
            ],
        ]);

        $showRecentCampaigns = true;
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            $showRecentCampaigns = false;
        }

        $this->addPageStyle(['src' => apps()->getBaseUrl('assets/css/placeholder-loading.css')]);

        $this->render('index', compact('showRecentCampaigns'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionGlance_stats()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $appName = apps()->getCurrentAppName();

        /** @var array $glanceStats */
        $glanceStats = (array)hooks()->applyFilters($appName . '_dashboard_glance_stats_list', [], $this);
        if (empty($glanceStats)) {
            $glanceStats = $this->getGlanceStats();
        }

        $keys = ['count', 'heading', 'icon', 'url'];

        /**
         * @var int $index
         * @var array $stat
         */
        foreach ($glanceStats as $index => $stat) {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $stat)) {
                    unset($glanceStats[$index]);
                }
            }
        }

        $html = '';
        if (!empty($glanceStats)) {
            $html = $this->renderPartial('_glance-stats', compact('glanceStats'), true);
        }

        $this->renderJson([
            'html' => $html,
        ]);
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionTimeline_items()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $appName       = apps()->getCurrentAppName();
        $timelineItems = [];
        if (!is_subaccount()) {
            $timelineItems = $this->getTimelineItems();
        }

        /** @var array $timelineItems */
        $timelineItems = (array)hooks()->applyFilters($appName . '_dashboard_timeline_items_list', $timelineItems, $this);

        $html = '';
        if (!empty($timelineItems)) {
            $html = $this->renderPartial('_timeline-items', compact('timelineItems'), true);
        }

        $this->renderJson([
            'html' => $html,
        ]);
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionCampaigns()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
        }

        $listId     = (int)request()->getPost('list_id');
        $campaignId = (int)request()->getPost('campaign_id');

        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id, name';
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->compare('status', Campaign::STATUS_SENT);
        if (!empty($listId)) {
            $criteria->compare('list_id', $listId);
        }
        $criteria->order = 'campaign_id DESC';
        $criteria->limit = 30;

        $latestCampaigns = Campaign::model()->findAll($criteria);
        $campaignsList   = [];
        foreach ($latestCampaigns as $cmp) {
            $campaignsList[$cmp->campaign_id] = $cmp->name;
        }

        if (empty($campaignId) && !empty($latestCampaigns)) {
            $campaignId = $latestCampaigns[0]->campaign_id;
        }

        $campaign = Campaign::model()->findByAttributes([
            'customer_id' => (int)customer()->getId(),
            'campaign_id' => $campaignId,
            'status'      => Campaign::STATUS_SENT,
        ]);

        $html = '';
        if (!empty($campaign)) {
            $html = $this->renderPartial('_campaigns', compact('campaign', 'campaignsList'), true);
        }

        $this->renderJson([
            'html'  => $html,
        ]);
    }

    /**
     * Export
     *
     * @return void
     */
    public function actionExport_recent_activity()
    {
        $models = CustomerActionLog::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('recent-activity.csv');

        $attrsList  = ['category', 'message', 'date_added'];
        $attributes = AttributeHelper::removeSpecialAttributes($models[0]->getAttributes($attrsList));

        /** @var callable $callback */
        $callback = [$models[0], 'getAttributeLabel'];
        $columns  = array_map($callback, array_keys($attributes));

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertOne($columns);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->getAttributes($attrsList));
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @return array
     */
    public function getGlanceStats()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $customer_id = (int)$customer->customer_id;
        $languageId  = (int)$customer->language_id;
        $cacheKey    = sha1('customer.' . $customer_id . '.dashboard.glanceStats.' . $languageId);
        $cache       = cache();

        if (($items = $cache->get($cacheKey))) {
            return $items;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('t.customer_id', $customer_id);
        $criteria->addNotInCondition('t.status', [Lists::STATUS_PENDING_DELETE]);

        $subsCriteria = new CDbCriteria();
        $subsCriteria->addInCondition('t.list_id', $customer->getAllListsIds());

        $items = [
            [
                'id'        => 'campaigns',
                'count'     => formatter()->formatNumber(Campaign::model()->count($criteria)),
                'heading'   => t('dashboard', 'Campaigns'),
                'icon'      => IconHelper::make('ion-ios-email-outline'),
                'url'       => createUrl('campaigns/index'),
            ],
            [
                'id'        => 'lists',
                'count'     => formatter()->formatNumber(Lists::model()->count($criteria)),
                'heading'   => t('dashboard', 'Lists'),
                'icon'      => IconHelper::make('ion ion-clipboard'),
                'url'       => createUrl('lists/index'),
            ],
            [
                'id'        => 'subscribers',
                'count'     => formatter()->formatNumber(ListSubscriber::model()->count($subsCriteria)),
                'heading'   => t('dashboard', 'Subscribers'),
                'icon'      => IconHelper::make('ion-ios-people'),
                'url'       => createUrl('lists/all_subscribers'),
            ],
            [
                'id'        => 'email-templates',
                'count'     => formatter()->formatNumber(CustomerEmailTemplate::model()->countByAttributes(['customer_id' => $customer_id])),
                'heading'   => t('dashboard', 'Templates'),
                'icon'      => IconHelper::make('ion-ios-albums'),
                'url'       => createUrl('templates/index'),
            ],
        ];

        $cache->set($cacheKey, $items, 600);

        return $items;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTimelineItems()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $customer_id = (int)$customer->customer_id;
        $languageId  = (int)$customer->language_id;
        $cacheKey    = sha1('customer.' . $customer_id . '.dashboard.timelineItems.' . $languageId);
        $cache       = cache();

        if (($items = $cache->get($cacheKey))) {
            return $items;
        }

        $criteria = new CDbCriteria();
        $criteria->select    = 'DISTINCT(DATE(t.date_added)) as date_added';
        $criteria->condition = 't.customer_id = :customer_id AND DATE(t.date_added) >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        $criteria->group     = 'DATE(t.date_added)';
        $criteria->order     = 't.date_added DESC';
        $criteria->limit     = 3;
        $criteria->params    = [':customer_id' => $customer_id];
        $models = CustomerActionLog::model()->findAll($criteria);

        $items = [];
        foreach ($models as $model) {
            $_item = [
                'date'  => $model->dateTimeFormatter->formatLocalizedDate($model->date_added),
                'items' => [],
            ];
            $criteria = new CDbCriteria();
            $criteria->select    = 't.log_id, t.customer_id, t.message, t.date_added';
            $criteria->condition = 't.customer_id = :customer_id AND DATE(t.date_added) = :date';
            $criteria->params    = [':customer_id' => $customer_id, ':date' => $model->date_added];
            $criteria->limit     = 5;
            $criteria->order     = 't.date_added DESC';
            $criteria->with      = [
                'customer' => [
                    'select'   => 'customer.customer_id, customer.first_name, customer.last_name',
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                ],
            ];

            /** @var CustomerActionLog[] $records */
            $records = CustomerActionLog::model()->findAll($criteria);

            // since 1.9.26
            if (!empty($records)) {
                $_item['date'] = $records[0]->dateTimeFormatter->formatLocalizedDate($records[0]->date_added, 'yyyy-MM-dd HH:mm:ss');
            }

            foreach ($records as $record) {
                $customer = $record->customer;
                $time     = $record->dateTimeFormatter->formatLocalizedTime($record->date_added);
                $_item['items'][] = [
                    'time'         => $time,
                    'customerName' => $customer->getFullName(),
                    'customerUrl'  => createUrl('account/index'),
                    'message'      => $record->message,
                ];
            }
            $items[] = $_item;
        }

        $cache->set($cacheKey, $items, 600);

        return $items;
    }
}
