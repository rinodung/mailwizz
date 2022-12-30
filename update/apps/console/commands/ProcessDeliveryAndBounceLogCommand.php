<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ProcessDeliveryAndBounceLogCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class ProcessDeliveryAndBounceLogCommand extends ConsoleCommand
{
    /**
     * Will process the logs and decide what subscribers to be blacklisted.
     *
     * @return int
     */
    public function actionIndex()
    {
        // added in 1.3.4.7
        hooks()->doAction('console_command_process_delivery_and_bounce_log_before_process', $this);

        $result = $this->process();

        // added in 1.3.4.7
        hooks()->doAction('console_command_process_delivery_and_bounce_log_after_process', $this);

        return $result;
    }

    /**
     * @return int
     */
    protected function process()
    {
        // 1.3.9.5
        $lockName = sha1(__METHOD__);
        if (!mutex()->acquire($lockName)) {
            return 1;
        }

        try {
            /** @var OptionCronProcessDeliveryBounce $optionCronProcessDeliveryBounce */
            $optionCronProcessDeliveryBounce = container()->get(OptionCronProcessDeliveryBounce::class);

            $processLimit                   = $optionCronProcessDeliveryBounce->getProcessAtOnce();
            $blacklistAtDeliveryFatalErrors = $optionCronProcessDeliveryBounce->getMaxFatalErrors();
            $blacklistAtDeliverySoftErrors  = $optionCronProcessDeliveryBounce->getMaxSoftErrors();
            $blacklistAtHardBounce          = $optionCronProcessDeliveryBounce->getMaxHardBounce();
            $blacklistAtSoftBounce          = $optionCronProcessDeliveryBounce->getMaxSoftBounce();

            $cdlModel = !CampaignDeliveryLog::getArchiveEnabled() ? CampaignDeliveryLog::model() : CampaignDeliveryLogArchive::model();

            // subscribers with fatal delivery errors.
            $sql = sprintf(
                '
            SELECT subscriber_id, message, COUNT(*) as counter FROM `' . $cdlModel->tableName() . '` 
                WHERE `processed` = :processed AND `status` = :status GROUP BY subscriber_id HAVING(counter) >= %d 
            LIMIT %d',
                $blacklistAtDeliveryFatalErrors,
                $processLimit
            );
            $rows = (array)db()->createCommand($sql)->queryAll(true, [
                ':processed' => CampaignDeliveryLog::TEXT_NO,
                ':status'    => CampaignDeliveryLog::STATUS_FATAL_ERROR,
            ]);
            $subscriberIds = [];
            foreach ($rows as $row) {
                $subscriber = ListSubscriber::model()->findByPk((int)$row['subscriber_id']);
                if (empty($subscriber)) {
                    continue;
                }
                $subscriber->addToBlacklist(!empty($row['message']) ? $row['message'] : 'Too many delivery errors!');
                $subscriberIds[] = (int)$row['subscriber_id'];
            }
            if (!empty($subscriberIds)) {
                db()->createCommand()->update($cdlModel->tableName(), ['processed' => CampaignDeliveryLog::TEXT_YES], 'subscriber_id IN(' . implode(',', $subscriberIds) . ')');
            }

            // subscribers with soft delivery errors.
            $sql = sprintf(
                '
            SELECT subscriber_id, message, COUNT(*) as counter FROM `' . $cdlModel->tableName() . '` 
                WHERE `processed` = :processed AND `status` = :status GROUP BY subscriber_id HAVING(counter) >= %d 
            LIMIT %d',
                $blacklistAtDeliverySoftErrors,
                $processLimit
            );
            $rows = (array)db()->createCommand($sql)->queryAll(true, [
                ':processed' => CampaignDeliveryLog::TEXT_NO,
                ':status'    => CampaignDeliveryLog::STATUS_ERROR,
            ]);
            $subscriberIds = [];
            foreach ($rows as $row) {
                $subscriber = ListSubscriber::model()->findByPk((int)$row['subscriber_id']);
                if (empty($subscriber)) {
                    continue;
                }
                $subscriber->addToBlacklist(!empty($row['message']) ? $row['message'] : 'Too many delivery errors!');
                $subscriberIds[] = (int)$row['subscriber_id'];
            }
            if (!empty($subscriberIds)) {
                db()->createCommand()->update($cdlModel->tableName(), ['processed' => CampaignDeliveryLog::TEXT_YES], 'subscriber_id IN(' . implode(',', $subscriberIds) . ')');
            }

            // subscribers with hard bounces.
            $sql = sprintf(
                '
            SELECT subscriber_id, message, COUNT(*) as counter FROM `{{campaign_bounce_log}}` 
                WHERE `processed` = :processed AND `bounce_type` = :bounce_type GROUP BY subscriber_id HAVING(counter) >= %d 
            LIMIT %d',
                $blacklistAtHardBounce,
                $processLimit
            );
            $rows = (array)db()->createCommand($sql)->queryAll(true, [
                ':processed'    => CampaignBounceLog::TEXT_NO,
                ':bounce_type'  => CampaignBounceLog::BOUNCE_HARD,
            ]);
            $subscriberIds = [];
            foreach ($rows as $row) {
                $subscriber = ListSubscriber::model()->findByPk((int)$row['subscriber_id']);
                if (empty($subscriber)) {
                    continue;
                }
                $subscriber->addToBlacklist(!empty($row['message']) ? $row['message'] : 'Too many hard bounces!');
                $subscriberIds[] = (int)$row['subscriber_id'];
            }
            if (!empty($subscriberIds)) {
                db()->createCommand()->update('{{campaign_bounce_log}}', ['processed' => CampaignBounceLog::TEXT_YES], 'subscriber_id IN(' . implode(',', $subscriberIds) . ')');
            }

            // subscribers with soft bounces.
            $sql = sprintf(
                '
            SELECT subscriber_id, message, COUNT(*) as counter FROM `{{campaign_bounce_log}}` 
                WHERE `processed` = :processed AND `bounce_type` = :bounce_type GROUP BY subscriber_id HAVING(counter) >= %d 
            LIMIT %d',
                $blacklistAtSoftBounce,
                $processLimit
            );
            $rows = (array)db()->createCommand($sql)->queryAll(true, [
                ':processed'    => CampaignBounceLog::TEXT_NO,
                ':bounce_type'  => CampaignBounceLog::BOUNCE_SOFT,
            ]);
            $subscriberIds = [];
            foreach ($rows as $row) {
                $subscriber = ListSubscriber::model()->findByPk((int)$row['subscriber_id']);
                if (empty($subscriber)) {
                    continue;
                }
                $subscriber->addToBlacklist(!empty($row['message']) ? $row['message'] : 'Too many soft bounces!');
                $subscriberIds[] = (int)$row['subscriber_id'];
            }
            if (!empty($subscriberIds)) {
                db()->createCommand()->update('{{campaign_bounce_log}}', ['processed' => CampaignBounceLog::TEXT_YES], 'subscriber_id IN(' . implode(',', $subscriberIds) . ')');
            }
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        // 1.3.9.5
        mutex()->release($lockName);

        return 0;
    }
}
