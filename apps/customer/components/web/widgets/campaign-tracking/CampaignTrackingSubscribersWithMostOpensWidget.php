<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignTrackingSubscribersWithMostOpensWidget
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CampaignTrackingSubscribersWithMostOpensWidget extends CWidget
{
    /**
     * @var Campaign
     */
    public $campaign;

    /**
     * @var bool
     */
    public $showDetailLinks = true;

    /**
     * @return void
     * @throws CException
     */
    public function run()
    {
        $campaign = $this->campaign;

        if ($campaign->status == Campaign::STATUS_DRAFT) {
            return;
        }

        // 1.7.9
        if ($campaign->option->open_tracking != CampaignOption::TEXT_YES) {
            return;
        }

        // 1.7.9 - static counters
        if ($campaign->option->opens_count >= 0) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 't.subscriber_id, COUNT(*) as counter';
        $criteria->compare('t.campaign_id', $campaign->campaign_id);
        $criteria->group = 't.subscriber_id';
        $criteria->order = 'counter DESC';
        $criteria->limit = 10;

        $criteria->with = ['subscriber' => [
            'together'  => true,
            'joinType'  => 'INNER JOIN',
            'select'    => 'subscriber.email, subscriber.list_id',
        ]];

        $models = CampaignTrackOpen::model()->findAll($criteria);
        if (empty($models)) {
            return;
        }

        $this->render('subscribers-with-most-opens', compact('campaign', 'models'));
    }
}
