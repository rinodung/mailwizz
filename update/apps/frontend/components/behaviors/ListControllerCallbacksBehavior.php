<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListControllerCallbacksBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property mixed $onSubscriberSaveSuccess
 * @property mixed $onSubscriberSaveError
 * @property mixed $onSubscriberFieldsSorting
 */
class ListControllerCallbacksBehavior extends CBehavior
{
    /**
     * @param CEvent $event
     * @return array
     */
    public function _orderFields(CEvent $event)
    {
        $fields = [];
        $sort   = [];

        foreach ($event->params['fields'] as $type => $_fields) {
            foreach ($_fields as $index => $field) {
                if (!isset($field['sort_order'], $field['field_html'])) {
                    unset($event->params['fields'][$type][$index]);
                    continue;
                }
                $fields[] = $field;
                $sort[] = (int)$field['sort_order'];
            }
        }

        array_multisort($sort, $fields);

        return $event->params['fields'] = $fields;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _addUnsubscribeEmailValidationRules(CEvent $event)
    {
        // get the refrence
        $rules = $event->params['rules'];
        // clear all of them
        $rules->clear();
        // add the email rules
        $rules->add(['email', 'required']);
        $rules->add(['email', 'email']);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function _unsubscribeAfterValidate(CEvent $event)
    {
        /** @var Controller $owner */
        $owner = $this->owner;

        /** @var CAttributeCollection $ownerData */
        $ownerData = $owner->getData();

        /** @var Lists $list */
        $list = $ownerData->itemAt('list');

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'   => $list->list_id,
            'email'     => $event->sender->email,
        ]);
        $allowedStatuses = [
            ListSubscriber::STATUS_CONFIRMED,
            ListSubscriber::STATUS_UNSUBSCRIBED,
            ListSubscriber::STATUS_MOVED,
        ];

        if (empty($subscriber) || !in_array($subscriber->status, $allowedStatuses)) {
            $event->sender->addError('email', t('lists', 'The specified email address does not exist in the list!'));
            return;
        }

        if ($subscriber->status == ListSubscriber::STATUS_UNSUBSCRIBED) {
            $event->sender->addError('email', t('lists', 'The specified email address is already unsubscribed from this list!'));
            return;
        }

        /* // disabled because lists might have cascade actions
        if ($subscriber->status == ListSubscriber::STATUS_MOVED) {
            notify()->addSuccess(t('list_subscribers', 'You have been unsubscribed successfully!'));
            return;
        }
        */

        if ($event->sender->hasErrors()) {
            return;
        }

        // 1.3.9.8 - Create optout history
        $subscriber->createOptoutHistory();

        $unsubscribeUrl = createAbsoluteUrl('lists/unsubscribe_confirm', [
            'list_uid'          => $list->list_uid,
            'subscriber_uid'    => $subscriber->subscriber_uid,
        ]);

        if (!empty($ownerData->_campaign)) {
            $unsubscribeUrl = createAbsoluteUrl('lists/unsubscribe_confirm', [
                'list_uid'          => $list->list_uid,
                'subscriber_uid'    => $subscriber->subscriber_uid,
                'campaign_uid'      => $ownerData->_campaign->campaign_uid,
            ]);
        }

        if ($list->opt_out == Lists::OPT_OUT_SINGLE || $owner->getData('unsubscribeDirect')) {
            $owner->redirect($unsubscribeUrl);
        }

        $dsParams = ['useFor' => DeliveryServer::USE_FOR_LIST_EMAILS];
        if (!($server = DeliveryServer::pickServer(0, $list, $dsParams))) {

            // since 2.1.4
            $this->handleUnsubscribeAfterValidateSendEmailFail($list);

            return;
        }

        $pageType = ListPageType::model()->findBySlug('unsubscribe-confirm-email');

        if (empty($pageType)) {
            return;
        }

        $page = ListPage::model()->findByAttributes([
            'list_id' => $list->list_id,
            'type_id' => $pageType->type_id,
        ]);

        $content = !empty($page->content) ? $page->content : $pageType->content;
        $subject = !empty($page->email_subject) ? $page->email_subject : $pageType->email_subject;

        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[COMPANY_NAME]'        => !empty($list->company) ? $list->company->name : null,
            '[UNSUBSCRIBE_URL]'     => $unsubscribeUrl,
            '[CURRENT_YEAR]'        => date('Y'),

            // 1.5.3
            '[COMPANY_FULL_ADDRESS]'=> !empty($list->company) ? nl2br($list->company->getFormattedAddress()) : null,
        ];

