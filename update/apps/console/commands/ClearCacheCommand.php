<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ClearCacheCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.6
 *
 */

class ClearCacheCommand extends ConsoleCommand
{
    // enable verbose mode
    public $verbose = 1;

    /**
     * @return int
     */
    public function actionIndex()
    {
        $result = 0;

        try {
            hooks()->doAction('console_command_clear_cache_before_process', $this);

            $result = $this->process();

            hooks()->doAction('console_command_clear_cache_after_process', $this);
        } catch (Exception $e) {
            $this->stdout(__LINE__ . ': ' . $e->getMessage());
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $result;
    }

    /**
     * @return int
     * @throws CDbException
     */
    protected function process()
    {
        $this->stdout(FileSystemHelper::clearCache());

        $this->stdout('Calling Cache::flush()...');
        cache()->flush();

        $this->stdout('Clearing the database schema cache...');
        db()->getSchema()->getTables();
        db()->getSchema()->refresh();

        $this->stdout('DONE.');

        return 0;
    }
}
