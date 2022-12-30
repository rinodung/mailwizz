<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * HourlyCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.7.5
 */

class HourlyCommand extends ConsoleCommand
{
    /**
     * @return int
     */
    public function actionIndex()
    {
        // set the lock name
        $lockName = sha1(__METHOD__);

        if (!mutex()->acquire($lockName, 5)) {
            return 0;
        }

        $result = 0;

        try {
            hooks()->doAction('console_command_hourly_before_process', $this);

            $result = $this->process();

            hooks()->doAction('console_command_hourly_after_process', $this);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        mutex()->release($lockName);

        return $result;
    }

    /**
     * @return int
     * @throws CDbException
     * @throws Exception
     */
    public function process()
    {
        $this
            ->resetProcessingCampaigns()
            ->resetBounceServers()
            ->handleCampaignsMaxAllowedBounceAndComplaintRates()
            ->updateListsCounters()
            ->updateCustomersQuota()
            ->handleDeliveryServersWarmup()
            ->handleCustomersSubaccountsPermissions()
            ->handleCampaignsResendGiveups()
            ->handleSendingDomainsDnsTxtRecords()
            ->handleTrackingDomainsDnsRecords()
            ->updateListSubscriberCountHistory();

        return 0;
    }

    /**
     * @return $this
     */
    public function handleDeliveryServersWarmup()
    {
        $argv = [
            $_SERVER['argv'][0],
            'delivery-servers-warmup-handler',
        ];

        foreach ($_SERVER['argv'] as $arg) {
            if ($arg == '--verbose=1') {
                $argv[] = $arg;
                break;
            }
        }

        try {
            /** @var CConsoleApplication $app */
            $app = app();
            $runner = clone $app->getCommandRunner();
            $runner->run($argv);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function updateListsCounters()
    {
        $limit  = 50;
        $offset = 0;

        while (true) {
            $criteria = new CDbCriteria();
            $criteria->compare('status', Lists::STATUS_ACTIVE);
            $criteria->limit  = $limit;
            $criteria->offset = $offset;

            $lists = Lists::model()->findAll($criteria);
            if (empty($lists)) {
                break;
            }
            $offset = $offset + $limit;

            foreach ($lists as $list) {
                $this->stdout('Processing list uid: ' . $list->list_uid);
                try {
                    $list->flushSubscribersCountCache(-1, true);
                } catch (Exception $e) {
                    $this->stdout('Processing list uid: ' . $list->list_uid . ' failed with: ' . $e->getMessage());
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws CDbException
     */
    public function handleCampaignsResendGiveups()
    {
        /** @var CampaignResendGiveupQueue[] $queue */
        $queue = CampaignResendGiveupQueue::model()->findAll();

        foreach ($queue as $q) {
            if (empty($q->campaign)) {
                continue;
            }

            $this->stdout('Processing campaign uid: ' . $q->campaign->campaign_uid);

            try {

                /** @var Campaign $campaign */
                $campaign = $q->campaign;
                $campaign->resetSendingGiveups();
                $campaign->updateSendingGiveupCount(0);
                $campaign->updateSendingGiveupCounter(0);

                $campaign->saveStatus(Campaign::STATUS_SENDING);
            } catch (Exception $e) {
                $this->stdout('Processing campaign uid: ' . $q->campaign->campaign_uid . ' failed with: ' . $e->getMessage());
            }

            $q->delete();
        }

        return $this;
    }

    /**
     * @return $this
     * @throws CDbException
     */
    public function handleSendingDomainsDnsTxtRecords()
    {
        /** @var OptionCronProcessSendingDomains $cronSendingDomainsModel */
        $cronSendingDomainsModel = container()->get(OptionCronProcessSendingDomains::class);
        if (!$cronSendingDomainsModel->getHourlyChecksEnabled()) {
            return $this;
        }

        /** @var SendingDomain[] $sendingDomains */
        $sendingDomains = SendingDomain::model()->findAllByAttributes([
            'verified'  => SendingDomain::TEXT_YES,
        ]);

        if (empty($sendingDomains)) {
            return $this;
        }

        foreach ($sendingDomains as $domain) {
            $this->stdout(sprintf('Checking the DNS records for the following sending domain: %s', $domain->name));

            $handle = false;
            try {
                $valid  = $domain->hasValidDNSTxtRecord();
                $handle = $valid === false;
            } catch (Net_DNS2_Exception $e) {
                // We're looking for a domain like "mailer._domainkey.domain.com".
                // If it is missing, it means the TXT records were never added.
                // Any other error code can pass.
                if ($e->getCode() === Net_DNS2_Lookups::E_DNS_NXDOMAIN) {
                    $handle = true;
                }
            }

            $this->stdout(sprintf('Result for %s: %s', $domain->name, $handle ? 'Requires verification' : 'OK'));

            if (!$handle) {
                continue;
            }

            $domain->saveAttributes([
                'verified'  => SendingDomain::TEXT_NO,
            ]);

            $message = new UserMessage();
            $message->title   = 'Sending domain requires verification';
            $message->message = 'The sending domain "{domain}" has failed the verification checks, please take action!';
            $message->message_translation_params = [
                '{domain}' => $domain->name,
            ];
            $message->broadcast();

            if (!empty($domain->customer_id)) {
                $message = new CustomerMessage();
                $message->customer_id   = $domain->customer_id;
                $message->title         = 'Sending domain requires verification';
                $message->message       = 'The sending domain "{domain}" has failed the verification checks, please take action!';
                $message->message_translation_params = [
                    '{domain}' => $domain->name,
                ];
                $message->save();
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws CDbException
     */
    public function handleTrackingDomainsDnsRecords()
    {
        /** @var OptionCronProcessTrackingDomains $cronTrackingDomainsModel */
        $cronTrackingDomainsModel = container()->get(OptionCronProcessTrackingDomains::class);
        if (!$cronTrackingDomainsModel->getHourlyChecksEnabled()) {
            return $this;
        }

        /** @var TrackingDomain[] $trackingDomains */
        $trackingDomains = TrackingDomain::model()->findAllByAttributes([
            'verified'  => TrackingDomain::TEXT_YES,
        ]);

        if (empty($trackingDomains)) {
            return $this;
        }

        foreach ($trackingDomains as $domain) {
            $this->stdout(sprintf('Checking the DNS records for the following tracking domain: %s', $domain->name));

            $handle = false;
            try {
                $valid  = $domain->hasValidDNSRecords();
                $handle = $valid === false;
            } catch (Net_DNS2_Exception $e) {
                // Domain does not exists
                if ($e->getCode() === Net_DNS2_Lookups::E_DNS_NXDOMAIN) {
                    $handle = true;
                }
            }

            $this->stdout(sprintf('Result for %s: %s', $domain->name, $handle ? 'Requires verification' : 'OK'));

            if (!$handle) {
                continue;
            }

            $domain->saveAttributes([
                'verified'  => TrackingDomain::TEXT_NO,
            ]);

            $message = new UserMessage();
            $message->title   = 'Tracking domain requires verification';
            $message->message = 'The tracking domain "{domain}" has failed the verification checks, please take action!';
            $message->message_translation_params = [
                '{domain}' => $domain->name,
            ];
            $message->broadcast();

            if (!empty($domain->customer_id)) {
                $message = new CustomerMessage();
                $message->customer_id   = $domain->customer_id;
                $message->title         = 'Tracking domain requires verification';
                $message->message       = 'The tracking domain "{domain}" has failed the verification checks, please take action!';
                $message->message_translation_params = [
                    '{domain}' => $domain->name,
                ];
                $message->save();
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function resetProcessingCampaigns()
    {
        try {
            db()->createCommand('UPDATE `{{campaign}}` SET `status` = "sending", last_updated = NOW() WHERE status = "processing" AND last_updated < DATE_SUB(NOW(), INTERVAL 7 HOUR)')->execute();
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function resetBounceServers()
    {
        try {
            db()->createCommand('UPDATE `{{bounce_server}}` SET `status` = "active", last_updated = NOW() WHERE status = "cron-running" AND last_updated < DATE_SUB(NOW(), INTERVAL 7 HOUR)')->execute();
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
        return $this;
    }

    /**
     * @since 1.6.1
     * @return $this
     */
    protected function handleCampaignsMaxAllowedBounceAndComplaintRates()
    {
        /** @var OptionCronDelivery $cronDelivery */
        $cronDelivery = container()->get(OptionCronDelivery::class);

        try {
            $criteria = new CDbCriteria();
            $criteria->addInCondition('status', [Campaign::STATUS_SENDING]);
            $campaigns = Campaign::model()->findAll($criteria);

            foreach ($campaigns as $campaign) {
                $customer         = $campaign->customer;
                $maxBounceRate    = (float)$customer->getGroupOption('campaigns.max_bounce_rate', $cronDelivery->getMaxBounceRate());
                $maxComplaintRate = (float)$customer->getGroupOption('campaigns.max_complaint_rate', $cronDelivery->getMaxComplaintRate());

                if ($maxBounceRate > -1) {
                    $bouncesRate = $campaign->getStats()->getBouncesRate() - $campaign->getStats()->getInternalBouncesRate();
                    if ((float)$bouncesRate > (float)$maxBounceRate) {
                        $campaign->block('Campaign bounce rate is higher than allowed!');
                        continue;
                    }
                }

                if ($maxComplaintRate > -1 && (float)$campaign->getStats()->getComplaintsRate() > (float)$maxComplaintRate) {
                    $campaign->block('Campaign complaint rate is higher than allowed!');
                    continue;
                }
            }
        } catch (Exception $e) {
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function updateCustomersQuota()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('status', Customer::STATUS_ACTIVE);

        /** @var Customer[] $customers */
        $customers = Customer::model()->findAll($criteria);

        foreach ($customers as $customer) {
            try {
                $customer->getIsOverQuota();
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
    protected function handleCustomersSubaccountsPermissions()
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('parent_id IS NULL');

        /** @var Customer[] $customers */
        $customers = Customer::model()->findAll($criteria);

        foreach ($customers as $customer) {
            try {
                $customer->handleSubaccountsIfGroupForbidThem();
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    protected function updateListSubscriberCountHistory(): self
    {
        $criteria = new CDbCriteria();
        $criteria->compare('status', Customer::STATUS_ACTIVE);

        /** @var Customer[] $customers */
        $customers = Customer::model()->findAll($criteria);

        $dateStart = \Carbon\Carbon::createFromTimestamp((int)strtotime(MW_DATETIME_NOW) - 3600)->format('Y-m-d H:i:s');
        $dateEnd   = \Carbon\Carbon::createFromTimestamp((int)strtotime(MW_DATETIME_NOW))->format('Y-m-d H:i:s');

        foreach ($customers as $customer) {
            queue_send('console.hourly.listsubscriberscounthistory.update', [
                'dateStart'  => $dateStart,
                'dateEnd'    => $dateEnd,
                'customerId' => $customer->customer_id,
            ]);
        }

        return $this;
    }
}
