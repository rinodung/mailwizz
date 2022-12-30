<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Fs\FsConnectionFactory;
use Interop\Queue\Context;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueFilesystem extends QueueBase
{
    /**
     * @return Context
     * @throws Exception
     */
    public function getContext(): Context
    {
        if ($this->_context === null) {
            $storagePath = (string)Yii::getPathOfAlias('common.runtime.queue');
            if ((!file_exists($storagePath) || !is_dir($storagePath)) && !mkdir($storagePath)) {
                throw new Exception(sprintf('Please make sure the folder "%s" exists and it is writable!', $storagePath));
            }
            $connectionFactory = new FsConnectionFactory([
                'path'              => $storagePath,
                'pre_fetch_count'   => 1,
            ]);
            $this->_context = $connectionFactory->createContext();
        }

        return $this->_context;
    }
}
