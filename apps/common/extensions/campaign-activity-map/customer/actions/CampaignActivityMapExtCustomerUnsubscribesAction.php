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

class CampaignActivityMapExtCustomerUnsubscribesAction extends ExtensionAction
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
        $campaign         = $controller->loadCampaignModel($campaign_uid);
        $trackUnsubscribe = new CampaignTrackUnsubscribe();

        /** @var CampaignActivityMapExtCommon $settings */
        $settings = container()->get(CampaignActivityMapExtCommon::class);

        $criteria = new CDbCriteria();
        $criteria->select = 't.campaign_id, t.location_id, t.subscriber_id, t.ip_address, t.user_agent, t.date_added';
        $criteria->compare('t.campaign_id', (int)$campaign->campaign_id);
        $criteria->addCondition('t.location_id IS NOT NULL');
        $criteria->with = [
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
        $count = $trackUnsubscribe->count($criteria);

        $pages = new CPagination($count);
        $pages->pageSize = $settings->getUnsubscribesAtOnce();
        $pages->applyLimit($criteria);

        /** @var CampaignTrackUnsubscribe[] $unsubscribes */
        $unsubscribes = $trackUnsubscribe->findAll($criteria);

        /** @var array $results */
        $results = [];

        /** @var Mobile_Detect $mobileDetect */
        $mobileDetect = new Mobile_Detect();

        foreach ($unsubscribes as $unsubscribe) {
            $device = t('campaign_reports', 'Desktop');
            if (!empty($unsubscribe->user_agent)) {
                $mobileDetect->setUserAgent($unsubscribe->user_agent);
                if ($mobileDetect->isMobile()) {
                    $device = t('campaign_reports', 'Mobile');
                } elseif ($mobileDetect->isTablet()) {
                    $device = t('campaign_reports', 'Tablet');
                }
            }

            $results[] = [
                'email'     => $unsubscribe->subscriber->getDisplayEmail(),
                'ip_address'=> $unsubscribe->ip_address,
                'location'  => $unsubscribe->ipLocation->getLocation(),
                'device'    => $device,
                'date_added'=> $unsubscribe->dateTimeFormatter->getDateAdded(),
                'latitude'  => $unsubscribe->ipLocation->latitude,
                'longitude' => $unsubscribe->ipLocation->longitude,
            ];
        }

        $controller->renderJson([
            'results'       => $results,
            'pages_count'   => $pages->pageCount,
            'current_page'  => $pages->currentPage + 1,
        ]);
    }
}
