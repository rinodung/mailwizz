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

class QueueMonitorDatabase extends QueueMonitorBase
{
    /**
     * @param QueueMonitorPropertiesInterface $properties
     *
     * @return bool
     */
    public function save(QueueMonitorPropertiesInterface $properties): bool
    {
        /** @var QueueMonitor $model */
        $model = null;

        $id = (string)$properties->getProperty('id', '');
        if (empty($id)) {
            return false;
        }

        $model = QueueMonitor::model()->findByAttributes([
            'id' => $id,
        ]);

        if (empty($model)) {
            $model = new QueueMonitor();
            $model->id = $id;
        }

        $model->setAttributes($properties->getProperties());

        return $model->save();
    }

    /**
     * @param QueueMonitorCriteriaInterface $criteria
     *
     * @return QueueMonitorPropertiesInterface|null
     */
    public function findByCriteria(QueueMonitorCriteriaInterface $criteria): ?QueueMonitorPropertiesInterface
    {
        $model = QueueMonitor::model()->find($criteria->getCriteria());
        if (empty($model)) {
            return null;
        }
        return new QueueMonitorProperties($model->getAttributes());
    }

    /**
     * @param QueueMonitorPropertiesInterface $properties
     *
     * @return QueueMonitorPropertiesInterface|null
     */
    public function findByProperties(QueueMonitorPropertiesInterface $properties): ?QueueMonitorPropertiesInterface
    {
        $model = QueueMonitor::model()->findByAttributes($properties->getProperties());
        if (empty($model)) {
            return null;
        }
        return new QueueMonitorProperties($model->getAttributes());
    }
}
