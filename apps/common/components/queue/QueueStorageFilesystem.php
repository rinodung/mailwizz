<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueStorageFilesystem extends QueueStorageBase
{
    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface
    {
        $path = (string)Yii::getPathOfAlias('common.runtime.queued');
        if (!file_exists($path) || !is_dir($path)) {
            mkdir($path);
        }

        return new Local($path);
    }
}
