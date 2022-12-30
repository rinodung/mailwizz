<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Campaign_bouncesController
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

class Campaign_bouncesController extends Controller
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

        $count = CampaignBounceLog::model()->count($criteria);

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

        $bounces = CampaignBounceLog::model()->findAll($criteria);

        foreach ($bounces as $bounce) {
            $data['records'][] = [
                'message'     => $bounce->message,
                'processed'   => $bounce->processed,
                'bounce_type' => $bounce->bounce_type,
                'subscriber'  => [
                    'subscriber_uid' => $bounce->subscriber->subscriber_uid,
                    'email'          => $bounce->subscriber->getDisplayEmail(),
                ],
            ];
        }

        $this->renderJson([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     */
    public function actionCreate($campaign_uid)
    {
        if (!request()->getIsPostRequest()) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'Only POST requests allowed for this endpoint.'),
            ], 400);
            return;
        }

        /** @var Campaign|null $campaign */
        $campaign = $this->loadCampaignByUid($campaign_uid);
        if (empty($campaign)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'The campaign does not exist!'),
            ], 404);
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = $this->loadSubscriberByUid((string)request()->getPost('subscriber_uid', ''));
        if (empty($subscriber)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'The subscriber does not exist!'),
            ], 404);
            return;
        }

        $count = CampaignBounceLog::model()->countByAttributes([
            'campaign_id'   => (int)$campaign->campaign_id,
            'subscriber_id' => (int)$subscriber->subscriber_id,
        ]);

        if (!empty($count)) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'This subscriber has already been marked as bounced!'),
            ], 422);
            return;
        }

        $bounceType = (string)request()->getPost('bounce_type', 'internal');
        $message    = StringHelper::truncateLength((string)request()->getPost('message', 'BOUNCED BACK'), 250);
        $bounce     = new CampaignBounceLog();

        if (!in_array($bounceType, array_keys($bounce->getBounceTypesArray()))) {
            $this->renderJson([
                'status' => 'error',
                'error'  => t('api', 'Invalid bounce type!'),
            ], 422);
            return;
        }

        $bounce->campaign_id   = (int)$campaign->campaign_id;
        $bounce->subscriber_id = (int)$subscriber->subscriber_id;
        $bounce->message       = $message;
        $bounce->bounce_type   = $bounceType;

        if (!$bounce->save()) {
            $this->renderJson([
                'status' => 'error',
                'error'  => $bounce->shortErrors->getAll(),
            ], 422);
            return;
        }

        if ($bounce->bounce_type == CampaignBounceLog::BOUNCE_HARD) {
            $subscriber->addToBlacklist($message);
        }

        $this->renderJson([
            'status' => 'success',
            'data'   => [
                'record' => [
                    'message'     => $bounce->message,
                    'processed'   => $bounce->processed,
                    'bounce_type' => $bounce->bounce_type,
                    'subscriber'  => [
                        'subscriber_uid' => $subscriber->subscriber_uid,
                        'email'          => $subscriber->getDisplayEmail(),
                    ],
                ],
            ],
        ], 201);
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

    /**
     * @param string $subscriber_uid
     *
     * @return ListSubscriber|null
     */
    public function loadSubscriberByUid(string $subscriber_uid): ?ListSubscriber
    {
        if (empty($subscriber_uid)) {
            return null;
        }
        $criteria = new CDbCriteria();
        $criteria->compare('subscriber_uid', $subscriber_uid);
        return ListSubscriber::model()->find($criteria);
    }
}
