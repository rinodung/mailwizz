<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Consumption\ExtensionInterface;
use Interop\Queue\Context;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

interface QueueInterface
{
    /**
     * @return Context
     */
    public function getContext(): Context;

    /**
     * @return QueueProcessorRegistryInterface
     */
    public function getRegistry(): QueueProcessorRegistryInterface;

    /**
     * @return QueueStorageInterface
     */
    public function getStorage(): QueueStorageInterface;

    /**
     * @return QueueMonitorInterface
     */
    public function getMonitor(): QueueMonitorInterface;

    /**
     * @param QueueNameInterface $queue
     * @param QueueMessageInterface $message
     */
    public function send(QueueNameInterface $queue, QueueMessageInterface $message): void;

    /**
     * @param QueueNameInterface $queue
     * @param ExtensionInterface|null $extension
     *
     * @return void
     */
    public function consume(QueueNameInterface $queue, ExtensionInterface $extension = null): void;

    /**
     * @param QueueNameInterface $queue
     */
    public function purge(QueueNameInterface $queue): void;
}
