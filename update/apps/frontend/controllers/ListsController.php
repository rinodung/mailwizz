<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListsController
 *
 * Handles the actions for lists related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ListControllerCallbacksBehavior $callbacks
 */
class ListsController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        Yii::import('customer.components.list-field-builder.*');
        parent::init();
    }

    /**
     * @return array
     * @throws CException
     */
    public function behaviors()
    {
        return CMap::mergeArray([
            'callbacks' => [
                'class' => 'frontend.components.behaviors.ListControllerCallbacksBehavior',
            ],
        ], parent::behaviors());
    }

    /**
     * Subscribe a new user to a certain email list
     *
     * @param string $list_uid
     * @param mixed $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionSubscribe($list_uid, $subscriber_uid = null)
    {
        $list = $this->loadListModel($list_uid);

        if (!empty($list->customer)) {
            $this->setCustomerLanguage($list->customer);
        }

        $pageType = $this->loadPageTypeModel('subscribe-form');
        $page     = $this->loadPageModel((int)$list->list_id, (int)$pageType->type_id);

        $content = !empty($page->content) ? $page->content : $pageType->content;
        $content = html_decode($content);

        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[SUBMIT_BUTTON]'       => CHtml::button(t('lists', 'Subscribe'), ['type' => 'submit', 'class' => 'btn btn-primary btn-flat']),
        ];

        // load the list fields and bind the behavior.
        $listFields = ListField::model()->findAll([
            'condition' => 'list_id = :lid',
            'params'    => [':lid' => (int)$list->list_id],
            'order'     => 'sort_order ASC',
        ]);

        if (empty($listFields)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        // since 2.1.6
        if (request()->getIsPostRequest()) {
            $ip = CustomerIpBlacklist::model()->findByAttributes([
                'customer_id' => (int)$list->customer_id,
                'ip_address'  => (string)request()->getUserHostAddress(),
            ]);

            if (!empty($ip)) {
                throw new CHttpException(403, t('app', 'Your access to this resource is forbidden.'));
            }
        }

        // since 1.9.12
        $honeypotFieldName = sha1($list->customer->customer_uid . ':' . $list->list_uid);
        if (request()->getIsPostRequest() && strlen((string)request()->getPost($honeypotFieldName, '')) > 0) {
            $pageType = $this->loadPageTypeModel('subscribe-pending');
            $page     = $this->loadPageModel((int)$list->list_id, (int)$pageType->type_id);
            $content  = !empty($page->content) ? $page->content : $pageType->content;
            $content  = html_decode($content);
            $searchReplace = [
                '[LIST_NAME]'           => $list->display_name,
                '[LIST_DISPLAY_NAME]'   => $list->display_name,
                '[LIST_INTERNAL_NAME]'  => $list->name,
                '[LIST_UID]'            => $list->list_uid,
            ];
            $content = str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
            if (CampaignHelper::isTemplateEngineEnabled()) {
                $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
            }
            $this->render('display_content', compact('content'));
            return;
        }

        if (!empty($subscriber_uid)) {
            $_subscriber = $this->loadSubscriberModel((string)$subscriber_uid, (int)$list->list_id);
            if ($_subscriber->status == ListSubscriber::STATUS_UNSUBSCRIBED) {
                $subscriber = $_subscriber;
            } else {
                $_subscriber = null;
            }
        }
        if (empty($subscriber)) {
            $subscriber = new ListSubscriber();
        }
        $subscriber->list_id    = (int)$list->list_id;
        $subscriber->ip_address = (string)request()->getUserHostAddress();

        $usedTypes = [];
        foreach ($listFields as $field) {
            $usedTypes[] = (int)$field->type->type_id;
        }

        $criteria = new CDbCriteria();
        $criteria->addInCondition('type_id', $usedTypes);

        /** @var ListFieldType[] $listFieldTypes */
        $listFieldTypes = ListFieldType::model()->findAll($criteria);

        $instances = [];
        foreach ($listFieldTypes as $fieldType) {
            if (empty($fieldType->identifier) || !is_file((string)Yii::getPathOfAlias($fieldType->class_alias) . '.php')) {
                continue;
            }

            /** @var CWebApplication $app */
            $app = app();

            $component = $app->getWidgetFactory()->createWidget($this, $fieldType->class_alias, [
                'fieldType'     => $fieldType,
                'list'          => $list,
                'subscriber'    => $subscriber,
            ]);

            if (!($component instanceof ListFieldBuilderType)) {
                continue;
            }

            // run the component to hook into next events
            $component->run();

            $instances[] = $component;
        }

        // since 1.3.9.7
        if (!request()->getIsPostRequest()) {
            foreach ($listFields as $listField) {
                if ($tagValue = request()->getQuery($listField->tag)) {
                    $_POST[$listField->tag] = $tagValue;
                }
            }
        }

        $fields = [];

        // if the fields are saved
        if (request()->getIsPostRequest()) {
            $mutexKey = sha1(__METHOD__ . ':' . $list->list_uid . ':' . date('YmdH') . ':' . request()->getPost('EMAIL', ''));
            if (!mutex()->acquire($mutexKey)) {
                throw new CHttpException(500, t('lists', 'Please try to resubmit the form once again!'));
            }

            // since 1.3.5.6
            hooks()->doAction('frontend_list_subscribe_before_transaction', $this);

            $transaction = db()->beginTransaction();

            try {

                // since 1.3.5.8
                hooks()->doAction('frontend_list_subscribe_at_transaction_start', $this);

                // since 1.5.3
                $customer = Customer::model()->findByPk($list->customer_id);

                $maxSubscribersPerList   = (int)$customer->getGroupOption('lists.max_subscribers_per_list', -1);
                $maxSubscribers          = (int)$customer->getGroupOption('lists.max_subscribers', -1);

                if ($maxSubscribers > -1 || $maxSubscribersPerList > -1) {
                    $criteria = new CDbCriteria();
                    $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';

                    if ($maxSubscribers > -1 && ($listsIds = $customer->getAllListsIds())) {
                        $criteria->addInCondition('t.list_id', $listsIds);
                        $totalSubscribersCount = ListSubscriber::model()->count($criteria);
                        if ($totalSubscribersCount >= $maxSubscribers) {
                            throw new Exception(t('lists', 'The maximum number of allowed subscribers has been reached.'));
                        }
                    }

                    if ($maxSubscribersPerList > -1) {
                        $criteria->compare('t.list_id', (int)$list->list_id);
                        $listSubscribersCount = ListSubscriber::model()->count($criteria);
                        if ($listSubscribersCount >= $maxSubscribersPerList) {
                            throw new Exception(t('lists', 'The maximum number of allowed subscribers for this list has been reached.'));
                        }
                    }
                }

                // only if this isn't a subscriber that re-subscribes and it is a double optin
                if (empty($_subscriber) && $list->opt_in == Lists::OPT_IN_DOUBLE) {
                    // bind the event handler that will send the confirm email once the subscriber is saved.
                    $this->callbacks->onSubscriberSaveSuccess = [$this->callbacks, '_sendSubscribeConfirmationEmail'];
                }

                if (!$subscriber->save()) {
                    if ($subscriber->hasErrors()) {
                        throw new Exception($subscriber->shortErrors->getAllAsString());
                    }
                    throw new Exception(t('app', 'Temporary error, please contact us if this happens too often!'));
                }

                // 1.3.8.8 - Create optin history
                $subscriber->createOptinHistory();

                // raise event
                $this->callbacks->onSubscriberSave(new CEvent($this->callbacks, [
                    'fields' => &$fields,
                    'action' => 'subscribe',
                ]));

                // if no exception thrown but still there are errors in any of the instances, stop.
                foreach ($instances as $instance) {
                    if (!empty($instance->errors)) {
                        throw new Exception(t('app', 'Your form has a few errors. Please fix them and try again!'));
                    }
                }

                // raise event. at this point everything seems to be fine.
                $this->callbacks->onSubscriberSaveSuccess(new CEvent($this->callbacks, [
                    'instances'     => $instances,
                    'subscriber'    => $subscriber,
                    'list'          => $list,
                    'action'        => 'subscribe',
                ]));

                $transaction->commit();
                mutex()->release($mutexKey);

                if (!empty($_subscriber)) {
                    $subscriber->status = ListSubscriber::STATUS_UNCONFIRMED;
                    $subscriber->save(false);
                    $this->redirect(['lists/subscribe_confirm', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid, 'do' => 'subscribe-back']);
                    return;
                }

                // is single opt in.
                if ($list->opt_in == Lists::OPT_IN_SINGLE) {
                    // because redirect will fail curl requests that doesn't follow
                    // $this->redirect(array('lists/subscribe_confirm', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid));
                    $this->setData('singleOptInSubscribeConfirm', true);
                } else {
                    $this->redirect(['lists/subscribe_pending', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid]);
                    return;
                }

                // since 1.3.5.8
                hooks()->doAction('frontend_list_subscribe_at_transaction_end', $this);
            } catch (Exception $e) {
                $transaction->rollback();
                mutex()->release($mutexKey);

                if (($message = $e->getMessage())) {
                    notify()->addError($message);
                }

                // bind default save error event handler
                $this->callbacks->onSubscriberSaveError = [$this->callbacks, '_collectAndShowErrorMessages'];

                // raise event
                $this->callbacks->onSubscriberSaveError(new CEvent($this->callbacks, [
                    'instances'     => $instances,
                    'subscriber'    => $subscriber,
                    'list'          => $list,
                    'action'        => 'subscribe',
                ]));

                // since 1.3.5.9
                $duplicate = app_param('validationSubscriberAlreadyExists');
                if ($duplicate) {

                    /** @var ListSubscriber $existingSubscriber */
                    $existingSubscriber = clone $subscriber;
                    if (app_param('validationSubscriberAlreadyExistsSubscriber') instanceof ListSubscriber) {
                        /** @var ListSubscriber $existingSubscriber */
                        $existingSubscriber = clone app_param('validationSubscriberAlreadyExistsSubscriber');
                        app_param_unset('validationSubscriberAlreadyExistsSubscriber');

                        // 1.4.0
                        if ($existingSubscriber->status == ListSubscriber::STATUS_UNSUBSCRIBED) {
                            $existingSubscriber->saveStatus(ListSubscriber::STATUS_UNCONFIRMED);
                            $existingSubscriber->removeOptinHistory();
                            $existingSubscriber->confirmOptinHistory();
                            $redirect = [
                                'lists/subscribe_confirm',
                                'list_uid'       => $existingSubscriber->list->list_uid,
                                'subscriber_uid' => $existingSubscriber->subscriber_uid,
                                'do'             => 'subscribe-back',
                            ];
                            notify()->clearAll();
                            $this->redirect($redirect);
                            return;
                        }

                        if ($redirect = $list->getSubscriberExistsRedirect($existingSubscriber)) {
                            notify()->clearAll();
                            unset($existingSubscriber);
                            $this->redirect($redirect);
                            return;
                        }
                    }
                    //

                    unset($existingSubscriber);
                }

                // since 1.3.9.8
                app_param_unset('validationSubscriberAlreadyExistsSubscriber');
                //

                // 1.3.7
                if ($duplicate) {
                    if (empty($_subscriber) || empty($_subscriber->subscriber_uid)) {
                        $_subscriber = ListSubscriber::model()->findByAttributes([
                            'list_id' => (int)$list->list_id,
                            'email'   => request()->getPost('EMAIL'),
                        ]);
                    }
                    if (!empty($_subscriber)) {
                        notify()->clearAll();
                        if ($_subscriber->status == ListSubscriber::STATUS_CONFIRMED) {
                            notify()->addInfo(t('lists', 'The email address is already registered in the list, therefore you have been redirected to the update profile page.'));
                            $updateProfileUrl = createUrl('lists/update_profile', ['list_uid' => $list->list_uid, 'subscriber_uid' => $_subscriber->subscriber_uid]);
                            $this->redirect($updateProfileUrl);
                            return;
                        }
                    }
                }
            }

            // since 1.3.5.6
            hooks()->doAction('frontend_list_subscribe_after_transaction', $this);

            // because redirect will fail curl requests that doesn't follow
            if ($this->getData('singleOptInSubscribeConfirm')) {
                $_GET['list_uid']       = (string)$list->list_uid;
                $_GET['subscriber_uid'] = (string)$subscriber->subscriber_uid;
                $this->run('subscribe_confirm');
                return;
            }
        }

        // raise event. simply the fields are shown
        $this->callbacks->onSubscriberFieldsDisplay(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        // add the default sorting of fields actions and raise the event
        $this->callbacks->onSubscriberFieldsSorting = [$this->callbacks, '_orderFields'];
        $this->callbacks->onSubscriberFieldsSorting(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        /** @var array $fields */
        $fields = !empty($fields) && is_array($fields) ? $fields : []; // @phpstan-ignore-line

        // and build the html for the fields.
        $fieldsHtml = '';

        foreach ($fields as $field) {
            $fieldsHtml .= $field['field_html'];
        }

        // since 1.9.12
        $fieldsHtml .= sprintf('
		<div style="position: absolute; left: -5000px;" aria-hidden="true">
			<input type="text" name="%s" tabindex="-1" autocomplete="%s" value=""/>
		</div>', $honeypotFieldName, $honeypotFieldName);

        // since 1.3.5.6
        /** @var string $content */
        $content = (string)hooks()->applyFilters('frontend_list_subscribe_before_transform_list_fields', $content);

        // list fields transform and handling
        $content = (string)preg_replace('/\[LIST_FIELDS\]/', $fieldsHtml, $content, 1, $count);

        // since 1.3.5.6
        /** @var string $content */
        $content = (string)hooks()->applyFilters('frontend_list_subscribe_after_transform_list_fields', $content);

        // since 1.9.7
        $content = str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
        }

        // embed output
        if (request()->getQuery('output') == 'embed') {
            $width  = (string)request()->getQuery('width', 400);
            $height = (string)request()->getQuery('height', 400);
            $width  = substr($width, -1)  == '%' ? (int)substr($width, 0, strlen($width) - 1) . '%' : (int)$width . 'px';
            $height = substr($height, -1) == '%' ? (int)substr($height, 0, strlen($height) - 1) . '%' : (int)$height . 'px';

            $attributes = [
                'width'  => $width,
                'height' => $height,
                'target' => request()->getQuery('target'),
            ];
            $this->layout = 'embed';
            $this->setData('attributes', $attributes);
        }

        // Since 2.0.10
        $list->attachBehavior(ListOpenGraphRegisterMetaTagsBehavior::class, [
            'class' => 'common.components.behaviors.ListOpenGraphRegisterMetaTagsBehavior',
        ]);

        /** @var ListOpenGraphRegisterMetaTagsBehavior $openGraphRegister */
        $openGraphRegister = $list->asa(ListOpenGraphRegisterMetaTagsBehavior::class);
        $openGraphRegister->registerMetaTags();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $list->display_name,
        ]);

        $this->render('display_content', compact('content'));
    }

    /**
     * This page is shown after the user has submitted the subscription form
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionSubscribe_pending($list_uid, $subscriber_uid = '')
    {
        $list = $this->loadListModel($list_uid);

        if (!empty($list->customer)) {
            $this->setCustomerLanguage($list->customer);
        }

        $pageType = $this->loadPageTypeModel('subscribe-pending');
        $page     = $this->loadPageModel((int)$list->list_id, (int)$pageType->type_id);

        $content = !empty($page->content) ? $page->content : $pageType->content;
        $content = html_decode($content);

        // 1.9.7
        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
        ];
        $content = str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
        }
        //

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $list->display_name,
        ]);

        $this->render('display_content', compact('content'));
    }

    /**
     * This pages is shown when the user clicks on the confirmation email that he received
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     * @param mixed $do
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws Throwable
     */
    public function actionSubscribe_confirm($list_uid, $subscriber_uid, $do = null)
    {
        $list = $this->loadListModel($list_uid);

        if (!empty($list->customer)) {
            $this->setCustomerLanguage($list->customer);
        }

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel($subscriber_uid, (int)$list->list_id);

        // since 1.9.28
        if ($do === 'subscribe-back' && $list->opt_in === Lists::OPT_IN_DOUBLE) {
            $subscriber->saveStatus(ListSubscriber::STATUS_UNCONFIRMED);
            $subscriber->removeOptinHistory();
            $subscriber->createOptinHistory();
            $this->callbacks->_sendSubscribeConfirmationEmail(new CEvent($this->callbacks, [
                'instances'     => [],
                'subscriber'    => $subscriber,
                'list'          => $list,
                'action'        => 'subscribe',
            ]));
            $this->redirect(['lists/subscribe_pending', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid]);
            return;
        }

        // update profile link
        $updateProfileUrl = createUrl('lists/update_profile', ['list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid]);

        // if confirmed, redirect to update profile.
        if ($subscriber->isConfirmed) {
            $this->redirect($updateProfileUrl);
            return;
        }

        if (!$subscriber->isUnconfirmed) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $subscriber->status = ListSubscriber::STATUS_CONFIRMED;

        // since 1.3.6.2
        if ($do != 'subscribe-back' && $list->subscriber_require_approval == Lists::TEXT_YES) {
            $subscriber->status = ListSubscriber::STATUS_UNAPPROVED;
        }
        //

        $saved = $subscriber->save(false);

        // 1.3.8.8 - Confirm optin history
        if ($saved) {
            $subscriber->confirmOptinHistory();
        }

        // since 1.3.5 - this should be expanded in future
        $takeListAction = $saved && $subscriber->getIsConfirmed();
        if ($takeListAction) {
            $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_SUBSCRIBE);
        }

        $name       = 'subscribe-confirm' . ($subscriber->getIsUnapproved() ? '-approval' : '');
        $pageType   = $this->loadPageTypeModel($name);
        $page       = $this->loadPageModel((int)$list->list_id, (int)$pageType->type_id);

        $content = !empty($page->content) ? $page->content : $pageType->content;
        $content = html_decode($content);

        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[UPDATE_PROFILE_URL]'  => $updateProfileUrl,
        ];

        if ($do != 'subscribe-back') {
            $list->customer->logAction->subscriberCreated($subscriber);

            // since 1.3.8.2
            $subscriber->sendCreatedNotifications();

            // since 1.3.6.2
            $subscriber->handleWelcome();
        } else {

            // since it subscribes again, it makes sense to remove from unsubscribes logs for any campaign.
            CampaignTrackUnsubscribe::model()->deleteAllByAttributes([
                'subscriber_id' => (int)$subscriber->subscriber_id,
            ]);
        }

        if ($saved) {
            // raise event.
            $this->callbacks->onSubscriberSaveSuccess(new CEvent($this->callbacks, [
                'subscriber'    => $subscriber,
                'list'          => $list,
                'action'        => 'subscribe-confirm',
                'do'            => $do,
            ]));
        }

        // since 1.3.5.9
        $subscriberCustomFields = $subscriber->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }

        $content = str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $list->display_name,
        ]);

        $this->render('display_content', compact('content'));
    }

    /**
     * Allows a subscriber to update his profile
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate_profile($list_uid, $subscriber_uid)
    {
        $list = $this->loadListModel($list_uid);

        if (!empty($list->customer)) {
            $this->setCustomerLanguage($list->customer);
        }

        /** @var ListPageType $pageType */
        $pageType = $this->loadPageTypeModel('update-profile');

        /** @var ListPage $page */
        $page = $this->loadPageModel((int)$list->list_id, (int)$pageType->type_id);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel($subscriber_uid, (int)$list->list_id);

        if ($subscriber->status != ListSubscriber::STATUS_CONFIRMED) {
            if ($redirect = $list->getSubscriber404Redirect()) {
                $this->redirect($redirect);
                return;
            }
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $subscriber->list_id    = (int)$list->list_id;
        $subscriber->ip_address = (string)request()->getUserHostAddress();

        $content = !empty($page->content) ? $page->content : $pageType->content;
        $content = html_decode($content);

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[SUBMIT_BUTTON]'       => CHtml::button(t('lists', 'Update profile'), ['type' => 'submit', 'class' => 'btn btn-default']),
            '[UNSUBSCRIBE_URL]'     => $optionUrl->getFrontendUrl('lists/' . $list->list_uid . '/unsubscribe/' . $subscriber->subscriber_uid),
        ];

        // load the list fields and bind the behavior.
        $listFields = ListField::model()->findAll([
            'condition' => 'list_id = :lid',
            'params'    => [':lid' => (int)$list->list_id],
            'order'     => 'sort_order asc',
        ]);

        if (empty($listFields)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $usedTypes = [];
        foreach ($listFields as $listField) {
            $usedTypes[] = $listField->type->type_id;
        }
        $criteria = new CDbCriteria();
        $criteria->addInCondition('type_id', $usedTypes);

        /** @var ListFieldType[] $fieldTypes */
        $fieldTypes = ListFieldType::model()->findAll($criteria);

        $instances = [];
        foreach ($fieldTypes as $fieldType) {
            if (empty($fieldType->identifier) || !is_file((string)Yii::getPathOfAlias($fieldType->class_alias) . '.php')) {
                continue;
            }

            /** @var CWebApplication $app */
            $app = app();

            $component = $app->getWidgetFactory()->createWidget($this, $fieldType->class_alias, [
                'fieldType'     => $fieldType,
                'list'          => $list,
                'subscriber'    => $subscriber,
            ]);

            if (!($component instanceof ListFieldBuilderType)) {
                continue;
            }

            // run the component to hook into next events
            $component->run();

            $instances[] = $component;
        }

        $fields = [];

        // if the fields are saved
        if (request()->getIsPostRequest()) {

            // since 1.3.5.8
            hooks()->doAction('frontend_list_update_profile_before_transaction');

            $transaction = db()->beginTransaction();

            try {

                // since 1.3.5.8
                hooks()->doAction('frontend_list_update_profile_at_transaction_start');

                if (!$subscriber->save()) {
                    if ($subscriber->hasErrors()) {
                        throw new Exception($subscriber->shortErrors->getAllAsString());
                    }
                    throw new Exception(t('app', 'Temporary error, please contact us if this happens too often!'));
                }

                // raise event
                $this->callbacks->onSubscriberSave(new CEvent($this->callbacks, [
                    'fields' => &$fields,
                    'action' => 'update-profile',
                ]));

                // if no exception thrown but still there are errors in any of the instances, stop.
                foreach ($instances as $instance) {
                    if (!empty($instance->errors)) {
                        throw new Exception(t('app', 'Your form has a few errors. Please fix them and try again!'));
                    }
                }

                // bind the default actions for sucess update
                $this->callbacks->onSubscriberSaveSuccess = [$this->callbacks, '_profileUpdatedSuccessfully'];

                // raise event. at this point everything seems to be fine.
                $this->callbacks->onSubscriberSaveSuccess(new CEvent($this->callbacks, [
                    'instances'     => $instances,
                    'subscriber'    => $subscriber,
                    'list'          => $list,
                    'action'        => 'update-profile',
                ]));

                if ($list->customer->getGroupOption('lists.subscriber_profile_update_optin_history', 'yes') == 'yes') {
                    $subscriber->createOptinHistory()->confirmOptinHistory();
                }

                $transaction->commit();

                // since 1.3.5.8
                hooks()->doAction('frontend_list_update_profile_at_transaction_end');
            } catch (Exception $e) {
                $transaction->rollback();
                notify()->addError($e->getMessage());

                // bind default save error event handler
                $this->callbacks->onSubscriberSaveError = [$this->callbacks, '_collectAndShowErrorMessages'];

                // raise event
                $this->callbacks->onSubscriberSaveError(new CEvent($this->callbacks, [
                    'instances'     => $instances,
                    'subscriber'    => $subscriber,
                    'list'          => $list,
                    'action'        => 'update-profile',
                ]));
            }
        }

        // since 1.3.5.8
        hooks()->doAction('frontend_list_update_profile_after_transaction');

        // raise event. simply the fields are shown
        $this->callbacks->onSubscriberFieldsDisplay(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        // add the default sorting of fields actions and raise the event
        $this->callbacks->onSubscriberFieldsSorting = [$this->callbacks, '_orderFields'];
        $this->callbacks->onSubscriberFieldsSorting(new CEvent($this->callbacks, [
            'fields' => &$fields,
        ]));

        /** @var array $fields */
        $fields = !empty($fields) && is_array($fields) ? $fields : []; // @phpstan-ignore-line

        // and build the html for the fields.
        $fieldsHtml = '';

        foreach ($fields as $field) {
            $fieldsHtml .= $field['field_html'];
        }

        // since 1.3.5.8
        $content = (string)hooks()->applyFilters('frontend_list_update_profile_before_transform_list_fields', $content);

        // list fields transform and handling
        $content = preg_replace('/\[LIST_FIELDS\]/', $fieldsHtml, $content, 1, $count);

        // since 1.3.5.8
        $content = hooks()->applyFilters('frontend_list_update_profile_after_transform_list_fields', $content);

        // since 1.3.5.9
        $subscriberCustomFields = $subscriber->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }

        $content = str_replace(array_keys($searchReplace), array_values($searchReplace), (string)$content);
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $list->display_name,
        ]);

        $this->render('display_content', compact('content'));
    }

    /**
     * Allows a subscriber to unsubscribe from a list
     *
     * @param string $list_uid
     * @param mixed $subscriber_uid
     * @param mixed $campaign_uid
     * @param mixed $type
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUnsubscribe($list_uid, $subscriber_uid = null, $campaign_uid = null, $type = null)
    {
        $list = $this->loadListModel($list_uid);

        if (!empty($list->customer)) {
            $this->setCustomerLanguage($list->customer);
        }

        $pageType = $this->loadPageTypeModel('unsubscribe-form');
        $page     = $this->loadPageModel((int)$list->list_id, (int)$pageType->type_id);

        $content = !empty($page->content) ? (string)$page->content : (string)$pageType->content;
        $content = html_decode($content);

        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[SUBMIT_BUTTON]'       => CHtml::button(t('lists', 'Unsubscribe'), ['type' => 'submit', 'class' => 'btn btn-default']),
        ];

        $_subscriber = $_campaign = null;

        if (!empty($subscriber_uid)) {
            $_subscriber     = $this->loadSubscriberModel((string)$subscriber_uid, (int)$list->list_id);
            $allowedStatuses = [ListSubscriber::STATUS_CONFIRMED, ListSubscriber::STATUS_UNSUBSCRIBED, ListSubscriber::STATUS_MOVED];
            if (!in_array($_subscriber->status, $allowedStatuses)) {
                $_subscriber = null;
            }
        }

        if (!empty($campaign_uid)) {
            $_campaign = Campaign::model()->findByAttributes([
                'campaign_uid'  => $campaign_uid,
                'list_id'       => (int)$list->list_id,
            ]);
        }

        $subscriber       = new ListSubscriber();
        $trackUnsubscribe = new CampaignTrackUnsubscribe();

        $this->setData([
            'list'              => $list,
            'subscriber'        => $subscriber,
            '_subscriber'       => $_subscriber,
            '_campaign'         => $_campaign,
            'trackUnsubscribe'  => new CampaignTrackUnsubscribe(),
        ]);

        $subscriber->onRules = [$this->callbacks, '_addUnsubscribeEmailValidationRules'];
        $subscriber->onAfterValidate = [$this->callbacks, '_unsubscribeAfterValidate'];

        if (request()->getIsPostRequest() && !isset($_POST[$subscriber->getModelName()]) && isset($_POST['EMAIL'])) {
            $_POST[$subscriber->getModelName()]['email'] = request()->getPost('EMAIL');
        }

        // since 1.3.6.2
        if (request()->getIsPostRequest() && ($reason = (string)request()->getPost('unsubscribe_reason', ''))) {
            $this->setUnsubscribeReason($reason);
        }

        // since 1.9.24
        // Fix auto-unsubscribe from platforms like GMail/etc that take into consideration
        // the "List-Unsubscribe-Post: List-Unsubscribe=One-Click" header
        if (
            request()->getIsPostRequest() &&
            empty($_POST[$subscriber->getModelName()]['email']) &&
            !empty($_subscriber) &&
            request()->getQuery('source', '') === 'email-client-unsubscribe-button'
        ) {
            $_POST[$subscriber->getModelName()]['email'] = $_subscriber->email;
            if (!$this->getUnsubscribeReason()) {
                $this->setUnsubscribeReason('Unsubscribed via Direct POST request');
            }
        }
        //

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($subscriber->getModelName(), []))) {
            $subscriber->attributes = $attributes;
            $subscriber->validate();
        } elseif (!request()->getIsPostRequest() && !empty($_subscriber)) {
            $subscriber->email = $_subscriber->email;
            // $subscriber->validate(); // do not auto validate for now
        }

        // since 1.3.4.7 - for usage in behavior to allow direct unsubscribe from a list
        // decide if we keep this as it raises multiple questions
        if (!request()->getIsPostRequest() && !empty($subscriber->email) && $list->opt_out == Lists::OPT_OUT_SINGLE && !empty($type) && $type == 'unsubscribe-direct') {
            $this->setData('unsubscribeDirect', true);
            $subscriber->validate();
        }

        // input fields
        $reasonField = '';
        if (!empty($_campaign)) {
            $trackUnsubscribe->reason = $this->getUnsubscribeReason();
            $reasonField = $this->renderPartial('_unsubscribe-reason', compact('trackUnsubscribe'), true);
        }
        $inputField    = $this->renderPartial('_unsubscribe-input', compact('subscriber'), true);
        $searchReplace['[UNSUBSCRIBE_EMAIL_FIELD]']  = $inputField;
        $searchReplace['[UNSUBSCRIBE_REASON_FIELD]'] = $reasonField;

        $content = str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
        }

        // avoid a nasty bug with model input array
        $content = preg_replace('/(ListSubscriber)(\[)([a-zA-Z0-9]+)(\])/', '$1_$3_', $content);

        // remove all remaining tags, if any of course.
        $content = preg_replace('/\[([^\]]?)+\]/six', '', (string)$content);

        // put back the correct input array
        $content = preg_replace('/(ListSubscriber)(\_)([a-zA-Z0-9]+)(\_)/', '$1[$3]', (string)$content);

        // embed output
        if (request()->getQuery('output') == 'embed') {
            $attributes = [
                'width'     => (int)request()->getQuery('width', 400),
                'height'    => (int)request()->getQuery('height', 200),
            ];
            $this->layout = 'embed';
            $this->setData('attributes', $attributes);
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $list->display_name,
        ]);

        $this->render('display_content', compact('content'));
    }

    /**
     * This page is shown when the subscriber confirms his
     * unsubscription from email by clicking on the unsubscribe confirm link.
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     * @param mixed $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUnsubscribe_confirm($list_uid, $subscriber_uid, $campaign_uid = null)
    {
        $list = $this->loadListModel($list_uid);

        if (!empty($list->customer)) {
            $this->setCustomerLanguage($list->customer);
        }

        $pageType        = $this->loadPageTypeModel('unsubscribe-confirm');
        $page            = $this->loadPageModel((int)$list->list_id, (int)$pageType->type_id);
        $subscriber      = $this->loadSubscriberModel($subscriber_uid, (int)$list->list_id);
        $allowedStatuses = [ListSubscriber::STATUS_CONFIRMED, ListSubscriber::STATUS_UNSUBSCRIBED, ListSubscriber::STATUS_MOVED];

        if (!in_array($subscriber->status, $allowedStatuses)) {
            if ($redirect = $list->getSubscriber404Redirect()) {
                $this->redirect($redirect);
                return;
            }
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $subscriber->status = ListSubscriber::STATUS_UNSUBSCRIBED;
        $saved = $subscriber->save(false);

        if ($saved && !empty($campaign_uid)) {
            $campaign = Campaign::model()->findByAttributes([
                'campaign_uid'  => $campaign_uid,
                'list_id'       => (int)$list->list_id,
            ]);

            // add this subscriber to the list of campaign unsubscribers
            if (!empty($campaign)) {
                $track = CampaignTrackUnsubscribe::model()->findByAttributes([
                    'campaign_id'   => (int)$campaign->campaign_id,
                    'subscriber_id' => (int)$subscriber->subscriber_id,
                ]);

                $saved = true;
                if (empty($track)) {
                    $track = new CampaignTrackUnsubscribe();
                    $track->campaign_id   = (int)$campaign->campaign_id;
                    $track->subscriber_id = (int)$subscriber->subscriber_id;
                    $track->ip_address    = (string)request()->getUserHostAddress();
                    $track->user_agent    = substr((string)request()->getUserAgent(), 0, 255);
                    // since 1.3.6.2
                    $track->reason        = $this->getUnsubscribeReason();
                    //
                    $saved = $track->save();
                }

                if ($saved) {
                    // raise the action, hook added in 1.2
                    $this->setData('ipLocationSaved', false);
                    hooks()->doAction('frontend_lists_after_track_campaign_unsubscribe', $this, $track);
                }
            }
        }

        // since 1.3.5 - this should be expanded in future
        if ($saved) {
            $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_UNSUBSCRIBE);
        }

        // 1.3.7.8 - Confirm optout history
        if ($saved) {
            $subscriber->confirmOptoutHistory();
        }

        $content = !empty($page->content) ? $page->content : $pageType->content;
        $content = html_decode($content);

        $searchReplace = [
            '[LIST_NAME]'           => $list->display_name,
            '[LIST_DISPLAY_NAME]'   => $list->display_name,
            '[LIST_INTERNAL_NAME]'  => $list->name,
            '[LIST_UID]'            => $list->list_uid,
            '[SUBSCRIBE_URL]'       => apps()->getAppUrl('frontend', sprintf('lists/%s/subscribe/%s', $list->list_uid, $subscriber->subscriber_uid), true),
        ];

        // since 1.3.5.9
        $subscriberCustomFields = $subscriber->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }

        $content = str_replace(array_keys($searchReplace), array_values($searchReplace), $content);
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $content = CampaignHelper::parseByTemplateEngine($content, $searchReplace);
        }

        if ($saved) {
            // raise event.
            $this->callbacks->onSubscriberSaveSuccess(new CEvent($this->callbacks, [
                'subscriber'    => $subscriber,
                'list'          => $list,
                'action'        => 'unsubscribe-confirm',
            ]));
        }

        $list->customer->logAction->subscriberUnsubscribed($subscriber);

        $dsParams = ['useFor' => DeliveryServer::USE_FOR_LIST_EMAILS];
        if ($list->customerNotification->unsubscribe == ListCustomerNotification::TEXT_YES && !empty($list->customerNotification->unsubscribe_to) && ($server = DeliveryServer::pickServer(0, $list, $dsParams))) {
            $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                'list-subscriber-unsubscribed',
                [
                    'fromName'  => $list->default->from_name,
                    'subject'   => t('lists', 'List subscriber unsubscribed!'),
                ],
                [
                    '[LIST_NAME]'           => $list->name,
                    '[LIST_DISPLAY_NAME]'   => $list->display_name,
                    '[LIST_INTERNAL_NAME]'  => $list->name,
                    '[LIST_UID]'            => $list->list_uid,
                    '[SUBSCRIBER_EMAIL]'    => $subscriber->getDisplayEmail(),
                ]
            );

            $recipients = explode(',', $list->customerNotification->unsubscribe_to);
            $recipients = array_map('trim', $recipients);

            foreach ($recipients as $recipient) {
                if (!FilterVarHelper::email($recipient)) {
                    continue;
                }
                $params['to'] = [$recipient => $list->customer->getFullName()];
                $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($list)->sendEmail($params);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . $list->display_name,
        ]);

        $this->render('display_content', compact('content'));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     * @since 1.7.6
     */
    public function actionVcard($list_uid)
    {
        $list = $this->loadListModel($list_uid);
        if (empty($list->customer) || empty($list->company)) {
            $this->redirect(['site/index']);
            return;
        }

        $company  = $list->company;
        $customer = $list->customer;
        $this->setCustomerLanguage($customer);

        $vcard = new JeroenDesloovere\VCard\VCard();

        $vcard->addName($customer->last_name, $customer->first_name);
        $vcard->addCompany($company->name);

        $emailAddress = !empty($list->default->from_email) ? $list->default->from_email : $customer->email;
        $vcard->addEmail($emailAddress);

        if (!empty($company->phone)) {
            $vcard->addPhoneNumber($company->phone, 'PREF;WORK');
        }

        $zone = !empty($company->zone_id) ? $company->zone->name : $company->zone_name;
        $vcard->addAddress(null, null, $company->address_1, $company->city, $zone, $company->zip_code, $company->country->name);

        if (!empty($company->website)) {
            $vcard->addURL($company->website);
        }

        $vcard->download();
    }

    /**
     * Allows a subscriber to unsubscribe from a customer, from all lists
     *
     * @param string $customer_uid
     * @param string $subscriber_uid
     * @param string $campaign_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @since 1.7.4
     */
    public function actionUnsubscribe_from_customer($customer_uid, $subscriber_uid = '', $campaign_uid = '')
    {
        $customer = Customer::model()->findByAttributes([
            'customer_uid' => $customer_uid,
        ]);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $subscriber = new ListSubscriber('search');

        if (!empty($subscriber_uid)) {
            if ($sub = ListSubscriber::model()->findByUid($subscriber_uid)) {
                $subscriber->email = $sub->email;
            }
        }

        // since 1.3.6.2
        if (request()->getIsPostRequest() && ($reason = (string)request()->getPost('unsubscribe_reason', ''))) {
            $this->setUnsubscribeReason($reason);
        }

        $campaign = null;
        if (!empty($campaign_uid)) {
            $campaign = Campaign::model()->findByUid($campaign_uid);
        }

        $reasonField = '';
        if (!empty($campaign)) {
            $trackUnsubscribe = new CampaignTrackUnsubscribe();
            $trackUnsubscribe->reason = $this->getUnsubscribeReason();
            $reasonField = $this->renderPartial('_unsubscribe-reason', compact('trackUnsubscribe'), true);
        }

        if (request()->getIsPostRequest()) {
            $subscriber->attributes = (array)request()->getPost($subscriber->getModelName(), []);

            if (!FilterVarHelper::email($subscriber->email)) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
                $subscriber->addError('email', t('lists', 'Please enter a valid email address!'));
            } else {
                $lists = Lists::model()->findAllByAttributes([
                    'customer_id' => $customer->customer_id,
                ]);

                foreach ($lists as $list) {
                    $listSubscriber = ListSubscriber::model()->findByAttributes([
                        'list_id' => (int)$list->list_id,
                        'email'   => $subscriber->email,
                        'status'  => ListSubscriber::STATUS_CONFIRMED,
                    ]);

                    if (empty($listSubscriber)) {
                        continue;
                    }

                    // capture the output
                    ob_start();
                    ob_implicit_flush();

                    $this->actionUnsubscribe_confirm($list->list_uid, $listSubscriber->subscriber_uid, $campaign_uid);

                    // and discard it
                    ob_get_clean();
                }

                $subscriber->email = '';
                notify()
                    ->clearAll()
                    ->addSuccess(t('lists', 'You have been successfully unsubscribed from all this customer lists!'));
            }
        }

        $this->render('unsubscribe-from-customer', compact('customer', 'subscriber', 'reasonField'));
    }

    /**
     * Allow guests to add their email address into the global blacklist
     *
     * @return void
     * @throws CException
     * @since 1.3.7.3
     */
    public function actionBlock_address()
    {
        $model = new BlockEmailRequest();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            $model->ip_address = (string)request()->getUserHostAddress();
            $model->user_agent = StringHelper::truncateLength((string)request()->getUserAgent(), 255);

            // since 1.9.30
            hooks()->doAction('controller_action_before_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => $model->validate(),
                'model'     => $model,
            ]));

            if ($collection->itemAt('success')) {
                $success = $error = '';
                $transaction = db()->beginTransaction();

                /** @var OptionUrl $optionUrl */
                $optionUrl = container()->get(OptionUrl::class);

                try {
                    if (!$model->save()) {
                        throw new Exception($error = t('app', 'Your form has a few errors, please fix them and try again!'));
                    }

                    $detailsLink = CHtml::link(t('messages', 'here'), $optionUrl->getBackendUrl('block-email-request/index'));
                    $message = new UserMessage();
                    $message->title   = 'New block email request!';
                    $message->message = 'Email "{email}" has requested to be blocked! You can see more details {here}!';
                    $message->message_translation_params = [
                        '{email}' => $model->email,
                        '{here}'  => $detailsLink,
                    ];
                    $message->broadcast();

                    $blocked = false;
                    $sent    = false;
                    $server  = DeliveryServer::pickServer();

                    // send the confirmation email if we have a server
                    if ($server) {
                        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                            'confirm-block-email-request',
                            [
                                'to'      => [$model->email => $model->email],
                                'subject' => t('email_blacklist', 'Confirm the block email request!'),
                            ],
                            [
                                '[CONFIRMATION_URL]' => apps()->getAppUrl('frontend', sprintf('lists/block-address-confirmation/%s', $model->confirmation_key), true),
                            ]
                        );

                        $sent = $server->sendEmail($params);
                    }

                    // if no server found or email could not be sent, then just proceed,
                    // subscriber does not have to suffer because of inability to send the email
                    if (!$server || !$sent) {
                        $model->block();
                        $blocked = true;
                    }

                    $transaction->commit();

                    if ($sent) {
                        $success = t('app', 'Please check your email in order to confirm the request!');
                    }

                    if ($blocked) {
                        $success = t('email_blacklist', 'The email address has been successfully blocked!');
                    }
                } catch (Exception $e) {
                    $transaction->rollback();

                    if (empty($error)) {
                        $error = t('email_blacklist', 'Something went wrong, please try again!');
                    }
                }

                if ($success) {
                    notify()->addSuccess($success);
                } elseif ($error) {
                    notify()->addError($error);
                }

                hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                    'controller'=> $this,
                    'success'   => $model->validate(),
                    'model'     => $model,
                ]));

                if ($collection->itemAt('success')) {
                    $this->redirect(['lists/block_address']);
                    return;
                }
            }
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);
        $appName = $common->getSiteName();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('email_blacklist', 'Block email address'),
            'pageHeading'     => t('email_blacklist', 'Block email address'),
            'pageBreadcrumbs' => [
                t('email_blacklist', 'Block email address') => createUrl('email_blacklist/index'),
            ],
        ]);

        $view = 'block-address';
        if ($this->getViewFile($view . '-custom') !== false) {
            $view .= '-custom';
        }

        $this->render($view, compact('model', 'appName'));
    }

    /**
     * Show the campaigns sent for the list
     *
     * @return void
     * @throws CException
     */
    public function actionCampaigns(string $list_uid)
    {
        $list = $this->loadListModel($list_uid);

        if ($list->getIsPendingDelete()) {
            $this->redirect(['index']);
        }

        $criteria = new CDbCriteria();
        $criteria->select = 't.campaign_uid, t.name, t.send_at';
        $criteria->compare('t.type', Campaign::TYPE_REGULAR);
        $criteria->compare('t.status', Campaign::STATUS_SENT);
        $criteria->compare('t.list_id', $list->list_id);
        $criteria->order = 't.campaign_id DESC';
        $criteria->limit = 20;

        $latestCampaigns = Campaign::model()->findAll($criteria);

        $pageHeading = t('lists', 'Campaigns history for "{list}" list', [
            '{list}' => sprintf('<em>%s</em>', html_encode((string)$list->display_name)),
        ]);

        $pageSubHeading = !empty($list->company) && !empty($list->company->name) ? $list->company->name : '';

        $this->setData([
            'pageMetaTitle' => $this->getData('pageMetaTitle') . ' | ' . $pageHeading,
            'pageHeading'   => $pageHeading,
            'pageSubHeading'=> $pageSubHeading,
        ]);

        $this->render('campaigns', compact('list', 'latestCampaigns'));
    }

    /**
     * Confirm block address request
     *
     * @param string $key
     *
     * @return void
     * @since 1.5.5
     */
    public function actionBlock_address_confirmation($key)
    {
        $model = BlockEmailRequest::model()->findByAttributes([
            'confirmation_key' => $key,
        ]);

        if (empty($model)) {
            notify()->addError(t('email_blacklist', 'The request has not been found!'));
            $this->redirect(['lists/block_address']);
            return;
        }

        if ($model->isConfirmed) {
            notify()->addError(t('email_blacklist', 'The request has already been confirmed!'));
            $this->redirect(['lists/block_address']);
            return;
        }

        $model->block();

        notify()->addSuccess(t('email_blacklist', 'The email address has been successfully blocked!'));
        $this->redirect(['lists/block_address']);
    }

    /**
     * Responds to the ajax calls from the country list fields
     *
     * @return void
     * @throws CException
     */
    public function actionFields_country_states_by_country_name()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['site/index']);
            return;
        }

        $countryName = request()->getQuery('country');
        $country = Country::model()->findByAttributes(['name' => $countryName]);
        if (empty($country)) {
            $this->renderJson([]);
            return;
        }

        $statesList = [];
        $states     = !empty($country->zones) ? $country->zones : [];

        foreach ($states as $state) {
            $statesList[$state->name] = $state->name;
        }

        $this->renderJson($statesList);
    }

    /**
     * Responds to the ajax calls from the state list fields
     *
     * @return void
     * @throws CException
     */
    public function actionFields_country_by_zone()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $zone = Zone::model()->findByAttributes([
            'name' => request()->getQuery('zone'),
        ]);

        if (empty($zone)) {
            $this->renderJson([]);
            return;
        }

        $this->renderJson([
            'country' => [
                'name' => $zone->country->name,
                'code' => $zone->country->code,
            ],
        ]);
    }

    /**
     * @param string $list_uid
     *
     * @return Lists
     * @throws CHttpException
     */
    public function loadListModel(string $list_uid): Lists
    {
        $criteria = new CDbCriteria();
        $criteria->compare('list_uid', $list_uid);
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
        $model = Lists::model()->find($criteria);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param string $slug
     *
     * @return ListPageType
     * @throws CHttpException
     */
    public function loadPageTypeModel(string $slug): ListPageType
    {
        $model = ListPageType::model()->findBySlug((string)$slug);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param int $list_id
     * @param int $type_id
     *
     * @return ListPage|null
     */
    public function loadPageModel(int $list_id, int $type_id): ?ListPage
    {
        return ListPage::model()->findByAttributes([
            'list_id' => (int)$list_id,
            'type_id' => (int)$type_id,
        ]);
    }

    /**
     * @param string $subscriber_uid
     * @param int $list_id
     *
     * @return ListSubscriber
     * @throws CHttpException
     */
    public function loadSubscriberModel(string $subscriber_uid, int $list_id): ListSubscriber
    {
        $model = ListSubscriber::model()->findByAttributes([
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => (int)$list_id,
        ]);

        if ($model === null) {
            if (($list = Lists::model()->findByPk((int)$list_id)) && ($redirect = $list->getSubscriber404Redirect())) {
                $this->redirect($redirect);
            }
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * Helper method to set the language for this customer.
     *
     * @param Customer $customer
     *
     * @return ListsController
     * @throws CException
     */
    public function setCustomerLanguage(Customer $customer): self
    {
        if (empty($customer->language_id)) {
            return $this;
        }

        // 1.5.3 - language has been forced already at init
        if (($langCode = (string)request()->getQuery('lang', '')) && strlen($langCode) <= 5) {
            return $this;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        // multilanguage is available since 1.1 and the Language class does not exist prior to that version
        if (!version_compare($common->version, '1.1', '>=')) {
            return $this;
        }

        if (!empty($customer->language)) {
            app()->setLanguage($customer->language->getLanguageAndLocaleCode());
        }

        return $this;
    }

    /**
     * @param string $reason
     */
    public function setUnsubscribeReason(string $reason): void
    {
        if (empty($reason)) {
            return;
        }
        $session = session();
        $session['unsubscribe_reason']    = StringHelper::truncateLength($reason, 250);
        $session['unsubscribe_reason_ts'] = time();
    }

    /**
     * @return string
     */
    public function getUnsubscribeReason(): string
    {
        $session     = session();
        $unsubReason = '';
        if (isset($session['unsubscribe_reason'], $session['unsubscribe_reason_ts'])) {
            if ($session['unsubscribe_reason_ts'] + 600 > time()) {
                $unsubReason = (string)$session['unsubscribe_reason'];
            }
            unset($session['unsubscribe_reason'], $session['unsubscribe_reason_ts']);
        }
        return $unsubReason;
    }
}
