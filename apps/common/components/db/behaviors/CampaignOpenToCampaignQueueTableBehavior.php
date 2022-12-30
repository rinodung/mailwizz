<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignOpenToCampaignQueueTableBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 *
 */

/**
 * @property CampaignTrackOpen $owner
 */
class CampaignOpenToCampaignQueueTableBehavior extends CActiveRecordBehavior
{
    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CDbException
     *
     * @see https://github.com/onetwist-software/mailwizz/issues/470 for minTimeHour and minTimeMinute condition
     */
    public function afterSave($event)
    {
        parent::afterSave($event);

        /** @var CampaignTrackOpen $owner */
        $owner = $this->owner;

        $count = CampaignTrackOpen::model()->countByAttributes([
            'campaign_id'   => $owner->campaign_id,
            'subscriber_id' => $owner->subscriber_id,
        ]);

        if ($count > 1) {
            return;
        }

        /** @var ListSubscriber $subscriber */
        $subscriber = $owner->subscriber;

        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->compare('t.list_id', (int)$owner->campaign->list_id);
        $criteria->addCondition('t.segment_id IS NULL');
        $criteria->compare('t.type', Campaign::TYPE_AUTORESPONDER);
        $criteria->addNotInCondition('t.status', [Campaign::STATUS_SENT, Campaign::STATUS_DRAFT, Campaign::STATUS_PENDING_DELETE]);

        $criteria->with['option'] = [
            'together'  => true,
            'joinType'  => 'INNER JOIN',
            'select'    => 'option.autoresponder_include_imported, autoresponder_include_current, option.autoresponder_time_value, option.autoresponder_time_unit, option.autoresponder_time_min_hour, option.autoresponder_time_min_minute',
            'condition' => 'option.autoresponder_event = :evt AND option.autoresponder_open_campaign_id = :cid',
            'params'    => [
                ':evt' => CampaignOption::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_OPEN,
                ':cid' => $owner->campaign_id,
            ],
        ];

        /** @var Campaign[] $campaigns */
        $campaigns = Campaign::model()->findAll($criteria);

        foreach ($campaigns as $campaign) {

            /** @var CampaignOption $campaignOption */
            $campaignOption = $campaign->option;

            // if imported are not allowed to receive
            if ($subscriber->getIsImported() && !$campaignOption->getAutoresponderIncludeImported()) {
                continue;
            }

            // if the subscriber does not fall into segments criteria
            if (!empty($campaign->segment_id) && !$campaign->segment->hasSubscriber((int)$owner->subscriber_id)) {
                continue;
            }

            $minTimeHour   = !empty($campaignOption->autoresponder_time_min_hour) ? $campaignOption->autoresponder_time_min_hour : null;
            $minTimeMinute = !empty($campaignOption->autoresponder_time_min_minute) ? $campaignOption->autoresponder_time_min_minute : null;
            $timeValue     = (int)$campaignOption->autoresponder_time_value;
            $timeUnit      = strtoupper((string)$campaignOption->autoresponder_time_unit);

            try {
                $sendAt = new CDbExpression(sprintf('DATE_ADD(NOW(), INTERVAL %d %s)', $timeValue, $timeUnit));

                // 1.4.3
                if (!empty($minTimeHour) && !empty($minTimeMinute)) {
                    $sendAt = new CDbExpression(sprintf(
                        '
	                	IF (
	                		DATE_ADD(NOW(), INTERVAL %1$d %2$s) > DATE_FORMAT(DATE_ADD(NOW(), INTERVAL %1$d %2$s), \'%%Y-%%m-%%d %3$s:%4$s:00\'),
	                		DATE_ADD(NOW(), INTERVAL %1$d %2$s),
	                		DATE_FORMAT(DATE_ADD(NOW(), INTERVAL %1$d %2$s), \'%%Y-%%m-%%d %3$s:%4$s:00\')
	                	)',
                        $timeValue,
                        $timeUnit,
                        $minTimeHour,
                        $minTimeMinute
                    ));
                }

                $campaign->queueTable->addSubscriber([
                    'subscriber_id' => $owner->subscriber_id,
                    'send_at'       => $sendAt,
                ]);
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }
    }
}
