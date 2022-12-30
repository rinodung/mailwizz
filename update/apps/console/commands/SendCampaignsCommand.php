<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SendCampaignsCommand
 *
 * Please do not alter/extend this file as it is subject to major changes always and future updates will break your app.
 * Since 1.3.5.9 this file has been changed drastically.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 *
 */

class SendCampaignsCommand extends ConsoleCommand
{
    /**
     * @var string what type of campaigns this command is sending
     */
    public $campaigns_type = '';

    /**
     * @var int how many campaigns to process at once
     */
    public $campaigns_limit = 0;

    /**
     * @var int from where to start
     */
    public $campaigns_offset = 0;

    /**
     * @var int
     */
    public $pcntl = -1;

    /**
     * @var int
     */
    public $pcntl_campaigns_parallel = 0;

    /**
     * @var int
     */
    public $pcntl_subscriber_batches_parallel = 0;

    /**
     * @var string
     */
    public $customer_id = '';

    /**
     * @var string
     */
    public $exclude_customer_id = '';

    /**
     * @var string
     */
    public $campaign_id = '';

    /**
     * @var string
     */
    public $exclude_campaign_id = '';

    /**
     * @var string
     */
    public $list_id = '';

    /**
     * @var string
     */
    public $exclude_list_id = '';

    /**
     * @var Campaign|null
     */
    protected $_campaign;

    /**
     * @var bool
     */
    protected $_restoreStates = true;

    /**
     * @var bool
     */
    protected $_improperShutDown = false;

    /**
     * @since 1.3.7.3
     * @var array
     */
    protected $_customerData = [];

    /**
     * @since 2.0.0
     *
     * @var array
     */
    protected $_sendGroupHashKeys = [];

    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        // this will catch exit signals and restore states
        if (CommonHelper::functionExists('pcntl_signal')) {
            declare(ticks = 1);
            pcntl_signal(SIGINT, [$this, '_handleExternalSignal']);
            pcntl_signal(SIGTERM, [$this, '_handleExternalSignal']);
            pcntl_signal(SIGHUP, [$this, '_handleExternalSignal']);
        }

        register_shutdown_function([$this, '_restoreStates']);
        app()->attachEventHandler('onError', [$this, '_restoreStates']);
        app()->attachEventHandler('onException', [$this, '_restoreStates']);

