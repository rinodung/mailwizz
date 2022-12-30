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
 * @since 2.0.0
 */

class QueueProcessorCustomerListsAllSubscribersFilterBlacklist implements Processor
{
    /**
     * @param Message $message
     * @param Context $context
     *
     * @return string
     * @throws CException
     */
    public function process(Message $message, Context $context)
    {
        // do not retry this message
        if ($message->isRedelivered()) {
            return self::ACK;
        }

        Yii::import('customer.models.AllListsSubscribersFilters');
        $filter = new AllListsSubscribersFilters();

        $attributes = CMap::mergeArray($filter->getAttributes(), $message->getProperties());
        foreach ($attributes as $key => $value) {
            if ($filter->hasAttribute($key) || property_exists($filter, $key)) {
                $filter->$key = $value;
            }
        }

        $customer = Customer::model()->findByPk((int)$filter->customer_id);
        if (empty($customer)) {
            return self::ACK;
        }

        $filter->blacklistSubscribers();

        $message = new CustomerMessage();
        $message->customer_id = (int)$filter->customer_id;
        $message->title       = 'Blacklist subscribers';
        $message->message     = 'Action completed successfully!';
        $message->save();

        return self::ACK;
    }
}
