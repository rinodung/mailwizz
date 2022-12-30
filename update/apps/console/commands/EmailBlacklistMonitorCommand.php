<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBlacklistMonitorCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.9
 */

class EmailBlacklistMonitorCommand extends ConsoleCommand
{
    /**
     * @return int
     */
    public function actionIndex()
    {
        $lockName = sha1(__METHOD__);

        if (!mutex()->acquire($lockName, 1)) {
            return 1;
        }

        try {
            hooks()->doAction('console_command_email_blacklist_monitor_before_process', $this);

            $this->process();

            hooks()->doAction('console_command_email_blacklist_monitor_after_process', $this);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        mutex()->release($lockName);

        return 0;
    }

    /**
     * @return int
     * @throws CException
     */
    protected function process()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('t.status', EmailBlacklistMonitor::STATUS_ACTIVE);
        $criteria->order = 't.monitor_id ASC';

        // 1.3.7.3 - offer a chance to alter this criteria.
        $criteria = hooks()->applyFilters('console_command_email_blacklist_monitor_process_find_all_criteria', $criteria, $this);

        /** @var EmailBlacklistMonitor[] $monitors */
        $monitors = EmailBlacklistMonitor::model()->findAll($criteria);
        if (empty($monitors)) {
            $this->stdout('No active monitor, stopping...');
            return 0;
        }

        foreach ($monitors as $monitor) {
            $this->stdout(sprintf('Processing the "%s" monitor...', $monitor->name));

            if (empty($monitor->email) && empty($monitor->reason)) {
                continue;
            }

            $criteria = new CDbCriteria();
            $criteria->select = 'email_id, email';
            $criteria->params = [];

            $compareAttributes = ['email', 'reason'];
            foreach ($compareAttributes as $attribute) {
                if (empty($monitor->$attribute)) {
                    continue;
                }
                $attrCondition = $attribute . '_condition';
                if ($monitor->$attrCondition == EmailBlacklistMonitor::CONDITION_EQUALS) {
                    if ($attribute == 'reason' && $monitor->$attribute == '[EMPTY]') {
                        $criteria->addCondition(' LENGTH(' . $attribute . ') = :length ', strtoupper((string)$monitor->condition_operator));
                        $criteria->params[':length'] = 0; // for: if (empty($criteria->params)) {...}
                    } else {
                        $criteria->addCondition($attribute . ' = :' . $attribute, strtoupper((string)$monitor->condition_operator));
                        $criteria->params[':' . $attribute] = $monitor->$attribute;
                    }
                } elseif ($monitor->$attrCondition == EmailBlacklistMonitor::CONDITION_CONTAINS) {
                    $criteria->addCondition($attribute . ' LIKE :' . $attribute, strtoupper((string)$monitor->condition_operator));
                    $criteria->params[':' . $attribute] = '%' . $monitor->$attribute . '%';
                } elseif ($monitor->$attrCondition == EmailBlacklistMonitor::CONDITION_STARTS_WITH) {
                    $criteria->addCondition($attribute . ' LIKE :' . $attribute, strtoupper((string)$monitor->condition_operator));
                    $criteria->params[':' . $attribute] = $monitor->$attribute . '%';
                } elseif ($monitor->$attrCondition == EmailBlacklistMonitor::CONDITION_ENDS_WITH) {
                    $criteria->addCondition($attribute . ' LIKE :' . $attribute, strtoupper((string)$monitor->condition_operator));
                    $criteria->params[':' . $attribute] = '%' . $monitor->$attribute;
                }
            }

            // no params means to conditions, so we can continue with next monitor
            if (empty($criteria->params)) {
                $this->stdout(sprintf('No params for the "%s" monitor!!!', $monitor->name));
                continue;
            }

            // if nothing in database, continue
            $models = EmailBlacklist::model()->findAll($criteria);
            if (empty($models)) {
                $this->stdout(sprintf('No records were found for the "%s" monitor...', $monitor->name));
                continue;
            }

            $modelsCount               = is_countable($models) ? count($models) : 0;
            $modelsDeletedSuccessCount = 0;
            $modelsDeletedErrorCount   = 0;

            $this->stdout(sprintf('Found "%d" records for the "%s" monitor.', $modelsCount, $monitor->name));

            foreach ($models as $model) {
                try {
                    $model->delete();

                    $subscribers = ListSubscriber::model()->findAllByAttributes(['email' => $model->email]);
                    foreach ($subscribers as $subscriber) {
                        if ($subscriber->status == ListSubscriber::STATUS_BLACKLISTED) {
                            $subscriber->saveStatus(ListSubscriber::STATUS_CONFIRMED);
                        }
                        CampaignBounceLog::model()->deleteAllByAttributes([
                            'subscriber_id' => $subscriber->subscriber_id,
                        ]);
                    }

                    $modelsDeletedSuccessCount++;
                    $this->stdout(sprintf('Deleted successfully "%s"...', $model->email));
                } catch (Exception $e) {
                    $modelsDeletedErrorCount++;
                    $this->stdout(sprintf('Error deleting "%s": %s', $model->email, $e->getMessage()));
                }
            }

            $this->stdout(sprintf('Found "%d" records for the "%s" monitor out of which "%d" were processed successfully and "%d" with errors!', $modelsCount, $monitor->name, $modelsDeletedSuccessCount, $modelsDeletedErrorCount));

            // if no need for notifications, just continue.
            if (empty($monitor->notifications_to)) {
                continue;
            }

            // if no delivery server, just continue, we tried.
            if (!($server = DeliveryServer::pickServer())) {
                continue;
            }

            $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                'email-blacklist-monitor-results',
                [
                    'subject' => t('email_blacklist', 'Blacklist monitor results for: {name}', ['{name}' => $monitor->name]),
                ],
                [
                    '[MONITOR_NAME]' => $monitor->name,
                    '[COUNT]'        => $modelsCount,
                    '[SUCCESS_COUNT]'=> $modelsDeletedSuccessCount,
                    '[ERROR_COUNT]'  => $modelsDeletedErrorCount,
                ]
            );

            // prepare and send the email.
            $recipients = CommonHelper::getArrayFromString((string)$monitor->notifications_to, ',');
            foreach ($recipients as $recipient) {
                if (!FilterVarHelper::email($recipient)) {
                    continue;
                }
                $params['to']  = [$recipient => $recipient];
                $server->sendEmail($params);
                $this->stdout(sprintf('Sent the notification to "%s" email address.', $recipient));
            }

            $this->stdout(sprintf('Done processing the "%s" monitor.', $monitor->name));
            $this->stdout('', false, "\n\n");
        }

        return 0;
    }
}
