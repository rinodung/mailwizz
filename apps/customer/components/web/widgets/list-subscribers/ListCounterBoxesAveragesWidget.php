<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListCounterBoxesAveragesWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.6
 */

class ListCounterBoxesAveragesWidget extends CWidget
{
    /**
     * @var Lists
     */
    public $list;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $list = $this->list;

        $opensAverage = $clicksAverage = $unsubscribesAverage = $complaintsAverage = $bouncesAverage = 0;

        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id';
        $criteria->compare('list_id', (int)$list->list_id);
        $criteria->compare('status', Campaign::STATUS_SENT);

        $campaigns = Campaign::model()->findAll($criteria);

        $campaignsCount = count($campaigns);

        if ($campaignsCount) {
            $campaignsOpens = $campaignsClicks = $campaignsUnsubscribes = $campaignsComplaints = $campaignsBounces = 0;
            foreach ($campaigns as $campaign) {
                $campaignsOpens        += (int)$campaign->getStats()->getUniqueOpensCount();
                $campaignsClicks       += (int)$campaign->getStats()->getUniqueClicksCount();
                $campaignsUnsubscribes += (int)$campaign->getStats()->getUnsubscribesCount();
                $campaignsComplaints   += (int)$campaign->getStats()->getComplaintsCount();
                $campaignsBounces      += (int)$campaign->getStats()->getBouncesCount();
            }
            $opensAverage        = $campaignsOpens / $campaignsCount;
            $clicksAverage       = $campaignsClicks / $campaignsCount;
            $unsubscribesAverage = $campaignsUnsubscribes / $campaignsCount;
            $complaintsAverage   = $campaignsComplaints / $campaignsCount;
            $bouncesAverage      = $campaignsBounces / $campaignsCount;
        }

        $this->render('list-counter-boxes-averages', compact(
            'list',
            'opensAverage',
            'clicksAverage',
            'unsubscribesAverage',
            'complaintsAverage',
            'bouncesAverage'
        ));
    }
}
