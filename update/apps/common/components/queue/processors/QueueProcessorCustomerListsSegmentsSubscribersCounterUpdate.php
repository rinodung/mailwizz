<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.16
 */

class QueueProcessorCustomerListsSegmentsSubscribersCounterUpdate implements Processor
{
    /**
     * @param Message $message
     * @param Context $context
     *
     * @return string
     */
    public function process(Message $message, Context $context)
    {
        // do not retry this message
        if ($message->isRedelivered()) {
            return self::ACK;
        }

        $criteria = new CDbCriteria();
        $criteria->with = [];

        $criteria->compare('t.status', ListSegment::STATUS_ACTIVE);
        $criteria->with['list'] = [
            'together'  => true,
            'joinType'  => 'INNER JOIN',
            'with'      => [
                'customer' => [
                    'together'  => true,
                    'joinType'  => 'INNER JOIN',
                ],
            ],
        ];

        $criteria->compare('list.status', Lists::STATUS_ACTIVE);
        $criteria->compare('customer.status', Lists::STATUS_ACTIVE);

        if ($message->getProperty('customer_id')) {
            $criteria->compare('list.customer_id', $message->getProperty('customer_id'));
        }
        if ($message->getProperty('list_id')) {
            $criteria->compare('t.list_id', $message->getProperty('list_id'));
        }
        if ($message->getProperty('segment_id')) {
            $criteria->compare('t.segment_id', $message->getProperty('segment_id'));
        }

        /** @var ListSegment[] $segments */
        $segments = ListSegment::model()->findAll($criteria);

        foreach ($segments as $segment) {
            try {
                $count = $segment->countSubscribers();
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                $count = null;
            }

            $cacheKey = sha1(sprintf(ListSegment::SUBSCRIBERS_COUNTER_KEY_PATTERN, $segment->list_id, $segment->segment_id));
            if (is_numeric($count)) {
                cache()->set($cacheKey, $count);
            } else {
                cache()->delete($cacheKey);
            }
        }

        return self::ACK;
    }
}
