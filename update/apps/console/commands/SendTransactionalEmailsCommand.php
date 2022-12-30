<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SendTransactionalEmailsCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

class SendTransactionalEmailsCommand extends ConsoleCommand
{
    /**
     * @return int
     */
    public function actionIndex()
    {
        $lockName = sha1(__METHOD__);

        if (!mutex()->acquire($lockName)) {
            $this->stdout('PCNTL processes running already, locks acquired previously!');
            return 1;
        }

        try {
            $timeStart        = microtime(true);
            $memoryUsageStart = memory_get_peak_usage(true);

            // added in 1.3.4.7
            hooks()->doAction('console_command_transactional_emails_before_process', $this);

            $this->process();

            // added in 1.3.4.7
            hooks()->doAction('console_command_transactional_emails_after_process', $this);

            $timeEnd        = microtime(true);
            $memoryUsageEnd = memory_get_peak_usage(true);

            $time        = round($timeEnd - $timeStart, 5);
            $memoryUsage = CommonHelper::formatBytes($memoryUsageEnd - $memoryUsageStart);
            $this->stdout(sprintf('This cycle completed in %.5f seconds and used %s of memory!', $time, $memoryUsage));
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        mutex()->release($lockName);
        return 0;
    }

    /**
     * @return $this
     * @throws CDbException
     * @throws CException
     */
    protected function process()
    {
        // 1.3.7.3
        $criteria = new CDbCriteria();
        $criteria->addCondition('t.status = "unsent" AND t.send_at < NOW() AND t.retries < t.max_retries');
        $criteria->order = 't.priority ASC, t.retries ASC';
        $criteria->limit = 500;

        // 1.3.7.3 - offer a chance to alter this criteria.
        $criteria = hooks()->applyFilters('console_send_transactional_emails_command_find_all_criteria', $criteria, $this);

        $emails = TransactionalEmail::model()->findAll($criteria);
        if (empty($emails)) {
            return $this;
        }

        $pcntl = CommonHelper::functionExists('pcntl_fork') && CommonHelper::functionExists('pcntl_waitpid');
        if ($pcntl) {
            $this->setExternalConnectionsActive(false);
        }
        $children       = [];
        $maxProcesses   = 10;
        $batchSize      = (int)(ceil(count($emails) / $maxProcesses));

        /** @var TransactionalEmail[][] $batches */
        $batches = array_chunk($emails, $batchSize); // @phpstan-ignore-line
        foreach ($batches as $index => $batch) {
            if (!$pcntl) {
                $this->processBatch($batch, $index);
                continue;
            }

            $pid = pcntl_fork();
            if ($pid == -1) {
                continue;
            }

            // parent
            if ($pid) {
                $children[] = $pid;
            }

            // child
            if (!$pid) {
                $this->processBatch($batch, $index);
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

        db()->createCommand('UPDATE {{transactional_email}} SET `status` = "sent" WHERE `status` = "unsent" AND send_at < NOW() AND retries >= max_retries')->execute();
        db()->createCommand('DELETE FROM {{transactional_email}} WHERE `status` = "unsent" AND send_at < NOW() AND date_added < DATE_SUB(NOW(), INTERVAL 1 MONTH)')->execute();

        return $this;
    }

    /**
     * @param array $emails
     * @param int $workerNumber
     *
     * @return void
     * @throws CException
     */
    protected function processBatch(array $emails, int $workerNumber): void
    {
        /** @var TransactionalEmail[] $emails */
        foreach ($emails as $email) {
            $start  = (float)microtime(true);
            $sent   = $email->send();
            $end    = (float)microtime(true);
            $this->stdout(sprintf(
                '[Worker #%d] - Sending from "%s" to "%s", took %.5f seconds, result: %s',
                $workerNumber,
                !empty($email->from_email) ? $email->from_email : $email->from_name,
                $email->to_email,
                round($end - $start, 5),
                $sent ? 'success' : 'fail'
            ));
        }
    }
}
