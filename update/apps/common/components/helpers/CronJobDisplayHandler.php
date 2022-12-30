<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CronJobDisplayHandler
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

class CronJobDisplayHandler
{
    /**
     * @return array
     */
    public static function getCronJobsList(): array
    {
        static $cronJobs;
        if ($cronJobs !== null) {
            return $cronJobs;
        }

        $cronJobs = [
            [
                'frequency'     => '* * * * *',
                'phpBinary'     => CommonHelper::findPhpCliPath(),
                'consolePath'   => MW_APPS_PATH . '/console/console.php',
                'command'       => 'send-campaigns',
                'description'   => 'Campaigns sender, runs each minute.',
            ],
            [
                'frequency'     => '* * * * *',
                'phpBinary'     => CommonHelper::findPhpCliPath(),
                'consolePath'   => MW_APPS_PATH . '/console/console.php',
                'command'       => 'queue',
                'description'   => 'Queue handler, runs each minute.',
            ],
            [
                'frequency'     => '*/2 * * * *',
                'phpBinary'     => CommonHelper::findPhpCliPath(),
                'consolePath'   => MW_APPS_PATH . '/console/console.php',
                'command'       => 'send-transactional-emails',
                'description'   => 'Transactional email sender, runs once at 2 minutes.',
            ],
            [
                'frequency'     => '*/10 * * * *',
                'phpBinary'     => CommonHelper::findPhpCliPath(),
                'consolePath'   => MW_APPS_PATH . '/console/console.php',
                'command'       => 'bounce-handler',
                'description'   => 'Bounce handler, runs once at 10 minutes.',
            ],
            [
                'frequency'     => '*/20 * * * *',
                'phpBinary'     => CommonHelper::findPhpCliPath(),
                'consolePath'   => MW_APPS_PATH . '/console/console.php',
                'command'       => 'feedback-loop-handler',
                'description'   => 'Feedback loop handler, runs once at 20 minutes.',
            ],
            [
                'frequency'     => '*/3 * * * *',
                'phpBinary'     => CommonHelper::findPhpCliPath(),
                'consolePath'   => MW_APPS_PATH . '/console/console.php',
                'command'       => 'process-delivery-and-bounce-log',
                'description'   => 'Delivery/Bounce processor, runs once at 3 minutes.',
            ],
            [
                'frequency'     => '0 * * * *',
                'phpBinary'     => CommonHelper::findPhpCliPath(),
                'consolePath'   => MW_APPS_PATH . '/console/console.php',
                'command'       => 'hourly',
                'description'   => 'Various tasks, runs each hour.',
            ],
            [
                'frequency'     => '0 0 * * *',
                'phpBinary'     => CommonHelper::findPhpCliPath(),
                'consolePath'   => MW_APPS_PATH . '/console/console.php',
                'command'       => 'daily',
                'description'   => 'Daily cleaner, runs once a day.',
            ],
        ];

        if (class_exists('Yii', false)) {
            /** @var array $cronJobs */
            $cronJobs = (array)hooks()->applyFilters('cron_job_display_handler_cron_jobs_list', $cronJobs);
        }

        /**
         * @var int $index
         * @var array $data
         */
        foreach ($cronJobs as $index => $data) {
            if (!isset($data['frequency'], $data['phpBinary'], $data['consolePath'], $data['command'], $data['description'])) {
                unset($cronJobs[$index]);
                continue;
            }
            $cronJobs[$index]['cronjob'] = sprintf('%s %s -q %s %s >/dev/null 2>&1', $data['frequency'], $data['phpBinary'], $data['consolePath'], $data['command']);
        }

        return $cronJobs;
    }

    /**
     * @return CArrayDataProvider
     */
    public static function getAsDataProvider(): CArrayDataProvider
    {
        $crons = [];
        foreach (self::getCronJobsList() as $data) {
            $crons[] = [
                'id'          => $data['command'],
                'frequency'   => $data['frequency'],
                'phpBinary'   => $data['phpBinary'],
                'consolePath' => $data['consolePath'],
                'command'     => $data['command'],
                'description' => t('cron_jobs', $data['description']),
                'cronjob'     => $data['cronjob'],
            ];
        }

        return new CArrayDataProvider($crons, [
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);
    }
}
