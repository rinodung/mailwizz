<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

abstract class QueueMonitorBase implements QueueMonitorInterface
{
    /**
     * @var QueueInterface
     */
    protected $queue;

    /**
     * QueueMonitorBase constructor.
     *
     * @param QueueInterface $queue
     */
    public function __construct(QueueInterface $queue)
    {
        $this->queue = $queue;
    }

    /**
     * @param QueueMonitorPropertiesInterface $properties
     *
     * @return bool
     */
    abstract public function save(QueueMonitorPropertiesInterface $properties): bool;

    /**
     * @param QueueMonitorCriteriaInterface $criteria
     *
     * @return QueueMonitorPropertiesInterface|null
     */
    abstract public function findByCriteria(QueueMonitorCriteriaInterface $criteria): ?QueueMonitorPropertiesInterface;

    /**
     * @param QueueMonitorPropertiesInterface $properties
     *
     * @return null|QueueMonitorPropertiesInterface
     */
    abstract public function findByProperties(QueueMonitorPropertiesInterface $properties): ?QueueMonitorPropertiesInterface;

    /**
     * @return QueueMonitorConsumptionExtensionInterface
     */
    public function getConsumptionExtension(): QueueMonitorConsumptionExtensionInterface
    {
        return new QueueMonitorConsumptionExtension($this->queue);
    }
}
