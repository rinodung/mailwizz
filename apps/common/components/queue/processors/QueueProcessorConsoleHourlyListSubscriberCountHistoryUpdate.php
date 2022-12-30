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
 * @since 2.0.34
 */

class QueueProcessorConsoleHourlyListSubscriberCountHistoryUpdate implements Processor
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
        $dateStart  = $message->getProperty('dateStart');
        $dateEnd    = $message->getProperty('dateEnd');
        $customerId = $message->getProperty('customerId');

        if (empty($dateEnd) || empty($dateStart) || empty($customerId)) {
            return self::ACK;
        }

        $dateStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateStart);
        $dateEnd   = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dateEnd);

        if (!$dateStart || !$dateEnd) {
            return self::ACK;
        }

        if ($dateStart->greaterThan($dateEnd)) {
            return self::ACK;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('status', Lists::STATUS_ACTIVE);
        $criteria->compare('customer_id', (int)$customerId);

        $lists = Lists::model()->findAll($criteria);

        foreach ($lists as $list) {
            $counterExists = (int)ListSubscriberCountHistory::model()->countByAttributes([
                'list_id'    => $list->list_id,
                'date_added' => $dateEnd->format('Y-m-d H:00:00'),
            ]);

            if ($counterExists > 0) {
                continue;
            }

            $countHistory = new ListSubscriberCountHistory();
            $countHistory->list_id = $list->list_id;
            if ($countHistory->calculate($dateStart, $dateEnd)) {
                $countHistory->date_added = $dateEnd->format('Y-m-d H:00:00');
                $countHistory->detachBehavior('CTimestampBehavior');
                $countHistory->save(false);
            }
        }

        return self::ACK;
    }
}
