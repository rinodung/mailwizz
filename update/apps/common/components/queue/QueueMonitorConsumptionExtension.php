<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\InitLogger;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\ProcessorException;
use Enqueue\Consumption\Context\Start;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueMonitorConsumptionExtension implements QueueMonitorConsumptionExtensionInterface
{
    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * QueueMonitorConsumptionExtension constructor.
     *
     * @param QueueInterface $queue
     */
    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @return QueueInterface
     */
    public function getQueue(): QueueInterface
    {
        return $this->queue;
    }

    /**
     * @param End $context
     *
     * @return void
     */
    public function onEnd(End $context): void
    {
    }

    /**
     * @param InitLogger $context
     *
     * @return void
     */
    public function onInitLogger(InitLogger $context): void
    {
    }

    /**
     * @param MessageReceived $context
     *
     * @return void
     */
    public function onMessageReceived(MessageReceived $context): void
    {
        $message = $context->getMessage();
        $id      = $message->getProperty('_monitor', [])['id'] ?? '';
        if (empty($id)) {
            return;
        }

        $monitor    = $this->getQueue()->getMonitor();
        $properties = new QueueMonitorProperties([
            'id'         => $id,
            'message_id' => $context->getMessage()->getMessageId(),
            'status'     => QueueStatus::PROCESSING,
        ]);

        $monitor->save($properties);
    }

    /**
     * @param MessageResult $context
     *
     * @return void
     */
    public function onResult(MessageResult $context): void
    {
    }

    /**
     * @param PostConsume $context
     *
     * @return void
     */
    public function onPostConsume(PostConsume $context): void
    {
    }

    /**
     * @param PostMessageReceived $context
     *
     * @return void
     */
    public function onPostMessageReceived(PostMessageReceived $context): void
    {
        $message = $context->getMessage();
        $id      = $message->getProperty('_monitor', [])['id'] ?? '';
        if (empty($id)) {
            return;
        }

        $monitor    = $this->getQueue()->getMonitor();
        $properties = new QueueMonitorProperties([
            'id'     => $id,
            'status' => (string)$context->getResult(),
        ]);

        $monitor->save($properties);
    }

    /**
     * @param PreConsume $context
     *
     * @return void
     */
    public function onPreConsume(PreConsume $context): void
    {
    }

    /**
     * @param PreSubscribe $context
     *
     * @return void
     */
    public function onPreSubscribe(PreSubscribe $context): void
    {
    }

    /**
     * @param ProcessorException $context
     *
     * @return void
     */
    public function onProcessorException(ProcessorException $context): void
    {
    }

    /**
     * @param Start $context
     *
     * @return void
     */
    public function onStart(Start $context): void
    {
    }
}
