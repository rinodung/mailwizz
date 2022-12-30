<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign_delivery_logsController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.0
 */

class Campaign_delivery_logsController extends Controller
{
    /**
     * @return array
     */
    public function accessRules()
    {
        return [
            // allow all authenticated users on all actions
            ['allow', 'users' => ['@']],
            // deny all rule.
            ['deny'],
        ];
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     */
    public function actionIndex($campaign_uid)
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);

        if (empty($campaign)) {
            $this->renderJson([
                'status'    => 'error',
                'error'     => t('api', 'The campaign does not exist!'),
            ], 404);
            return;
        }

        $perPage    = (int)request()->getQuery('per_page', 10);
        $page       = (int)request()->getQuery('page', 1);
        $maxPerPage = 50;
        $minPerPage = 10;

        if ($perPage < $minPerPage) {
            $perPage = $minPerPage;
        }

        if ($perPage > $maxPerPage) {
            $perPage = $maxPerPage;
        }

        if ($page < 1) {
            $page = 1;
        }

        $data = [
            'count'        => null,
            'total_pages'  => null,
            'current_page' => null,
            'next_page'    => null,
            'prev_page'    => null,
            'records'      => [],
        ];

        $criteria = new CDbCriteria();
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);

        $count = CampaignDeliveryLog::model()->count($criteria);

        if ($count == 0) {
            $this->renderJson([
                'status' => 'success',
                'data'   => $data,
            ]);
            return;
        }

        $totalPages = ceil($count / $perPage);

        $data['count']        = $count;
        $data['current_page'] = $page;
        $data['next_page']    = $page < $totalPages ? $page + 1 : null;
        $data['prev_page']    = $page > 1 ? $page - 1 : null;
        $data['total_pages']  = $totalPages;

        $criteria->order  = 't.log_id DESC';
        $criteria->limit  = $perPage;
        $criteria->offset = ($page - 1) * $perPage;

        $logs = CampaignDeliveryLog::model()->findAll($criteria);

        foreach ($logs as $log) {
            $data['records'][] = $log->toApiResponse();
        }

        $this->renderJson([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * @param string $email_message_id
     *
     * @return void
     * @throws CException
     */
    public function actionEmail_message_id($email_message_id)
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.email_message_id', $email_message_id);
        $criteria->with = [
            'campaign' => [
                'joinType'  => 'INNER JOIN',
                'together'  => true,
                'condition' => 'campaign.customer_id = :cid',
                'params'    => [':cid' => (int)user()->getId()],
            ],
        ];

        /** @var CampaignDeliveryLog|null $log */
        $log = CampaignDeliveryLog::model()->find($criteria);
        if (empty($log)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'The delivery log does not exist!'),
            ], 404);
            return;
        }

        $this->renderJson([
            'status' => 'success',
            'data'   => [
                'record' => $log->toApiResponse(),
            ],
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return Campaign|null
     */
    public function loadCampaignByUid(string $campaign_uid): ?Campaign
    {
        if (empty($campaign_uid)) {
            return null;
        }
        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)user()->getId());
        $criteria->compare('campaign_uid', $campaign_uid);

        /** @var Campaign|null $model */
        $model = Campaign::model()->find($criteria);

        return $model;
    }
}
