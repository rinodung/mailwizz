<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

interface QueueStorageInterface
{
    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface;

    /**
     * @return FilesystemInterface
     */
    public function getFilesystem(): FilesystemInterface;
}
