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

class QueueProcessorCustomerListsAllSubscribersFilterCreateList implements Processor
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

        if (($maxLists = (int)$customer->getGroupOption('lists.max_lists', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);
            $listsCount = Lists::model()->count($criteria);
            if ($listsCount >= $maxLists) {
                $message = new CustomerMessage();
                $message->customer_id = (int)$filter->customer_id;
                $message->title       = 'Create list from export';
                $message->message     = 'You have reached the maximum number of allowed lists.';
                $message->save();
                return self::ACK;
            }
        }

        $criteria = new CDbCriteria();
        $criteria->compare('customer_id', (int)$filter->customer_id);
        $criteria->compare('status', Lists::STATUS_ACTIVE);
        $criteria->order = 'list_id DESC';
        $criteria->limit = 1;
        $list = Lists::model()->find($criteria);
        if (empty($list)) {
            return self::ACK;
        }

        if (!($list = $list->copy())) {
            $message = new CustomerMessage();
            $message->customer_id = (int)$filter->customer_id;
            $message->title       = 'Create list from export';
            $message->message     = 'Cannot create a new list were to copy the subscribers.';
            $message->save();
            return self::ACK;
        }

        // 1.9.19
        ListSubscriberAction::model()->deleteAllByAttributes([
            'source_list_id' => $list->list_id,
        ]);

        $name = t('list_subscribers', 'Auto-generated at {datetime}', [
            '{datetime}' => $list->dateTimeFormatter->formatLocalizedDateTime(date('Y-m-d H:i:s')),
        ]);

        $list->name         = $name;
        $list->display_name = $name;
        $list->description  = $name;
        $list->save(false);

        $filter->unique = AllListsSubscribersFilters::TEXT_YES;

        $totalSubscribersCount  = 0;
        $listSubscribersCount   = 0;
        $maxSubscribersPerList  = (int)$customer->getGroupOption('lists.max_subscribers_per_list', -1);
        $maxSubscribers         = (int)$customer->getGroupOption('lists.max_subscribers', -1);

        if ($maxSubscribers > -1) {
            $criteria = new CDbCriteria();
            $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';
            if ($listsIds = $customer->getAllListsIds()) {
                $criteria->addInCondition('t.list_id', $listsIds);
                $totalSubscribersCount = ListSubscriber::model()->count($criteria);
                if ($totalSubscribersCount >= $maxSubscribers) {
                    $message = new CustomerMessage();
                    $message->customer_id = (int)$filter->customer_id;
                    $message->title       = 'Create list from export';
                    $message->message     = 'You have reached the maximum number of allowed subscribers.';
                    $message->save();
                    return self::ACK;
                }
            }
        }

        // allow max 10 consecutive errors
        $errorsCount = 0;
        $maxErrors   = 10;

        /** @var ListSubscriber $subscriber */
        foreach ($filter->getSubscribers() as $subscriber) {
            try {
                if (!$subscriber->copyToList((int)$list->list_id, false)) {
                    throw new Exception('Unable to copy the subscriber to list!');
                }
                $totalSubscribersCount++;
                $listSubscribersCount++;
            } catch (Exception $e) {
                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                if ($errorsCount > $maxErrors) {
                    break;
                }
                $errorsCount++;
                continue;
            }
            $errorsCount = 0;

            if ($maxSubscribersPerList > -1 && $listSubscribersCount >= $maxSubscribersPerList) {
                $message = new CustomerMessage();
                $message->customer_id = (int)$filter->customer_id;
                $message->title       = 'Create list from export';
                $message->message     = 'You have reached the maximum number of allowed subscribers into this list.';
                $message->save();
                break;
            }

            if ($maxSubscribers > -1 && $totalSubscribersCount >= $maxSubscribers) {
                $message = new CustomerMessage();
                $message->customer_id = (int)$filter->customer_id;
                $message->title       = 'Create list from export';
                $message->message     = 'You have reached the maximum number of allowed subscribers.';
                $message->save();
                break;
            }
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $message = new CustomerMessage();
        $message->customer_id = (int)$filter->customer_id;
        $message->title       = 'Create list from export';

        if ($listSubscribersCount === 0) {
            $list->saveStatus(Lists::STATUS_PENDING_DELETE);
            $list->delete();

            $message->message = 'Action completed successfully, but the list has not been created since there are no subscribers matching the filters!';
        } elseif ($errorsCount === 0) {
            $message->message = 'Action completed successfully, you can view the new list {view} and update it from {update}!';
            $message->message_translation_params = [
                '{view}'   => CHtml::link(t('app', 'here'), $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $list->list_uid))),
                '{update}' => CHtml::link(t('app', 'here'), $optionUrl->getCustomerUrl(sprintf('lists/%s/update', $list->list_uid))),
            ];
        } else {
            $message->message = 'Action completed with errors, you can view the new list {view} and update it from {update}!';
            $message->message_translation_params = [
                '{view}'   => CHtml::link(t('app', 'here'), $optionUrl->getCustomerUrl(sprintf('lists/%s/overview', $list->list_uid))),
                '{update}' => CHtml::link(t('app', 'here'), $optionUrl->getCustomerUrl(sprintf('lists/%s/update', $list->list_uid))),
            ];
        }
        $message->save();

        return self::ACK;
    }
}
