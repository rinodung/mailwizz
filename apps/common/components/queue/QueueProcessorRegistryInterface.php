<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Interop\Queue\Processor;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

interface QueueProcessorRegistryInterface
{
    /**
     * @param string $queueName
     * @param Processor $processor
     * @return void
     */
    public function register(string $queueName, Processor $processor): void;

    /**
     * @param string $queueName
     *
     * @return bool
     */
    public function isRegistered(string $queueName): bool;

    /**
     * @param string $queueName
     *
     * @return Processor
     * @throws QueueNotRegisteredException
     */
    public function getProcessor(string $queueName): Processor;

    /**
     * @return array
     */
    public function getQueueNames(): array;
}
