<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBoxMonitorHandlerCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.5
 */

class EmailBoxMonitorHandlerCommand extends ConsoleCommand
{
    /**
     * @var int
     */
    public $fast = 1;

    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();

        Yii::import('common.vendors.BounceHandler.*');
    }

    /**
     * @return int
     */
    public function actionIndex()
    {
        $this->stdout('Start processing...');

        // because some cli are not compiled same way with the web module.
        if (!CommonHelper::functionExists('imap_open')) {
            $message = t('servers', 'The PHP CLI binary is missing the IMAP extension!');
            Yii::log($message, CLogger::LEVEL_ERROR);
            $this->stdout($message);
            return 1;
        }

        // make sure we only allow a single cron job at a time if this flag is disabled
        $fastLockName = sha1(__METHOD__);
        if (!$this->fast && !mutex()->acquire($fastLockName, 5)) {
            $this->stdout('Cannot acquire lock, seems another process is already running!');
            return 0;
        }

        try {

            // since 1.5.0
            EmailBoxMonitor::model()->updateAll([
                'status' => EmailBoxMonitor::STATUS_ACTIVE,
            ], 'status = :st', [
                ':st' => EmailBoxMonitor::STATUS_CRON_RUNNING,
            ]);
            //

            // added in 1.3.4.7
            hooks()->doAction('console_command_email_box_monitor_handler_before_process', $this);

            if ($this->getCanUsePcntl()) {
                $this->stdout('Processing with PCNTL!');
                $this->processWithPcntl();
            } else {
                $this->stdout('Processing without PCNTL!');
                $this->processWithoutPcntl();
            }

            // added in 1.3.4.7
            hooks()->doAction('console_command_email_box_monitor_handler_after_process', $this);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        if (!$this->fast) {
            mutex()->release($fastLockName);
        }

        return 0;
    }

    /**
     * @return $this
     * @throws CException
     */
    protected function processWithPcntl()
    {
        // get all servers
        $servers = EmailBoxMonitor::model()->findAll([
            'condition' => 't.status = :status',
            'params'    => [':status' => EmailBoxMonitor::STATUS_ACTIVE],
        ]);

        // close the external connections
        $this->setExternalConnectionsActive(false);

        /** @var OptionCronProcessEmailBoxMonitors $optionCronProcessEmailBoxMonitors */
        $optionCronProcessEmailBoxMonitors = container()->get(OptionCronProcessEmailBoxMonitors::class);

        // split into x server chunks
        $chunkSize = (int)$optionCronProcessEmailBoxMonitors->getPcntlProcesses();
        $serverChunks = array_chunk($servers, $chunkSize); // @phpstan-ignore-line
        unset($servers);

        foreach ($serverChunks as $servers) {
            $children = [];

            foreach ($servers as $server) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    continue;
                }

                // Parent
                if ($pid) {
                    $children[] = $pid;
                }

                // child
                if (!$pid) {
                    try {
                        $this->stdout(sprintf('Started processing server ID %d.', $server->server_id));

                        $server->processRemoteContents([
                            'logger' => $this->verbose ? [$this, 'stdout'] : null,
                        ]);

                        $this->stdout(sprintf('Finished processing server ID %d.', $server->server_id));
                    } catch (Exception $e) {
                        $this->stdout(__LINE__ . ': ' . $e->getMessage());
                        Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
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
                sleep(1);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function processWithoutPcntl()
    {
        // get all servers
        $servers = EmailBoxMonitor::model()->findAll([
            'condition' => 't.status = :status',
            'params'    => [':status' => EmailBoxMonitor::STATUS_ACTIVE],
        ]);

        foreach ($servers as $server) {
            try {
                $this->stdout(sprintf('Started processing server ID %d.', $server->server_id));

                $server->processRemoteContents([
                    'logger' => $this->verbose ? [$this, 'stdout'] : null,
                ]);

                $this->stdout(sprintf('Finished processing server ID %d.', $server->server_id));
            } catch (Exception $e) {
                $this->stdout(__LINE__ . ': ' . $e->getMessage());
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    protected function getCanUsePcntl()
    {
        static $canUsePcntl;
        if ($canUsePcntl !== null) {
            return $canUsePcntl;
        }

        /** @var OptionCronProcessEmailBoxMonitors $optionCronProcessEmailBoxMonitors */
        $optionCronProcessEmailBoxMonitors = container()->get(OptionCronProcessEmailBoxMonitors::class);

        if (!$optionCronProcessEmailBoxMonitors->getUsePcntl()) {
            return $canUsePcntl = false;
        }

        if (!CommonHelper::functionExists('pcntl_fork') || !CommonHelper::functionExists('pcntl_waitpid')) {
            return $canUsePcntl = false;
        }
        return $canUsePcntl = true;
    }
}
