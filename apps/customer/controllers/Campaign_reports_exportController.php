<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign_reports_export
 *
 * Handles the actions for exporting campaign reports
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.2
 */

class Campaign_reports_exportController extends Controller
{
    /**
     * Default export limit
     */
    const DEFAULT_LIMIT = 300;

    /**
     * @var array
     */
    public $redirectRoute = ['campaigns/overview'];

    /**
     * @var int
     */
    public $customerId = 0;

    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        // if not set from a child class, fallback on this customer
        if (empty($this->customerId)) {
            $this->customerId = (int)customer()->getId();
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        parent::init();
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionBasic($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        $csvData = [];
        $csvData[] = [t('campaign_reports', 'Name'), $campaign->name];
        $csvData[] = [t('campaign_reports', 'Subject'), $campaign->subject];
        $csvData[] = [t('campaign_reports', 'Processed'), $campaign->getStats()->getProcessedCount(true)];
        $csvData[] = [t('campaign_reports', 'Sent with success'), $campaign->getStats()->getDeliverySuccessCount(true)];
        $csvData[] = [t('campaign_reports', 'Sent success rate'), $campaign->getStats()->getDeliverySuccessRate(true) . '%'];
        $csvData[] = [t('campaign_reports', 'Send error'), $campaign->getStats()->getDeliveryErrorCount(true)];
        $csvData[] = [t('campaign_reports', 'Send error rate'), $campaign->getStats()->getDeliveryErrorRate(true) . '%'];
        $csvData[] = [t('campaign_reports', 'Unique opens'), $campaign->getStats()->getUniqueOpensCount(true)];
        $csvData[] = [t('campaign_reports', 'Unique open rate'), $campaign->getStats()->getUniqueOpensRate(true) . '%'];
        $csvData[] = [t('campaign_reports', 'All opens'), $campaign->getStats()->getOpensCount(true)];
        $csvData[] = [t('campaign_reports', 'All opens rate'), $campaign->getStats()->getOpensRate(true) . '%'];
        $csvData[] = [t('campaign_reports', 'Bounced back'), $campaign->getStats()->getBouncesCount(true)];
        $csvData[] = [t('campaign_reports', 'Bounce rate'), $campaign->getStats()->getBouncesRate(true) . '%'];
        $csvData[] = [t('campaign_reports', 'Hard bounce'), $campaign->getStats()->getHardBouncesCount(true)];
        $csvData[] = [t('campaign_reports', 'Hard bounce rate'), $campaign->getStats()->getHardBouncesRate(true) . '%'];
        $csvData[] = [t('campaign_reports', 'Soft bounce'), $campaign->getStats()->getSoftBouncesCount(true) . '%'];
        $csvData[] = [t('campaign_reports', 'Soft bounce rate'), $campaign->getStats()->getSoftBouncesRate(true) . '%'];
        $csvData[] = [t('campaign_reports', 'Unsubscribe'), $campaign->getStats()->getUnsubscribesCount(true)];
        $csvData[] = [t('campaign_reports', 'Unsubscribe rate'), $campaign->getStats()->getUnsubscribesRate(true) . '%'];

        if ($campaign->option->url_tracking == CampaignOption::TEXT_YES) {
            $csvData[] = [t('campaign_reports', 'Total urls for tracking'), $campaign->getStats()->getTrackingUrlsCount(true)];
            $csvData[] = [t('campaign_reports', 'Unique clicks'), $campaign->getStats()->getUniqueClicksCount(true)];
            $csvData[] = [t('campaign_reports', 'Unique clicks rate'), $campaign->getStats()->getUniqueClicksRate(true) . '%'];
            $csvData[] = [t('campaign_reports', 'All clicks'), $campaign->getStats()->getClicksCount(true)];
            $csvData[] = [t('campaign_reports', 'All clicks rate'), $campaign->getStats()->getClicksRate(true) . '%'];
        }

        $csvData[] = [t('campaign_reports', 'Send at'), $campaign->send_at];
        $csvData[] = [t('campaign_reports', 'Started at'), $campaign->started_at];
        $csvData[] = [t('campaign_reports', 'Finished at'), $campaign->finished_at];
        $csvData[] = [t('campaign_reports', 'Date added'), $campaign->date_added];
        $csvData[] = [t('campaign_reports', 'Last updated'), $campaign->last_updated];

        // Set the download headers
        HeaderHelper::setDownloadHeaders('basic-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($csvData);
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionDelivery($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('sent-email-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getDeliveryLogsForExport($campaign));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionBounce($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('bounce-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getBounceLogsForExport($campaign));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionOpen($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('open-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getOpenDataForExport($campaign));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionOpen_unique($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('unique-open-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getOpenUniqueDataForExport($campaign));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionUnsubscribe($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('unsubscribe-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getUnsubscribeDataForExport($campaign));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionComplain($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('complain-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getComplainDataForExport($campaign));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionClick($campaign_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('click-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getClickDataForExport($campaign));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     * @param int $url_id
     *
     * @return void
     * @throws CHttpException
     */
    public function actionClick_url($campaign_uid, $url_id)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        if ($campaign->option->url_tracking != CampaignOption::TEXT_YES) {
            $this->redirect($redirect);
        }

        /** @var CampaignUrl $url */
        $url = $this->loadUrlModel((int)$campaign->campaign_id, (int)$url_id);

        // Set the download headers
        HeaderHelper::setDownloadHeaders('click-url-stats-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getClickUrlDataForExport($url));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionClick_by_subscriber($campaign_uid, $subscriber_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$campaign->list_id, (string)$subscriber_uid);

        if ($campaign->option->url_tracking != CampaignOption::TEXT_YES) {
            $this->redirect($redirect);
        }

        $fileName = 'clicks-by-' . $subscriber->getDisplayEmail() . '-to-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv';

        // Set the download headers
        HeaderHelper::setDownloadHeaders($fileName);

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getSubscriberClickUrlsDataForExport($campaign, $subscriber));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $campaign_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionClick_by_subscriber_unique($campaign_uid, $subscriber_uid)
    {
        /** @var Campaign $campaign */
        $campaign = $this->loadCampaignModel((string)$campaign_uid);
        $redirect = [$this->redirectRoute, 'campaign_uid' => $campaign->campaign_uid];

        // since 1.3.5.9
        if ($campaign->customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$campaign->list_id, (string)$subscriber_uid);

        if ($campaign->option->url_tracking != CampaignOption::TEXT_YES) {
            $this->redirect($redirect);
        }

        $fileName = 'unique-clicks-by-' . $subscriber->getDisplayEmail() . '-to-' . $campaign->campaign_uid . '-' . date('Y-m-d-h-i-s') . '.csv';

        // Set the download headers
        HeaderHelper::setDownloadHeaders($fileName);

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getSubscriberUniqueClickUrlsDataForExport($campaign, $subscriber));
        } catch (Exception $e) {
        }

        app()->end();
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
        /** @var CampaignUrl|null $model */
        $model = CampaignUrl::model()->findByAttributes([
            'url_id'        => $url_id,
            'campaign_id'   => $campaign_id,
        ]);

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
        /** @var ListSubscriber|null $model */
        $model = ListSubscriber::model()->findByAttributes([
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => $list_id,
        ]);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign
     * @throws CHttpException
     */
    public function loadCampaignModel(string $campaign_uid): Campaign
    {
        /** @var Campaign|null $model */
        $model = Campaign::model()->findByAttributes([
            'customer_id'   => (int)$this->customerId,
            'campaign_uid'  => $campaign_uid,
        ]);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $this->setData('campaign', $model);

        return $model;
    }

    /**
     * @param Campaign $campaign
     *
     * @return Generator
     * @throws CException
     */
    protected function getDeliveryLogsForExport(Campaign $campaign): Generator
    {
        yield [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Process status'),
            t('campaign_reports', 'Sent'),
            t('campaign_reports', 'Date added'),
        ];

        $criteria = new CDbCriteria();
        $criteria->select = 't.status, t.delivery_confirmed, t.date_added';
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);
        $criteria->with = [
            'subscriber' => [
                'select'    => 'subscriber.email, subscriber.list_id',
                'together'  => true,
                'joinType'  => 'INNER JOIN',
            ],
        ];

        $campaignDeliveryLog = new CampaignDeliveryLog();
        if ($attributes = (array)request()->getQuery($campaignDeliveryLog->getModelName())) {
            foreach ($attributes as $key => $value) {
                if ($campaignDeliveryLog->hasAttribute($key)) {
                    $campaignDeliveryLog->$key = $value;
                }
            }

            if (!empty($campaignDeliveryLog->status)) {
                $criteria->compare('t.status', $campaignDeliveryLog->status);
            }
        }

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        $cdlModel = $campaign->getDeliveryLogsArchived() ? CampaignDeliveryLogArchive::model() : CampaignDeliveryLog::model();

        while (true) {

            /** @var CampaignDeliveryLog[] $models */
            $models = $cdlModel->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $model->subscriber->getDisplayEmail(),
                    ucfirst(t('app', (string)$model->status)),
                    ucfirst(t('app', (string)$model->delivery_confirmed)),
                    $model->date_added,
                ];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param Campaign $campaign
     *
     * @return Generator
     */
    protected function getBounceLogsForExport(Campaign $campaign): Generator
    {
        yield [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Bounce type'),
            t('campaign_reports', 'Message'),
            t('campaign_reports', 'Date added'),
        ];

        $criteria = new CDbCriteria();
        $criteria->select = 't.bounce_type, t.message, t.date_added';
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);
        $criteria->with = [
            'subscriber' => [
                'select'    => 'subscriber.email, subscriber.list_id',
                'together'  => true,
                'joinType'  => 'INNER JOIN',
            ],
        ];

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignBounceLog[] $models */
            $models = CampaignBounceLog::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $model->subscriber->getDisplayEmail(),
                    ucfirst(t('app', $model->bounce_type)),
                    $model->message,
                    $model->date_added,
                ];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param Campaign $campaign
     *
     * @return Generator
     * @throws CException
     */
    protected function getOpenDataForExport(Campaign $campaign): Generator
    {
        $columns = [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Date added'),
        ];

        foreach (ListField::getAllByListId((int)$campaign->list_id) as $field) {
            if ($field['tag'] == 'EMAIL') {
                continue;
            }
            $columns[] = $field['tag'];
        }

        yield $columns;

        $criteria = new CDbCriteria();
        $criteria->select = 't.location_id, t.ip_address, t.user_agent, t.date_added';
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);
        $criteria->with = [
            'subscriber' => [
                'select'    => 'subscriber.subscriber_id, subscriber.list_id, subscriber.email',
                'together'  => true,
                'joinType'  => 'INNER JOIN',
            ],
        ];

        if ($countryCode = request()->getQuery('country_code')) {
            $criteria->with['ipLocation'] = [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ];
            $criteria->compare('ipLocation.country_code', $countryCode);
        }

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignTrackOpen[] $models */
            $models = CampaignTrackOpen::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                $row = [
                    $model->subscriber->getDisplayEmail(),
                    strip_tags($model->getIpWithLocationForGrid()),
                    $model->user_agent,
                    $model->date_added,
                ];

                foreach ($model->subscriber->getAllCustomFieldsWithValues() as $fieldTag => $fieldValue) {
                    if ($fieldTag == '[EMAIL]') {
                        continue;
                    }
                    $row[] = $fieldValue;
                }

                yield $row;
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param Campaign $campaign
     *
     * @return Generator
     * @throws CException
     */
    protected function getOpenUniqueDataForExport(Campaign $campaign): Generator
    {
        // columns
        $columns = [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Open times'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Date added'),
        ];
        foreach (ListField::getAllByListId((int)$campaign->list_id) as $field) {
            if ($field['tag'] == 'EMAIL') {
                continue;
            }
            $columns[] = $field['tag'];
        }

        yield $columns;

        $criteria = new CDbCriteria();
        $criteria->select = 't.location_id, t.ip_address, t.user_agent, t.date_added, COUNT(*) AS counter';
        $criteria->compare('campaign_id', (int)$campaign->campaign_id);
        $criteria->group = 't.subscriber_id';

        $criteria->with = [
            'subscriber' => [
                'select'    => 'subscriber.subscriber_id, subscriber.list_id, subscriber.email',
                'together'  => true,
                'joinType'  => 'INNER JOIN',
            ],
        ];

        if ($countryCode = request()->getQuery('country_code')) {
            $criteria->with['ipLocation'] = [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ];
            $criteria->compare('ipLocation.country_code', $countryCode);
        }

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignTrackOpen[] $models */
            $models = CampaignTrackOpen::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                $row = [
                    $model->subscriber->getDisplayEmail(),
                    $model->counter,
                    strip_tags($model->getIpWithLocationForGrid()),
                    $model->user_agent,
                    $model->date_added,
                ];

                foreach ($model->subscriber->getAllCustomFieldsWithValues() as $fieldTag => $fieldValue) {
                    if ($fieldTag == '[EMAIL]') {
                        continue;
                    }
                    $row[] = $fieldValue;
                }

                yield $row;
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param Campaign $campaign
     *
     * @return Generator
     */
    protected function getUnsubscribeDataForExport(Campaign $campaign): Generator
    {
        yield [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Reason'),
            t('campaign_reports', 'Note'),
            t('campaign_reports', 'Date added'),
        ];

        $criteria = new CDbCriteria();
        $criteria->select = 't.location_id, t.ip_address, t.user_agent, t.reason, t.note, t.date_added';
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);
        $criteria->with = [
            'subscriber' => [
                'select'    => 'subscriber.email, subscriber.list_id',
                'together'  => true,
                'joinType'  => 'INNER JOIN',
            ],
        ];

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignTrackUnsubscribe[] $models */
            $models = CampaignTrackUnsubscribe::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $model->subscriber->getDisplayEmail(),
                    strip_tags($model->getIpWithLocationForGrid()),
                    $model->user_agent,
                    $model->reason,
                    $model->note,
                    $model->date_added,
                ];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param Campaign $campaign
     *
     * @return Generator
     */
    protected function getComplainDataForExport(Campaign $campaign): Generator
    {
        yield [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Message'),
            t('campaign_reports', 'Date added'),
        ];

        $criteria = new CDbCriteria();
        $criteria->select = 't.*';
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);
        $criteria->with = [
            'subscriber' => [
                'select'    => 'subscriber.email, subscriber.list_id',
                'together'  => true,
                'joinType'  => 'INNER JOIN',
            ],
        ];

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignComplainLog[] $models */
            $models = CampaignComplainLog::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $model->subscriber->getDisplayEmail(),
                    $model->message,
                    $model->date_added,
                ];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param Campaign $campaign
     *
     * @return Generator
     * @throws CException
     */
    protected function getClickDataForExport(Campaign $campaign): Generator
    {
        $columns = [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Url'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'Date added'),
        ];

        foreach (ListField::getAllByListId((int)$campaign->list_id) as $field) {
            if ($field['tag'] == 'EMAIL') {
                continue;
            }
            $columns[] = $field['tag'];
        }

        yield $columns;

        $criteria = new CDbCriteria();
        $criteria->compare('url.campaign_id', (int)$campaign->campaign_id);
        $criteria->with = [
            'url' => [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ],
            'subscriber' => [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ],
        ];

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignTrackUrl[] $models */
            $models = CampaignTrackUrl::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                $row = [
                    $model->subscriber->getDisplayEmail(),
                    $model->url->destination,
                    $model->user_agent,
                    $model->ip_address,
                    $model->date_added,
                ];
                foreach ($model->subscriber->getAllCustomFieldsWithValues() as $fieldTag => $fieldValue) {
                    if ($fieldTag == '[EMAIL]') {
                        continue;
                    }
                    $row[] = $fieldValue;
                }
                yield $row;
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param CampaignUrl $url
     *
     * @return Generator
     */
    protected function getClickUrlDataForExport(CampaignUrl $url): Generator
    {
        yield [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Url'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'Date added'),
        ];

        $criteria = new CDbCriteria();
        $criteria->compare('url_id', (int)$url->url_id);
        $criteria->with = [
            'subscriber' => [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ],
        ];

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignTrackUrl[] $models */
            $models = CampaignTrackUrl::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $model->subscriber->getDisplayEmail(),
                    $url->destination,
                    $model->user_agent,
                    $model->ip_address,
                    $model->date_added,
                ];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return Generator
     */
    protected function getSubscriberClickUrlsDataForExport(Campaign $campaign, ListSubscriber $subscriber): Generator
    {
        yield [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Url'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'Date added'),
        ];

        $criteria = new CDbCriteria();
        $criteria->compare('t.subscriber_id', (int)$subscriber->subscriber_id);
        $criteria->compare('url.campaign_id', (int)$campaign->campaign_id);
        $criteria->with = [
            'url' => [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ],
        ];

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignTrackUrl[] $models */
            $models = CampaignTrackUrl::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $subscriber->getDisplayEmail(),
                    $model->url->destination,
                    $model->user_agent,
                    $model->ip_address,
                    $model->date_added,
                ];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }

    /**
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     *
     * @return Generator
     */
    protected function getSubscriberUniqueClickUrlsDataForExport(Campaign $campaign, ListSubscriber $subscriber): Generator
    {
        yield [
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Url'),
            t('campaign_reports', 'Clicked times'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'Date added'),
        ];

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
        $criteria->group  = 't.url_id';

        $criteria->limit  = self::DEFAULT_LIMIT;
        $criteria->offset = 0;

        while (true) {

            /** @var CampaignTrackUrl[] $models */
            $models = CampaignTrackUrl::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $subscriber->getDisplayEmail(),
                    $model->url->destination,
                    $model->counter,
                    $model->user_agent,
                    $model->ip_address,
                    $model->date_added,
                ];
            }

            $criteria->offset = $criteria->offset + $criteria->limit;
        }
    }
}
