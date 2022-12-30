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

class ListsController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        // make sure the parent account has allowed access for this subaccount
        if (is_subaccount() && !subaccount()->canManageLists()) {
            $this->redirect(['dashboard/index']);
        }

        $this->addPageScript(['src' => AssetsUrl::js('lists.js')]);
        $this->onBeforeAction = [$this, '_registerJuiBs'];
        parent::init();
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + copy, all_subscribers_filters',
        ], parent::filters());
    }

    /**
     * Show available lists
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $session = session();
        $list = new Lists('search');
        $list->unsetAttributes();
        $list->attributes = (array)request()->getQuery($list->getModelName(), []);
        $list->customer_id = (int)customer()->getId();

        // 1.8.8
        $refreshRoute = ['lists/index'];
        $gridAjaxUrl  = createUrl($this->getRoute());
        if ($list->getIsArchived()) {
            $refreshRoute = ['lists/index', 'Lists[status]' => Lists::STATUS_ARCHIVED];
            $gridAjaxUrl = createUrl($this->getRoute(), ['Lists[status]' => Lists::STATUS_ARCHIVED]);
        }

        $this->setData([
            'refreshRoute' => $refreshRoute,
            'gridAjaxUrl'  => $gridAjaxUrl,
        ]);

        $pageHeading = t('lists', 'Lists');
        $breadcrumbs = [t('lists', 'Lists') => createUrl('lists/index')];
        if ($list->getIsArchived()) {
            $pageHeading = t('lists', 'Archived lists');
            $breadcrumbs[t('lists', 'Archived lists')] = createUrl('lists/index', ['Lists[status]' => Lists::STATUS_ARCHIVED]);
        }
        $breadcrumbs[] = t('app', 'View all');
        //

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Your lists'),
            'pageHeading'     => $pageHeading,
            'pageBreadcrumbs' => $breadcrumbs,
        ]);

        $this->render('list', compact('list'));
    }

    /**
     * Create a new list
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (($maxLists = (int)$customer->getGroupOption('lists.max_lists', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);

            $listsCount = Lists::model()->count($criteria);
            if ($listsCount >= $maxLists) {
                notify()->addWarning(t('lists', 'You have reached the maximum number of allowed lists.'));
                $this->redirect(['lists/index']);
            }
        }

        $list = new Lists();
        $list->customer_id = (int)$customer->customer_id;

        $listDefault = new ListDefault();
        $listCompany = new ListCompany();
        $listCustomerNotification = new ListCustomerNotification();

        // since 1.3.5 - this should be expanded in future
        $listSubscriberAction      = new ListSubscriberAction();
        $subscriberActionLists     = CMap::mergeArray([0 => t('app', 'Select all')], $list->findAllForSubscriberActions());
        $selectedSubscriberActions = [ListSubscriberAction::ACTION_SUBSCRIBE => [], ListSubscriberAction::ACTION_UNSUBSCRIBE => []];

        // to create the default mail list fields.
        $list->attachBehavior('listDefaultFields', [
            'class' => 'customer.components.db.behaviors.ListDefaultFieldsBehavior',
        ]);

        if (!empty($customer->company)) {
            $listCompany->mergeWithCustomerCompany($customer->company);
        }

        $listDefault->mergeWithCustomerInfo($customer);

        // since 1.5.3
        if (($forceOptIn = (string)$customer->getGroupOption('lists.force_optin_process', '')) && in_array($forceOptIn, array_keys($list->getOptInArray()))) {
            $list->opt_in = $forceOptIn;
        }
        if (($forceOptOut = (string)$customer->getGroupOption('lists.force_optout_process', '')) && in_array($forceOptOut, array_keys($list->getOptOutArray()))) {
            $list->opt_out = $forceOptOut;
        }
        //

        if (request()->getIsPostRequest() && request()->getPost($list->getModelName())) {
            $models = [$list, $listCompany, $listCustomerNotification, $listDefault];
            $hasErrors = false;
            foreach ($models as $model) {
                $model->attributes = (array)request()->getPost($model->getModelName(), []);
                if (!$model->validate()) {
                    $hasErrors = true; // don't break to collect errors for all models.
                }
            }

            if (!$hasErrors) {

                // 1.4.5
                $listSubscriberActions = (array)request()->getPost($listSubscriberAction->getModelName(), []);
                $isSelectAll           = !empty($listSubscriberActions['subscribe']) && array_search(0, (array)$listSubscriberActions['subscribe']) !== false ? 1 : 0;
                $list->setIsSelectAllAtActionWhenSubscribe($isSelectAll);
                $isSelectAll           = !empty($listSubscriberActions['unsubscribe']) && array_search(0, (array)$listSubscriberActions['unsubscribe']) !== false ? 1 : 0;
                $list->setIsSelectAllAtActionWhenUnsubscribe($isSelectAll);
                //

                foreach ($models as $model) {
                    if (!($model instanceof Lists)) {
                        $model->list_id = (int)$list->list_id;
                    }
                    $model->save(false);
                }

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->listCreated($list);

                // since 1.3.5 - this should be expanded in future
                if ($listSubscriberActions = (array)request()->getPost($listSubscriberAction->getModelName(), [])) {
                    $allowedActions = array_keys($listSubscriberAction->getActions());
                    /**
                     * @var string $actionName
                     * @var array $targetLists
                     */
                    foreach ($listSubscriberActions as $actionName => $targetLists) {
                        if (!in_array($actionName, $allowedActions)) {
                            continue;
                        }
                        foreach ($targetLists as $targetListId) {
                            $subscriberAction = new ListSubscriberAction();
                            $subscriberAction->source_list_id = (int)$list->list_id;
                            $subscriberAction->source_action  = (string)$actionName;
                            $subscriberAction->target_list_id = (int)$targetListId;
                            $subscriberAction->target_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                            $subscriberAction->save();
                        }
                    }
                }

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form contains errors, please correct them and try again.'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'    => $this,
                'success'       => notify()->getHasSuccess(),
                'list'          => $list,
            ]));

            if ($collection->itemAt('success')) {
                if (request()->getPost('save-back')) {
                    $this->redirect(['lists/index']);
                }
                $this->redirect(['lists/update', 'list_uid' => $list->list_uid]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Create new list'),
            'pageHeading'     => t('lists', 'Create new list'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact(
            'list',
            'listDefault',
            'listCompany',
            'listCustomerNotification',
            'listSubscriberAction',
            'subscriberActionLists',
            'selectedSubscriberActions',
            'forceOptIn',
            'forceOptOut'
        ));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadModel((string)$list_uid);

        if (!$list->getEditable()) {
            $this->redirect(['lists/index']);
        }

        $customer    = $list->customer;
        $listDefault = $list->default;
        $listCompany = $list->company;
        $listCustomerNotification = $list->customerNotification;

        // since 1.3.5 - this should be expanded in future
        $listSubscriberAction  = new ListSubscriberAction();
        $subscriberActionLists = CMap::mergeArray([0 => t('app', 'Select all')], $list->findAllForSubscriberActions());

        $selectedSubscriberActions = [
            ListSubscriberAction::ACTION_SUBSCRIBE   => [],
            ListSubscriberAction::ACTION_UNSUBSCRIBE => [],
        ];

        // 1.4.5
        if ($list->getIsSelectAllAtActionWhenSubscribe()) {
            $selectedSubscriberActions[ListSubscriberAction::ACTION_SUBSCRIBE][]  = 0;
        }
        if ($list->getIsSelectAllAtActionWhenUnsubscribe()) {
            $selectedSubscriberActions[ListSubscriberAction::ACTION_UNSUBSCRIBE][] = 0;
        }
        //

        if (!empty($list->subscriberSourceActions)) {
            foreach ($list->subscriberSourceActions as $model) {
                $selectedSubscriberActions[$model->source_action][] = (int)$model->target_list_id;
            }
        }

        // since 1.5.3
        if (($forceOptIn = (string)$customer->getGroupOption('lists.force_optin_process', '')) && in_array($forceOptIn, array_keys($list->getOptInArray()))) {
            $list->opt_in = $forceOptIn;
        }
        if (($forceOptOut = (string)$customer->getGroupOption('lists.force_optout_process', '')) && in_array($forceOptOut, array_keys($list->getOptOutArray()))) {
            $list->opt_out = $forceOptOut;
        }
        //

        if (request()->getIsPostRequest() && request()->getPost($list->getModelName())) {
            $models = [$list, $listCompany, $listCustomerNotification, $listDefault];
            $hasErrors = false;
            foreach ($models as $model) {
                $model->attributes = (array)request()->getPost($model->getModelName(), []);
                if (!$model->validate()) {
                    $hasErrors = true; // don't break to collect errors for all models.
                }
            }
            if (!$hasErrors) {

                // 1.4.5
                $listSubscriberActions  = (array)request()->getPost($listSubscriberAction->getModelName(), []);
                $isSelectAllSubscribe   = !empty($listSubscriberActions['subscribe']) && array_search(0, (array)$listSubscriberActions['subscribe']) !== false ? 1 : 0;
                $list->setIsSelectAllAtActionWhenSubscribe($isSelectAllSubscribe);
                $isSelectAllUnsubscribe = !empty($listSubscriberActions['unsubscribe']) && array_search(0, (array)$listSubscriberActions['unsubscribe']) !== false ? 1 : 0;
                $list->setIsSelectAllAtActionWhenUnsubscribe($isSelectAllUnsubscribe);
                //

                foreach ($models as $model) {
                    $model->save(false);
                }

                /** @var CustomerActionLogBehavior $logAction */
                $logAction = $customer->getLogAction();
                $logAction->listUpdated($list);

                // since 1.3.5 - this should be expanded in future
                ListSubscriberAction::model()->deleteAllByAttributes(['source_list_id' => (int)$list->list_id]);
                $listSubscriberActions = (array)request()->getPost($listSubscriberAction->getModelName(), []);

                // 1.4.5
                $listIds  = [];
                if ($isSelectAllSubscribe || $isSelectAllUnsubscribe) {
                    $criteria = new CDbCriteria();
                    $criteria->compare('customer_id', $list->customer_id);
                    $criteria->addNotInCondition('list_id', [$list->list_id]);
                    $criteria->select = 'list_id';
                    $listIds = ListsCollection::findAll($criteria)->map(function (Lists $list) {
                        return $list->list_id;
                    })->toArray();
                }
                if ($isSelectAllSubscribe) {
                    $listSubscriberActions['subscribe'] = $listIds;
                }
                if ($isSelectAllUnsubscribe) {
                    $listSubscriberActions['unsubscribe'] = $listIds;
                }
                //

                if ($listSubscriberActions) {
                    $allowedActions = array_keys($listSubscriberAction->getActions());
                    /**
                     * @var string $actionName
                     * @var array $targetLists
                     */
                    foreach ($listSubscriberActions as $actionName => $targetLists) {
                        if (!in_array($actionName, $allowedActions)) {
                            continue;
                        }
                        foreach ($targetLists as $targetListId) {
                            $subscriberAction = new ListSubscriberAction();
                            $subscriberAction->source_list_id = (int)$list->list_id;
                            $subscriberAction->source_action  = (string)$actionName;
                            $subscriberAction->target_list_id = (int)$targetListId;
                            $subscriberAction->target_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                            $subscriberAction->save();
                        }
                    }
                }

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            } else {
                notify()->addError(t('app', 'Your form contains errors, please correct them and try again.'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'    => $this,
                'success'       => notify()->getHasSuccess(),
                'list'          => $list,
            ]));

            if ($collection->itemAt('success')) {
                if (request()->getPost('save-back')) {
                    $this->redirect(['lists/index']);
                }
                $this->redirect(['lists/update', 'list_uid' => $list->list_uid]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Update list'),
            'pageHeading'     => t('lists', 'Update list'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact(
            'list',
            'listDefault',
            'listCompany',
            'listCustomerNotification',
            'listSubscriberAction',
            'subscriberActionLists',
            'selectedSubscriberActions',
            'forceOptIn',
            'forceOptOut'
        ));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCopy($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadModel((string)$list_uid);

        $customer = $list->customer;
        $canCopy  = true;

        if ($list->getIsPendingDelete()) {
            $this->redirect(['lists/index']);
        }

        if (($maxLists = $customer->getGroupOption('lists.max_lists', -1)) > -1) {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$customer->customer_id);
            $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);

            $listsCount = Lists::model()->count($criteria);
            if ($listsCount >= $maxLists) {
                notify()->addWarning(t('lists', 'You have reached the maximum number of allowed lists.'));
                $canCopy = false;
            }
        }

        if ($canCopy && $list->copy()) {
            notify()->addSuccess(t('lists', 'Your list was successfully copied!'));
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['lists/index']));
        }
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionToggle_archive($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadModel((string)$list_uid);

        /** @var array $returnRoute */
        $returnRoute = ['lists/index'];

        if ($list->getIsPendingDelete()) {
            $this->redirect($returnRoute);
        }

        if ($list->getIsArchived()) {
            $list->saveStatus(Lists::STATUS_ACTIVE);
            notify()->addSuccess(t('lists', 'Your list was successfully unarchived!'));
            $returnRoute = ['lists/index'];
        } else {
            $list->saveStatus(Lists::STATUS_ARCHIVED);
            notify()->addSuccess(t('lists', 'Your list was successfully archived!'));
            $returnRoute = ['lists/index', 'Lists[status]' => Lists::STATUS_ARCHIVED];
        }

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', $returnRoute));
        }
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadModel((string)$list_uid);

        if (!$list->getIsRemovable()) {
            $this->redirect(['lists/index']);
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (request()->getIsPostRequest()) {
            $list->delete();

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->listDeleted($list);

            notify()->addSuccess(t('app', 'Your item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['lists/index']);

            // since 1.3.5.9
            hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'model'      => $list,
                'redirect'   => $redirect,
            ]));

            if ($collection->itemAt('redirect')) {
                $this->redirect($collection->itemAt('redirect'));
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Confirm list removal'),
            'pageHeading'     => t('lists', 'Confirm list removal'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('lists', 'Confirm list removal'),
            ],
        ]);

        $campaign = new Campaign();
        $campaign->unsetAttributes();
        $campaign->attributes  = (array)request()->getQuery($campaign->getModelName(), []);
        $campaign->list_id     = (int)$list->list_id;
        $campaign->customer_id = (int)customer()->getId();

        $this->render('delete', compact('list', 'campaign'));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionOverview($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadModel((string)$list_uid);

        if ($list->getIsPendingDelete()) {
            $this->redirect(['lists/index']);
        }

        $this->addPageStyle(['src' => apps()->getBaseUrl('assets/css/placeholder-loading.css')]);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'List overview'),
            'pageHeading'     => t('lists', 'List overview'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('lists', 'Overview'),
            ],
        ]);

        $this->render('overview', compact('list'));
    }

    /**
     * Display a searchable table of subscribers from all lists
     *
     * @return void
     * @throws CException
     */
    public function actionAll_subscribers()
    {
        /** @var AllListsSubscribersFilters $filter */
        $filter = new AllListsSubscribersFilters();

        /** @var Customer $customer */
        $customer = customer()->getModel();
        $filter->customer_id = $customer->customer_id;
        $filter->customer    = $customer;

        if ($attributes = (array)request()->getQuery('')) {
            $filter->setAttributes(CMap::mergeArray($filter->getAttributes(), $attributes));
            $filter->hasSetFilters = true;
        }

        if ($attributes = (array)request()->getPost('')) {
            $filter->setAttributes(CMap::mergeArray($filter->getAttributes(), $attributes));
            $filter->hasSetFilters = true;
        }

        if ($filter->hasSetFilters && !$filter->validate()) {
            notify()->addError($filter->shortErrors->getAllAsString());
            $this->redirect([$this->getRoute()]);
        }

        // 1.6.8
        if (!$filter->getIsViewAction()) {
            if (request()->getPost('confirm', null) === null) {
                $this->render('confirm-filters-action');
                return;
            }

            if (request()->getPost('confirm', '') !== 'true') {
                $this->redirect([$this->getRoute()]);
            }
        }
        //

        // the export action
        $canExport = $customer->getGroupOption('lists.can_export_subscribers', 'yes') == 'yes';
        if ($filter->getIsExportAction() && $canExport) {
            queue_send('customer.lists.allsubscribers.filter.export', $filter->getAttributes(array_merge($filter->getSafeAttributeNames(), ['customer_id'])));
            notify()->addSuccess(t('app', 'Your request has been successfully queued, you will be notified once it is completed!'));
            $this->redirect([$this->getRoute()]);
        }

        // create list from selection
        $canCreateList = $customer->getGroupOption('lists.can_create_list_from_filters', 'yes') == 'yes';
        if ($canCreateList && $filter->getIsCreateListAction()) {
            queue_send('customer.lists.allsubscribers.filter.createlist', $filter->getAttributes(array_merge($filter->getSafeAttributeNames(), ['customer_id'])));
            notify()->addSuccess(t('app', 'Your request has been successfully queued, you will be notified once it is completed!'));
            $this->redirect([$this->getRoute()]);
        }

        // the confirm action
        if ($filter->getIsConfirmAction()) {
            queue_send('customer.lists.allsubscribers.filter.confirm', $filter->getAttributes(array_merge($filter->getSafeAttributeNames(), ['customer_id'])));
            notify()->addSuccess(t('app', 'Your request has been successfully queued, you will be notified once it is completed!'));
            $this->redirect([$this->getRoute()]);
        }

        // the unsubscribe action
        if ($filter->getIsUnsubscribeAction()) {
            queue_send('customer.lists.allsubscribers.filter.unsubscribe', $filter->getAttributes(array_merge($filter->getSafeAttributeNames(), ['customer_id'])));
            notify()->addSuccess(t('app', 'Your request has been successfully queued, you will be notified once it is completed!'));
            $this->redirect([$this->getRoute()]);
        }

        // the disable action
        if ($filter->getIsDisableAction()) {
            queue_send('customer.lists.allsubscribers.filter.disable', $filter->getAttributes(array_merge($filter->getSafeAttributeNames(), ['customer_id'])));
            notify()->addSuccess(t('app', 'Your request has been successfully queued, you will be notified once it is completed!'));
            $this->redirect([$this->getRoute()]);
        }

        // the blacklist action
        $canBlacklist = $filter->customer->getGroupOption('lists.can_use_own_blacklist', 'no') == 'yes';
        if ($filter->getIsBlacklistAction() && $canBlacklist) {
            queue_send('customer.lists.allsubscribers.filter.blacklist', $filter->getAttributes(array_merge($filter->getSafeAttributeNames(), ['customer_id'])));
            notify()->addSuccess(t('app', 'Your request has been successfully queued, you will be notified once it is completed!'));
            $this->redirect([$this->getRoute()]);
        }

        // the delete action
        $canDelete = $customer->getGroupOption('lists.can_delete_own_subscribers', 'yes') == 'yes';
        if ($filter->getIsDeleteAction() && $canDelete) {
            queue_send('customer.lists.allsubscribers.filter.delete', $filter->getAttributes(array_merge($filter->getSafeAttributeNames(), ['customer_id'])));
            notify()->addSuccess(t('app', 'Your request has been successfully queued, you will be notified once it is completed!'));
            $this->redirect([$this->getRoute()]);
        }

        // the view action, default one.
        $this->addPageScript(['src' => AssetsUrl::js('lists-all-subscribers.js')]);
        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('lists', 'Subscribers'),
            'pageHeading'     => t('lists', 'Subscribers from all your lists'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                t('lists', 'Subscribers'),
            ],
        ]);

        $this->render('all-subscribers', compact('filter'));
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
            $this->redirect(['dashboard/index']);
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
     * Export
     *
     * @return void
     */
    public function actionExport()
    {
        $models = Lists::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('lists.csv');

        try {
            $csvWriter  = League\Csv\Writer::createFromPath('php://output', 'w');
            $attributes = AttributeHelper::removeSpecialAttributes($models[0]->attributes);

            /** @var callable $callback */
            $callback   = [$models[0], 'getAttributeLabel'];
            $attributes = array_map($callback, array_keys($attributes));

            $csvWriter->insertOne($attributes);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->attributes);
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionList_growth_export(string $list_uid)
    {
        $list = $this->loadModel($list_uid);

        $timestamp = (int)strtotime('-1 year');

        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$list->list_id);
        $criteria->addCondition('t.date_added >= :datetime');
        $criteria->params[':datetime'] = date('Y-m-d H:i:s', $timestamp);

        $listSubscriberCounters = ListSubscriberCountHistory::model()->findAll($criteria);

        // Set the download headers
        HeaderHelper::setDownloadHeaders('list-growth.csv');

        try {
            $csvWriter  = League\Csv\Writer::createFromPath('php://output', 'w');
            $attributes = AttributeHelper::removeSpecialAttributes($listSubscriberCounters[0]->attributes);

            /** @var callable $callback */
            $callback   = [$listSubscriberCounters[0], 'getAttributeLabel'];
            $attributes = array_map($callback, array_keys($attributes));

            $csvWriter->insertOne($attributes);

            foreach ($listSubscriberCounters as $listSubscriberCounter) {
                $attributes = AttributeHelper::removeSpecialAttributes($listSubscriberCounter->attributes);
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionList_growth($list_uid)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['lists/index']);
            return;
        }

        $dates = explode(' - ', (string)request()->getPost('range', ''));
        if (count($dates) != 2) {
            $this->renderJson([
                'chartData'    => [],
                'chartOptions' => [],
            ]);
        }

        $dateStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dates[0]);
        $dateEnd   = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $dates[1]);
        if (!$dateStart || !$dateEnd) {
            $this->renderJson([
                'chartData'    => [],
                'chartOptions' => [],
            ]);
            return;
        }

        $list = $this->loadModel($list_uid);
        $data = $list->getSubscribersGrowthDataForChart($dateStart, $dateEnd);

        $this->renderJson([
            'chartData'    => $data['chartData'],
            'chartOptions' => $data['chartOptions'],
        ]);
    }

    /**
     * @param string $list_uid
     *
     * @return Lists
     * @throws CHttpException
     */
    public function loadModel(string $list_uid): Lists
    {
        $criteria = new CDbCriteria();
        $criteria->compare('list_uid', $list_uid);
        $criteria->compare('customer_id', (int)customer()->getId());
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE]);

        $model = Lists::model()->find($criteria);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($model->getIsPendingDelete()) {
            $this->redirect(['lists/index']);
        }

        return $model;
    }

    /**
     * Callback to register Jquery ui bootstrap only for certain actions
     *
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['all_subscribers'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
