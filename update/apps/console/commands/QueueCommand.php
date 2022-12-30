<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Enqueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\Consumption\Extension\LoggerExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\ProcessorExceptionExtensionInterface;
use Enqueue\Consumption\Result;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * QueueCommand
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueCommand extends ConsoleCommand
{
    /**
     * @var string
     *
     * The queue from where we will read
     */
    public $queue = '';

    /**
     * @return int
     */
    public function actionIndex()
    {
        if (!empty($this->queue)) {
            $queues = [$this->queue];
        } else {
            $queues = queue()->getRegistry()->getQueueNames();
        }

        // see https://php-enqueue.github.io/consumption/extensions/
        $extensions = [];
        if (CommonHelper::functionExists('pcntl_async_signals') && CommonHelper::functionExists('pcntl_signal')) {
            $extensions[] = new SignalExtension();
        }

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

        if ($this->verbose) {
            $logger = new Logger('queue');
            $logger->pushHandler(new StreamHandler('php://stdout'));
            $extensions[] = new LoggerExtension($logger);

            $extensions[] = new class() implements
                MessageReceivedExtensionInterface,
                PostMessageReceivedExtensionInterface,
                ProcessorExceptionExtensionInterface {
                public function onMessageReceived(MessageReceived $context): void
                {
                    $context->getLogger()->debug(sprintf(
                        '[%s] Processing message ID %s',
                        $context->getConsumer()->getQueue()->getQueueName(),
                        $context->getMessage()->getMessageId()
                    ));
                }
                public function onPostMessageReceived(PostMessageReceived $context): void
                {
                    $context->getLogger()->debug(sprintf(
                        '[%s] Processed message ID %s in %s seconds with the result %s',
                        $context->getConsumer()->getQueue()->getQueueName(),
                        $context->getMessage()->getMessageId(),
                        round((((int)(microtime(true) * 1000)) - $context->getReceivedAt()) / 1000, 5),
                        json_encode($context->getResult())
                    ));
                }
                public function onProcessorException(ProcessorException $context): void
                {
                    $context->getLogger()->debug(sprintf(
                        '[%s] Exception thrown for message ID %s: %s',
                        $context->getConsumer()->getQueue()->getQueueName(),
                        $context->getMessage()->getMessageId(),
                        $context->getException()->getMessage()
                    ));
                }
            };
        }

        $extensions[] = new LimitConsumptionTimeExtension(new \DateTime('now + 60 sec'));
        $extensions[] = new LimitConsumedMessagesExtension(10);
        $extensions[] = new LimitConsumerMemoryExtension(2 * 1024);
        $extensions[] = queue()->getMonitor()->getConsumptionExtension();

        queue_consume($queues, new ChainExtension($extensions));

        return 0;
    }

    /**
     * @return int
     */
    public function actionPurge()
    {
        if (!empty($this->queue)) {
            $queues = [$this->queue];
        } else {
            $queues = queue()->getRegistry()->getQueueNames();
        }

        queue_purge($queues);

        return 0;
    }
}