        // since 1.3.5.9
        $subscriberCustomFields = $subscriber->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }
        //

        $content = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        $subject = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $subject);

        // 1.5.3
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
            $subject = CampaignHelper::parseByTemplateEngine($subject, $searchReplace);
        }

        $params = [
            'to'        => $subscriber->email,
            'fromName'  => $list->default->from_name,
            'subject'   => $subject,
            'body'      => $content,
        ];

        $sent = false;
        for ($i = 0; $i < 3; ++$i) {
            if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($list)->sendEmail($params)) {
                break;
            }
            if (!($server = DeliveryServer::pickServer((int)$server->server_id, $list, $dsParams))) {
                break;
            }
        }

        // since 2.1.4
        if (!$sent) {
            $this->handleUnsubscribeAfterValidateSendEmailFail($list);
        }

        notify()->addSuccess(t('list_subscribers', 'Please check your email and click on the provided unsubscribe link.'));
        $owner->redirect(['lists/unsubscribe', 'list_uid' => $list->list_uid]);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws Exception
     */
    public function _sendSubscribeConfirmationEmail(CEvent $event)
    {
        $subscriber = $event->params['subscriber'];
        $list       = $event->params['list'];

        $dsParams = ['useFor' => DeliveryServer::USE_FOR_LIST_EMAILS];
        if (!($server = DeliveryServer::pickServer(0, $event->params['list'], $dsParams))) {
            // since 2.1.4
            $this->handleSendSubscribeConfirmationEmailFailed($list);

            throw new Exception(t('app', 'Email delivery is disabled at the moment, please try again later!'));
        }

        $pageType = ListPageType::model()->findBySlug('subscribe-confirm-email');
        if (empty($pageType)) {
            throw new Exception(t('app', 'Temporary error, please try again later!'));
        }

        $page = ListPage::model()->findByAttributes([
            'list_id' => $list->list_id,
            'type_id' => $pageType->type_id,
        ]);

        $content = !empty($page->content) ? $page->content : $pageType->content;
        $subject = !empty($page->email_subject) ? $page->email_subject : $pageType->email_subject;

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);
        $subscribeUrl = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/confirm-subscribe/' . $subscriber->subscriber_uid);

        // 1.5.3
        $updateProfileUrl = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/update-profile/' . $subscriber->subscriber_uid);
        $unsubscribeUrl   = $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/unsubscribe/' . $subscriber->subscriber_uid);

        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[COMPANY_NAME]'        => !empty($list->company) ? $list->company->name : null,
            '[SUBSCRIBE_URL]'       => $subscribeUrl,
            '[CURRENT_YEAR]'        => date('Y'),

            // 1.5.3
            '[UPDATE_PROFILE_URL]'  => $updateProfileUrl,
            '[UNSUBSCRIBE_URL]'     => $unsubscribeUrl,
            '[COMPANY_FULL_ADDRESS]'=> !empty($list->company) ? nl2br($list->company->getFormattedAddress()) : null,
        ];

        // since 1.3.5.9
        $subscriberCustomFields = $subscriber->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }
        //

        $content = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        $subject = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $subject);

        // 1.5.3
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
            $subject = CampaignHelper::parseByTemplateEngine($subject, $searchReplace);
        }

        $params = [
            'to'        => $subscriber->email,
            'fromName'  => $list->default->from_name,
            'subject'   => $subject,
            'body'      => $content,
        ];

        $sent = false;
        for ($i = 0; $i < 3; ++$i) {
            if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($list)->sendEmail($params)) {
                break;
            }
            Yii::log(print_r($server->getMailer()->getLogs(), true), CLogger::LEVEL_ERROR);
            if (!($server = DeliveryServer::pickServer((int)$server->server_id, $list, $dsParams))) {
                break;
            }
        }

        if (!$sent) {

            // since 2.1.4
            $this->handleSendSubscribeConfirmationEmailFailed($list);

            throw new Exception(t('app', 'We are sorry, but we cannot deliver the confirmation email right now!'));
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _profileUpdatedSuccessfully(CEvent $event)
    {
        // mark action log
        $event->params['list']->customer->logAction->subscriberUpdated($event->params['subscriber']);

        notify()->addSuccess(t('app', 'Your profile has been successfully updated!'));
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _collectAndShowErrorMessages(CEvent $event)
    {
        $instances = isset($event->params['instances']) ? (array)$event->params['instances'] : [];

        // collect and show visible errors.
        foreach ($instances as $instance) {
            if (empty($instance->errors)) {
                continue;
            }
            foreach ($instance->errors as $error) {
                if (empty($error['show']) || empty($error['message'])) {
                    continue;
                }
                notify()->addError($error['message']);
            }
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberFieldsSorting(CEvent $event)
    {
        $this->raiseEvent('onSubscriberFieldsSorting', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberSave(CEvent $event)
    {
        $this->raiseEvent('onSubscriberSave', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberFieldsDisplay(CEvent $event)
    {
        $this->raiseEvent('onSubscriberFieldsDisplay', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberSaveSuccess(CEvent $event)
    {
        $this->raiseEvent('onSubscriberSaveSuccess', $event);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     * @throws CException
     */
    public function onSubscriberSaveError(CEvent $event)
    {
        $this->raiseEvent('onSubscriberSaveError', $event);
    }

    /**
     * @since 2.1.4
     *
     * @param Lists $list
     *
     * @return void
     */
    protected function handleUnsubscribeAfterValidateSendEmailFail(Lists $list): void
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $messageTitle   = 'Unable to send email';
        $messageContent = 'Sending the unsubscribe email for a subscriber belonging to the {list} list failed because the system was not able to find a suitable delivery server to send the email';

        try {
            $message = new CustomerMessage();
            $message->customer_id = (int)$list->customer_id;
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{list}' => CHtml::link($list->name, $optionUrl->getCustomerUrl('lists/' . $list->list_uid . '/overview')),
            ];
            $message->save();

            $message = new UserMessage();
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{list}' => CHtml::link($list->name, $optionUrl->getBackendUrl('lists/index?Lists[list_uid]=' . $list->list_uid)),
            ];
            $message->broadcast();
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
    }

    /**
     * @since 2.1.4
     *
     * This runs inside a transaction so in order to actually persist the data to database,
     * regardless of transaction status, we need to open a different database connection.
     *
     * @param Lists $list
     *
     * @return void
     */
    protected function handleSendSubscribeConfirmationEmailFailed(Lists $list)
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $messageTitle   = 'Unable to send email';
        $messageContent = 'A subscriber has been rejected from joining the {list} list because the system was not able to find a suitable delivery server to send the email';

        try {
            // since we are inside a transaction, which will be rolledback at this point,
            // we need to open a new database connection to persist the message
            $newDb = clone db();
            $newDb->setActive(false);
            $newDb->setActive(true);

            $message = new CustomerMessage();

            // so we can restore it later
            $oldDb = $message->getDbConnection();

            // make use of the new connection
            CustomerMessage::$db = $newDb;

            $message->customer_id = (int)$list->customer_id;
            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{list}' => CHtml::link($list->name, $optionUrl->getCustomerUrl('lists/' . $list->list_uid . '/overview')),
            ];
            $message->save();

            // restore the old db connection
            CustomerMessage::$db = $oldDb;

            $message = new UserMessage();

            // so we can restore it later
            $oldDb = $message->getDbConnection();

            // make use of the new connection
            UserMessage::$db = $newDb;

            $message->title   = $messageTitle;
            $message->message = $messageContent;
            $message->message_translation_params = [
                '{list}' => CHtml::link($list->name, $optionUrl->getBackendUrl('lists/index?Lists[list_uid]=' . $list->list_uid)),
            ];
            $message->broadcast();

            // restore the old db connection
            UserMessage::$db = $oldDb;

            // close the new db connection
            $newDb->setActive(false);
            unset($newDb);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
    }
}
