<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.8
 */

class UpdateCommand extends ConsoleCommand
{
    /**
     * @var int
     */
    public $verbose = 1;

    /**
     * @var int
     */
    public $interactive = 1;

    /**
     * @return int
     * @throws CDbException
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var OptionCommon $common */
        $common         = container()->get(OptionCommon::class);
        $versionInFile  = defined('MW_AUTOUPDATE_VERSION') ? MW_AUTOUPDATE_VERSION : MW_VERSION;
        $versionInDb    = $common->version;

        if (!version_compare($versionInFile, $versionInDb, '>')) {
            $common->saveAttributes([
                'site_status' => OptionCommon::STATUS_ONLINE,
            ]);
            $this->stdout(t('update', 'You are already at latest version!'));
            return 0;
        }

        if ($this->interactive) {
            $input = $this->confirm(t('update', 'Are you sure you want to update your Mailwizz application from version {vFrom} to version {vTo} ?', [
                '{vFrom}' => $versionInDb,
                '{vTo}'   => $versionInFile,
            ]));

            if (!$input) {
                $this->stdout(t('update', 'Okay, aborting the update process!'));
                return 0;
            }
        }

        // put the application offline
        $common->saveAttributes([
            'site_status' => OptionCommon::STATUS_OFFLINE,
        ]);

        $workersPath = (string)Yii::getPathOfAlias('backend.components.update');
        require_once $workersPath . '/UpdateWorkerAbstract.php';

        $updateWorkers  = (array)FileSystemHelper::readDirectoryContents($workersPath);

        foreach ($updateWorkers as $index => $fileName) {
            $fileName = basename($fileName, '.php');
            if (strpos($fileName, 'UpdateWorkerFor_') !== 0) {
                unset($updateWorkers[$index]);
                continue;
            }

            $workerVersion = (string)str_replace('UpdateWorkerFor_', '', $fileName);
            $workerVersion = (string)str_replace('_', '.', $workerVersion);

            // previous versions ?
            if (version_compare($workerVersion, $versionInDb, '<=')) {
                unset($updateWorkers[$index]);
                continue;
            }

            // next versions ?
            if (version_compare($workerVersion, $versionInFile, '>')) {
                unset($updateWorkers[$index]);
                continue;
            }

            $updateWorkers[$index] = $workerVersion;
        }

        $updateWorkers = array_values($updateWorkers);
        usort($updateWorkers, 'version_compare');

        db()->createCommand('SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0')->execute();
        db()->createCommand('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0')->execute();
        db()->createCommand('SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=""')->execute();

        $success = true;
        foreach ($updateWorkers as $workerVersion) {
            $transaction = db()->beginTransaction();
            try {
                $this->stdout(t('update', 'Updating to version {version}.', ['{version}' => $workerVersion]));
                $this->runWorker($workerVersion);
                $this->stdout(t('update', 'Updated to version {version} successfully.', ['{version}' => $workerVersion]));

                $common->saveAttributes([
                    'version'                           => $versionInFile,
                    'version_update.current_version'    => $versionInFile,
                ]);

                $transaction->commit();
            } catch (Exception $e) {
                $success = false;
                $transaction->rollback();
                $this->stdout(t('update', 'Updating to version {version} failed with: {message}', [
                    '{version}' => $workerVersion,
                    '{message}' => $e->getMessage(),
                ]));
                break;
            }
        }

        if (!$success) {
            return 1;
        }

        db()->createCommand('SET SQL_MODE=@OLD_SQL_MODE')->execute();
        db()->createCommand('SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS')->execute();
        db()->createCommand('SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS')->execute();

        $common->saveAttributes([
            'version'                           => $versionInFile,
            'site_status'                       => OptionCommon::STATUS_ONLINE,
            'version_update.current_version'    => $versionInFile,
        ]);

        $this->stdout(t('update', 'Congratulations, your application has been successfully updated to version {version}', [
            '{version}' => $versionInFile,
        ]));

        // since 1.3.6.3 - update extensions
        $manager    = extensionsManager();
        $extensions = $manager->getCoreExtensions();
        $errors     = [];
        foreach ($extensions as $id => $instance) {
            if ($manager->extensionMustUpdate($id) && !$manager->updateExtension($id)) {
                $errors[] = t('extensions', 'The extension "{name}" has failed to update!', [
                    '{name}' => html_encode($instance->name),
                ]);
                $errors[] = "\n";
                $errors = CMap::mergeArray($errors, (array)$manager->getErrors());
                $errors[] = "\n";
                $manager->resetErrors();
            }
        }
        if (!empty($errors)) {
            $this->stdout(implode("\n", $errors));
        }
        //

        // clean directories of old asset files.
        $this->stdout(FileSystemHelper::clearCache());

        // remove the cache, can be redis for example
        cache()->flush();

        // rebuild the tables schema cache
        $this->stdout('Rebuilding database schema cache...');
        db()->getSchema()->getTables();
        db()->getSchema()->refresh();
        $this->stdout('Done.');

        // and done...
        return 0;
    }

    /**
     * @param string $version
     * @return bool
     */
    protected function runWorker(string $version)
    {
        $workersPath    = (string)Yii::getPathOfAlias('backend.components.update');
        $version        = (string)str_replace('.', '_', $version);
        $className      = 'UpdateWorkerFor_' . $version;

        if (!is_file($classFile = $workersPath . '/' . $className . '.php')) {
            return false;
        }

        require_once $classFile;
        $instance = new $className();

        if ($instance instanceof UpdateWorkerAbstract) {
            $instance->run();
        }

        return true;
    }
}
