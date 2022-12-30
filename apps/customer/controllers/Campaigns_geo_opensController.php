<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaigns_geo_opensController
 *
 * Handles the actions for campaigns geo opens related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */

class Campaigns_geo_opensController extends Controller
{
    /**
     * Default export limit
     */
    const DEFAULT_LIMIT = 300;

    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if ($customer->getGroupOption('campaigns.show_geo_opens', 'no') != 'yes') {
            $this->redirect(['campaigns/index']);
        }

        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageCampaigns()) {
            $this->redirect(['dashboard/index']);
        }
    }

    /**
     * List opens for all campaigns
     *
     * @return void
     */
    public function actionIndex()
    {
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaigns', 'Campaigns Geo Opens'),
            'pageHeading'     => t('campaigns', 'Campaigns Geo Opens'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Geo Opens') => createUrl('campaigns_geo_opens/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('index');
    }

    /**
     * Show all campaigns opens
     *
     * @return void
     * @throws CException
     */
    public function actionAll()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $model = new CampaignTrackOpen();
        $model->unsetAttributes();

        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->order = 't.id DESC';
        $criteria->with['campaign'] = [
            'joinType' => 'INNER JOIN',
            'together' => true,
            'condition'=> 'campaign.customer_id = :cid',
            'params'   => [
                ':cid' => (int)$customer->customer_id,
            ],
        ];

        if ($countryCode = request()->getQuery('country_code')) {
            $criteria->with['ipLocation'] = [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ];
            $criteria->compare('ipLocation.country_code', $countryCode);
        }

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'     => $criteria,
            'pagination'   => [
                'pageSize' => (int)$model->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Campaigns Opens'),
            'pageHeading'     => t('campaign_reports', 'Campaigns Opens'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Geo Opens') => createUrl('campaigns_geo_opens/index'),
                t('campaigns', 'View opens'),
            ],
        ]);

        $this->setData('canExportStats', ($customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render('open', compact('model', 'dataProvider'));
    }

    /**
     * Show campaigns unique opens
     *
     * @return void
     * @throws CException
     */
    public function actionUnique()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $model = new CampaignTrackOpen();
        $model->unsetAttributes();

        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->select = 't.*, COUNT(*) AS counter';
        $criteria->group  = 't.subscriber_id';
        $criteria->order  = 'counter DESC';

        $criteria->with['campaign'] = [
            'joinType' => 'INNER JOIN',
            'together' => true,
            'condition'=> 'campaign.customer_id = :cid',
            'params'   => [
                ':cid' => (int)$customer->customer_id,
            ],
        ];

        if ($countryCode = request()->getQuery('country_code')) {
            $criteria->with['ipLocation'] = [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ];
            $criteria->compare('ipLocation.country_code', $countryCode);
        }

        $dataProvider = new CActiveDataProvider($model->getModelName(), [
            'criteria'     => $criteria,
            'pagination'   => [
                'pageSize' => (int)$model->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
        ]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('campaign_reports', 'Unique opens report'),
            'pageHeading'     => t('campaign_reports', 'Unique opens report'),
            'pageBreadcrumbs' => [
                t('campaigns', 'Campaigns') => createUrl('campaigns/index'),
                t('campaigns', 'Geo Opens') => createUrl('campaigns_geo_opens/index'),
                t('campaigns', 'View opens'),
            ],
        ]);

        $this->setData('canExportStats', ($customer->getGroupOption('campaigns.can_export_stats', 'yes') == 'yes'));

        $this->render('open-unique', compact('model', 'dataProvider'));
    }

    /**
     * Export campaigns opens
     *
     * @return void
     */
    public function actionExport_all()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $redirect = ['campaigns_geo_opens/index'];

        if ($customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        $fileName = 'open-stats-' . $customer->customer_uid . '-' . date('Y-m-d-h-i-s') . '.csv';

        // Set the download headers
        HeaderHelper::setDownloadHeaders($fileName);

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getOpenDataForExport());
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * Export campaigns unique opens
     *
     * @return void
     */
    public function actionExport_unique()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        $redirect = ['campaigns_geo_opens/index'];

        if ($customer->getGroupOption('campaigns.can_export_stats', 'yes') != 'yes') {
            $this->redirect($redirect);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('unique-open-stats-' . $customer->customer_uid . '-' . date('Y-m-d-h-i-s') . '.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertAll($this->getOpenUniqueDataForExport());
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @return Generator
     * @throws CException
     */
    protected function getOpenDataForExport(): Generator
    {
        yield [
            t('campaign_reports', 'Campaign'),
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Date added'),
        ];

        $criteria = new CDbCriteria();
        $criteria->select = 't.location_id, t.ip_address, t.user_agent, t.date_added';

        $criteria->with = [
            'campaign'   => [
                'joinType' => 'INNER JOIN',
                'together' => true,
                'condition'=> 'campaign.customer_id = :cid',
                'params'   => [
                    ':cid' => (int)customer()->getId(),
                ],
            ],
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
            $models = CampaignTrackOpen::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $model->campaign->name,
                    $model->subscriber->getDisplayEmail(),
                    strip_tags($model->getIpWithLocationForGrid()),
                    $model->user_agent,
                    $model->date_added,
                ];
            }

            $criteria->offset = (int)$criteria->offset + (int)$criteria->limit;
        }
    }

    /**
     * @return Generator
     * @throws CException
     */
    protected function getOpenUniqueDataForExport(): Generator
    {
        yield [
            t('campaign_reports', 'Campaign'),
            t('campaign_reports', 'Email'),
            t('campaign_reports', 'Open times'),
            t('campaign_reports', 'Ip address'),
            t('campaign_reports', 'User agent'),
            t('campaign_reports', 'Date added'),
        ];

        $criteria = new CDbCriteria();
        $criteria->select = 't.location_id, t.ip_address, t.user_agent, t.date_added, COUNT(*) AS counter';
        $criteria->group  = 't.subscriber_id';
        $criteria->order  = 'counter DESC';

        $criteria->with = [
            'campaign'   => [
                'joinType' => 'INNER JOIN',
                'together' => true,
                'condition'=> 'campaign.customer_id = :cid',
                'params'   => [
                    ':cid' => (int)customer()->getId(),
                ],
            ],
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
            $models = CampaignTrackOpen::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield [
                    $model->campaign->name,
                    $model->subscriber->getDisplayEmail(),
                    $model->counter,
                    strip_tags($model->getIpWithLocationForGrid()),
                    $model->user_agent,
                    $model->date_added,
                ];
            }

            $criteria->offset = (int)$criteria->offset + (int)$criteria->limit;
        }
    }
}
