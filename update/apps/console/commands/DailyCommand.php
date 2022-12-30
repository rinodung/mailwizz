<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DailyCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3.1
 */

class DailyCommand extends ConsoleCommand
{
    /**
     * @return int
     */
    public function actionIndex()
    {
        $this
            ->notifyOnCronjobsNotRunning()
            ->deleteSubscribers()
            ->deleteDeliveryServersUsageLogs()
            ->deleteDeliveryServers()
            ->deleteCustomerOldActionLogs()
            ->deleteUnconfirmedCustomers()
            ->deleteUncompleteOrders()
            ->deleteGuestFailedAttempts()
            ->deleteCampaigns()
            ->deleteSegments()
            ->deleteLists()
            ->deleteSurveys()
            ->syncListsCustomFields()
            ->syncSurveysCustomFields()
            ->deleteCampaignsQueueTables()
            ->deleteCustomers()
            ->deleteDisabledCustomers()
            ->deleteDisabledCustomersData()
            ->deleteMutexes()
            ->deleteCampaignDeliveryLogs()
            ->deleteCampaignBounceLogs()
            ->deleteCampaignOpenLogs()
            ->deleteCampaignClickLogs()
            ->deleteTransactionalEmails()
            ->deleteUnusedCampaignShareCodes()
            ->deleteQueueMonitorData()
            ->sendCampaignStatsEmail()
            ->handleScheduledInactiveCustomers()
            ->sendUnreadMessagesReminderToUsers()
            ->sendUnreadMessagesReminderToCustomers()
            ->writePhpInfo()
            ->verifyLicense();

        hooks()->doAction('console_command_daily', $this);

        /**
         * Run the auto-updater at the end of everything.
         */
        $this->runAutoUpdater();

        return 0;
    }

