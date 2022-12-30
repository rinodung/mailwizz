<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\ProcessorExceptionExtensionInterface;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

abstract class QueueBase extends CApplicationComponent implements QueueInterface
{
    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var QueueProcessorRegistryInterface
     */
    protected $_registry;

    /**
     * @var QueueStorageInterface|null
     */
    protected $_storage;

    /**
     * @var QueueMonitorInterface|null
     */
    protected $_monitor;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        /** @var QueueProcessorRegistry $registry */
        $registry = $this->getRegistry();
        $registry->registerDefaultQueues();
    }

    /**
     * @return Context
     */
    abstract public function getContext(): Context;

    /**
     * @param QueueNameInterface $queue
     * @param QueueMessageInterface $message
     *
     * @return void
     * @throws QueueNotRegisteredException
     * @throws \Interop\Queue\Exception
     * @throws InvalidDestinationException
     * @throws InvalidMessageException
     */
    public function send(QueueNameInterface $queue, QueueMessageInterface $message): void
    {
        foreach ($queue->toArray() as $queueName) {
            if (!$this->getRegistry()->isRegistered($queueName)) {
                throw new QueueNotRegisteredException(sprintf('The queue name "%s" is not registered!', $queueName));
            }

            $createdQueue    = $this->getContext()->createQueue($queueName);
            $createdMessage  = $this->getContext()->createMessage($message->getBody(), $message->getProperties());
            $createdProducer = $this->getContext()->createProducer();

            $monitorProperties = (array)$createdMessage->getProperty('_monitor', []);
            $monitorProperties['queue'] = $queueName;

            if ($message->getDeliveryDelay()) {
                try {
                    $createdProducer->setDeliveryDelay($message->getDeliveryDelay());
                } catch (Exception $e) {
                }
            }

            if ($message->getPriority()) {
                try {
                    $createdProducer->setPriority($message->getPriority());
                } catch (Exception $e) {
                }
            }

            if ($message->getTimeToLive()) {
                try {
                    $createdProducer->setTimeToLive($message->getTimeToLive());
                } catch (Exception $e) {
                }
            }

            $props = new QueueMonitorProperties($monitorProperties, true);
            $this->getMonitor()->save($props);
            $createdMessage->setProperty('_monitor', $props->getProperties());

            $createdProducer->send($createdQueue, $createdMessage);
        }
    }

    /**
     * @param QueueNameInterface $queue
     * @param ExtensionInterface|null $extension
     *
     * @return void
     * @throws QueueNotRegisteredException
     * @throws Exception
     */
    public function consume(QueueNameInterface $queue, ExtensionInterface $extension = null): void
    {
        foreach ($queue->toArray() as $queueName) {
            if (!$this->getRegistry()->isRegistered($queueName)) {
                throw new QueueNotRegisteredException(sprintf('The queue name "%s" is not registered!', $queueName));
            }
        }

        $consumerQueue = new QueueConsumer($this->getContext());
        foreach ($queue->toArray() as $queueName) {
            $consumerQueue->bind($queueName, $this->getRegistry()->getProcessor($queueName));
        }

        if (empty($extension)) {
            // see https://php-enqueue.github.io/consumption/extensions/
            $extensions = [];
            if (CommonHelper::functionExists('pcntl_async_signals') && CommonHelper::functionExists('pcntl_signal')) {
                $extensions[] = new SignalExtension();
            }
            $extensions[] = new LimitConsumptionTimeExtension(new DateTime('now + 5 sec'));
            $extensions[] = new LimitConsumedMessagesExtension(10);
            $extensions[] = new LimitConsumerMemoryExtension(1024);
            $extensions[] = $this->getMonitor()->getConsumptionExtension();
            // Handle exceptions
            $extensions[] = new class() implements ProcessorExceptionExtensionInterface {
                public function onProcessorException(ProcessorException $context): void
                {
                    if ($context->getException() instanceof QueueProcessorRetriableException) {
                        $context->setResult(Result::requeue('Caught retriable exception!'));
                    } else {
                        $context->setResult(Result::reject('Caught exception!'));
                    }
                }
            };
            $extension = new ChainExtension($extensions);
        }

        $consumerQueue->consume($extension);
    }

    /**
     * @param QueueNameInterface $queue
     *
     * @return void
     * @throws QueueNotRegisteredException
     * @throws PurgeQueueNotSupportedException
     */
    public function purge(QueueNameInterface $queue): void
    {
        foreach ($queue->toArray() as $queueName) {
            if (!$this->getRegistry()->isRegistered($queueName)) {
                throw new QueueNotRegisteredException(sprintf('The queue name "%s" is not registered!', $queueName));
            }
        }

        foreach ($queue->toArray() as $queueName) {
            $this->getContext()->purgeQueue($this->getContext()->createQueue($queueName));
        }
    }

    /**
     * @return QueueProcessorRegistryInterface
     */
    public function getRegistry(): QueueProcessorRegistryInterface
    {
        if ($this->_registry === null) {
            $this->_registry = new QueueProcessorRegistry();
        }

        return $this->_registry;
    }

    /**
     * @param string|QueueStorageInterface $storage
     *
     * @return void
     * @throws CException
     * @throws Exception
     */
    public function setStorage($storage): void
    {
        if (is_string($storage)) {
            $storage = Yii::createComponent(['class' => $storage]);
        }

        if (!($storage instanceof QueueStorageInterface)) {
            // @phpstan-ignore-next-line
            throw new Exception(sprintf('"%s" must implement QueueStorageInterface', get_class($storage)));
        }

        $this->_storage = $storage;
    }

    /**
     * @return QueueStorageInterface
     */
    public function getStorage(): QueueStorageInterface
    {
        if (empty($this->_storage)) {
            $this->_storage = new QueueStorageFilesystem();
        }
        return $this->_storage;
    }

    /**
     * @param string|QueueMonitorInterface $monitor
     *
     * @return void
     * @throws CException
     * @throws Exception
     */
    public function setMonitor($monitor): void
    {
        if (is_string($monitor)) {
            $monitor = Yii::createComponent($monitor, $this);
        }

        if (!($monitor instanceof QueueMonitorInterface)) {
            // @phpstan-ignore-next-line
            throw new Exception(sprintf('"%s" must implement QueueMonitorInterface', get_class($monitor)));
        }

        $this->_monitor = $monitor;
    }

    /**
     * @return QueueMonitorInterface
     */
    public function getMonitor(): QueueMonitorInterface
    {
        if (empty($this->_monitor)) {
            $this->_monitor = new QueueMonitorDatabase($this);
        }
        return $this->_monitor;
    }
}