        // 1.5.3
        mutex()->shutdownCleanup = false;
    }

    /**
     * @param int $signalNumber
     *
     * @return void
     */
    public function _handleExternalSignal(int $signalNumber)
    {
        // this will trigger all the handlers attached via register_shutdown_function
        $this->_improperShutDown = true;
        exit;
    }

    /**
     * @param mixed $event
     *
     * @return void
     */
    public function _restoreStates($event = null)
    {
        // since 2.0.0 - in all cases, delete the stored cached keys
        foreach ($this->_sendGroupHashKeys as $key) {
            cache()->delete($key);
        }

        if (!$this->_restoreStates) {
            return;
        }
        $this->_restoreStates = false;

        // called as a callback from register_shutdown_function
        // must pass only if improper shutdown in this case
        if ($event === null && !$this->_improperShutDown) {
            return;
        }

        if (!empty($this->_campaign) && $this->_campaign instanceof Campaign) {
            if ($this->_campaign->getIsProcessing()) {
                $this->_campaign->saveStatus(Campaign::STATUS_SENDING);
                $this->stdout('Campaign status has been restored to sending!');
            }
        }

        $this->stdout('Shutting down!');
    }

    /**
     * @return int
     * @throws Throwable
     */
    public function actionIndex()
    {
        // 1.5.3
        $this->stdout('Starting the work for this batch...');

        // set the lock name
        $lockName = sha1(sprintf(
            '%s:campaigns_type:%s:customer_id:%s:exclude_customer_id:%s:campaign_id:%s:exclude_campaign_id:%s:list_id:%s:exclude_list_id:%s',
            __METHOD__,
            $this->campaigns_type,
            $this->customer_id,
            $this->exclude_customer_id,
            $this->campaign_id,
            $this->exclude_campaign_id,
            $this->list_id,
            $this->exclude_list_id
        ));

        // 1.3.7.3 - mutex
        if ($this->getCanUsePcntl() && !mutex()->acquire($lockName)) {
            $this->stdout('PCNTL processes running already, locks acquired previously!');
            return 0;
        }

        $result = 0;

        try {

            /** @var OptionCronDelivery $cronDelivery */
            $cronDelivery = container()->get(OptionCronDelivery::class);

            // since 1.5.3 - whether we should automatically adjust the number of campaigns at once
            if ($cronDelivery->getAutoAdjustCampaignsAtOnce()) {
                $criteria = new CDbCriteria();
                $criteria->addInCondition('status', [Campaign::STATUS_SENDING, Campaign::STATUS_PROCESSING, Campaign::STATUS_PENDING_SENDING]);
                $newCount = (int)Campaign::model()->count($criteria);
                $cronDelivery->saveAttributes([
                    'campaigns_at_once' => $newCount,
                ]);
            }

            // since 1.5.0
            // we can do this because we are under a lock now if pcntl is used
            // the master lock is per campaign type, which means two processes can get same handler
            if ($this->getCanUsePcntl()) {

                // since 1.5.3 make sure we lock to avoid deadlock when processing regular and ar separately.
                $mutexKey = __METHOD__ . ':update-other-campaigns-status';
                if (mutex()->acquire($mutexKey, 5)) {
                    try {
                        if ($this->campaigns_type) {
                            Campaign::model()->updateAll(['status' => Campaign::STATUS_SENDING], '`type` = :tp AND `status` = :st', [':tp' => $this->campaigns_type, ':st' => Campaign::STATUS_PROCESSING]);
                        } else {
                            Campaign::model()->updateAll(['status' => Campaign::STATUS_SENDING], '`status` = :st', [':st' => Campaign::STATUS_PROCESSING]);
                        }
                    } catch (Exception $e) {
                        $this->stdout(__LINE__ . ': ' . $e->getMessage());
                        Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                    }

                    mutex()->release($mutexKey);
                }
            }
            //

            $timeStart        = microtime(true);
            $memoryUsageStart = memory_get_peak_usage(true);

            // added in 1.3.4.7
            hooks()->doAction('console_command_send_campaigns_before_process', $this);

            $result = $this->process();

            // 1.3.7.5 - do we need to send notifications for reaching the quota?
            // we do this after processing to not send notifications before the sending actually ends...
            if ($result === 0) {
                $this->checkCustomersQuotaLimits();
            }

            // added in 1.3.4.7
            hooks()->doAction('console_command_send_campaigns_after_process', $this);

            $timeEnd        = microtime(true);
            $memoryUsageEnd = memory_get_peak_usage(true);

            $time        = round($timeEnd - $timeStart, 5);
            $memoryUsage = CommonHelper::formatBytes($memoryUsageEnd - $memoryUsageStart);
            $this->stdout(sprintf('This cycle completed in %.5f seconds and used %s of memory!', $time, $memoryUsage));

            if (CommonHelper::functionExists('sys_getloadavg')) {
                [$_1, $_5, $_15] = (array)sys_getloadavg();
                $this->stdout(sprintf('CPU usage in last minute: %.2f, in last 5 minutes: %.2f, in last 15 minutes: %.2f!', $_1, $_5, $_15));
            }
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        // remove the lock
        if ($this->getCanUsePcntl()) {
            mutex()->release($lockName);
        }

        return $result;
    }

    /**
     * @since 1.3.6.6
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     * @param Lists $list
     * @param Customer $customer
     * @return string
     */
    public function getFeedbackIdHeaderValue(Campaign $campaign, ListSubscriber $subscriber, Lists $list, Customer $customer)
    {
        $format = (string)$customer->getGroupOption('campaigns.feedback_id_header_format', '');
        if (empty($format)) {
            return sprintf('%s:%s:%s:%s', $campaign->campaign_uid, $subscriber->subscriber_uid, $list->list_uid, $customer->customer_uid);
        }

        $searchReplace = [
            '[CAMPAIGN_UID]'    => $campaign->campaign_uid,
            '[CAMPAIGN_TYPE]'   => $campaign->type,
            '[SUBSCRIBER_UID]'  => $subscriber->subscriber_uid,
            '[LIST_UID]'        => $list->list_uid,
            '[CUSTOMER_UID]'    => $customer->customer_uid,
            '[CUSTOMER_NAME]'   => StringHelper::truncateLength(URLify::filter($customer->getFullName()), 15, ''),
        ];
        /** @var array $searchReplace */
        $searchReplace = (array)hooks()->applyFilters('feedback_id_header_format_tags_search_replace', $searchReplace);

        return (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $format);
    }

    /**
     * @param mixed $message
     * @param bool $timer
     * @param string $separator
     * @param bool $store
     *
     * @return int
     */
    public function stdout($message, bool $timer = true, string $separator = "\n", bool $store = false)
    {
        if (!is_array($message)) {
            $message = [
                'message' => $message,
            ];
        }

        if ($timer) {
            $message['timestamp'] = time();
        }

        if (empty($message['category'])) {
            $message['category'] = 'common';
        }

        /** @var array $message */
        $message = (array)hooks()->applyFilters('console_command_send_campaigns_stdout_message', $message, $this);

        if (!empty($message['message'])) {
            return parent::stdout($message['message'], $timer, $separator, $store);
        }

        return 0;
    }

    /**
     * @return int
     * @throws CDbException
     * @throws CException
     * @throws Throwable
     */
    protected function process()
    {
        /** @var OptionCronDelivery $cronDelivery */
        $cronDelivery = container()->get(OptionCronDelivery::class);

        $statuses = [Campaign::STATUS_SENDING, Campaign::STATUS_PENDING_SENDING];
        $types    = [Campaign::TYPE_REGULAR, Campaign::TYPE_AUTORESPONDER];
        $limit    = $cronDelivery->getCampaignsAtOnce();

        if ($this->campaigns_type !== null && !in_array($this->campaigns_type, $types)) {
            $this->campaigns_type = '';
        }

        if ((int)$this->campaigns_limit > 0) {
            $limit = (int)$this->campaigns_limit;
        }

        $criteria = new CDbCriteria();
        $criteria->addInCondition('t.status', $statuses);
        $criteria->addCondition('t.send_at <= NOW()');
        if (!empty($this->campaigns_type)) {
            $criteria->addCondition('t.type = :type');
            $criteria->params[':type'] = $this->campaigns_type;
        }
        if (!empty($this->customer_id)) {
            $criteria->addInCondition('t.customer_id', array_filter(array_unique(array_map('intval', array_map('trim', (array)explode(',', $this->customer_id))))));
        }
        if (!empty($this->exclude_customer_id)) {
            $criteria->addNotInCondition('t.customer_id', array_filter(array_unique(array_map('intval', array_map('trim', (array)explode(',', $this->exclude_customer_id))))));
        }
        if (!empty($this->campaign_id)) {
            $criteria->addInCondition('t.campaign_id', array_filter(array_unique(array_map('intval', array_map('trim', (array)explode(',', $this->campaign_id))))));
        }
        if (!empty($this->exclude_campaign_id)) {
            $criteria->addNotInCondition('t.campaign_id', array_filter(array_unique(array_map('intval', array_map('trim', (array)explode(',', $this->exclude_campaign_id))))));
        }
        if (!empty($this->list_id)) {
            $criteria->addInCondition('t.list_id', array_filter(array_unique(array_map('intval', array_map('trim', (array)explode(',', $this->list_id))))));
        }
        if (!empty($this->exclude_list_id)) {
            $criteria->addNotInCondition('t.list_id', array_filter(array_unique(array_map('intval', array_map('trim', (array)explode(',', $this->exclude_list_id))))));
        }
        $criteria->order  = 't.priority ASC, t.campaign_id ASC';
        $criteria->limit  = $limit;
        $criteria->offset = (int)$this->campaigns_offset;

        // offer a chance to alter this criteria.
        /** @var CDbCriteria $criteria */
        $criteria = hooks()->applyFilters('console_send_campaigns_command_find_campaigns_criteria', $criteria, $this);

        // in case it has been changed in hook
        $criteria->limit = $limit;

        $this->stdout(sprintf('Loading %d campaigns, starting with offset %d...', $criteria->limit, $criteria->offset));

        /** @var Campaign[] $campaigns */
        $campaigns = Campaign::model()->findAll($criteria);

        if (empty($campaigns)) {
            $this->stdout('No campaign found, stopping.');

            return 0;
        }

        $this->stdout(sprintf('Found %d campaigns and now starting processing them...', count($campaigns)));
        if ($this->getCanUsePcntl()) {
            $this->stdout(sprintf(
                'Since PCNTL is active, we will send %d campaigns in parallel and for each campaign, %d batches of subscribers in parallel.',
                $this->getCampaignsInParallel(),
                $this->getSubscriberBatchesInParallel()
            ));
        }

        // 1.9.13
        $start = microtime(true);
        $this->stdout('Starting pre-checks for campaigns...');
        $campaignIds = [];
        foreach ($campaigns as $campaign) {
            $campaignIds[] = $campaign->campaign_id;
        }

        $preCheckData = $this->sendCampaignsPreChecksStep0($campaignIds);
        if (empty($preCheckData['campaignIds']) || empty($preCheckData['customerData'])) {
            $this->stdout('Pre-checks for campaigns are done now, nothing else to do, stopping...');
            return 0;
        }

        $campaignIds = $preCheckData['campaignIds'];
        $campaigns   = [];
        foreach ($campaignIds as $campaignId) {
            $campaign = Campaign::model()->findByPk((int)$campaignId);
            if (empty($campaign)) {
                continue;
            }
            $campaigns[] = $campaign;
        }

        $this->_customerData = $preCheckData['customerData'];
        foreach ($this->_customerData as $customerId => $customerData) {
            if (empty($customerData['customer_id'])) {
                unset($this->_customerData[$customerId]);
                continue;
            }
            $customer = Customer::model()->findByPk((int)$customerData['customer_id']);
            if (empty($customer)) {
                $this->stdout(sprintf(
                    'Cannot load customer(id %d), please check it in the web interface and make sure it is still a valid customer! Stopping the process...',
                    $customerData['customer_id']
                ));
                $this->_customerData = [];
                return 0;
            }
            $this->_customerData[$customerId]['customer'] = $customer;
        }
        $this->stdout(sprintf(
            'Campaigns pre-checks are done now, it took %.5f seconds!',
            round(microtime(true) - $start, 5)
        ));
        //

        // 1.3.7.5
        foreach ($campaigns as $campaign) {
            if (!$campaign->option->getCanSetMaxSendCount()) {
                continue;
            }

            $campaignDeliveryLogSuccessCount = CampaignDeliveryLog::model()->countByAttributes([
                'campaign_id' => $campaign->campaign_id,
                'status'      => CampaignDeliveryLog::STATUS_SUCCESS,
            ]);

            $sendingsLeft = $campaign->option->max_send_count - $campaignDeliveryLogSuccessCount;
            $sendingsLeft = $sendingsLeft >= 0 ? $sendingsLeft : 0;

            if (!$sendingsLeft) {
                unset($this->_customerData[$campaign->customer_id]['campaigns'][$campaign->campaign_id]);
                if (($idx = array_search($campaign->campaign_id, $campaignIds)) !== false) {
                    unset($campaignIds[$idx]);
                }

                if ($this->markCampaignSent($campaign)) {
                    $this->stdout('Campaign has been marked as sent because of MaxSendCount settings!');
                }

                continue;
            }

            $campaignMaxSubscribers = $this->_customerData[$campaign->customer_id]['campaigns'][$campaign->campaign_id];
            if ($sendingsLeft < $campaignMaxSubscribers) {
                $this->_customerData[$campaign->customer_id]['campaigns'][$campaign->campaign_id] = $sendingsLeft;
                continue;
            }
        }
        unset($campaigns);
        //

        $this->sendCampaignStep0($campaignIds);

        return 0;
    }

    /**
     * @param array $campaignIds
     *
     * @return array
     * @throws CException
     */
    protected function sendCampaignsPreChecksStep0(array $campaignIds)
    {
        $defaultCachedData = [
            'customersFail' => [],
            'customerData'  => [],
            'campaignIds'   => [],
        ];

        // generate the hash key and store it in cache
        $hashKey = StringHelper::random(32);
        cache()->set($hashKey, $defaultCachedData);

        $cachedData = cache()->get($hashKey);
        if (empty($cachedData) || !is_array($cachedData)) {
            $this->stdout(sprintf('Unable to fetch cached data after setting it, on line %d!', __LINE__));
            return $defaultCachedData;
        }

        $handled = false;

        if ($this->getCanUsePcntl() && ($campaignsInParallel = (int)$this->getCampaignsInParallel()) > 1) {
            $handled = true;

            // make sure we close the external connections
            $this->setExternalConnectionsActive(false);

            $campaignChunks = array_chunk($campaignIds, $campaignsInParallel);
            foreach ($campaignChunks as $cids) {
                $children = [];
                foreach ($cids as $index => $cid) {
                    $pid = pcntl_fork();
                    if ($pid == -1) {
                        continue;
                    }

                    // Parent
                    if ($pid) {
                        $children[] = $pid;
                    }

                    // Child
                    if (!$pid) {
                        $mutexKey = sprintf('send-campaigns:prechecks:campaign:%d:date:%s', $cid, date('Ymd'));
                        if (mutex()->acquire($mutexKey)) {
                            try {
                                $this->sendCampaignsPreChecksStep1((int)$cid, $hashKey, (int)$index+1);
                            } catch (Exception $e) {
                                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

                                try {
                                    if ($campaign = Campaign::model()->findByPk($cid)) {
                                        $campaign->saveStatus(Campaign::STATUS_SENDING);
                                    }
                                } catch (Exception $e) {
                                    $this->stdout(__LINE__ . ': ' . $e->getMessage());
                                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                                }
                            }
                            mutex()->release($mutexKey);
                        }
                        app()->end();
                    }
                }

                while (count($children) > 0) {
                    foreach ($children as $key => $pid) {
                        $res = pcntl_waitpid($pid, $status, WNOHANG);
                        if ($res == -1 || $res > 0) {
                            unset($children[$key]);
                        }
                    }
                    usleep(100000);
                }
            }
        }

        if (!$handled) {
            foreach ($campaignIds as $campaignId) {
                try {
                    $this->sendCampaignsPreChecksStep1((int)$campaignId, $hashKey);
                } catch (Exception $e) {
                    $this->stdout(__LINE__ . ': ' . $e->getMessage());
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

                    try {
                        if ($campaign = Campaign::model()->findByPk($campaignId)) {
                            $campaign->saveStatus(Campaign::STATUS_SENDING);
                        }
                    } catch (Exception $e) {
                        $this->stdout(__LINE__ . ': ' . $e->getMessage());
                        Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                    }
                }
            }
        }

        $cachedData = cache()->get($hashKey);
        if (empty($cachedData) || !is_array($cachedData)) {
            $cachedData = $defaultCachedData;
        }

        return (array)$cachedData;
    }

    /**
     * @param int $campaignId
     * @param string $hashKey
     * @param int $workerNumber
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    protected function sendCampaignsPreChecksStep1(int $campaignId, string $hashKey, int $workerNumber = 0)
    {
        $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Starting...', $workerNumber, $campaignId));

        $cachedData = cache()->get($hashKey);
        if (empty($cachedData) || !is_array($cachedData)) {
            $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Cannot fetch cache data', $workerNumber, $campaignId));
            return;
        }

        $campaign = Campaign::model()->findByPk((int)$campaignId);
        if (empty($campaign)) {
            $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Cannot load campaign', $workerNumber, $campaignId));
            return;
        }

        /** @var Customer $customer */
        $customer = $campaign->customer;

        // already processed but failed
        if (in_array($customer->customer_id, $cachedData['customersFail'])) {
            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Customer already processed, we are done!', $workerNumber, $campaignId));
            return;
        }

        $mutexHashTTL = 120;

        if (!$customer->getIsActive()) {
            $campaign->saveStatus(Campaign::STATUS_PAUSED);
            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: This customer is inactive!', $workerNumber, $campaignId));

            if (mutex()->acquire($hashKey, $mutexHashTTL)) {
                $cachedData = cache()->get($hashKey);
                if (empty($cachedData) || !is_array($cachedData)) {
                    $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Unable to fetch cached data on line %d!', $workerNumber, $campaignId, __LINE__));
                    mutex()->release($hashKey);
                    return;
                }
                $cachedData['customersFail'][] = (int)$customer->customer_id;
                cache()->set($hashKey, $cachedData);
                mutex()->release($hashKey);
            }

            return;
        }

        // since 1.3.9.7
        if ($customer->getCanHaveHourlyQuota() && !$customer->getHourlyQuotaLeft()) {
            $campaign->incrementPriority(); // move at the end of the processing queue
            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: This customer reached the hourly assigned quota!', $workerNumber, $campaignId));

            if (mutex()->acquire($hashKey, $mutexHashTTL)) {
                $cachedData = cache()->get($hashKey);
                if (empty($cachedData) || !is_array($cachedData)) {
                    $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Unable to fetch cached data on line %d!', $workerNumber, $campaignId, __LINE__));
                    mutex()->release($hashKey);
                    return;
                }
                $cachedData['customersFail'][] = (int)$customer->customer_id;
                cache()->set($hashKey, $cachedData);
                mutex()->release($hashKey);
            }

            return;
        }

        if ($customer->getIsOverQuota()) {
            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: This customer reached the assigned quota', $workerNumber, $campaignId));
            $campaign->postponeBecauseCustomerReachedQuota();

            if (mutex()->acquire($hashKey, $mutexHashTTL)) {
                $cachedData = cache()->get($hashKey);
                if (empty($cachedData) || !is_array($cachedData)) {
                    $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Unable to fetch cached data on line %d!', $workerNumber, $campaignId, __LINE__));
                    mutex()->release($hashKey);
                    return;
                }
                $cachedData['customersFail'][] = (int)$customer->customer_id;
                cache()->set($hashKey, $cachedData);
                mutex()->release($hashKey);
            }

            return;
        }

        // 1.3.7.9 - create the queue table and populate it...
        if ($campaign->getCanUseQueueTable()) {
            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Using queue tables...', $workerNumber, $campaignId));

            // put proper status
            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Temporary changing the campaign status into PROCESSING!', $workerNumber, $campaignId));
            $campaign->saveStatus(Campaign::STATUS_PROCESSING);

            // 1.5.8
            $mutexKey = sprintf('send-campaigns:campaign:%d:populateTempTable:date:%s', $campaign->campaign_id, date('Ymd'));

            try {

                // 1.5.8 - mutex protection
                if (!mutex()->acquire($mutexKey)) {
                    throw new Exception('Unable to acquire the mutex for table population!');
                }

                // populate table
                // 1.9.13 - it will throw exception on populate failure so we stop the processing.
                $campaign->queueTable->populateTable();

                // release the mutex
                mutex()->release($mutexKey);
            } catch (Exception $e) {

                // release the mutex
                mutex()->release($mutexKey);

                $campaign->saveStatus(Campaign::STATUS_SENDING);

                $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Failed to populate queue table, reason: %s', $workerNumber, $campaignId, $e->getMessage()));
                return;
            }

            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Restoring the campaign status to SENDING!', $workerNumber, $campaignId));
            $campaign->saveStatus(Campaign::STATUS_SENDING);
        }

        /** @var OptionCronDelivery $cronDelivery */
        $cronDelivery = container()->get(OptionCronDelivery::class);

        // counter
        $subscribersAtOnce = (int)$customer->getGroupOption('campaigns.subscribers_at_once', $cronDelivery->getSubscribersAtOnce());
        if ($this->getCanUsePcntl()) {
            $subscribersAtOnce *= $this->getSubscriberBatchesInParallel();
        }

        $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Populating customer data...', $workerNumber, $campaignId));
        if (mutex()->acquire($hashKey, $mutexHashTTL)) {
            $cachedData = cache()->get($hashKey);
            if (empty($cachedData) || !is_array($cachedData)) {
                $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Unable to fetch cached data on line %d!', $workerNumber, $campaignId, __LINE__));
                mutex()->release($hashKey);
                return;
            }

            // 1.3.7.3 - precheck and allow because pcntl mainly
            if (!isset($cachedData['customerData'][$campaign->customer_id])) {
                $quotaTotal  = (int)$customer->getGroupOption('sending.quota', -1);

                $quotaUsage = 0;
                $quotaLeft  = PHP_INT_MAX;
                if ($quotaTotal > -1) {
                    $quotaUsage = (int)$customer->countUsageFromQuotaMark();
                    $quotaLeft  = $quotaTotal - $quotaUsage;
                    $quotaLeft  = $quotaLeft >= 0 ? $quotaLeft : 0;
                }

                // 1.3.9.7
                if ($customer->getCanHaveHourlyQuota()) {
                    $hourlyQuotaLeft = $customer->getHourlyQuotaLeft();
                    if ($hourlyQuotaLeft <= $quotaLeft) {
                        $quotaLeft = $hourlyQuotaLeft;
                        $quotaLeft = $quotaLeft >= 0 ? $quotaLeft : 0;
                    }
                }

                $cachedData['customerData'][$campaign->customer_id] = [
                    'customer_id'       => $customer->customer_id,
                    'customer'          => null,
                    'campaigns'         => [],
                    'quotaTotal'        => $quotaTotal,
                    'quotaUsage'        => $quotaUsage,
                    'quotaLeft'         => $quotaLeft,
                    'subscribersAtOnce' => $subscribersAtOnce,
                    'subscribersCount'  => $this->countSubscribers($campaign),
                ];

                cache()->set($hashKey, $cachedData);
            }

            mutex()->release($hashKey);
        } else {
            $this->stdout(sprintf('[Worker %d] [WARNING] Campaign id %d pre-checks: Unable to acquire mutex on line %d!', $workerNumber, $campaignId, __LINE__));
            return;
        }

        // count outside the mutex
        $campaignPreSubscribersCount = 0;
        $mustCount = false;
        $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Pre-counting campaign subscribers...', $workerNumber, $campaignId));
        // @phpstan-ignore-next-line
        if (mutex()->acquire($hashKey, $mutexHashTTL)) {
            $cachedData = cache()->get($hashKey);
            if (empty($cachedData) || !is_array($cachedData)) {
                $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Unable to fetch cached data on line %d!', $workerNumber, $campaignId, __LINE__));
                mutex()->release($hashKey);
                return;
            }
            $mustCount = $cachedData['customerData'][$campaign->customer_id]['quotaLeft'] > 0;
            mutex()->release($hashKey);
        } else {
            $this->stdout(sprintf('[Worker %d] [WARNING] Campaign id %d pre-checks: Unable to acquire mutex on line %d!', $workerNumber, $campaignId, __LINE__));
            return;
        }

        // We count so we can know how many subs a campaign has to send and remove it from the sending
        // process in case it does not have subscribers to send, this way we don't spawn processes that do nothing.
        if ($mustCount) {
            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Pre-counting campaign subscribers, start counting...', $workerNumber, $campaignId));
            $start = microtime(true);
            $campaignPreSubscribersCount = (int)$this->countSubscribers($campaign);
            $campaignPreSubscribersCount = $campaignPreSubscribersCount > $subscribersAtOnce ? $subscribersAtOnce : $campaignPreSubscribersCount;
            $end = round(microtime(true) - $start, 5);
            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Pre-counting campaign subscribers, finished counting in %.5f seconds...', $workerNumber, $campaignId, $end));
        }

        $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Finding campaign max subscribers count and adjusting quota left...', $workerNumber, $campaignId));
        // @phpstan-ignore-next-line
        if (mutex()->acquire($hashKey, $mutexHashTTL)) {
            if ($mustCount) {
                $this->stdout(sprintf(
                    '[Worker %d] Campaign id %d pre-checks: Currently quota left is %d and campaign available subscribers for this batch is %d...',
                    $workerNumber,
                    $campaignId,
                    $cachedData['customerData'][$campaign->customer_id]['quotaLeft'],
                    $campaignPreSubscribersCount
                ));
            } else {
                $this->stdout(sprintf(
                    '[Worker %d] Campaign id %d pre-checks: Currently quota left is %d, available subscribers were not counted anymore...',
                    $workerNumber,
                    $campaignId,
                    $cachedData['customerData'][$campaign->customer_id]['quotaLeft']
                ));
            }

            $cachedData = cache()->get($hashKey);
            if (empty($cachedData) || !is_array($cachedData)) {
                $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Unable to fetch cached data on line %d!', $workerNumber, $campaignId, __LINE__));
                mutex()->release($hashKey);
                return;
            }

            $campaignMaxSubscribers = 0;
            if ($cachedData['customerData'][$campaign->customer_id]['quotaLeft'] > 0) {
                if ($cachedData['customerData'][$campaign->customer_id]['quotaLeft'] >= $subscribersAtOnce) {
                    $campaignMaxSubscribers = $subscribersAtOnce;
                } else {
                    $campaignMaxSubscribers = $cachedData['customerData'][$campaign->customer_id]['quotaLeft'];
                }

                if ($campaignPreSubscribersCount < $campaignMaxSubscribers) {
                    $campaignMaxSubscribers = $campaignPreSubscribersCount;
                }

                $cachedData['customerData'][$campaign->customer_id]['quotaLeft'] -= $campaignMaxSubscribers;
                if ($cachedData['customerData'][$campaign->customer_id]['quotaLeft'] < 0) {
                    $cachedData['customerData'][$campaign->customer_id]['quotaLeft'] = 0;
                }
            }

            // how much each campaign is allowed to send
            $cachedData['customerData'][$campaign->customer_id]['campaigns'][$campaign->campaign_id] = $campaignMaxSubscribers;

            cache()->set($hashKey, $cachedData);
            mutex()->release($hashKey);
        } else {
            $this->stdout(sprintf('[Worker %d] [WARNING] Campaign id %d pre-checks: Unable to acquire mutex on line %d!', $workerNumber, $campaignId, __LINE__));
            return;
        }

        // populate the campaigns array
        // @phpstan-ignore-next-line
        if (mutex()->acquire($hashKey, $mutexHashTTL)) {
            $cachedData = cache()->get($hashKey);
            if (empty($cachedData) || !is_array($cachedData)) {
                $this->stdout(sprintf('[Worker %d] [ERROR] Campaign id %d pre-checks: Unable to fetch cached data on line %d!', $workerNumber, $campaignId, __LINE__));
                mutex()->release($hashKey);
                return;
            }

            // This will take into consideration campaigns with more than 0 subscribers
            // but will leave the ones with 0 subscribers in a sending state,
            // therefore we need one extra check to make sure the campaign has no subscribers,
            // and if it does not, simply mark it as sent.
            // We do this only for regular campaigns, autoresponders can stay in sending status, it's their normal behavior
            if ($cachedData['customerData'][$campaign->customer_id]['campaigns'][$campaign->campaign_id] > 0) {
                $cachedData['campaignIds'][] = (int)$campaign->campaign_id;
                cache()->set($hashKey, $cachedData);
            } else {
                // this is the extra check to mark the campaign as sent in case it's in sending state
                if ($campaign->getIsRegular()) {
                    $count = $mustCount ? $campaignPreSubscribersCount : $this->countSubscribers($campaign);

                    // since 1.9.14
                    $count = $this->handleCampaignTimewarp($campaign, $count);

                    if ($count === 0) {
                        if ($this->markCampaignSent($campaign)) {
                            $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Campaign has been marked as sent!', $workerNumber, $campaignId));
                        }
                        mutex()->release($hashKey);
                        return;
                    }
                }
            }
            mutex()->release($hashKey);
        } else {
            $this->stdout(sprintf('[Worker %d] [WARNING] Campaign id %d pre-checks: Unable to acquire mutex on line %d!', $workerNumber, $campaignId, __LINE__));
            return;
        }

        $this->stdout(sprintf('[Worker %d] Campaign id %d pre-checks: Done!', $workerNumber, $campaignId));
    }

    /**
     * @param array $campaignIds
     *
     * @return void
     * @throws CException
     * @throws Throwable
     */
    protected function sendCampaignStep0(array $campaignIds = [])
    {
        $handled = false;

        if ($this->getCanUsePcntl() && ($campaignsInParallel = (int)$this->getCampaignsInParallel()) > 1) {
            $handled = true;

            // make sure we close the external connections
            $this->setExternalConnectionsActive(false);

            $campaignChunks = array_chunk($campaignIds, $campaignsInParallel);
            foreach ($campaignChunks as $index => $cids) {
                $children = [];
                foreach ($cids as $cid) {
                    $pid = pcntl_fork();
                    if ($pid == -1) {
                        continue;
                    }

                    // Parent
                    if ($pid) {
                        $children[] = $pid;
                    }

                    // Child
                    if (!$pid) {
                        $mutexKey = sprintf('send-campaigns:campaign:%d:date:%s', $cid, date('Ymd'));
                        if (mutex()->acquire($mutexKey)) {

                            // 1.5.3
                            try {
                                $this->sendCampaignStep1($cid, $index+1);
                            } catch (Exception $e) {
                                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

                                try {
                                    if ($campaign = Campaign::model()->findByPk($cid)) {
                                        $campaign->saveStatus(Campaign::STATUS_SENDING);
                                    }
                                } catch (Exception $e) {
                                    $this->stdout(__LINE__ . ': ' . $e->getMessage());
                                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                                }
                            }

                            mutex()->release($mutexKey);
                        }
                        app()->end();
                    }
                }

                while (count($children) > 0) {
                    foreach ($children as $key => $pid) {
                        $res = pcntl_waitpid($pid, $status, WNOHANG);
                        if ($res == -1 || $res > 0) {
                            unset($children[$key]);
                        }
                    }
                    usleep(100000);
                }
            }
        }

        if (!$handled) {
            foreach ($campaignIds as $campaignId) {
                $mutexKey = sprintf('send-campaigns:campaign:%d:date:%s', $campaignId, date('Ymd'));
                if (mutex()->acquire($mutexKey)) {

                    // 1.5.3
                    try {
                        $this->sendCampaignStep1($campaignId);
                    } catch (Exception $e) {
                        $this->stdout(__LINE__ . ': ' . $e->getMessage());
                        Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

                        try {
                            if ($campaign = Campaign::model()->findByPk($campaignId)) {
                                $campaign->saveStatus(Campaign::STATUS_SENDING);
                            }
                        } catch (Exception $e) {
                            $this->stdout(__LINE__ . ': ' . $e->getMessage());
                            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                        }
                    }

                    mutex()->release($mutexKey);
                }
            }
        }
    }

    /**
     * @param int $campaignId
     * @param int $workerNumber
     *
     * @return int
     * @throws CDbException
     * @throws CException
     * @throws Throwable
     */
    protected function sendCampaignStep1(int $campaignId, int $workerNumber = 0)
    {
        $this->stdout(sprintf('Campaign Worker #%d looking into the campaign with ID: %d', $workerNumber, $campaignId));

        $statuses = [Campaign::STATUS_SENDING, Campaign::STATUS_PENDING_SENDING];

        /** @var Campaign|null $campaign */
        $campaign = Campaign::model()->findByPk((int)$campaignId);

        // since 1.3.7.3
        hooks()->doAction('console_command_send_campaigns_send_campaign_step1_start', $campaign);

        if (empty($campaign) || !in_array($campaign->status, $statuses)) {
            $this->stdout(sprintf('The campaign with ID: %d is not ready for processing.', $campaignId));
            return 1;
        }

        // this should never happen unless the list is removed while sending
        if (empty($campaign->list_id)) {
            $this->stdout(sprintf('The campaign with ID: %d is not ready for processing.', $campaignId));
            return 1;
        }

        // Make this available in entire class
        $this->_campaign = $campaign;

        /** @var Lists $list */
        $list = $campaign->list;

        /** @var Customer $customer */
        $customer = $list->customer;

        $this->stdout(sprintf('This campaign belongs to %s(uid: %s).', $customer->getFullName(), $customer->customer_uid));

        // put proper status and priority
        $this->stdout('Changing the campaign status into PROCESSING!');
        $campaign->saveStatus(Campaign::STATUS_PROCESSING); // because we need the extra checks we can't get in saveAttributes
        $campaign->saveAttributes(['priority' => 0]);

        $dsParams = ['customerCheckQuota' => false, 'useFor' => [DeliveryServer::USE_FOR_CAMPAIGNS]];
        $server   = DeliveryServer::pickServer(0, $campaign, $dsParams);

        if (empty($server)) {
            $message  = 'Cannot find a valid server to send the campaign email, aborting until a delivery server is available! ';
            $message .= 'Campaign UID: ' . $campaign->campaign_uid;
            Yii::log($message, CLogger::LEVEL_ERROR);
            $this->stdout($message);
            $campaign->saveStatus(Campaign::STATUS_SENDING);
            return 1;
        }

        if (!empty($customer->language_id)) {
            $language = Language::model()->findByPk((int)$customer->language_id);
            if (!empty($language)) {
                app()->setLanguage($language->getLanguageAndLocaleCode());
            }
        }

        /** @var OptionCronDelivery $cronDelivery */
        $cronDelivery = container()->get(OptionCronDelivery::class);

        // find the subscribers limit
        $limit = (int)$customer->getGroupOption('campaigns.subscribers_at_once', $cronDelivery->getSubscribersAtOnce());

        $attachments = CampaignAttachment::model()->findAll([
            'select'    => 'file',
            'condition' => 'campaign_id = :cid',
            'params'    => [':cid' => $campaign->campaign_id],
        ]);

        $changeServerAt    = (int)$customer->getGroupOption('campaigns.change_server_at', $cronDelivery->getChangeServerAt());
        $maxBounceRate     = (float)$customer->getGroupOption('campaigns.max_bounce_rate', $cronDelivery->getMaxBounceRate());
        $maxComplaintRate  = (float)$customer->getGroupOption('campaigns.max_complaint_rate', $cronDelivery->getMaxComplaintRate());

        $this->sendCampaignStep2([
            'campaign'                => $campaign,
            'customer'                => $customer,
            'list'                    => $list,
            'server'                  => $server,
            'limit'                   => $limit,
            'offset'                  => 0,
            'changeServerAt'          => $changeServerAt,
            'maxBounceRate'           => $maxBounceRate,
            'maxComplaintRate'        => $maxComplaintRate,
            'canChangeCampaignStatus' => true,
            'attachments'             => $attachments,
            'workerNumber'            => 0,
        ]);

        // since 1.3.9.7
        hooks()->doAction('console_command_send_campaigns_send_campaign_step1_end', $campaign);

        return 0;
    }

    /**
     * @param array $params
     *
     * @return int
     * @throws CException
     * @throws Throwable
     */
    protected function sendCampaignStep2(array $params = [])
    {
        // max number of subs allowed to send this time
        $maxSubscribers = $this->_customerData[$params['customer']->customer_id]['campaigns'][$params['campaign']->campaign_id];

        $handled = false;
        if ($this->getCanUsePcntl() && ($subscriberBatchesInParallel = $this->getSubscriberBatchesInParallel()) > 1) {
            $handled = true;

            // 1.9.24
            // #pcntlFxN1
            // we do same calculations a bit down the road so that we do not spawn more processes than it is actually needed.
            // pay attention in both code snippets if doing changes, they need to behave the same
            $initialMaxSubscribers = $maxSubscribers;
            $initialParamsLimit    = $params['limit'];
            $newSubscriberBatchesInParallel = 0;
            for ($i = 0; $i < $subscriberBatchesInParallel; ++$i) {
                if ($maxSubscribers <= $params['limit']) {
                    $params['limit'] = $maxSubscribers;
                }
                $maxSubscribers -= $params['limit'];
                $maxSubscribers  = $maxSubscribers > 0 ? $maxSubscribers : 0;
                $params['limit'] = $params['limit'] > 0 ? $params['limit'] : 0;
                if (empty($params['limit'])) {
                    break;
                }
                $newSubscriberBatchesInParallel++;
            }
            $subscriberBatchesInParallel = $newSubscriberBatchesInParallel;
            $maxSubscribers  = $initialMaxSubscribers;
            $params['limit'] = $initialParamsLimit;
            // #pcntlFxN1

            // 1.6.8 - this counter will be decremented under a mutex to allow sync for sendCampaignStep3 method
            // to avoid sending duplicates when under load and some processes start sending while others just load
            // data from database. When this counter is zero, we assume all processes loaded data in memory from database
            // and we can move on with sending
            $fsCounterKey = __CLASS__ . '::findSubscribersSyncLock::' . $params['campaign']->campaign_id;
            cache()->set($fsCounterKey, $subscriberBatchesInParallel);
            //

            // make sure we deny this for all right now.
            $params['canChangeCampaignStatus'] = false;

            // make sure we close the external connections
            $this->setExternalConnectionsActive(false);

            $children = [];
            $subscriberBatchesInParallelCounter = $subscriberBatchesInParallel;

            // 1.8.0 #pNa2E
            // offset must be kept separately because $params['limit'] might change from
            // iteration to iteration which might lead to subscribers overalapping
            // when calculating the offset
            $offset = 0;

            for ($i = 0; $i < $subscriberBatchesInParallel; ++$i) {

                // #pcntlFxN1
                // this piece connects with a similar one above, please be aware when doing changes here as they must stay in sync
                // 1.3.5.7
                if ($maxSubscribers <= $params['limit']) {
                    $params['limit'] = $maxSubscribers;
                }
                $maxSubscribers -= $params['limit'];
                $maxSubscribers  = $maxSubscribers > 0 ? $maxSubscribers : 0;
                $params['limit'] = $params['limit'] > 0 ? $params['limit'] : 0;
                $subscriberBatchesInParallelCounter--;
                // 1.9.13 - we're done, no need to spawn another process
                if (empty($params['limit'])) {
                    break;
                }
                // #pcntlFxN1

                // 1.8.0 #pNa2E
                $params['workerNumber']            = $i + 1;
                $params['offset']                  = $offset;
                $params['canChangeCampaignStatus'] = ($i == 0); // keep an eye on this.
                $offset                            = $params['offset'] + $params['limit'];
                //

                $pid = pcntl_fork();
                if ($pid == -1) {
                    continue;
                }

                // Parent
                if ($pid) {
                    $children[] = $pid;
                }

                // Child
                if (!$pid) {

                    // TODO: Remove me if 1.8.0 #pNa2E proves correct
                    // $params['workerNumber'] = $i + 1;
                    // $params['offset'] = ($i * $params['limit']);
                    // $params['canChangeCampaignStatus'] = ($i == 0); // keep an eye on this.

                    $mutexKey = sprintf(
                        'send-campaigns:campaign:%s:date:%s:offset:%d:limit:%d',
                        $params['campaign']->campaign_uid,
                        date('Ymd'),
                        $params['offset'],
                        $params['limit']
                    );

                    if (mutex()->acquire($mutexKey)) {

                        // 1.5.3
                        try {
                            $this->sendCampaignStep3($params);
                        } catch (Exception $e) {
                            $this->stdout(__LINE__ . ': ' . $e->getMessage());
                            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

                            try {
                                $params['campaign']->saveStatus(Campaign::STATUS_SENDING);
                            } catch (Exception $e) {
                                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                            }
                        }

                        mutex()->release($mutexKey);
                    }
                    app()->end();
                }
            }

            if (count($children) == 0) {
                $handled = false;
            }

            while (count($children) > 0) {
                foreach ($children as $key => $pid) {
                    $res = pcntl_waitpid($pid, $status, WNOHANG);
                    if ($res == -1 || $res > 0) {
                        unset($children[$key]);
                    }
                }
                usleep(100000);
            }
        }

        if (!$handled) {

            // 1.3.5.7
            if ($maxSubscribers > $params['limit']) {
                $maxSubscribers -= $params['limit'];
            } else {
                $params['limit'] = $maxSubscribers;
            }
            $params['limit'] = $params['limit'] > 0 ? $params['limit'] : 0;
            //

            $mutexKey = sprintf(
                'send-campaigns:campaign:%s:date:%s:offset:%d:limit:%d',
                $params['campaign']->campaign_uid,
                date('Ymd'),
                $params['offset'],
                $params['limit']
            );

            if (mutex()->acquire($mutexKey)) {

                // 1.5.3
                try {
                    $this->sendCampaignStep3($params);
                } catch (Exception $e) {
                    $this->stdout(__LINE__ . ': ' . $e->getMessage());
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

                    try {
                        $params['campaign']->saveStatus(Campaign::STATUS_SENDING);
                    } catch (Exception $e) {
                        $this->stdout(__LINE__ . ': ' . $e->getMessage());
                        Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                    }
                }

                mutex()->release($mutexKey);
            }
        }

        return 0;
    }

    /**
     * @param array $params
     *
     * @return int
     * @throws CDbException
     * @throws CException
     * @throws Throwable
     */
    protected function sendCampaignStep3(array $params = [])
    {
        /** @var Campaign $campaign */
        $campaign = $params['campaign'];

        /** @var Customer $customer */
        $customer = $params['customer'];

        /** @var int $workerNumber */
        $workerNumber = $params['workerNumber'];

        /** @var int $offset */
        $offset = $params['offset'];

        /** @var int $limit */
        $limit = $params['limit'];

        /** @var int $canChangeCampaignStatus */
        $canChangeCampaignStatus = $params['canChangeCampaignStatus'];

        /** @var int $maxBounceRate */
        $maxBounceRate = $params['maxBounceRate'];

        /** @var int $maxComplaintRate */
        $maxComplaintRate = $params['maxComplaintRate'];

        $this->stdout(sprintf('Looking for subscribers for campaign with uid %s...(This is subscribers worker #%d)', $campaign->campaign_uid, $workerNumber));
        $this->stdout(sprintf('For campaign with uid %s and worker %d the offset is %d and the limit is %d', $campaign->campaign_uid, $workerNumber, $offset, $limit));

        $this->stdout(sprintf('Finding subscribers for campaign with uid %s and worker %d the offset is %d and the limit is %d', $campaign->campaign_uid, $workerNumber, $offset, $limit));
        $startTime = microtime(true);

        /** @var ListSubscriber[] $subscribers */
        $subscribers = $this->findSubscribers($offset, $limit, $campaign);

        $endTime = round(microtime(true) - $startTime, 5);
        $this->stdout(sprintf('Finding subscribers for campaign with uid %s and worker %d the offset is %d and the limit is %d took %.5f seconds', $campaign->campaign_uid, $workerNumber, $offset, $limit, $endTime));

        // 1.6.8 - this will force all parallel processes for this campaign to wait until all the other processes have
        // loaded the data from database. This avoids sending duplicates when some processes started sending while others
        // are still at the loading data from database step.
        if ($this->getCanUsePcntl() && ($subscriberBatchesInParallel = $this->getSubscriberBatchesInParallel()) > 1) {
            $this->stdout(sprintf('Sync start for subscriber batches for campaign with uid %s in worker %d', $campaign->campaign_uid, $workerNumber));
            $startTime      = microtime(true);
            $fsCounterKey   = __CLASS__ . '::findSubscribersSyncLock::' . $campaign->campaign_id;

            // each process must decrement it's own increment, so just once!
            // the while loop is here to make sure we eventually acquire the lock
            while (true) {
                if (!mutex()->acquire($fsCounterKey)) {
                    continue;
                }
                $fsCounter = (int)cache()->get($fsCounterKey);
                if ($fsCounter <= 0) {
                    mutex()->release($fsCounterKey);
                    break;
                }
                $fsCounter = $fsCounter - 1;
                $fsCounter = $fsCounter > 0 ? $fsCounter : 0;
                cache()->set($fsCounterKey, $fsCounter);
                mutex()->release($fsCounterKey);
                break;
            }
            // wait for all processes to have this counter decremented only once on their end.
            // when they are done, the counter should be 0 and we can move on
            while (true) {
                if (!mutex()->acquire($fsCounterKey)) {
                    continue;
                }
                $fsCounter = (int)cache()->get($fsCounterKey);
                if ($fsCounter <= 0) {
                    mutex()->release($fsCounterKey);
                    break;
                }
                mutex()->release($fsCounterKey);
            }

            $endTime = round(microtime(true) - $startTime, 5);
            $this->stdout(sprintf('Sync end for subscriber batches for campaign with uid %s in worker %d took %.5f seconds', $campaign->campaign_uid, $workerNumber, $endTime));
        }
        //

        $this->stdout(sprintf('This subscribers worker(#%d) will process %d subscribers for this campaign...', $workerNumber, count($subscribers)));

        // run some cleanup on subscribers
        $this->stdout('Running subscribers cleanup...');

        // since 1.3.6.2 - in some very rare conditions this happens!
        foreach ($subscribers as $index => $subscriber) {
            if (empty($subscriber->email)) {
                $subscriber->delete();
                unset($subscribers[$index]);
                continue;
            }

            // 1.3.7
            $separators = [',', ';'];
            foreach ($separators as $separator) {
                if (strpos((string)$subscriber->email, $separator) === false) {
                    continue;
                }

                $emails = explode($separator, (string)$subscriber->email);
                $emails = array_map('trim', $emails);

                while (!empty($emails)) {
                    $email = (string)array_shift($emails);
                    if (!FilterVarHelper::email($email)) {
                        continue;
                    }
                    $exists = ListSubscriber::model()->findByAttributes([
                        'list_id' => $subscriber->list_id,
                        'email'   => $email,
                    ]);
                    if (!empty($exists)) {
                        continue;
                    }
                    $subscriber->email = $email;
                    $subscriber->save(false);
                    break;
                }

                foreach ($emails as $email) {
                    if (!FilterVarHelper::email($email)) {
                        continue;
                    }
                    $exists = ListSubscriber::model()->findByAttributes([
                        'list_id' => $subscriber->list_id,
                        'email'   => $email,
                    ]);
                    if (!empty($exists)) {
                        continue;
                    }
                    $sub = new ListSubscriber();
                    $sub->list_id = (int)$subscriber->list_id;
                    $sub->email   = $email;
                    $sub->save();
                }
                break;
            }
            //

            if (!FilterVarHelper::email($subscriber->email)) {
                $subscriber->delete();
                unset($subscribers[$index]);
                continue;
            }
        }

        // reset the keys
        $subscribers      = array_values($subscribers);
        $subscribersCount = count($subscribers);

        $this->stdout(sprintf('Checking subscribers count after cleanup: %d', $subscribersCount));

        try {
            $params['subscribers'] = &$subscribers;

            $this->processSubscribersLoop($params);

            // free mem
            unset($params);
        } catch (Exception $e) {

            // free mem
            unset($params);

            $this->stdout(sprintf('Exception thrown: %s', $e->getMessage()));

            // exception code to be returned later
            $code = (int)$e->getCode();

            // make sure sending is resumed next time.
            $campaign->status = Campaign::STATUS_SENDING;

            // since 1.9.29
            if ($code == 98) {
                $reloadedStatusMutexKey = sha1(sprintf(
                    '%s:campaign:%d:getReloadedStatus:%s',
                    __METHOD__,
                    $campaign->campaign_id,
                    date('YmdH')
                ));
                if (mutex()->acquire($reloadedStatusMutexKey, 120)) {
                    $reloadedStatus = $campaign->getReloadedStatus();
                    if (!empty($reloadedStatus) && $reloadedStatus != Campaign::STATUS_PROCESSING) {
                        $campaign->status = $reloadedStatus;
                        $canChangeCampaignStatus = true;
                    }
                    mutex()->release($reloadedStatusMutexKey);
                }
            }
            // 1.9.29 end

            if ($canChangeCampaignStatus) {

                // save the changes, but no validation
                $campaign->saveStatus();

                if ($code == 98 && $customer->getIsOverQuota()) {
                    $campaign->postponeBecauseCustomerReachedQuota();
                }

                // since 1.3.5.9
                $this->checkCampaignOverMaxBounceRate($campaign, $maxBounceRate);

                // since 1.6.1
                $this->checkCampaignOverMaxComplaintRate($campaign, $maxComplaintRate);
            }

            // log the error so we can reference it
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

            // return the exception code
            return $code;
        }

        $this->stdout('', false);
        $this->stdout(sprintf('Done processing %d subscribers!', $subscribersCount));

        if ($canChangeCampaignStatus) {

            // do a final check for this campaign to see if it still exists or has been somehow changed from web interface.
            // this used to exist in the foreach loop but would cause so much overhead that i think is better to move it here
            // since if a campaign is paused from web interface it will keep that status anyway so it won't affect customers and will improve performance
            $reloadedStatus = $campaign->getReloadedStatus();

            if (empty($reloadedStatus) || $reloadedStatus != Campaign::STATUS_PROCESSING) {
                if (!empty($reloadedStatus)) {
                    $campaign->saveStatus($reloadedStatus);
                    $this->checkCampaignOverMaxBounceRate($campaign, $maxBounceRate);
                    $this->checkCampaignOverMaxComplaintRate($campaign, $maxComplaintRate);
                    $this->stdout('Campaign status has been changed successfully!');
                }
                return 0;
            }

            // the sending batch is over.
            // if we don't have enough subscribers for next batch, we stop.
            $count = $this->countSubscribers($campaign);

            // since 1.9.14
            $count = $this->handleCampaignTimewarp($campaign, $count);

            if ($count === 0) {
                if ($this->markCampaignSent($campaign)) {
                    $this->stdout('Campaign has been marked as sent!');
                }
                return 0;
            }

            // make sure sending is resumed next time
            $campaign->saveStatus(Campaign::STATUS_SENDING);
            $this->checkCampaignOverMaxBounceRate($campaign, $maxBounceRate);
            $this->checkCampaignOverMaxComplaintRate($campaign, $maxComplaintRate);
            $this->stdout('Campaign status has been changed successfully!');
        }

        $this->stdout('Done processing the campaign.');

        return 0;
    }

    /**
     * @param array $params
     *
     * @return mixed
     * @throws CException
     * @throws Throwable
     */
    protected function processSubscribersLoop(array $params = [])
    {
        /** @var Campaign $campaign */
        $campaign = $params['campaign'];

        /** @var ListSubscriber[] $subscribers */
        $subscribers = $params['subscribers'];

        /** @var int $changeServerAt */
        $changeServerAt = $params['changeServerAt'];

        /** @var Lists $list */
        $list = $params['list'];

        /** @var Customer $customer */
        $customer = $params['customer'];

        /** @var DeliveryServer|null $server */
        $server = !empty($params['server']) ? $params['server'] : null;

        /** @var array $attachments */
        $attachments = !empty($params['attachments']) && is_array($params['attachments']) ? $params['attachments'] : [];

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        // 1.4.5
        if ($campaign->getIsPaused()) {
            throw new Exception('Campaign has been paused!', 98);
        }

        $subscribersCount = count($subscribers);
        $processedCounter = 0;
        $failuresCount    = 0;
        $serverHasChanged = false;

        $dsParams = empty($params['dsParams']) || !is_array($params['dsParams']) ? [] : $params['dsParams'];
        $dsParams = CMap::mergeArray([
            'customerCheckQuota' => false,
            'serverCheckQuota'   => false,
            'useFor'             => [DeliveryServer::USE_FOR_CAMPAIGNS],
            'excludeServers'     => [],
        ], $dsParams);
        $domainPolicySubscribers = [];

        if (empty($server)) {
            $server = DeliveryServer::pickServer(0, $campaign, $dsParams);

            // 1.9.13
            // This block of code will execute only when we reach here from calling this method recursively because of domain policies.
            // We need to make sure we pull a delivery server so that we reach the end of the method and process the
            // subscribers that were domain policy rejected
            if (empty($server) && !empty($params['domainPolicySubscribersCounter']) && !empty($dsParams['excludeServers'])) {
                $dsParams['excludeServers'] = [];
                $server = DeliveryServer::pickServer(0, $campaign, $dsParams);
            }
            //
        }

        if (empty($server)) {
            $serverNotFoundMessage  = 'Cannot find a valid server to send the campaign email, aborting until a delivery server is available! ';
            $serverNotFoundMessage .= 'Campaign UID: ' . $campaign->campaign_uid;
            throw new Exception($serverNotFoundMessage, 99);
        }

        $this->stdout('Sorting the subscribers...');
        $subscribers = $this->sortSubscribers($subscribers);

        // 1.8.2 - preload the list of campaign group block subscribers. if any...
        $campaignGroupBlockSubscribersList = [];
        if (!empty($campaign->group_id) && CampaignGroupBlockSubscriber::model()->countByAttributes(['group_id' => (int)$campaign->group_id])) {
            $subscribersIds = [];
            foreach ($subscribers as $subscriber) {
                $subscribersIds[] = (int)$subscriber->subscriber_id;
            }
            $subscribersIdsChunks = array_chunk($subscribersIds, 100);
            $models = [];
            foreach ($subscribersIdsChunks as $subscribersIdsChunk) {
                $criteria = new CDbCriteria();
                $criteria->select = 'subscriber_id';
                $criteria->compare('group_id', $campaign->group_id);
                $criteria->addInCondition('subscriber_id', $subscribersIdsChunk);
                $models = CampaignGroupBlockSubscriber::model()->findAll($criteria);
                foreach ($models as $model) {
                    $campaignGroupBlockSubscribersList[$model->subscriber_id] = true;
                }
            }
            unset($subscribersIds, $subscribersIdsChunks, $subscribersIdsChunk, $models);
        }
        //

        $this->stdout(sprintf('Entering the foreach processing loop for all %d subscribers...', $subscribersCount));

        /**
         * @var int $index
         * @var ListSubscriber $subscriber
         */
        foreach ($subscribers as $index => $subscriber) {

            // 2.0.22 - see https://github.com/onetwist-software/mailwizz/issues/590
            // When using PCNTL there is a global command mutex that prevents multiple instances of the send-campaigns command to run at same time.
            // When not using PCNTL, the lack of this mutex allow multiple instances of send-campaigns to start sending
            // and if previous send-campaigns instances did not finish sending yet, the system will not see if the customer went over the entire assigned quota.
            // With this additional check, we make sure that no matter how many instances of send-campaigns are called, the customer does not go over quota.
            if (!$this->getCanUsePcntl() && $customer->getIsOverQuota()) {
                throw new Exception(sprintf('Not using PCNTL, this customer(id %d) reached the assigned quota!', $customer->customer_id), 98);
            }

            // 1.4.5
            if (rand(0, 10) <= 5) {
                // 1.9.29 - check any status that isn't processing
                $reloadedStatus = $campaign->getReloadedStatus();
                if (!empty($reloadedStatus) && !in_array($reloadedStatus, [Campaign::STATUS_PROCESSING, Campaign::STATUS_SENDING])) {
                    throw new Exception(sprintf('Campaign status has been changed to: %s', $reloadedStatus), 98);
                }
            }

            $this->stdout('', false);
            $this->stdout(sprintf('%s - %d/%d', $subscriber->email, ($index+1), $subscribersCount));
            $this->stdout(sprintf('Checking if we can send to domain of %s...', $subscriber->email));

            // if this server is not allowed to send to this email domain, then just skip it.
            if (!$server->canSendToDomainOf($subscriber->email)) {
                $domainPolicySubscribers[] = $subscriber;
                unset($subscribers[$index]);
                continue;
            }

            // 1.4.5
            hooks()->doAction('console_send_campaigns_command_process_subscribers_loop_in_loop_start', $collection = new CAttributeCollection([
                'campaign'                => $campaign,
                'subscriber'              => $subscriber,
                'server'                  => $server,
                'domainPolicySubscribers' => $domainPolicySubscribers,
                'subscribers'             => $subscribers,
                'index'                   => $index,
                'continueProcessing'      => true,
            ]));
            if (!$collection->itemAt('continueProcessing')) {
                continue;
            }

            /** @var ListSubscriber[] $domainPolicySubscribers */
            $domainPolicySubscribers = $collection->itemAt('domainPolicySubscribers');

            /** @var ListSubscriber[] $subscribers */
            $subscribers = $collection->itemAt('subscribers');
            //

            // 1.4.4 - because of the temp queue campaigns
            $this->stdout(sprintf('Checking if %s is still confirmed...', $subscriber->email));
            if (!$subscriber->getIsConfirmed()) {
                $this->logDelivery($subscriber, t('campaigns', 'Subscriber not confirmed anymore!'), CampaignDeliveryLog::STATUS_ERROR, '', $server, $campaign);
                continue;
            }

            // if blacklisted, goodbye.
            $this->stdout(sprintf('Checking if %s is blacklisted...', $subscriber->email));
            /** @var EmailBlacklistCheckInfo|bool $blCheckInfo */
            $blCheckInfo = $subscriber->getIsBlacklisted(['checkZone' => EmailBlacklist::CHECK_ZONE_CAMPAIGN]);
            if ($blCheckInfo instanceof EmailBlacklistCheckInfo) {
                if ($blCheckInfo->getCustomerBlacklist()) {
                    $this->logDelivery($subscriber, $blCheckInfo->getReason(), CampaignDeliveryLog::STATUS_BLACKLISTED, '', $server, $campaign);
                } else {
                    $this->logDelivery($subscriber, t('campaigns', 'This email is blacklisted. Sending is denied!'), CampaignDeliveryLog::STATUS_BLACKLISTED, '', $server, $campaign);
                }
                continue;
            }

            // if in a campaign suppression list, goodbye.
            $this->stdout(sprintf('Checking if %s is listed in a campaign suppression list...', $subscriber->email));
            if (CustomerSuppressionListEmail::isSubscriberListedByCampaign($subscriber, $campaign)) {
                $this->logDelivery($subscriber, t('campaigns', 'This email is listed in a suppression list. Sending is denied!'), CampaignDeliveryLog::STATUS_SUPPRESSED, '', $server, $campaign);
                continue;
            }

            // 1.8.2 - if listed in a campaign group block, stop
            $this->stdout(sprintf('Checking if %s is blocked in the campaign group...', $subscriber->email));
            if (isset($campaignGroupBlockSubscribersList[$subscriber->subscriber_id])) {
                unset($campaignGroupBlockSubscribersList[$subscriber->subscriber_id]);
                $this->logDelivery($subscriber, t('campaigns', 'This email is blocked for the current campaign group. Sending is denied!'), CampaignDeliveryLog::STATUS_BLOCKED, '', $server, $campaign);
                continue;
            }
            //

            // in case the server is over quota
            $this->stdout('Checking if the server is over quota...');
            if ($server->getIsOverQuota()) {
                $this->stdout('Server is over quota, choosing another one.');
                $currentServerId = (int)$server->server_id;
                if (!($server = DeliveryServer::pickServer($currentServerId, $campaign, $dsParams))) {
                    $message  = 'Cannot find a valid server to send the campaign email, aborting until a delivery server is available! ';
                    $message .= 'Campaign UID: ' . $campaign->campaign_uid;
                    throw new Exception($message, 99);
                }
            }

            $this->stdout('Preparing the entire email...');
            $emailParams = $this->prepareEmail($subscriber, $server, $campaign);

            if (empty($emailParams) || !is_array($emailParams)) {
                $this->logDelivery($subscriber, t('campaigns', 'Unable to prepare the email content!'), CampaignDeliveryLog::STATUS_ERROR, '', $server, $campaign);
                continue;
            }

            // since 1.5.2
            if (empty($emailParams['subject']) || (empty($emailParams['body']) && empty($emailParams['plainText']))) {
                $this->logDelivery($subscriber, t('campaigns', 'Unable to prepare the email content!'), CampaignDeliveryLog::STATUS_ERROR, '', $server, $campaign);
                continue;
            }

            if ($failuresCount >= 5 || ($changeServerAt > 0 && $processedCounter >= $changeServerAt && !$serverHasChanged)) {
                $this->stdout('Try to change the delivery server...');
                $currentServerId = (int)$server->server_id;
                $_serverChanged = false;
                if ($newServer = DeliveryServer::pickServer((int)$currentServerId, $campaign, $dsParams)) {
                    $_serverChanged = true;
                    $server = clone $newServer;
                    unset($newServer);
                    $this->stdout('Delivery server has been changed.');
                } else {
                    $this->stdout('Delivery server cannot be changed.');
                }

                $failuresCount    = 0;
                $processedCounter = 0;
                $serverHasChanged = true;

                // 1.9.13
                if ($_serverChanged) {
                    $this->stdout(sprintf('Checking if we can send to domain of %s after we changed the delivery server...', $subscriber->email));
                    // if this server is not allowed to send to this email domain, then just skip it.
                    if (!$server->canSendToDomainOf($subscriber->email)) {
                        $domainPolicySubscribers[] = $subscriber;
                        unset($subscribers[$index]);
                        continue;
                    }
                }
            }

            $listUnsubscribeHeaderValue = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/unsubscribe/' . $subscriber->subscriber_uid . '/' . $campaign->campaign_uid . '?source=email-client-unsubscribe-button');
            $listUnsubscribeHeaderValue = '<' . $listUnsubscribeHeaderValue . '>';

            $reportAbuseUrl = $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid . '/report-abuse/' . $list->list_uid . '/' . $subscriber->subscriber_uid);

            // since 1.3.4.9
            $listUnsubscribeHeaderEmail = '';
            if (!empty($campaign->reply_to)) {
                $listUnsubscribeHeaderEmail = $campaign->reply_to;
            }
            if (!empty($server->reply_to_email) && $server->force_reply_to === DeliveryServer::FORCE_REPLY_TO_ALWAYS) {
                $listUnsubscribeHeaderEmail = $server->reply_to_email;
            }
            if ($_email = (string)$customer->getGroupOption('campaigns.list_unsubscribe_header_email', '')) {
                $listUnsubscribeHeaderEmail = $_email;
            }
            if (!empty($listUnsubscribeHeaderEmail)) {
                $_subject = sprintf('Campaign-Uid:%s / Subscriber-Uid:%s - Unsubscribe request', $campaign->campaign_uid, $subscriber->subscriber_uid);
                $_body    = 'Please unsubscribe me!';
                $mailToUnsubscribeHeader    = sprintf(', <mailto:%s?subject=%s&body=%s>', $listUnsubscribeHeaderEmail, $_subject, $_body);
                $listUnsubscribeHeaderValue .= $mailToUnsubscribeHeader;
            }
            //

            $emailParams['headers'] = [
                ['name' => 'List-Unsubscribe',      'value' => $listUnsubscribeHeaderValue],
                ['name' => 'List-Unsubscribe-Post', 'value' => 'List-Unsubscribe=One-Click'],
                ['name' => 'List-Id',               'value' => $list->list_uid . ' <' . $list->display_name . '>'],
                ['name' => 'X-Report-Abuse',        'value' => $reportAbuseUrl],
                ['name' => 'X-EBS',                 'value' => $optionUrl->getFrontendUrl('lists/block-address')],
                ['name' => 'Feedback-ID',           'value' => $this->getFeedbackIdHeaderValue($campaign, $subscriber, $list, $customer)],
            ];

            // since 1.3.4.6
            $headers = !empty($server->additional_headers) && is_array($server->additional_headers) ? $server->additional_headers : [];
            $headers = (array)hooks()->applyFilters('console_command_send_campaigns_campaign_custom_headers', $headers, $campaign, $subscriber, $customer, $server, $emailParams);
            $headers = $server->parseHeadersFormat($headers);

            // since 1.3.9.8
            $defaultHeaders = (string)$customer->getGroupOption('servers.custom_headers', '');
            if (!empty($defaultHeaders)) {
                $defaultHeaders = DeliveryServerHelper::getOptionCustomerCustomHeadersArrayFromString($defaultHeaders);
                $headers = CMap::mergeArray($defaultHeaders, $headers);
            }

            if (!empty($headers)) {
                $headersNames = [];
                foreach ($headers as $header) {
                    if (!is_array($header) || !isset($header['name'], $header['value']) || isset($headersNames[$header['name']])) {
                        continue;
                    }

                    // 1.7.6
                    if (strtolower((string)$header['name']) == 'x-force-return-path') {
                        $header['value'] = (string)str_replace('@', '{{at}}', $header['value']);
                    }
                    //

                    $headersNames[$header['name']] = true;
                    $headerSearchReplace           = CampaignHelper::getCommonTagsSearchReplace($header['value'], $campaign, $subscriber, $server);
                    $header['value']               = (string)str_replace(array_keys($headerSearchReplace), array_values($headerSearchReplace), $header['value']);

                    // since 1.7.6
                    if (strtolower((string)$header['name']) == 'x-force-return-path') {
                        $header['value']           = str_replace('@', '=', $header['value']);
                        $header['value']           = str_replace('{{at}}', '@', $header['value']);
                        $emailParams['returnPath'] = $header['value'];
                    }
                    //

                    $emailParams['headers'][] = $header;
                }
                unset($headers, $headersNames);
            }

            if (!empty($attachments) && is_array($attachments)) {
                $emailParams['attachments'] = [];
                foreach ($attachments as $attachment) {
                    $emailParams['attachments'][] = (string)Yii::getPathOfAlias('root') . $attachment->file;
                }
            }

            $processedCounter++;
            if ($processedCounter >= $changeServerAt) {
                $serverHasChanged = false;
            }

            // since 1.3.6.6
            if (
                !empty($campaign->option->tracking_domain_id) &&
                !empty($campaign->option->trackingDomain) &&
                $campaign->option->trackingDomain->getIsVerified()
            ) {
                $emailParams['trackingEnabled']     = true;
                $emailParams['trackingDomainModel'] = $campaign->option->trackingDomain;
            }
            //

            // since 1.3.5.9
            /** @var array $emailParams */
            $emailParams = (array)hooks()->applyFilters('console_command_send_campaigns_before_send_to_subscriber', $emailParams, $campaign, $subscriber, $customer, $server);

            // set delivery object
            $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_CAMPAIGN)->setDeliveryObject($campaign);

            // default status
            $status = CampaignDeliveryLog::STATUS_SUCCESS;

            $this->stdout(sprintf('Using delivery server: %s (ID: %d).', $server->hostname, $server->server_id));

            // since 2.0.0
            $sent      = false;
            $mustSend  = true;
            $response  = 'N/A';
            $sendGroupHashKey = sprintf('group:%d:email:%s', (int)$campaign->send_group_id, $subscriber->email);

            if (!empty($campaign->send_group_id)) {
                $sendGroupStart = microtime(true);
                $this->stdout(sprintf(
                    'This campaign is part of "%s" sending group, checking if this email address has received any of the group campaigns...',
                    $campaign->sendGroup->name
                ));

                if (!mutex()->acquire($sendGroupHashKey, 10)) {
                    throw new Exception('Unable to acquire the send group mutex', 99);
                }

                if (cache()->get($sendGroupHashKey)) {
                    $mustSend = false;
                    $sent     = true;
                    $response = 'Sent by other campaign in same sending group!';
                    $status   = CampaignDeliveryLog::STATUS_HANDLED_BY_OTHER_SEND_GROUP_CAMPAIGN;
                } else {
                    cache()->set($sendGroupHashKey, (int)$campaign->campaign_id, 0);

                    if ($campaign->sendGroup->hasSentToEmailAddress($subscriber->email)) {
                        $mustSend = false;
                        $sent     = true;
                        $response = 'Sent by other campaign in same sending group!';
                        $status   = CampaignDeliveryLog::STATUS_HANDLED_BY_OTHER_SEND_GROUP_CAMPAIGN;
                    }
                }

                $this->_sendGroupHashKeys[] = $sendGroupHashKey;
                mutex()->release($sendGroupHashKey);

                $this->stdout('Email address check for sending group finished, took: ' . (round(microtime(true) - $sendGroupStart, 5)));
            }
            //

            /**
             * @since 1.5.3
             * Put a final check on the quota and put the sending under the mutex
             * to avoid concurrent access at incrementing the quota.
             * Keep in mind that the below is the best we can get because
             * we check the quota and increment it under a unique lock.
             */
            $canHaveQuota   = $server->getCanHaveQuota();
            $mutexKey       = sha1(__METHOD__ . '-delivery-server-usage-' . (int)$server->server_id . '-' . date('Ymd'));
            if ($canHaveQuota && $mustSend && !mutex()->acquire($mutexKey, 60)) {
                $message = 'Cannot acquire the mutex for delivery server to send the email! ';
                $message .= 'Campaign UID: ' . $campaign->campaign_uid;
                throw new Exception($message, 99);
            }

            try {
                if ($mustSend) {

                    /**
                     * We cannot swap the server anymore here because all the
                     * above information has been set for this server, that is headers, tags, etc.
                     * Therefore, we just give up and try again in the next run.
                     */
                    if ($canHaveQuota && $server->getIsOverQuota()) {
                        $message  = 'Cannot find a valid server to send the campaign email, aborting until a delivery server is available! ';
                        $message .= 'Campaign UID: ' . $campaign->campaign_uid;
                        throw new Exception($message, 99);
                    }

                    // optimistic logging
                    $deliveryServerUsageLog = null;
                    if ($server->getCanLogUsage()) {
                        $start = microtime(true);
                        $deliveryServerUsageLog = $server->logUsage();
                        if (!$deliveryServerUsageLog) {
                            $message  = 'Cannot log usage for the delivery server! ';
                            $message .= 'Campaign UID: ' . $campaign->campaign_uid;
                            throw new Exception($message, 99);
                        }
                        $this->stdout('Logging into delivery server usage log took: ' . (round(microtime(true) - $start, 5)));
                    }
                }
                //

                // since 1.5.8, release the lock
                if ($canHaveQuota && $mustSend) {
                    mutex()->release($mutexKey);
                }

                if ($mustSend) {
                    $this->stdout('Sending the email to the delivery server...');
                    $start = microtime(true);

                    $server->disableLogUsage();
                    try {
                        $sent     = $server->sendEmail($emailParams);
                        $response = $server->getMailer()->getLog();
                    } catch (Exception $e) {
                        $sent     = false;
                        $response = $e->getMessage();
                    }
                    $server->enableLogUsage();

                    $this->stdout('Communication with the delivery server took: ' . (round(microtime(true) - $start, 5)));
                }
            } catch (Exception $e) {
                if ($canHaveQuota) {
                    mutex()->release($mutexKey);
                }

                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                $this->stdout($e->getMessage());

                throw new Exception($e->getMessage(), 99);
            }
            // end 1.5.3

            $messageId = null;

            // 1.7.6
            $stdoutData = [
                'category'  => 'email.send.subscriber',
                'sent'      => $sent ? true : false,
                'campaign'  => [
                    'uid'  => $campaign->campaign_uid,
                    'name' => $campaign->name,
                ],
                'subscriber' => [
                    'uid'   => $subscriber->subscriber_uid,
                    'email' => $subscriber->email,
                ],
                'customer' => [
                    'uid'   => $customer->customer_uid,
                    'name'  => $customer->getFullName(),
                ],
                'server' => [
                    'id'   => $server->server_id,
                    'name' => $server->name,
                ],
            ];
            //

            if (!$sent) {
                $failuresCount++;
                $status = CampaignDeliveryLog::STATUS_GIVEUP;
                $this->stdout(CMap::mergeArray($stdoutData, [
                    'message'   => sprintf('Sending failed with: %s', $response),
                ]));
            } else {
                $failuresCount = 0;
                $this->stdout(CMap::mergeArray($stdoutData, [
                    'message' => sprintf('Sending response is: %s', (!empty($response) ? $response : 'OK')),
                ]));
            }

            if ($sent && is_array($sent) && !empty($sent['message_id'])) {
                $messageId = $sent['message_id'];
            }

            if ($sent) {
                $this->stdout('Sending OK.');
            }

            $start = microtime(true);
            $this->stdout(sprintf('Done for %s, logging delivery...', $subscriber->email));
            $this->logDelivery($subscriber, $response, $status, (string)$messageId, $server, $campaign);
            $this->stdout('Logging delivery took: ' . (round(microtime(true) - $start, 5)));

            // since 2.0.23
            $undoDeliveryServerUsageLog = $mustSend && (
                $status == CampaignDeliveryLog::STATUS_GIVEUP &&
                $campaign->customer->getGroupOption('quota_counters.campaign_giveup_emails', 'no') == 'no'
            );
            if ($undoDeliveryServerUsageLog && !empty($deliveryServerUsageLog)) {
                $start = microtime(true);
                $server->undoLogUsage($deliveryServerUsageLog);
                $this->stdout('Undo adding into delivery server usage log took: ' . (round(microtime(true) - $start, 5)));
            }
            //

            // since 1.4.2
            if ($sent) {
                $this->handleCampaignSentActionSubscriberField($campaign, $subscriber);
                $this->handleCampaignSentActionSubscriber($campaign, $subscriber);
            }

            // since 2.0.29
            if (
                $campaign->getCanDoAbTest() && $subscriber->getLastCampaignDeliveryLogId() &&
                !empty($emailParams['abSubject']) && $emailParams['abSubject'] instanceof CampaignAbtestSubject
            ) {
                /** @var CampaignAbtestSubject $abSubject */
                $abSubject = $emailParams['abSubject'];

                if ($sent) {
                    // save the relation so that later when opens happen, we can connect the
                    // subject with the campaign/subscriber
                    try {
                        $abTestSubjectToDeliveryLog = new CampaignAbtestSubjectToDeliveryLog();
                        $abTestSubjectToDeliveryLog->subject_id = (int)$abSubject->subject_id;
                        $abTestSubjectToDeliveryLog->log_id     = (int)$subscriber->getLastCampaignDeliveryLogId();
                        $abTestSubjectToDeliveryLog->save();
                    } catch (Exception $e) {
                        Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                    }
                } else {
                    // since the email has not been sent, and the counters were incremented already
                    // it only makes sense to decrement them
                    $abSubject->incrementUsageCount(-1);
                }
            }
            //

            // since 1.3.4.6
            hooks()->doAction('console_command_send_campaigns_after_send_to_subscriber', $campaign, $subscriber, $customer, $server, $sent, $response, $status);

            // since 1.3.8.8
            if (!empty($server->pause_after_send)) {
                $sleepSeconds = round($server->pause_after_send / 1000000, 6);
                $this->stdout(sprintf('According to server settings, sleeping for %d seconds.', $sleepSeconds));

                // if set to sleep for too much,
                // close the external connections otherwise will timeout.
                if ($sleepSeconds >= 30) {
                    $this->setExternalConnectionsActive(false);
                }

                // take a break
                usleep((int)$server->pause_after_send);

                // if set to sleep for too much, open the connections again
                if ($sleepSeconds >= 30) {
                    $this->setExternalConnectionsActive();
                }
            }
        }

        // free mem
        unset($subscribers);

        // since 1.3.6.3 - it's not 100% bullet proof but should be fine
        // for most of the use cases
        if (!isset($params['domainPolicySubscribersCounter'])) {
            $params['domainPolicySubscribersCounter'] = 0;
        }
        $params['domainPolicySubscribersCounter']++;
        if (!empty($domainPolicySubscribers)) {
            if (empty($params['domainPolicySubscribersMaxRounds'])) {
                $params['domainPolicySubscribersMaxRounds'] = 5 + (int)DeliveryServer::model()->countByAttributes([
                        'status' => DeliveryServer::STATUS_ACTIVE,
                    ]);
            }
            if ($params['domainPolicySubscribersCounter'] <= $params['domainPolicySubscribersMaxRounds']) {
                $params['subscribers'] = &$domainPolicySubscribers;
                $params['changeServerAt'] = 0;
                $params['dsParams']['excludeServers'][] = (int)$server->server_id;
                $params['dsParams']['excludeServers'] = array_unique($params['dsParams']['excludeServers']);
                $params['server'] = null;
                $this->stdout('', false);
                $this->stdout(sprintf(
                    'Processing the rest of %d subscribers because of delivery server domain policies...',
                    count($domainPolicySubscribers)
                ));
                $this->stdout('', false);
                return $this->processSubscribersLoop($params);
            }
            // 1.9.13
            /** @var ListSubscriber $subscriber */
            foreach ($domainPolicySubscribers as $subscriber) {
                $this->logDelivery(
                    $subscriber,
                    t('campaigns', 'Failed to send to this email address after multiple retries. Check your delivery servers domain policies.'),
                    CampaignDeliveryLog::STATUS_DOMAIN_POLICY_REJECT,
                    '',
                    $server,
                    $campaign
                );
            }
        }

        // free mem
        unset($params);
        return null;
    }

    /**
     * @since 1.3.5.9
     * @param Campaign $campaign
     * @param mixed $maxBounceRate
     *
     * @return void
     */
    protected function checkCampaignOverMaxBounceRate(Campaign $campaign, $maxBounceRate)
    {
        if ((int)$maxBounceRate < 0 || $campaign->getIsBlocked()) {
            return;
        }

        $bouncesRate = (float)$campaign->getStats()->getBouncesRate() - (float)$campaign->getStats()->getInternalBouncesRate();
        if ((float)$bouncesRate > (float)$maxBounceRate) {
            $campaign->block('Campaign bounce rate is higher than allowed!');
        }
    }

    /**
     * @since 1.6.1
     * @param Campaign $campaign
     * @param mixed $maxComplaintRate
     *
     * @return void
     */
    protected function checkCampaignOverMaxComplaintRate(Campaign $campaign, $maxComplaintRate)
    {
        if ((int)$maxComplaintRate < 0 || $campaign->getIsBlocked()) {
            return;
        }

        if ((float)$campaign->getStats()->getComplaintsRate() > (float)$maxComplaintRate) {
            $campaign->block('Campaign complaint rate is higher than allowed!');
        }
    }

    /**
     * @since 1.3.5.9
     * @return bool
     */
    protected function getCanUsePcntl()
    {
        static $canUsePcntl;
        if ($canUsePcntl !== null) {
            return $canUsePcntl;
        }

        $pcntlLoaded = CommonHelper::functionExists('pcntl_fork') && CommonHelper::functionExists('pcntl_waitpid');

        if ((int)$this->pcntl === 0) {
            return $canUsePcntl = false;
        }

        if ((int)$this->pcntl === 1) {
            return $canUsePcntl = $pcntlLoaded;
        }

        /** @var OptionCronDelivery $cronDelivery */
        $cronDelivery = container()->get(OptionCronDelivery::class);

        return $canUsePcntl = $cronDelivery->getUsePcntl() && $pcntlLoaded;
    }

    /**
     * @since 1.3.5.9
     * @return int
     */
    protected function getCampaignsInParallel()
    {
        if ((int)$this->pcntl_campaigns_parallel > 0) {
            return (int)$this->pcntl_campaigns_parallel;
        }

        /** @var OptionCronDelivery $cronDelivery */
        $cronDelivery = container()->get(OptionCronDelivery::class);

        return $cronDelivery->getCampaignsInParallel();
    }

    /**
     * @since 1.3.5.9
     * @return int
     */
    protected function getSubscriberBatchesInParallel()
    {
        if ((int)$this->pcntl_subscriber_batches_parallel > 0) {
            return (int)$this->pcntl_subscriber_batches_parallel;
        }

        /** @var OptionCronDelivery $cronDelivery */
        $cronDelivery = container()->get(OptionCronDelivery::class);

        return $cronDelivery->getSubscriberBatchesInParallel();
    }

    /**
     * @param ListSubscriber $subscriber
     * @param string $message
     * @param string $status
     * @param string $messageId
     * @param DeliveryServer $server
     * @param Campaign $campaign
     *
     * @return bool
     * @throws CDbException
     * @throws CException
     */
    protected function logDelivery(ListSubscriber $subscriber, string $message, string $status, string $messageId, DeliveryServer $server, Campaign $campaign): bool
    {
        // 1.3.7.9
        if ($campaign->getCanUseQueueTable()) {
            $campaign->queueTable->deleteSubscriber((int)$subscriber->subscriber_id);
        }

        /** @var CampaignDeliveryLog $deliveryLog */
        $deliveryLog = new CampaignDeliveryLog();

        $deliveryLog->campaign_id      = (int)$campaign->campaign_id;
        $deliveryLog->subscriber_id    = (int)$subscriber->subscriber_id;
        $deliveryLog->email_message_id = (string)$messageId;
        $deliveryLog->message          = str_replace("\n\n", "\n", $message);
        $deliveryLog->status           = $status;

        // since 1.3.6.1
        $deliveryLog->delivery_confirmed = CampaignDeliveryLog::TEXT_YES;
        if (!empty($server->server_id)) {
            $deliveryLog->server_id = (int)$server->server_id;
            if ($server->canConfirmDelivery && $server->must_confirm_delivery == DeliveryServer::TEXT_YES) {
                $deliveryLog->delivery_confirmed = CampaignDeliveryLog::TEXT_NO;
            }
        }

        $deliveryLog->addRelatedRecord('campaign', $campaign, false);
        $deliveryLog->addRelatedRecord('subscriber', $subscriber, false);
        $deliveryLog->addRelatedRecord('server', $server, false);

        $saved = $deliveryLog->save(false);

        if ($saved) {
            $subscriber->setLastCampaignDeliveryLogId((int)$deliveryLog->log_id);
        }

        return $saved;
    }

    /**
     * @param Campaign $campaign
     *
     * @return int
     * @throws CDbException
     * @throws CException
     */
    protected function countSubscribers(Campaign $campaign): int
    {
        // 1.3.7.9
        if ($campaign->getCanUseQueueTable()) {
            return (int)$campaign->queueTable->countSubscribers();
        }

        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->with['deliveryLogs'] = [
            'select'    => false,
            'together'  => true,
            'joinType'  => 'LEFT OUTER JOIN',
            'on'        => 'deliveryLogs.campaign_id = :cid',
            'condition' => 'deliveryLogs.subscriber_id IS NULL',
            'params'    => [':cid' => $campaign->campaign_id],
        ];

        // since 1.9.13
        if ($campaign->getIsRegular() && $campaign->option->getTimewarpEnabled()) {
            if ($_criteria = CampaignHelper::getTimewarpCriteria($campaign)) {
                $criteria->mergeWith($_criteria);
            }
        }

        return (int)$campaign->countSubscribers($criteria);
    }

    /**
     * @param int $offset
     * @param int $limit
     * @param Campaign $campaign
     *
     * @return array
     * @throws Exception
     */
    protected function findSubscribers(int $offset, int $limit, Campaign $campaign): array
    {
        // 1.3.7.3
        if (empty($limit) || $limit <= 0) {
            return [];
        }

        // 1.3.7.9
        if ($campaign->getCanUseQueueTable()) {
            return $campaign->queueTable->findSubscribers($offset, $limit);
        }

        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->with['deliveryLogs'] = [
            'select'    => false,
            'together'  => true,
            'joinType'  => 'LEFT OUTER JOIN',
            'on'        => 'deliveryLogs.campaign_id = :cid',
            'condition' => 'deliveryLogs.subscriber_id IS NULL',
            'params'    => [':cid' => $campaign->campaign_id],
        ];

        // since 1.5.2
        if ($campaign->getIsRegular() && $campaign->option->getTimewarpEnabled()) {
            if ($_criteria = CampaignHelper::getTimewarpCriteria($campaign)) {
                $criteria->mergeWith($_criteria);
            }
        }

        // since 1.3.6.3 - because in pcntl mode we send dupes, we don't want this
        if (!$this->getCanUsePcntl() && $campaign->option->getCanSetMaxSendCountRandom()) {
            $criteria->order = 'RAND()';
        }

        // and find them
        return $campaign->findSubscribers($offset, $limit, $criteria);
    }

    /**
     * @param array $subscribers
     * @return array
     */
    protected function sortSubscribers(array $subscribers = []): array
    {
        $subscribersCount = count($subscribers);
        $_subscribers = [];
        foreach ($subscribers as $index => $subscriber) {
            $emailParts = explode('@', $subscriber->email);
            $domainName = $emailParts[1];
            if (!isset($_subscribers[$domainName])) {
                $_subscribers[$domainName] = [];
            }
            $_subscribers[$domainName][] = $subscriber;
            unset($subscribers[$index]);
        }

        $subscribers = [];
        while ($subscribersCount > 0) {
            foreach ($_subscribers as $domainName => $subs) {
                foreach ($subs as $index => $sub) {
                    $subscribers[] = $sub;
                    unset($_subscribers[$domainName][$index]);
                    break;
                }
            }
            $subscribersCount--;
        }

        // free mem
        unset($_subscribers);

        return $subscribers;
    }

    /**
     * @param ListSubscriber $subscriber
     * @param DeliveryServer $server
     * @param Campaign $campaign
     *
     * @return array
     * @throws CException
     * @throws Throwable
     */
    protected function prepareEmail(ListSubscriber $subscriber, DeliveryServer $server, Campaign $campaign)
    {
        // how come ?
        if (empty($campaign->template)) {
            return [];
        }

        // since 1.3.9.3
        hooks()->applyFilters('console_command_send_campaigns_before_prepare_email', null, $campaign, $subscriber, $server);

        /** @var Lists $list */
        $list = $campaign->list;

        /** @var Customer $customer */
        $customer = $list->customer;

        // since 2.0.29
        $abSubject = null;
        if ($campaign->getCanDoAbTest()) {
            $abSubject = $campaign->pickAbTestSubject();
            if (!empty($abSubject)) {
                $campaign->setCurrentSubject((string)$abSubject->subject);
            }
        }

        $emailSubject   = $campaign->getCurrentSubject();
        $emailContent   = $campaign->template->content;
        $embedImages    = [];
        $emailFooter    = null;
        $onlyPlainText  = !empty($campaign->template->only_plain_text) && $campaign->template->only_plain_text === CampaignTemplate::TEXT_YES;
        $emailAddress   = $subscriber->email;
        $toName         = $subscriber->email;

        // since 1.3.5.9
        $fromEmailCustom= null;
        $fromNameCustom = null;
        $replyToCustom  = null;

        // really blind check to see if it contains a tag
        if (strpos((string)$campaign->from_email, '[') !== false || strpos((string)$campaign->from_name, '[') !== false || strpos((string)$campaign->reply_to, '[') !== false) {
            if (strpos((string)$campaign->from_email, '[') !== false) {
                $searchReplace   = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->from_email, $campaign, $subscriber, $server);
                $fromEmailCustom = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->from_email);
                $fromEmailCustom = CampaignHelper::applyRandomContentTag($fromEmailCustom);
                if (!FilterVarHelper::email($fromEmailCustom)) {
                    $fromEmailCustom = null;
                    $campaign->from_email = (string)$server->from_email;
                }
            }
            if (strpos((string)$campaign->from_name, '[') !== false) {
                $searchReplace  = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->from_name, $campaign, $subscriber, $server);
                $fromNameCustom = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->from_name);
                $fromNameCustom = CampaignHelper::applyRandomContentTag($fromNameCustom);
            }
            if (strpos((string)$campaign->reply_to, '[') !== false) {
                $searchReplace  = CampaignHelper::getCommonTagsSearchReplace((string)$campaign->reply_to, $campaign, $subscriber, $server);
                $replyToCustom  = str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$campaign->reply_to);
                $replyToCustom  = CampaignHelper::applyRandomContentTag($replyToCustom);
                if (!FilterVarHelper::email($replyToCustom)) {
                    $replyToCustom = null;
                    $campaign->reply_to = (string)$server->from_email;
                }
            }
        }

        if (!$onlyPlainText) {
            if (!empty($campaign->option->preheader)) {
                $emailContent = CampaignHelper::injectPreheader($emailContent, $campaign->option->preheader, $campaign);
            }

            if (($emailHeader = (string)$customer->getGroupOption('campaigns.email_header', '')) && strlen(trim($emailHeader)) > 5) {
                $emailContent = CampaignHelper::injectEmailHeader($emailContent, $emailHeader, $campaign);
            }

            if (($emailFooter = (string)$customer->getGroupOption('campaigns.email_footer', '')) && strlen(trim($emailFooter)) > 5) {
                $emailContent = CampaignHelper::injectEmailFooter($emailContent, $emailFooter, $campaign);
            }

            if ($server->getCanEmbedImages() && !empty($campaign->option) && !empty($campaign->option->embed_images) && $campaign->option->embed_images == CampaignOption::TEXT_YES) {
                [$emailContent, $embedImages] = CampaignHelper::embedContentImages($emailContent, $campaign);
            }

            if (CampaignHelper::contentHasXmlFeed($emailContent)) {
                $start = microtime(true);
                $emailContent = CampaignXmlFeedParser::parseContent($emailContent, $campaign, $subscriber, true, '', $server);
                $this->stdout(sprintf('Parsed and loaded the html email content XML feed in %.5f seconds.', round(microtime(true) - $start, 5)));
            }

            if (CampaignHelper::contentHasJsonFeed($emailContent)) {
                $start = microtime(true);
                $emailContent = CampaignJsonFeedParser::parseContent($emailContent, $campaign, $subscriber, true, '', $server);
                $this->stdout(sprintf('Parsed and loaded the html email content JSON feed in %.5f seconds.', round(microtime(true) - $start, 5)));
            }

            // 1.5.5
            if (CampaignHelper::hasRemoteContentTag($emailContent)) {
                $start = microtime(true);
                $emailContent = CampaignHelper::fetchContentForRemoteContentTag($emailContent, $campaign, $subscriber);
                $this->stdout(sprintf('Parsed and loaded the email content remote content in %.5f seconds.', round(microtime(true) - $start, 5)));
            }
            //

            if (!empty($campaign->option) && $campaign->option->url_tracking == CampaignOption::TEXT_YES) {
                $start = microtime(true);
                $emailContent = CampaignHelper::transformLinksForTracking($emailContent, $campaign, $subscriber, true);
                $this->stdout(sprintf('Parsed the tracking links from the html email content in %.5f seconds.', round(microtime(true) - $start, 5)));
            }

            // since 1.3.5.9 - optional open tracking.
            $trackOpen = $campaign->option->open_tracking == CampaignOption::TEXT_YES;
            //

            $emailData = CampaignHelper::parseContent($emailContent, $campaign, $subscriber, $trackOpen, $server);
            [$toName, $emailSubject, $emailContent] = $emailData;

            // since 2.0.32
            // This is a special case, where the custom fields can contain URLs and if we do not do the below
            // then those URLs will not be parsed into tracking URLs.
            // More details at https://github.com/onetwist-software/mailwizz/issues/653
            if (
                !empty($campaign->option) &&
                $campaign->option->url_tracking == CampaignOption::TEXT_YES &&
                CampaignHelper::contentHasUntransformedLinksForTracking($emailContent, $campaign, $subscriber)
            ) {
                $start = microtime(true);
                $emailContent = CampaignHelper::transformLinksForTracking($emailContent, $campaign, $subscriber, true);
                $this->stdout(sprintf('Parsed the tracking links from the html email content in %.5f seconds.', round(microtime(true) - $start, 5)));

                $emailData = CampaignHelper::parseContent($emailContent, $campaign, $subscriber, false, $server);
                [, , $emailContent] = $emailData;
            }
        }

        // Plain TEXT only supports basic tags transform, no xml/json feeds.
        $emailPlainText = '';
        if (!empty($campaign->option) && $campaign->option->plain_text_email == CampaignOption::TEXT_YES) {
            if ($campaign->template->auto_plain_text === CampaignTemplate::TEXT_YES /* && empty($campaign->template->plain_text)*/) {
                $emailPlainText = CampaignHelper::htmlToText($emailContent);
            }
            if (empty($emailPlainText) && !empty($campaign->template->plain_text) && !$onlyPlainText) {
                $emailPlainText = $campaign->template->plain_text;
                if (($emailHeader = (string)$customer->getGroupOption('campaigns.email_header', '')) && strlen(trim($emailHeader)) > 5) {
                    $emailHeader  = strip_tags($emailHeader);
                    $emailHeader .= "\n\n\n";
                    $emailPlainText = $emailHeader . $emailPlainText;
                }
                if (($emailFooter = (string)$customer->getGroupOption('campaigns.email_footer', '')) && strlen(trim($emailFooter)) > 5) {
                    $emailPlainText .= "\n\n\n";
                    $emailPlainText .= strip_tags($emailFooter);
                }
                if (!empty($campaign->option) && $campaign->option->url_tracking == CampaignOption::TEXT_YES) {
                    $emailPlainText = CampaignHelper::transformLinksForTracking($emailPlainText, $campaign, $subscriber, true, true);
                }
                $_emailData = CampaignHelper::parseContent($emailPlainText, $campaign, $subscriber, false, $server);
                [, , $emailPlainText] = $_emailData;

                /** @var string $emailPlainText */
                $emailPlainText = preg_replace('%<br(\s{0,}?/?)?>%i', "\n", $emailPlainText);

                // since 2.0.32
                // This is a special case, where the custom fields can contain URLs and if we do not do the below
                // then those URLs will not be parsed into tracking URLs.
                // More details at https://github.com/onetwist-software/mailwizz/issues/653
                if (
                    !empty($campaign->option) &&
                    $campaign->option->url_tracking == CampaignOption::TEXT_YES &&
                    CampaignHelper::contentHasUntransformedLinksForTracking($emailPlainText, $campaign, $subscriber)
                ) {
                    $emailPlainText = CampaignHelper::transformLinksForTracking($emailPlainText, $campaign, $subscriber, true, true);

                    $_emailData = CampaignHelper::parseContent($emailPlainText, $campaign, $subscriber, false, $server);
                    [, , $emailPlainText] = $_emailData;
                }
            }
        }

        if ($onlyPlainText) {
            /** @var string $emailPlainText */
            $emailPlainText = (string)$campaign->template->plain_text;
            if (($emailHeader = (string)$customer->getGroupOption('campaigns.email_header', '')) && strlen(trim($emailHeader)) > 5) {
                $emailHeader  = strip_tags($emailHeader);
                $emailHeader .= "\n\n\n";
                $emailPlainText = $emailHeader . $emailPlainText;
            }
            if (($emailFooter = (string)$customer->getGroupOption('campaigns.email_footer', '')) && strlen(trim($emailFooter)) > 5) {
                $emailPlainText .= "\n\n\n";
                $emailPlainText .= strip_tags($emailFooter);
            }
            if (!empty($campaign->option) && $campaign->option->url_tracking == CampaignOption::TEXT_YES) {
                $emailPlainText = CampaignHelper::transformLinksForTracking($emailPlainText, $campaign, $subscriber, true, true);
            }
            $_emailData = CampaignHelper::parseContent($emailPlainText, $campaign, $subscriber, false, $server);
            [$toName, $emailSubject, $emailPlainText] = $_emailData;

            /** @var string $emailPlainText */
            $emailPlainText = preg_replace('%<br(\s{0,}?/?)?>%i', "\n", $emailPlainText);

            // since 2.0.32
            // This is a special case, where the custom fields can contain URLs and if we do not do the below
            // then those URLs will not be parsed into tracking URLs.
            // More details at https://github.com/onetwist-software/mailwizz/issues/653
            if (
                !empty($campaign->option) &&
                $campaign->option->url_tracking == CampaignOption::TEXT_YES &&
                CampaignHelper::contentHasUntransformedLinksForTracking($emailPlainText, $campaign, $subscriber)
            ) {
                $emailPlainText = CampaignHelper::transformLinksForTracking($emailPlainText, $campaign, $subscriber, true, true);

                $_emailData = CampaignHelper::parseContent($emailPlainText, $campaign, $subscriber, false, $server);
                [, , $emailPlainText] = $_emailData;
            }
        }

        // since 1.3.5.3
        if (CampaignHelper::contentHasXmlFeed($emailSubject)) {
            $start = microtime(true);
            $emailSubject = CampaignXmlFeedParser::parseContent($emailSubject, $campaign, $subscriber, true, $emailSubject, $server);
            $this->stdout(sprintf('Parsed and loaded the email subject XML feed in %.5f seconds.', round(microtime(true) - $start, 5)));
        }

        if (CampaignHelper::contentHasJsonFeed($emailSubject)) {
            $start = microtime(true);
            $emailSubject = CampaignJsonFeedParser::parseContent($emailSubject, $campaign, $subscriber, true, $emailSubject, $server);
            $this->stdout(sprintf('Parsed and loaded the email subject JSON feed in %.5f seconds.', round(microtime(true) - $start, 5)));
        }

        // 1.5.3
        if (CampaignHelper::hasRemoteContentTag($emailSubject)) {
            $start = microtime(true);
            $emailSubject = CampaignHelper::fetchContentForRemoteContentTag($emailSubject, $campaign, $subscriber);
            $this->stdout(sprintf('Parsed and loaded the email subject remote content in %.5f seconds.', round(microtime(true) - $start, 5)));
        }
        //

        if (CampaignHelper::isTemplateEngineEnabled()) {
            if (!$onlyPlainText && !empty($emailContent)) {
                $searchReplace = CampaignHelper::getCommonTagsSearchReplace($emailContent, $campaign, $subscriber, $server);
                $emailContent  = CampaignHelper::parseByTemplateEngine($emailContent, $searchReplace);
            }
            if (!empty($emailSubject)) {
                $searchReplace = CampaignHelper::getCommonTagsSearchReplace($emailSubject, $campaign, $subscriber, $server);
                $emailSubject  = CampaignHelper::parseByTemplateEngine($emailSubject, $searchReplace);
            }
            if (!empty($emailPlainText)) {
                $searchReplace  = CampaignHelper::getCommonTagsSearchReplace($emailPlainText, $campaign, $subscriber, $server);
                $emailPlainText = CampaignHelper::parseByTemplateEngine($emailPlainText, $searchReplace);
            }
        }

        // since 1.9.27
        $emailSubject = !empty($emailSubject) ? (string)$emailSubject : 'N/A';
        $emailContent = (string)preg_replace('/<title>(.*)<\/title>/i', sprintf('<title>%s</title>', (string)$emailSubject), (string)$emailContent);

        $emailParams = [
            'to'              => [$emailAddress => $toName],
            'subject'         => trim($emailSubject),
            'body'            => trim($emailContent),
            'plainText'       => trim($emailPlainText),
            'embedImages'     => $embedImages,
            'onlyPlainText'   => $onlyPlainText,

            // since 1.3.5.9
            'fromEmailCustom' => $fromEmailCustom,
            'fromNameCustom'  => $fromNameCustom,
            'replyToCustom'   => $replyToCustom,

            // since 2.0.29
            'abSubject' => $abSubject,

            // since 2.0.34
            'campaignUid'   => $campaign->campaign_uid,
            'subscriberUid' => $subscriber->subscriber_uid,
            'customerUid'   => $customer->customer_uid,
        ];

        // since 1.3.9.3
        /** @var array $emailParams */
        $emailParams = (array)hooks()->applyFilters('console_command_send_campaigns_after_prepare_email', $emailParams, $campaign, $subscriber, $server);

        return $emailParams;
    }

    /**
     * @param Campaign $campaign
     * @return bool
     * @throws CDbException
     * @throws CException
     */
    protected function markCampaignSent(Campaign $campaign)
    {
        // 1.4.4 this might take a while...
        if ($this->campaignMustHandleGiveups($campaign)) {
            $campaign->saveStatus(Campaign::STATUS_SENDING);
            return true;
        }

        if ($campaign->getIsAutoresponder()) {
            $campaign->saveStatus(Campaign::STATUS_SENDING);
            return true;
        }

        // make sure we get fresh data here
        // TODO: Is this really needed? Is this a fix for Timewarp acting strange?
        $campaign->refresh();
        if ($campaign->getIsSent()) {
            return true;
        }

        $campaign->saveStatus(Campaign::STATUS_SENT);

        $campaign->customer->logAction->campaignSent($campaign);

        // since 1.3.4.6
        hooks()->doAction('console_command_send_campaigns_campaign_sent', $campaign);

        $campaign->sendStatsEmail();

        // since 1.3.5.3
        $campaign->tryReschedule();

        // since 1.7.6
        if (($count = (int)$campaign->getSendingGiveupsCount()) > 0) {
            $campaign->updateSendingGiveupCount($count);
        }

        return true;
    }

    /**
     * Check customers quota limits
     *
     * @return void
     * @throws CException
     */
    protected function checkCustomersQuotaLimits()
    {
        if (empty($this->_customerData) || !is_array($this->_customerData)) {
            return;
        }

        foreach ($this->_customerData as $cdata) {
            $customer = $cdata['customer'];
            $enabled  = $customer->getGroupOption('sending.quota_notify_enabled', 'no') == 'yes';

            if (!$enabled) {
                continue;
            }

            if ($this->getCanUsePcntl()) {
                sleep(rand(1, 3));
            }

            $counter = 0;
            foreach ($cdata['campaigns'] as $campaignMaxSubscribers) {
                if ($cdata['subscribersCount'] > $campaignMaxSubscribers) {
                    $counter += $campaignMaxSubscribers;
                } else {
                    $counter += $cdata['subscribersCount'];
                }
            }

            $timeNow    = time();
            $lastNotify = (int)$customer->getOption('sending_quota.last_notification', 0);
            $notifyTs   = 6 * 3600; // no less than 6 hours.

            $quotaTotal = $cdata['quotaTotal'];
            $quotaUsage = $cdata['quotaUsage'] + $counter; // current usage + future usage

            if ($quotaTotal <= 0 || ($lastNotify + $notifyTs) > $timeNow) {
                continue;
            }

            $quotaNotifyPercent = (int)$customer->getGroupOption('sending.quota_notify_percent', 95);
            $quotaUsagePercent  = ($quotaUsage / $quotaTotal) * 100;

            if ($quotaUsagePercent < $quotaNotifyPercent) {
                continue;
            }

            $customer->setOption('sending_quota.last_notification', $timeNow);

            $this->notifyCustomerReachingQuota([
                'customer'           => $customer,
                'quotaTotal'         => $quotaTotal,
                'quotaLeft'          => $cdata['quotaLeft'],
                'quotaUsage'         => $quotaUsage,
                'quotaUsagePercent'  => $quotaUsagePercent,
                'quotaNotifyPercent' => $quotaNotifyPercent,
            ]);
        }
    }

    /**
     * @param array $params
     *
     * @return void
     * @throws CException
     */
    protected function notifyCustomerReachingQuota(array $params = [])
    {
        $customer = $params['customer'];

        // create the message
        $_message  = 'Your maximum allowed sending quota is set to {max} emails and you currently have sent {current} emails, which means you have used {percent} of your allowed sending quota!<br />';
        $_message .= 'Once your sending quota is over, you will not be able to send any emails!<br /><br />';
        $_message .= 'Please make sure you renew your sending quota.<br /> Thank you!';

        $message = new CustomerMessage();
        $message->customer_id = (int)$customer->customer_id;
        $message->title       = 'Your sending quota is close to the limit!';
        $message->message     = $_message;
        $message->message_translation_params = [
            '{max}'     => $params['quotaTotal'],
            '{current}' => $params['quotaUsage'],
            '{percent}' => round($params['quotaUsagePercent'], 2) . '%',
        ];
        $message->save();

        $dsParams = ['useFor' => [DeliveryServer::USE_FOR_REPORTS]];
        if (!($server = DeliveryServer::pickServer(0, null, $dsParams))) {
            return;
        }

        /** @var OptionEmailTemplate $optionEmailTemplate */
        $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

        /** @var OptionCommon $optionCommon */
        $optionCommon = container()->get(OptionCommon::class);

        $searchReplace = [
            '[SITE_NAME]'       => $optionCommon->getSiteName(),
            '[SITE_TAGLINE]'    => $optionCommon->getSiteTagline(),
            '[CURRENT_YEAR]'    => date('Y'),
            '[CONTENT]'         => $customer->getGroupOption('sending.quota_notify_email_content'),
        ];
        $emailTemplate = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $optionEmailTemplate->common);

        $searchReplace  = [
            '[FIRST_NAME]'          => $customer->first_name,
            '[LAST_NAME]'           => $customer->last_name,
            '[FULL_NAME]'           => $customer->getFullName(),
            '[QUOTA_TOTAL]'         => $params['quotaTotal'],
            '[QUOTA_USAGE]'         => $params['quotaUsage'],
            '[QUOTA_USAGE_PERCENT]' => round($params['quotaUsagePercent'], 2) . '%',

        ];
        $emailTemplate = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $emailTemplate);

        $emailParams            = [];
        $emailParams['subject'] = t('customers', 'Your sending quota is close to the limit!');
        $emailParams['body']    = $emailTemplate;
        $emailParams['to']      = $customer->email;

        $server->sendEmail($emailParams);
    }

    /**
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     * @return $this
     */
    protected function handleCampaignSentActionSubscriberField(Campaign $campaign, ListSubscriber $subscriber)
    {
        static $sentActionModels = [];

        try {
            if (!isset($sentActionModels[$campaign->campaign_id])) {
                $sentActionModels[$campaign->campaign_id] = CampaignSentActionListField::model()->findAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
            }

            if (empty($sentActionModels[$campaign->campaign_id])) {
                return $this;
            }

            foreach ($sentActionModels[$campaign->campaign_id] as $model) {
                $valueModel = ListFieldValue::model()->findByAttributes([
                    'field_id'      => $model->field_id,
                    'subscriber_id' => $subscriber->subscriber_id,
                ]);
                if (empty($valueModel)) {
                    $valueModel = new ListFieldValue();
                    $valueModel->field_id       = (int)$model->field_id;
                    $valueModel->subscriber_id  = (int)$subscriber->subscriber_id;
                }

                $valueModel->value = $model->getParsedFieldValueByListFieldValue(new CAttributeCollection([
                    'valueModel' => $valueModel,
                    'campaign'   => $campaign,
                    'subscriber' => $subscriber,
                    'event'      => 'campaign:subscriber:sent',
                ]));
                $valueModel->save();
            }
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @param Campaign $campaign
     * @param ListSubscriber $subscriber
     * @return $this
     */
    protected function handleCampaignSentActionSubscriber(Campaign $campaign, ListSubscriber $subscriber)
    {
        static $sentActionModels = [];

        try {
            if (!isset($sentActionModels[$campaign->campaign_id])) {
                $sentActionModels[$campaign->campaign_id] = CampaignSentActionSubscriber::model()->findAllByAttributes([
                    'campaign_id' => $campaign->campaign_id,
                ]);
            }

            if (empty($sentActionModels[$campaign->campaign_id])) {
                return $this;
            }

            foreach ($sentActionModels[$campaign->campaign_id] as $model) {
                if ($model->action == CampaignSentActionSubscriber::ACTION_MOVE) {
                    $subscriber->moveToList((int)$model->list_id, false, false);
                } else {
                    $subscriber->copyToList((int)$model->list_id, false, false);
                }
            }
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     * @throws CDbException
     * @throws CException
     * @since 1.4.4
     *
     */
    protected function campaignMustHandleGiveups(Campaign $campaign)
    {
        /** @var OptionCronDelivery $cronDelivery */
        $cronDelivery = container()->get(OptionCronDelivery::class);
        if (!$cronDelivery->getRetryFailedSending()) {
            return false;
        }

        $count = $campaign->getSendingGiveupsCount();

        // since 1.7.6
        $campaign->updateSendingGiveupCount((int)$count);

        if (!$count) {
            return false;
        }

        if ($campaign->getIsRegular() && (int)$campaign->option->giveup_counter >= (int)app_param('campaign.delivery.giveup.retries', 3)) {
            return false;
        }

        $campaign->updateSendingGiveupCounter();

        if ($campaign->getCanUseQueueTable()) {
            $campaign->queueTable->handleSendingGiveups();
        } else {
            $campaign->resetSendingGiveups();
        }

        return true;
    }

    /**
     * @since 1.9.14
     *
     * @param Campaign $campaign
     * @param int $count
     *
     * @return int
     * @throws CDbException
     * @throws CException
     */
    protected function handleCampaignTimewarp(Campaign $campaign, int $count)
    {
        if (!$campaign->getIsRegular() || !$campaign->option->getTimewarpEnabled()) {
            return $count;
        }

        // 2.0.20
        if (
            !empty($campaign->started_at) &&
            is_string($campaign->started_at) &&
            (time() - (int)strtotime((string)$campaign->started_at)) > 7 * 24 * 3600
        ) {
            return 0;
        }

        if ($count !== 0) {
            return $count;
        }

        $campaign->option->timewarp_enabled = CampaignOption::TEXT_NO;
        $count = $this->countSubscribers($campaign);
        $campaign->option->timewarp_enabled = CampaignOption::TEXT_YES;
        if ($count === 0) {
            return $count;
        }

        $campaign->incrementPriority();
        $this->stdout('Campaign status has been changed successfully, but postponed!');

        return $count;
    }
}