    /**
     * @return $this
     */
    public function deleteMutexes(): self
    {
        $argv = [
            $_SERVER['argv'][0],
            'delete-mutexes',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function deleteCampaignDeliveryLogs(): self
    {
        /** @var OptionCronDeleteLogs $model */
        $model = container()->get(OptionCronDeleteLogs::class);
        if (!$model->getDeleteCampaignDeliveryLogs()) {
            return $this;
        }

        $argv = [
            $_SERVER['argv'][0],
            'delete-campaign-delivery-logs',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function deleteCampaignBounceLogs(): self
    {
        /** @var OptionCronDeleteLogs $model */
        $model = container()->get(OptionCronDeleteLogs::class);
        if (!$model->getDeleteCampaignBounceLogs()) {
            return $this;
        }

        $argv = [
            $_SERVER['argv'][0],
            'delete-campaign-bounce-logs',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function deleteCampaignOpenLogs(): self
    {
        /** @var OptionCronDeleteLogs $model */
        $model = container()->get(OptionCronDeleteLogs::class);
        if (!$model->getDeleteCampaignOpenLogs()) {
            return $this;
        }

        $argv = [
            $_SERVER['argv'][0],
            'delete-campaign-open-logs',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function deleteCampaignClickLogs(): self
    {
        /** @var OptionCronDeleteLogs $model */
        $model = container()->get(OptionCronDeleteLogs::class);
        if (!$model->getDeleteCampaignClickLogs()) {
            return $this;
        }

        $argv = [
            $_SERVER['argv'][0],
            'delete-campaign-click-logs',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function deleteTransactionalEmails(): self
    {
        /** @var OptionCronProcessTransactionalEmails $optionCronProcessTransactionalEmails */
        $optionCronProcessTransactionalEmails = container()->get(OptionCronProcessTransactionalEmails::class);

        $daysBack = $optionCronProcessTransactionalEmails->getDeleteDaysBack();

        if ($daysBack < 0) {
            return $this;
        }

        $argv = [
            $_SERVER['argv'][0],
            'delete-transactional-emails',
            sprintf('--time=-%d days', $daysBack),
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function sendCampaignStatsEmail(): self
    {
        try {
            while (true) {
                $criteria = new CDbCriteria();
                $criteria->with = [];
                $criteria->compare('t.status', Campaign::STATUS_SENT);
                $criteria->with['option'] = [
                    'together'  => true,
                    'joinType'  => 'INNER JOIN',
                    'condition' => '
                        LENGTH(`option`.`email_stats`) > 0 AND `option`.`email_stats_sent` = 0 AND 
                        DATE(t.finished_at) < DATE_SUB(NOW(), INTERVAL `option`.`email_stats_delay_days` DAY)
                    ',
                ];
                $criteria->limit = 100;

                /** @var Campaign[] $campaigns */
                $campaigns = Campaign::model()->findAll($criteria);
                if (empty($campaigns)) {
                    break;
                }

                foreach ($campaigns as $campaign) {
                    $campaign->option->updateCounters(['email_stats_sent' => 1], 'campaign_id = :cid', [
                        ':cid' => $campaign->campaign_id,
                    ]);
                    $campaign->sendStatsEmail();
                }
            }
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @since 2.0.23
     *
     * @return $this
     */
    protected function notifyOnCronjobsNotRunning(): self
    {
        if (!app_param('console.save_command_history', true)) {
            return $this;
        }

        $messages = [];
        foreach (ConsoleCommandList::getCommandMapCheckInterval() as $commandName => $seconds) {
            if (ConsoleCommandList::isCommandActive($commandName, $seconds)) {
                continue;
            }

            $messages[] = t('app', 'The "{command}" command did not run in the last {num}.', [
                '{command}' => $commandName,
                '{num}'     => DateTimeHelper::timespan(time() - $seconds),
            ]);
        }

        if (empty($messages)) {
            return $this;
        }

        $messages[] = '';
        $messages[] = t('app', 'Please check your cron jobs and make sure they are properly set!');

        $users = User::model()->findAllByAttributes([
            'status'    => User::STATUS_ACTIVE,
            'removable' => User::TEXT_NO,
        ]);

        if (empty($users)) {
            return $this;
        }

        /** @var OptionCommon $optionCommon */
        $optionCommon = container()->get(OptionCommon::class);

        /** @var OptionEmailTemplate $optionEmailTemplate */
        $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

        foreach ($users as $user) {
            try {
                $searchReplace = [
                    '[SITE_NAME]'       => $optionCommon->getSiteName(),
                    '[SITE_TAGLINE]'    => $optionCommon->getSiteTagline(),
                    '[CURRENT_YEAR]'    => date('Y'),
                    '[CONTENT]'         => implode('<br />', $messages),
                ];
                $email = new TransactionalEmail();
                $email->to_name   = $user->getFullName();
                $email->to_email  = $user->email;
                $email->from_name = $optionCommon->getSiteName();
                $email->subject   = t('app', 'Some of your cron jobs did not run');
                $email->body      = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $optionEmailTemplate->common);
                $email->save();
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteSubscribers(): self
    {
        /** @var  OptionCronProcessSubscribers $optionCronProcessSubscribers */
        $optionCronProcessSubscribers = container()->get(OptionCronProcessSubscribers::class);

        $unsubscribeDays = $optionCronProcessSubscribers->getUnsubscribeDays();
        $unconfirmDays   = $optionCronProcessSubscribers->getUnconfirmDays();
        $blacklistedDays = $optionCronProcessSubscribers->getBlacklistedDays();

        try {
            if ($unsubscribeDays > 0) {
                $interval = 60 * 60 * 24 * $unsubscribeDays;
                $sql = 'DELETE FROM `{{list_subscriber}}` WHERE `status` = :st AND last_updated < DATE_SUB(NOW(), INTERVAL ' . (int)$interval . ' SECOND)';
                db()->createCommand($sql)->execute([
                    ':st' => ListSubscriber::STATUS_UNSUBSCRIBED,
                ]);
            }

            if ($unconfirmDays > 0) {
                $interval = 60 * 60 * 24 * $unconfirmDays;
                $sql = 'DELETE FROM `{{list_subscriber}}` WHERE `status` = :st AND last_updated < DATE_SUB(NOW(), INTERVAL ' . (int)$interval . ' SECOND)';
                db()->createCommand($sql)->execute([
                    ':st' => ListSubscriber::STATUS_UNCONFIRMED,
                ]);
            }

            if ($blacklistedDays > 0) {
                $interval = 60 * 60 * 24 * $blacklistedDays;
                $sql = 'DELETE FROM `{{list_subscriber}}` WHERE `status` = :st AND last_updated < DATE_SUB(NOW(), INTERVAL ' . (int)$interval . ' SECOND)';
                db()->createCommand($sql)->execute([
                    ':st' => ListSubscriber::STATUS_BLACKLISTED,
                ]);
            }
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteDeliveryServersUsageLogs(): self
    {
        try {
            /** @var OptionCronProcessDeliveryBounce $optionCronProcessDeliveryBounce */
            $optionCronProcessDeliveryBounce = container()->get(OptionCronProcessDeliveryBounce::class);

            $daysRemoval = $optionCronProcessDeliveryBounce->getDeliveryServersUsageLogsRemovalDays();

            db()->createCommand(sprintf('DELETE FROM `{{delivery_server_usage_log}}` WHERE date_added < DATE_SUB(NOW(), INTERVAL %d DAY)', $daysRemoval))->execute();
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteDeliveryServers(): self
    {
        DeliveryServerCollection::findAllByAttributes([
            'status' => DeliveryServer::STATUS_PENDING_DELETE,
        ])->each(function (DeliveryServer $server) {
            try {
                $type = DeliveryServer::getTypesMapping()[$server->type] ?? null;
                if (empty($type)) {
                    return;
                }
                /** @var DeliveryServer $server */
                $server = DeliveryServer::model($type)->findByPk((int)$server->server_id);
                $server->delete();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteCustomerOldActionLogs(): self
    {
        try {
            db()->createCommand('DELETE FROM `{{customer_action_log}}` WHERE date_added < DATE_SUB(NOW(), INTERVAL 1 MONTH)')->execute();
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteUnconfirmedCustomers(): self
    {
        /** @var OptionCustomerRegistration $optionRegistration */
        $optionRegistration = container()->get(OptionCustomerRegistration::class);

        $unconfirmDays = $optionRegistration->getUnconfirmDaysRemoval();

        try {
            db()->createCommand(sprintf('DELETE FROM `{{customer}}` WHERE `status` = :st AND date_added < DATE_SUB(NOW(), INTERVAL %d DAY)', (int)$unconfirmDays))->execute([
                ':st' => Customer::STATUS_PENDING_CONFIRM,
            ]);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteUncompleteOrders(): self
    {
        /** @var OptionMonetizationOrders $optionMonetizationOrders */
        $optionMonetizationOrders = container()->get(OptionMonetizationOrders::class);

        $unconfirmDays = $optionMonetizationOrders->getUncompleteDaysRemoval();

        try {
            db()->createCommand(sprintf('DELETE FROM `{{price_plan_order}}` WHERE `status` != :st AND `status` != :st2 AND date_added < DATE_SUB(NOW(), INTERVAL %d DAY)', $unconfirmDays))->execute([
                ':st'   => PricePlanOrder::STATUS_COMPLETE,
                ':st2'  => PricePlanOrder::STATUS_REFUNDED,
            ]);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteCampaigns(): self
    {
        CampaignCollection::findAllByAttributes([
            'status' => Campaign::STATUS_PENDING_DELETE,
        ])->each(function (Campaign $campaign) {
            try {
                $campaign->delete();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteLists(): self
    {
        ListsCollection::findAllByAttributes([
            'status' => Lists::STATUS_PENDING_DELETE,
        ])->each(function (Lists $list) {
            try {
                $list->delete();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteSegments(): self
    {
        ListSegmentCollection::findAllByAttributes([
            'status' => ListSegment::STATUS_PENDING_DELETE,
        ])->each(function (ListSegment $segment) {
            try {
                $segment->delete();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteSurveys(): self
    {
        SurveyCollection::findAllByAttributes([
            'status' => Survey::STATUS_PENDING_DELETE,
        ])->each(function (Survey $survey) {
            try {
                $survey->delete();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteGuestFailedAttempts(): self
    {
        try {
            db()->createCommand('DELETE FROM `{{guest_fail_attempt}}` WHERE date_added < DATE_SUB(NOW(), INTERVAL 1 HOUR)')->execute();
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function syncListsCustomFields(): self
    {
        /** @var  OptionCronProcessSubscribers $optionCronProcessSubscribers */
        $optionCronProcessSubscribers = container()->get(OptionCronProcessSubscribers::class);

        if (!$optionCronProcessSubscribers->getSyncCustomFieldsValues()) {
            return $this;
        }

        $argv = [
            $_SERVER['argv'][0],
            'sync-lists-custom-fields',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function syncSurveysCustomFields(): self
    {
        /** @var  OptionCronProcessResponders $optionCronProcessResponders */
        $optionCronProcessResponders = container()->get(OptionCronProcessResponders::class);

        if (!$optionCronProcessResponders->getSyncCustomFieldsValues()) {
            return $this;
        }

        $argv = [
            $_SERVER['argv'][0],
            'sync-surveys-custom-fields',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteCampaignsQueueTables(): self
    {
        if (!app_param('send.campaigns.command.useTempQueueTables', false)) {
            return $this;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('status', Campaign::STATUS_SENT);
        $criteria->addCondition('finished_at IS NOT NULL AND finished_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');

        CampaignCollection::findAll($criteria)->each(function (Campaign $campaign) {
            try {
                $campaign->queueTable->dropTable();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        });

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteCustomers(): self
    {
        $customers = Customer::model()->findAllByAttributes([
            'status' => Customer::STATUS_PENDING_DELETE,
        ]);
        foreach ($customers as $customer) {
            try {
                $customer->delete();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteDisabledCustomers(): self
    {
        /** @var OptionCustomerCommon $optionCustomerCommon */
        $optionCustomerCommon = container()->get(OptionCustomerCommon::class);

        $days = $optionCustomerCommon->getDaysToKeepDisabledAccount();

        if ($days < 0) {
            return $this;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('status', Customer::STATUS_DISABLED);
        $criteria->addCondition(sprintf('DATE_SUB(NOW(), INTERVAL %d DAY) > last_login', $days));

        $customers = Customer::model()->findAll($criteria);

        foreach ($customers as $customer) {
            try {
                $customer->status = Customer::STATUS_PENDING_DELETE;
                $customer->delete();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteDisabledCustomersData(): self
    {
        $customers = Customer::model()->findAllByAttributes([
            'status' => Customer::STATUS_PENDING_DISABLE,
        ]);

        foreach ($customers as $customer) {
            try {
                $attributes = $customer->attributes;

                $customer->status = Customer::STATUS_PENDING_DELETE;
                $customer->delete();

                $newCustomer = new Customer();
                foreach ($attributes as $key => $value) {
                    $newCustomer->$key = $value;
                }
                $newCustomer->saveStatus(Customer::STATUS_DISABLED);
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteUnusedCampaignShareCodes(): self
    {
        try {
            db()->createCommand('DELETE FROM `{{campaign_share_code}}` WHERE date_added < DATE_SUB(NOW(), INTERVAL 1 WEEK)')->execute();
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function deleteQueueMonitorData(): self
    {
        try {
            db()->createCommand('DELETE FROM `{{queue_monitor}}` WHERE date_added < DATE_SUB(NOW(), INTERVAL 1 MONTH)')->execute();
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function writePhpInfo(): self
    {
        if (!CommonHelper::functionExists('phpinfo')) {
            return $this;
        }

        ob_start();
        toggle_ob_implicit_flush(false);
        phpinfo();
        $phpInfo = ob_get_clean();

        file_put_contents((string)Yii::getPathOfAlias('common.runtime') . '/php-info-cli.txt', $phpInfo);

        return $this;
    }

    /**
     * @return $this
     */
    protected function verifyLicense(): self
    {
        try {
            /** @var Psr\Http\Message\ResponseInterface $response */
            $response = LicenseHelper::verifyLicense();
        } catch (Exception $e) {
        }

        if (empty($response)) {
            return $this;
        }

        if ((int)$response->getStatusCode() === 200) {
            return $this;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        $common->saveAttributes([
            'site_status' => OptionCommon::STATUS_OFFLINE,
        ]);

        /** @var OptionLicense $license */
        $license = container()->get(OptionLicense::class);

        $license->saveAttributes([
            'error_message' => (string)$response->getBody(),
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    protected function runAutoUpdater(): self
    {
        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        if (!$common->getAutoUpdate()) {
            return $this;
        }

        $argv = [
            $_SERVER['argv'][0],
            'auto-update',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            $this->getCommandRunnerClone()->run($argv);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function handleScheduledInactiveCustomers(): self
    {
        try {
            $criteria = new CDbCriteria();
            $criteria->compare('status', Customer::STATUS_ACTIVE);
            $criteria->addCondition('inactive_at IS NOT NULL AND inactive_at < NOW()');

            /** @var Customer[] $customers */
            $customers = Customer::model()->findAll($criteria);

            if (empty($customers)) {
                return $this;
            }

            /** @var OptionUrl $optionsUrls */
            $optionsUrls = container()->get(OptionUrl::class);

            $customersBaseUrl = $optionsUrls->getBackendUrl('/customers/update/id/');

            $customersList = [];
            foreach ($customers as $customer) {
                $customer->saveStatus(Customer::STATUS_INACTIVE);
                $customersList[] = CHtml::link($customer->getFullName(), $customersBaseUrl . $customer->customer_id);
            }
            $customersList = implode('<br/>', $customersList);

            $users = User::model()->findAllByAttributes([
                'status'    => User::STATUS_ACTIVE,
                'removable' => User::TEXT_NO,
            ]);

            $params  = CommonEmailTemplate::getAsParamsArrayBySlug(
                'scheduled-inactive-customers',
                [
                    'subject' => t('customers', 'Scheduled inactive customers'),
                ],
                [
                    '[CUSTOMERS_LIST]'  => $customersList,
                ]
            );

            /** @var OptionCommon $common */
            $common = container()->get(OptionCommon::class);

            foreach ($users as $user) {
                $email = new TransactionalEmail();
                $email->to_name   = $user->getFullName();
                $email->to_email  = $user->email;
                $email->from_name = $common->getSiteName();
                $email->subject   = $params['subject'];
                $email->body      = $params['body'];
                $email->save();
                // add a notification message too
                $message = new UserMessage();
                $message->title   = 'Scheduled inactive customers';
                $message->message = $customersList;
                $message->user_id = $user->user_id;
                $message->save();
            }
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function sendUnreadMessagesReminderToUsers(): self
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        /** @var OptionEmailTemplate $optionEmailTemplate */
        $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

        /** @var OptionCommon $optionCommon */
        $optionCommon = container()->get(OptionCommon::class);

        $criteria = new CDbCriteria();
        $criteria->select = 'user_id';
        $criteria->distinct = true;
        $criteria->compare('status', UserMessage::STATUS_UNSEEN);
        $criteria->addCondition('date_added >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');

        /** @var int[] $userIds */
        $userIds = UserMessageCollection::findAll($criteria)->map(function (UserMessage $message): int {
            return (int)$message->user_id;
        })->all();

        foreach ($userIds as $userId) {

            /** @var User $user */
            $user = User::model()->findByPk($userId);

            $criteria = new CDbCriteria();
            $criteria->compare('user_id', $user->user_id);
            $criteria->compare('status', UserMessage::STATUS_UNSEEN);
            $criteria->addCondition('date_added >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');

            $count = (int)UserMessage::model()->count($criteria);

            $criteria->limit = 10;
            $criteria->order = 'message_id DESC';

            /** @var UserMessage[] $messages */
            $messages = UserMessage::model()->findAll($criteria);

            $messageLines = [];
            foreach ($messages as $message) {
                $messageLines[] = sprintf(
                    '%s<br />%s<br />',
                    $message->getTranslatedTitle(),
                    $message->getTranslatedMessage()
                );
            }

            $contentLines = [
                t('messages', 'You have {n} unread messages!', $count),
                t('messages', 'Please see below most recent messages'),
                '',
                implode('<br />', $messageLines),
                '',
                t('messages', 'Click {here} to view all unread messages', [
                    '{here}' => CHtml::link(t('app', 'here'), $optionUrl->getBackendUrl('messages/index?UserMessage[status]=unseen')),
                ]),
            ];

            $searchReplace = [
                '[SITE_NAME]'       => $optionCommon->getSiteName(),
                '[SITE_TAGLINE]'    => $optionCommon->getSiteTagline(),
                '[CURRENT_YEAR]'    => date('Y'),
                '[CONTENT]'         => implode('<br />', $contentLines),
            ];
            $emailTemplate = (string)str_replace(
                array_keys($searchReplace),
                array_values($searchReplace),
                $optionEmailTemplate->common
            );

            try {
                $email = new TransactionalEmail();
                $email->fallback_system_servers = TransactionalEmail::TEXT_YES;
                $email->to_name     = $user->getFullName();
                $email->to_email    = $user->email;
                $email->from_name   = $optionCommon->getSiteName();
                $email->subject     = t('messages', 'You have {n} unread messages!', $count);
                $email->body        = $emailTemplate;
                $email->save();
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function sendUnreadMessagesReminderToCustomers(): self
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        /** @var OptionEmailTemplate $optionEmailTemplate */
        $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

        /** @var OptionCommon $optionCommon */
        $optionCommon = container()->get(OptionCommon::class);

        $criteria = new CDbCriteria();
        $criteria->select = 'customer_id';
        $criteria->distinct = true;
        $criteria->compare('status', CustomerMessage::STATUS_UNSEEN);
        $criteria->addCondition('date_added >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');

        /** @var int[] $customerIds */
        $customerIds = CustomerMessageCollection::findAll($criteria)->map(function (CustomerMessage $message): int {
            return (int)$message->customer_id;
        })->all();

        foreach ($customerIds as $customerId) {

            /** @var Customer $customer */
            $customer = Customer::model()->findByPk($customerId);

            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', $customer->customer_id);
            $criteria->compare('status', CustomerMessage::STATUS_UNSEEN);
            $criteria->addCondition('date_added >= DATE_SUB(NOW(), INTERVAL 24 HOUR)');

            $count = (int)CustomerMessage::model()->count($criteria);

            $criteria->limit = 10;
            $criteria->order = 'message_id DESC';

            /** @var CustomerMessage[] $messages */
            $messages = CustomerMessage::model()->findAll($criteria);

            $messageLines = [];
            foreach ($messages as $message) {
                $messageLines[] = sprintf(
                    '%s<br />%s<br />',
                    $message->getTranslatedTitle(),
                    $message->getTranslatedMessage()
                );
            }

            $contentLines = [
                t('messages', 'You have {n} unread messages!', $count),
                t('messages', 'Please see below most recent messages'),
                '',
                implode('<br />', $messageLines),
                '',
                t('messages', 'Click {here} to view all unread messages', [
                    '{here}' => CHtml::link(t('app', 'here'), $optionUrl->getCustomerUrl('messages/index?CustomerMessage[status]=unseen')),
                ]),
            ];

            $searchReplace = [
                '[SITE_NAME]'       => $optionCommon->getSiteName(),
                '[SITE_TAGLINE]'    => $optionCommon->getSiteTagline(),
                '[CURRENT_YEAR]'    => date('Y'),
                '[CONTENT]'         => implode('<br />', $contentLines),
            ];
            $emailTemplate = (string)str_replace(
                array_keys($searchReplace),
                array_values($searchReplace),
                $optionEmailTemplate->common
            );

            try {
                $email = new TransactionalEmail();
                $email->fallback_system_servers = TransactionalEmail::TEXT_YES;
                $email->to_name     = $customer->getFullName();
                $email->to_email    = $customer->email;
                $email->from_name   = $optionCommon->getSiteName();
                $email->subject     = t('messages', 'You have {n} unread messages!', $count);
                $email->body        = $emailTemplate;
                $email->save();
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        return $this;
    }
}
