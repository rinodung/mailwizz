<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class CampaignActivityMapExtCustomerClicksAction extends ExtensionAction
{
    /**
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function run($campaign_uid)
    {
        /** @var CampaignsController $controller */
        $controller = $this->getController();

        if (!request()->getIsAjaxRequest()) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var Campaign $campaign */
        $campaign = $controller->loadCampaignModel($campaign_uid);
        $trackUrl = new CampaignTrackUrl();

        /** @var CampaignActivityMapExtCommon $settings */
        $settings = container()->get(CampaignActivityMapExtCommon::class);

        $criteria = new CDbCriteria();
        $criteria->select = 't.location_id, t.subscriber_id, t.ip_address, t.user_agent, t.date_added';
        $criteria->addCondition('t.location_id IS NOT NULL');
        $criteria->with = [
            'url' => [
                'select'    => 'url.campaign_id',
                'joinType'  => 'INNER JOIN',
                'condition' => 'url.campaign_id = :cid',
                'params'    => [
                    ':cid'  => (int)$campaign->campaign_id,
                ],
            ],
            'subscriber' => [
                'select'    => 'subscriber.email, subscriber.list_id',
                'joinType'  => 'INNER JOIN',
            ],
            'ipLocation' => [
                'together'  => true,
                'joinType'  => 'INNER JOIN',
                'condition' => 'ipLocation.latitude IS NOT NULL AND ipLocation.longitude IS NOT NULL',
            ],
        ];
        $criteria->group = 't.subscriber_id';

        /** @var int $count */
        $count = $trackUrl->count($criteria);

        $pages = new CPagination($count);
        $pages->pageSize = $settings->getClicksAtOnce();
        $pages->applyLimit($criteria);

        /** @var CampaignTrackUrl[] $uniqueClicks */
        $uniqueClicks = $trackUrl->findAll($criteria);

        /** @var array $results */
        $results = [];

        /** @var Mobile_Detect $mobileDetect */
        $mobileDetect = new Mobile_Detect();

        foreach ($uniqueClicks as $click) {
            $device = t('campaign_reports', 'Desktop');
            if (!empty($click->user_agent)) {
                $mobileDetect->setUserAgent($click->user_agent);
                if ($mobileDetect->isMobile()) {
                    $device = t('campaign_reports', 'Mobile');
                } elseif ($mobileDetect->isTablet()) {
                    $device = t('campaign_reports', 'Tablet');
                }
            }

            $results[] = [
                'email'     => $click->subscriber->getDisplayEmail(),
                'ip_address'=> $click->ip_address,
                'location'  => $click->ipLocation->getLocation(),
                'device'    => $device,
                'date_added'=> $click->dateTimeFormatter->getDateAdded(),
                'latitude'  => $click->ipLocation->latitude,
                'longitude' => $click->ipLocation->longitude,
            ];
        }

        $controller->renderJson([
            'results'       => $results,
            'pages_count'   => $pages->pageCount,
            'current_page'  => $pages->currentPage + 1,
        ]);
    }
}
