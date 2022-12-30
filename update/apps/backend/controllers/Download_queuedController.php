<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Download_queuedController
 *
 * Handles the actions for downloading the results of queued tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 */

class Download_queuedController extends Controller
{
    /**
     * Default action, allowing to download.
     *
     * @param string $file
     * @return void
     * @throws CHttpException|\League\Flysystem\FileNotFoundException
     */
    public function actionIndex($file)
    {
        $fileSystem = queue()->getStorage()->getFilesystem();
        if (!$fileSystem->has($file)) {
            throw new CHttpException(404, t('app', 'Page not found.'));
        }
        if (!($stream = $fileSystem->readStream($file))) {
            throw new CHttpException(404, t('app', 'Page not found.'));
        }
        HeaderHelper::setDownloadHeaders($file);
        fpassthru($stream);
    }
}
