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

class QueueProcessorRegistry implements QueueProcessorRegistryInterface
{
    /**
     * @var array
     */
    protected $registry = [];

    /**
     * @param string $queueName
     * @param Processor $processor
     * @return void
     */
    public function register(string $queueName, Processor $processor): void
    {
        $this->registry[$queueName] = $processor;
    }

    /**
     * @param string $queueName
     *
     * @return bool
     */
    public function isRegistered(string $queueName): bool
    {
        return isset($this->registry[$queueName]);
    }

    /**
     * @param string $queueName
     *
     * @return Processor
     * @throws QueueNotRegisteredException
     */
    public function getProcessor(string $queueName): Processor
    {
        if (!$this->isRegistered($queueName)) {
            throw new QueueNotRegisteredException(sprintf('The queue name "%s" is not registered!', $queueName));
        }

        return $this->registry[$queueName];
    }

    /**
     * @return array
     */
    public function getQueueNames(): array
    {
        return array_keys($this->registry);
    }

    /**
     * @return void
     */
    public function registerDefaultQueues(): void
    {
        $map = [
            'customer.lists.allsubscribers.filter.export'           => new QueueProcessorCustomerListsAllSubscribersFilterExport(),
            'customer.lists.allsubscribers.filter.export.delete'    => new QueueProcessorCustomerListsAllSubscribersFilterExportDelete(),
            'customer.lists.allsubscribers.filter.createlist'       => new QueueProcessorCustomerListsAllSubscribersFilterCreateList(),
            'customer.lists.allsubscribers.filter.confirm'          => new QueueProcessorCustomerListsAllSubscribersFilterConfirm(),
            'customer.lists.allsubscribers.filter.unsubscribe'      => new QueueProcessorCustomerListsAllSubscribersFilterUnsubscribe(),
            'customer.lists.allsubscribers.filter.disable'          => new QueueProcessorCustomerListsAllSubscribersFilterDisable(),
            'customer.lists.allsubscribers.filter.delete'           => new QueueProcessorCustomerListsAllSubscribersFilterDelete(),
            'customer.lists.allsubscribers.filter.blacklist'        => new QueueProcessorCustomerListsAllSubscribersFilterBlacklist(),
            'customer.lists.segments.subscribers.counter.update'    => new QueueProcessorCustomerListsSegmentsSubscribersCounterUpdate(),

            'backend.lists.allsubscribers.filter.export'           => new QueueProcessorBackendListsAllSubscribersFilterExport(),
            'backend.lists.allsubscribers.filter.export.delete'    => new QueueProcessorBackendListsAllSubscribersFilterExportDelete(),
            'backend.lists.allsubscribers.filter.confirm'          => new QueueProcessorBackendListsAllSubscribersFilterConfirm(),
            'backend.lists.allsubscribers.filter.unsubscribe'      => new QueueProcessorBackendListsAllSubscribersFilterUnsubscribe(),
            'backend.lists.allsubscribers.filter.disable'          => new QueueProcessorBackendListsAllSubscribersFilterDisable(),
            'backend.lists.allsubscribers.filter.delete'           => new QueueProcessorBackendListsAllSubscribersFilterDelete(),
            'backend.lists.allsubscribers.filter.blacklist'        => new QueueProcessorBackendListsAllSubscribersFilterBlacklist(),
            'backend.emailblacklist.deleteall'                     => new QueueProcessorBackendEmailBlacklistDeleteAll(),

            'console.hourly.listsubscriberscounthistory.update'    => new QueueProcessorConsoleHourlyListSubscriberCountHistoryUpdate(),
        ];

        foreach ($map as $queueName => $processor) {
            $this->register($queueName, $processor);
        }
    }
}
