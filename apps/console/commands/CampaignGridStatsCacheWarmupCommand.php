<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignGridStatsCacheWarmupCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.2
 *
 */

class CampaignGridStatsCacheWarmupCommand extends ConsoleCommand
{
    /**
     * @return int
     */
    public function actionIndex()
    {
        $this->stdout('Acquiring the mutex lock...');

        $result = 1;

        $mutexKey = sha1(__METHOD__);

        if (!mutex()->acquire($mutexKey, 5)) {
            $this->stdout('Unable to acquire the mutex lock!');
            return $result;
        }

        try {
            hooks()->doAction('console_command_campaign_grid_stats_cache_warmup_before_process', $this);

            $result = $this->process();

            hooks()->doAction('console_command_campaign_grid_stats_cache_warmup_after_process', $this);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

            $result = 1;
        }

        mutex()->release($mutexKey);

        return $result;
    }

    /**
     * @return int
     * @throws CException
     */
    protected function process(): int
    {
        $this->stdout('Starting campaign stats cache warmup...');

        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id';
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE, Campaign::STATUS_DRAFT]);

        /** @var int[] $campaignsIds */
        $campaignsIds = CampaignCollection::findAll($criteria)->map(function (Campaign $campaign): int {
            return (int)$campaign->campaign_id;
        })->all();

        if (count($campaignsIds) === 0) {
            $this->stdout('No campaign to process!');
            return 0;
        }

        $pcntlProcesses = 10;
        $chunkSize      = (int)(ceil(count($campaignsIds) / $pcntlProcesses));
        $chunks         = array_chunk($campaignsIds, $chunkSize); // @phpstan-ignore-line
        $pcntl          = CommonHelper::functionExists('pcntl_fork') && CommonHelper::functionExists('pcntl_waitpid');

        if (count($chunks) === 1) {
            $this->processCampaignsIds($chunks[0]);
            $this->stdout(sprintf('Done processing %d campaigns', count($chunks[0])));
            return 0;
        }

        // close the external connections
        if ($pcntl) {
            $this->setExternalConnectionsActive(false);

            $this->stdout(sprintf(
                'At most %d PCNTL processes and %d campaign chunks, each contains at most %d campaigns',
                $pcntlProcesses,
                count($chunks),
                $chunkSize
            ));
        }

        $this->stdout('Processing...');

        $children = [];

        foreach ($chunks as $index => $chunk) {
            if (!$pcntl) {
                $this->processCampaignsIds($chunk, $index);
                continue;
            }

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
                $this->processCampaignsIds($chunk, $index);
                app()->end();
            }
        }

        if ($pcntl) {
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

        $this->stdout(sprintf('Done processing %d campaigns', count($campaignsIds)));

        return 0;
    }

    /**
     * @param array $campaignsIds
     * @param int $workerNumber
     *
     * @return void
     */
    protected function processCampaignsIds(array $campaignsIds, int $workerNumber = 0): void
    {
        if (count($campaignsIds) === 0) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->addInCondition('campaign_id', $campaignsIds);
        $campaigns = Campaign::model()->findAll($criteria);

        foreach ($campaigns as $campaign) {
            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Start processing...', $workerNumber, $campaign->campaign_uid));

            $start = microtime(true);

            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Campaign::getStatusWithStats: %s', $workerNumber, $campaign->campaign_uid, $campaign->getStatusWithStats()));
            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Campaign::getGridViewSent: %s', $workerNumber, $campaign->campaign_uid, $campaign->getGridViewSent()));
            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Campaign::getGridViewRecipients: %s', $workerNumber, $campaign->campaign_uid, $campaign->getGridViewRecipients()));
            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Campaign::getGridViewDelivered: %s', $workerNumber, $campaign->campaign_uid, $campaign->getGridViewDelivered()));
            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Campaign::getGridViewOpens: %s', $workerNumber, $campaign->campaign_uid, $campaign->getGridViewOpens()));
            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Campaign::getGridViewClicks: %s', $workerNumber, $campaign->campaign_uid, $campaign->getGridViewClicks()));
            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Campaign::getGridViewBounces: %s', $workerNumber, $campaign->campaign_uid, $campaign->getGridViewBounces()));
            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Campaign::getGridViewUnsubs: %s', $workerNumber, $campaign->campaign_uid, $campaign->getGridViewUnsubs()));

            $this->stdout(sprintf('[Worker: %d / Campaign: %s] Done processing, took: %.5fs' . PHP_EOL, $workerNumber, $campaign->campaign_uid, round(microtime(true) - $start, 5)));
        }
    }
}
