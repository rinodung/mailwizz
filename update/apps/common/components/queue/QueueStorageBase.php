<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

abstract class QueueStorageBase implements QueueStorageInterface
{
    /**
     * @var FilesystemInterface;
     */
    protected $_fileSystem;

    /**
     * @return AdapterInterface
     */
    abstract public function getAdapter(): AdapterInterface;

    /**
     * @return FilesystemInterface
     */
    public function getFilesystem(): FilesystemInterface
    {
        if ($this->_fileSystem === null) {
            $this->_fileSystem = new Filesystem($this->getAdapter());
        }
        return $this->_fileSystem;
    }
}
