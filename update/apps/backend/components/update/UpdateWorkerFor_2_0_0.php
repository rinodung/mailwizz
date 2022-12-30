<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateWorkerFor_2_0_0
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class UpdateWorkerFor_2_0_0 extends UpdateWorkerAbstract
{
    public function run()
    {
        // run the sql from file
        $this->runQueriesFromSqlFile('2.0.0');

        try {
            $this->renameTranslationFiles();
        } catch (Exception $e) {
        }

        $phpCli = CommonHelper::findPhpCliPath();
        notify()->addInfo(t('update', 'Version {version} brings a new cron job that you have to add to run each minute. After addition, it must look like: {cron}', [
            '{version}' => '2.0.0',
            '{cron}'    => sprintf('<br /><strong>* * * * * %s -q ' . MW_ROOT_PATH . '/apps/console/console.php queue > /dev/null 2>&1</strong>', $phpCli),
        ]));
    }

    /**
     * Rename translation files to the new file name standard
     *
     * @return void
     */
    protected function renameTranslationFiles()
    {
        /** @var SplFileInfo[] $files */
        $files = (new Symfony\Component\Finder\Finder())
            ->files()
            ->name('*.php')
            ->in((string)Yii::getPathOfAlias('common.messages'));

        foreach ($files as $file) {
            if (stripos((string)$file->getFilename(), 'ext_') !== 0) {
                continue;
            }
            $newName = dirname((string)$file->getRealPath()) . '/' . substr(basename((string)$file->getFilename(), '.php'), 4) . '_ext.php';
            rename((string)$file->getRealPath(), $newName);
        }
    }
}
