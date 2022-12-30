<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UpdateController
 *
 * Handles the actions for updating the application
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.1
 */

class UpdateController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        parent::init();
    }

    /**
     * Display the update page and execute the update
     *
     * @return void
     * @throws CDbException
     * @throws CException
     */
    public function actionIndex()
    {
        notify()->clearAll();

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        $versionInFile = MW_VERSION;
        $versionInDb   = $common->version;

        if (!version_compare($versionInFile, $versionInDb, '>')) {
            if (!$common->getIsSiteOnline()) {
                $common->saveAttributes(['site_status' => OptionCommon::STATUS_ONLINE]);
            }
            $this->redirect(['dashboard/index']);
        }

        // put the application offline
        $common->saveAttributes(['site_status' => OptionCommon::STATUS_OFFLINE]);

        // start the work
        if (request()->getIsPostRequest()) {
            $workersPath = (string)Yii::getPathOfAlias('backend.components.update');
            require_once $workersPath . '/UpdateWorkerAbstract.php';

            $updateWorkers  = (array)FileSystemHelper::readDirectoryContents($workersPath);

            foreach ($updateWorkers as $index => $fileName) {
                $fileName = basename($fileName, '.php');
                if (strpos($fileName, 'UpdateWorkerFor_') !== 0) {
                    unset($updateWorkers[$index]);
                    continue;
                }

                $workerVersion = str_replace('UpdateWorkerFor_', '', $fileName);
                $workerVersion = str_replace('_', '.', $workerVersion);

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

            foreach ($updateWorkers as $workerVersion) {
                $transaction = db()->beginTransaction();
                try {
                    notify()->addInfo(t('update', 'Updating to version {version}.', ['{version}' => $workerVersion]));
                    $this->runWorker($workerVersion);
                    notify()->addInfo(t('update', 'Updated to version {version} successfully.', ['{version}' => $workerVersion]));

                    $common->saveAttributes([
                        'version'                        => $workerVersion,
                        'version_update.current_version' => $workerVersion,
                    ]);
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    notify()->addError(t('update', 'Updating to version {version} failed with: {message}', [
                        '{version}' => $workerVersion,
                        '{message}' => $e->getMessage(),
                    ]));
                    break;
                }
            }

            db()->createCommand('SET SQL_MODE=@OLD_SQL_MODE')->execute();
            db()->createCommand('SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS')->execute();
            db()->createCommand('SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS')->execute();

            if (notify()->getHasError()) {
                $this->redirect(['update/index']);
            }

            $common->saveAttributes([
                'version'                        => $versionInFile,
                'site_status'                    => OptionCommon::STATUS_ONLINE,
                'version_update.current_version' => $versionInFile,
            ]);

            notify()->addSuccess(t('update', 'Congratulations, your application has been successfully updated to version {version}', [
                '{version}' => '<span class="badge">' . $versionInFile . '</span>',
            ]));

            // since 1.3.6.3 - update extensions
            $manager    = extensionsManager();
            $extensions = $manager->getCoreExtensions();
            $errors     = [];
            foreach ($extensions as $id => $instance) {
                if ($manager->extensionMustUpdate($id) && !$manager->updateExtension($id)) {
                    $errors[] = t('extensions', 'The extension "{name}" has failed to update!', [
                        '{name}' => html_encode((string)$instance->name),
                    ]);
                    $errors = CMap::mergeArray($errors, (array)$manager->getErrors());
                    $manager->resetErrors();
                }
            }
            if (!empty($errors)) {
                notify()->addError($errors);
            }
            //

            // clean directories of old asset files.
            FileSystemHelper::clearCache();

            // remove the cache, can be redis for example
            cache()->flush();

            // rebuild the tables schema cache
            db()->getSchema()->getTables();
            db()->getSchema()->refresh();

            // and back
            $this->redirect(['dashboard/index']);
        }

        notify()->addInfo(t('update', 'Please note, depending on your database size it is better to run the command line update tool instead.'));
        notify()->addInfo(t('update', 'In order to run the command line update tool, you must run the following command from a ssh shell:'));
        notify()->addInfo(sprintf('<strong>%s</strong>', CommonHelper::findPhpCliPath() . ' ' . (string)Yii::getPathOfAlias('console') . '/console.php update'));

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('update', 'Update'),
            'pageHeading'     => t('update', 'Application update'),
            'pageBreadcrumbs' => [
                t('update', 'Update'),
            ],
        ]);

        $this->render('index', compact('versionInFile', 'versionInDb'));
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    protected function runWorker($version)
    {
        $workersPath    = (string)Yii::getPathOfAlias('backend.components.update');
        $version        = str_replace('.', '_', $version);
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
