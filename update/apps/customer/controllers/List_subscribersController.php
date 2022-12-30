<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * List_subscribersController
 *
 * Handles the actions for list subscribers related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * @property ListFieldsControllerCallbacksBehavior $callbacks
 */
class List_subscribersController extends Controller
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

        Yii::import('customer.components.list-field-builder.*');

        $this->addPageScript(['src' => AssetsUrl::js('subscribers.js')]);
        parent::init();
    }

    /**
     * @return array
     * @throws CException
     */
    public function filters()
    {
        return CMap::mergeArray([
            'postOnly + delete, subscribe, unsubscribe, disable, bulk_action, blacklist, blacklist_ip',
        ], parent::filters());
    }

    /**
     * @return array
     * @throws CException
     */
    public function behaviors()
    {
        return CMap::mergeArray([
            'callbacks' => [
                'class' => 'customer.components.behaviors.ListFieldsControllerCallbacksBehavior',
            ],
        ], parent::behaviors());
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionIndex($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $postFilter = (array)request()->getPost('filter', []);
        $subscriber = new ListSubscriber();

        $subscriberStatusesList = $subscriber->getFilterStatusesList();

        // since 1.3.6.2
        // filters
        $getFilterSet = false;
        $getFilter = [
            'campaigns' => [
                'campaign' => null,
                'action'   => null,
                'atu'      => null, // action time unit
                'atuc'     => null, // action time unit count
            ],
        ];
        if (request()->getQuery('filter') && is_array(request()->getQuery('filter'))) {
            $getFilter = CMap::mergeArray($getFilter, request()->getQuery('filter'));
            $getFilterSet = true;
        }

        // list campaigns for filters
        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id, name';
        $criteria->compare('list_id', $list->list_id);
        $criteria->addInCondition('status', [Campaign::STATUS_SENT, Campaign::STATUS_SENDING]);
        $criteria->order = 'campaign_id DESC';
        $campaigns = Campaign::model()->findAll($criteria);

        $listCampaigns = [];
        foreach ($campaigns as $campaign) {
            $listCampaigns[$campaign->campaign_id] = $campaign->name;
        }
        //
        /**
         * NOTE:
         * Following criteria will use filesort and create a temp table because of the group by condition.
         * So far, beside subqueries this is the only optimal way i have found to work fine.
         * Needs optimization in the future if will cause problems.
         */
        $criteria = new CDbCriteria();
        $criteria->with = [];
        $criteria->select = 'COUNT(DISTINCT t.subscriber_id) as counter';
        $criteria->compare('t.list_id', $list->list_id);
        $criteria->order = 't.subscriber_id DESC';

        // since 1.3.6.2
        if (!empty($getFilter['campaigns']['action'])) {
            $action      = $getFilter['campaigns']['action'];
            $campaignId  = !empty($getFilter['campaigns']['campaign']) ? (int)$getFilter['campaigns']['campaign'] : 0;
            $campaignIds = empty($campaignId) ? array_keys($listCampaigns) : [(int)$campaignId];
            $campaignIds = array_map('intval', $campaignIds);
            $campaignIds = !empty($campaignIds) ? $campaignIds : [0];
            $atu  = $subscriber->getFilterTimeUnitValueForDb(!empty($getFilter['campaigns']['atu']) ? (int)$getFilter['campaigns']['atu'] : 0);
            $atuc = !empty($getFilter['campaigns']['atuc']) ? (int)$getFilter['campaigns']['atuc'] : 0;
            $atuc = $atuc > 1024 ? 1024 : $atuc;
            $atuc = $atuc < 0 ? 0 : $atuc;

            if (in_array($action, [ListSubscriber::CAMPAIGN_FILTER_ACTION_DID_OPEN, ListSubscriber::CAMPAIGN_FILTER_ACTION_DID_NOT_OPEN])) {
                $rel = [
                    'select'   => false,
                    'together' => true,
                ];

                if ($action == ListSubscriber::CAMPAIGN_FILTER_ACTION_DID_OPEN) {
                    $rel['joinType']  = 'INNER JOIN';
                    $rel['condition'] = 'trackOpens.campaign_id IN (' . implode(',', $campaignIds) . ')';
                    if (!empty($atuc)) {
                        $rel['condition'] .= sprintf(' AND trackOpens.date_added >= DATE_SUB(NOW(), INTERVAL %d %s)', $atuc, $atu);
                    }
                } else {
                    $rel['on']        = 'trackOpens.campaign_id IN (' . implode(',', $campaignIds) . ')';
                    $rel['joinType']  = 'LEFT OUTER JOIN';
                    $rel['condition'] = 'trackOpens.subscriber_id IS NULL';
                    if (!empty($atuc)) {
                        $rel['condition'] .= sprintf(' OR (trackOpens.subscriber_id IS NOT NULL AND (SELECT date_added FROM {{campaign_track_open}} WHERE subscriber_id = trackOpens.subscriber_id ORDER BY date_added DESC LIMIT 1) <= DATE_SUB(NOW(), INTERVAL %d %s))', $atuc, $atu);
                    }
                }

                $criteria->with['trackOpens'] = $rel;
            }

            if (in_array($action, [ListSubscriber::CAMPAIGN_FILTER_ACTION_DID_CLICK, ListSubscriber::CAMPAIGN_FILTER_ACTION_DID_NOT_CLICK])) {
                $ucriteria = new CDbCriteria();
                $ucriteria->select = 'url_id';
                $ucriteria->addInCondition('campaign_id', $campaignIds);
                $models = CampaignUrl::model()->findAll($ucriteria);
                $urlIds = [];
                foreach ($models as $model) {
                    $urlIds[] = (int)$model->url_id;
                }

                if (empty($urlIds)) {
                    $urlIds = [0];
                }

                $rel = [
                    'select'   => false,
                    'together' => true,
                ];

                if ($action == ListSubscriber::CAMPAIGN_FILTER_ACTION_DID_CLICK) {
                    $rel['joinType']  = 'INNER JOIN';
                    $rel['condition'] = 'trackUrls.url_id IN (' . implode(',', $urlIds) . ')';
                    if (!empty($atuc)) {
                        $rel['condition'] .= sprintf(' AND trackUrls.date_added >= DATE_SUB(NOW(), INTERVAL %d %s)', $atuc, $atu);
                    }
                } else {
                    $rel['on']        = 'trackUrls.url_id IN (' . implode(',', $urlIds) . ')';
                    $rel['joinType']  = 'LEFT OUTER JOIN';
                    $rel['condition'] = 'trackUrls.subscriber_id IS NULL';
                    if (!empty($atuc)) {
                        $rel['condition'] .= sprintf(' OR (trackUrls.subscriber_id IS NOT NULL AND (SELECT date_added FROM {{campaign_track_url}} WHERE subscriber_id = trackUrls.subscriber_id ORDER BY date_added DESC LIMIT 1) <= DATE_SUB(NOW(), INTERVAL %d %s))', $atuc, $atu);
                    }
                }

                $criteria->with['trackUrls'] = $rel;
            }
        }
        //

        foreach ($postFilter as $field_id => $value) {
            if (empty($value)) {
                unset($postFilter[$field_id]);
                continue;
            }

            if (is_numeric($field_id)) {
                $model = ListField::model()->findByAttributes([
                    'field_id'  => $field_id,
                    'list_id'   => $list->list_id,
                ]);
                if (empty($model)) {
                    unset($postFilter[$field_id]);
                }
            }
        }

        if (!empty($postFilter['status']) && in_array($postFilter['status'], array_keys($subscriberStatusesList))) {
            $criteria->compare('status', $postFilter['status']);
        }

        if (!empty($postFilter['uid']) && strlen((string)$postFilter['uid']) == 13) {
            $criteria->compare('subscriber_uid', $postFilter['uid']);
        }

        if (!empty($postFilter)) {
            $with = [];
            foreach ($postFilter as $field_id => $value) {
                if (!is_numeric($field_id)) {
                    continue;
                }

                $i = (int)$field_id;
                $with['fieldValues' . $i] = [
                    'select'    => false,
                    'together'  => true,
                    'joinType'  => 'INNER JOIN',
                    'condition' => '`fieldValues' . $i . '`.`field_id` = :field_id' . $i . ' AND `fieldValues' . $i . '`.`value` LIKE :value' . $i,
                    'params'    => [
                        ':field_id' . $i  => (int)$field_id,
                        ':value' . $i     => '%' . $value . '%',
                    ],
                ];
            }

            $md = $subscriber->getMetaData();
            foreach ($postFilter as $field_id => $value) {
                if (!is_numeric($field_id)) {
                    continue;
                }
                if ($md->hasRelation('fieldValues' . $field_id)) {
                    continue;
                }
                $md->addRelation('fieldValues' . $field_id, [ListSubscriber::HAS_MANY, 'ListFieldValue', 'subscriber_id']);
            }

            if (!empty($with)) {
                $criteria->with = $with;
            }
        }

        // count all confirmed subscribers of this list
        $count = $subscriber->count($criteria);

        // instantiate the pagination and apply the limit statement to the query
        $pages = new CPagination($count);
        $pages->pageSize = (int)$subscriber->paginationOptions->getPageSize();
        $pages->applyLimit($criteria);

        // load the required models
        $criteria->select = 't.list_id, t.subscriber_id, t.subscriber_uid, t.email, t.ip_address, t.status, t.date_added';
        $criteria->group = 't.subscriber_id';

        /** @var ListSubscriber[] $subscribers */
        $subscribers = $subscriber->findAll($criteria);

        // 1.3.8.8
        $modelName  = sprintf('%s_list_%d', get_class($subscriber), $list->list_id);
        $optionKey  = sprintf('%s:%s:%s', $modelName, $this->getId(), $this->getAction()->getId());
        $customerId = (int)customer()->getId();
        $optionKey  = sprintf('system.views.grid_view_columns.customers.%d.%s', $customerId, $optionKey);

        $storedToggleColumns      = (array)options()->get($optionKey, []);
        $storedToggleColumnsEmpty = empty($storedToggleColumns);
        $displayToggleColumns     = [];
        //

        // now, we need to know what columns this list has, that is, all the tags available for this list.
        $columns = [];
        $rows = [];

        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', $list->list_id);
        $criteria->order = 't.sort_order ASC';

        $fields = ListField::model()->findAll($criteria);

        $columns[] = [
            'label'     => null,
            'field_type'=> 'checkbox',
            'field_id'  => 'bulk_select',
            'value'     => null,
            'checked'   => false,
            'htmlOptions'   => [],
        ];

        $columns[] = [
            'label'         => t('app', 'Options'),
            'field_type'    => null,
            'field_id'      => null,
            'value'         => null,
            'htmlOptions'   => ['class' => 'empty-options-header options'],
        ];

        $columns[] = [
            'label'     => t('list_subscribers', 'Unique ID'),
            'field_type'=> 'text',
            'field_id'  => 'uid',
            'value'     => isset($postFilter['uid']) ? html_encode((string)$postFilter['uid']) : null,
        ];

        $columns[] = [
            'label'         => t('app', 'Date added'),
            'field_type'    => null,
            'field_id'      => 'date_added',
            'value'         => null,
            'htmlOptions'   => ['class' => 'subscriber-date-added'],
        ];

        $columns[] = [
            'label'         => t('app', 'Ip address'),
            'field_type'    => null,
            'field_id'      => 'ip_address',
            'value'         => null,
            'htmlOptions'   => ['class' => 'subscriber-date-added'],
        ];

        $columns[] = [
            'label'     => t('app', 'Status'),
            'field_type'=> 'select',
            'field_id'  => 'status',
            'value'     => isset($postFilter['status']) ? html_encode((string)$postFilter['status']) : null,
            'options'   => CMap::mergeArray(['' => t('app', 'Choose')], $subscriberStatusesList),
        ];

        foreach ($fields as $field) {
            $columns[] = [
                'label'     => $field->label,
                'field_type'=> 'text',
                'field_id'  => $field->field_id,
                'value'     => isset($postFilter[$field->field_id]) ? html_encode((string)$postFilter[$field->field_id]) : null,
            ];
        }

        // 1.3.8.8
        foreach ($columns as $index => $column) {
            if (empty($column['field_id']) || in_array($column['field_id'], ['bulk_select'])) {
                continue;
            }
            $displayToggleColumns[] = $column;
            if ($storedToggleColumnsEmpty) {
                $storedToggleColumns[] = $column['field_id'];
                continue;
            }
            if (array_search($column['field_id'], $storedToggleColumns) === false) {
                unset($columns[$index]);
                continue;
            }
        }
        //

        /** @var Customer $customer */
        $customer = customer()->getModel();

        // since 1.5.2
        $canSegmentLists = ($customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes');

        foreach ($subscribers as $subscriber) {
            $subscriberRow = ['columns' => []];

            // checkbox
            $subscriberRow['columns'][] = CHtml::checkBox('bulk_select[]', false, ['value' => $subscriber->subscriber_id, 'class' => 'bulk-select']);

            $actions = [];
            $actions[] = CHtml::link(IconHelper::make('fa-user'), ['list_subscribers/profile', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['title' => t('app', 'Profile info'), 'class' => 'btn btn-primary btn-flat btn-xs btn-subscriber-profile-info']);
            $actions[] = CHtml::link(IconHelper::make('envelope'), ['list_subscribers/campaigns', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['title' => t('app', 'Campaigns sent to this subscriber'), 'class' => 'btn btn-primary btn-flat btn-xs']);

            if ($subscriber->getCanBeEdited()) {
                $actions[] = CHtml::link(IconHelper::make('update'), ['list_subscribers/update', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat btn-xs']);
            }

            if ($subscriber->getCanBeUnsubscribed() && $subscriber->getIsConfirmed()) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-log-out'), ['list_subscribers/unsubscribe', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat btn-xs unsubscribe', 'title' => t('app', 'Unsubscribe'), 'data-message' => t('list_subscribers', 'Are you sure you want to unsubscribe this subscriber?')]);
            } elseif ($subscriber->getCanBeConfirmed() && $subscriber->getIsUnconfirmed()) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-log-in'), ['list_subscribers/subscribe', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat subscribe', 'title' => t('list_subscribers', 'Subscribe back'), 'data-message' => t('list_subscribers', 'Are you sure you want to subscribe back this unsubscriber?')]);
            } elseif ($subscriber->getCanBeConfirmed() && $subscriber->getIsUnsubscribed()) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-log-in'), ['list_subscribers/subscribe', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat subscribe', 'title' => t('list_subscribers', 'Confirm subscriber'), 'data-message' => t('list_subscribers', 'Are you sure you want to confirm this subscriber?')]);
            } elseif ($subscriber->getCanBeConfirmed() && $subscriber->getIsUnapproved()) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-log-in'), ['list_subscribers/subscribe', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat subscribe', 'title' => t('list_subscribers', 'Approve subscriber'), 'data-message' => t('list_subscribers', 'Are you sure you want to approve this subscriber?')]);
            } elseif ($subscriber->getCanBeConfirmed() && $subscriber->getIsDisabled()) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-log-in'), ['list_subscribers/subscribe', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat subscribe', 'title' => t('list_subscribers', 'Enable subscriber'), 'data-message' => t('list_subscribers', 'This subscriber has been disabled, are you sure you want to enable it back?')]);
            }

            // since 1.5.3
            $actions[] = CHtml::link(IconHelper::make('export'), ['list_subscribers/profile_export', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['target' => '_blank', 'title' => t('app', 'Export profile info'), 'class' => 'btn btn-primary btn-flat btn-xs btn-export-subscriber-profile-info']);

            // since 1.5.2
            if ($canSegmentLists) {
                $actions[] = CHtml::link(IconHelper::make('fa-envelope-o'), ['list_subscribers/campaign_for_subscriber', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['title' => t('app', 'Create campaign for this subscriber'), 'class' => 'btn btn-primary btn-flat btn-xs']);
            }

            if ($subscriber->getCanBeDisabled()) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-remove'), ['list_subscribers/disable', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat unsubscribe', 'title' => t('list_subscribers', 'Disable subscriber'), 'data-message' => t('list_subscribers', 'Are you sure you want to disable this subscriber?')]);
            }

            // since 2.0.29
            if ($customer->getGroupOption('lists.can_use_own_blacklist', 'no') == 'yes' && !$subscriber->getStatusIs(ListSubscriber::STATUS_BLACKLISTED)) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-ban-circle'), ['list_subscribers/blacklist', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat blacklist', 'title' => t('list_subscribers', 'Blacklist subscriber'), 'data-message' => t('list_subscribers', 'Are you sure you want to blacklist this subscriber?')]);
            }

            // since 2.1.6
            if (!empty($subscriber->ip_address)) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-warning-sign'), ['list_subscribers/blacklist_ip', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat blacklist-ip', 'title' => t('list_subscribers', 'Blacklist IP'), 'data-message' => t('list_subscribers', 'Are you sure you want to blacklist this subscriber\'s IP?')]);
            }

            if ($subscriber->getCanBeDeleted()) {
                $actions[] = CHtml::link(IconHelper::make('glyphicon-remove-circle'), ['list_subscribers/delete', 'list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-danger btn-flat delete', 'title' => t('app', 'Delete'), 'data-message' => t('app', 'Are you sure you want to delete this item? There is no coming back after you do it.')]);
            }

            $subscriberRow['columns'][] = $this->renderPartial('_options-column', compact('actions'), true);

            if (in_array('uid', $storedToggleColumns)) {
                $subscriberRow['columns'][] = CHtml::link($subscriber->subscriber_uid, createUrl('list_subscribers/update', ['list_uid' => $list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid]));
            }
            if (in_array('date_added', $storedToggleColumns)) {
                $subscriberRow['columns'][] = $subscriber->dateTimeFormatter->getDateAdded();
            }
            if (in_array('ip_address', $storedToggleColumns)) {
                $subscriberRow['columns'][] = $subscriber->ip_address;
            }
            if (in_array('status', $storedToggleColumns)) {
                $subscriberRow['columns'][] = $subscriber->getGridViewHtmlStatus();
            }

            foreach ($fields as $field) {
                if (!in_array($field->field_id, $storedToggleColumns)) {
                    continue;
                }

                if ($field->tag == 'EMAIL') {
                    $value = $subscriber->getDisplayEmail();
                    $subscriberRow['columns'][] = html_encode((string)$value);
                    continue;
                }

                $criteria = new CDbCriteria();
                $criteria->select = 't.value';
                $criteria->compare('field_id', $field->field_id);
                $criteria->compare('subscriber_id', $subscriber->subscriber_id);
                $values = ListFieldValue::model()->findAll($criteria);

                $value = [];
                foreach ($values as $val) {
                    $value[] = $val->value;
                }

                $subscriberRow['columns'][] = html_encode((string)implode(', ', $value));
            }

            if (count($subscriberRow['columns']) == count($columns)) {
                $rows[] = $subscriberRow;
            }
        }

        if (request()->getIsPostRequest() && request()->getIsAjaxRequest()) {
            $this->renderPartial('_list', compact('list', 'subscriber', 'columns', 'rows', 'pages', 'count'));
            return;
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_subscribers', 'Your mail list subscribers'),
            'pageHeading'     => t('list_subscribers', 'List subscribers'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_subscribers', 'Subscribers') => createUrl('list_subscribers/index', ['list_uid' => $list->list_uid]),
                t('app', 'View all'),
            ],
        ]);

        $subBulkFromSource = new ListSubscriberBulkFromSource();
        $subBulkFromSource->list_id = (int)$list->list_id;

        $this->render('index', compact('list', 'subscriber', 'columns', 'rows', 'pages', 'count', 'subBulkFromSource', 'getFilter', 'getFilterSet', 'listCampaigns', 'displayToggleColumns'));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionCreate($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $listFields = ListField::model()->findAll([
            'condition' => 'list_id = :lid',
            'params'    => [':lid' => $list->list_id],
            'order'     => 'sort_order ASC',
        ]);

        if (empty($listFields)) {
            throw new CHttpException(404, t('list_fields', 'Your mail list does not have any field defined.'));
        }

        $usedTypes = [];
        foreach ($listFields as $field) {
            $usedTypes[] = $field->type->type_id;
        }
        $criteria = new CDbCriteria();
        $criteria->addInCondition('type_id', $usedTypes);

        /** @var ListFieldType[] $types */
        $types = ListFieldType::model()->findAll($criteria);

        $subscriber = new ListSubscriber();
        $subscriber->list_id = (int)$list->list_id;

        /** @var ListFieldBuilderType[] $instances */
        $instances = [];

        /** @var CWebApplication $app */
        $app = app();

        foreach ($types as $type) {
            if (empty($type->identifier) || !is_file((string)Yii::getPathOfAlias($type->class_alias) . '.php')) {
                continue;
            }

            $component = $app->getWidgetFactory()->createWidget($this, $type->class_alias, [
                'fieldType'     => $type,
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
            $transaction = db()->beginTransaction();

            try {
                $customer                = $list->customer;
                $maxSubscribersPerList   = (int)$customer->getGroupOption('lists.max_subscribers_per_list', -1);
                $maxSubscribers          = (int)$customer->getGroupOption('lists.max_subscribers', -1);

                if ($maxSubscribers > -1 || $maxSubscribersPerList > -1) {
                    $criteria = new CDbCriteria();
                    $criteria->select = 'COUNT(DISTINCT(t.email)) as counter';

                    if ($maxSubscribers > -1 && ($listsIds = $customer->getAllListsIds())) {
                        $criteria->addInCondition('t.list_id', $listsIds);
                        $totalSubscribersCount = ListSubscriber::model()->count($criteria);
                        if ($totalSubscribersCount >= $maxSubscribers) {
                            throw new Exception(t('lists', 'You have reached the maximum number of allowed subscribers.'));
                        }
                    }

                    if ($maxSubscribersPerList > -1) {
                        $criteria->compare('t.list_id', (int)$list->list_id);
                        $listSubscribersCount = ListSubscriber::model()->count($criteria);
                        if ($listSubscribersCount >= $maxSubscribersPerList) {
                            throw new Exception(t('lists', 'You have reached the maximum number of allowed subscribers into this list.'));
                        }
                    }
                }

                $attributes = (array)request()->getPost($subscriber->getModelName(), []);
                if (empty($subscriber->ip_address)) {
                    $subscriber->ip_address = (string)request()->getUserHostAddress();
                }
                if (isset($attributes['status']) && in_array($attributes['status'], array_keys($subscriber->getStatusesList()))) {
                    $subscriber->status = (string)$attributes['status'];
                } else {
                    $subscriber->status = ListSubscriber::STATUS_UNCONFIRMED;
                }

                if (!$subscriber->save()) {
                    if ($subscriber->hasErrors()) {
                        throw new Exception($subscriber->shortErrors->getAllAsString());
                    }
                    throw new Exception(t('app', 'Temporary error, please contact us if this happens too often!'));
                }

                // raise event
                $this->callbacks->onSubscriberSave(new CEvent($this->callbacks, [
                    'fields' => &$fields,
                ]));

                // if no error thrown but still there are errors in any of the instances, stop.
                foreach ($instances as $instance) {
                    if (!empty($instance->errors)) {
                        throw new Exception(t('app', 'Your form has a few errors. Please fix them and try again!'));
                    }
                }

                // add the default success message
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                // raise event. at this point everything seems to be fine.
                $this->callbacks->onSubscriberSaveSuccess(new CEvent($this->callbacks, [
                    'instances'     => $instances,
                    'subscriber'    => $subscriber,
                    'list'          => $list,
                ]));

                // since 1.9.5
                if ($subscriber->getIsConfirmed()) {
                    $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_SUBSCRIBE);
                } elseif ($subscriber->getIsUnsubscribed()) {
                    $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_UNSUBSCRIBE);
                }

                $transaction->commit();
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
                ]));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'   => $this,
                'success'      => notify()->getHasSuccess(),
                'subscriber'   => $subscriber,
            ]));

            if ($collection->itemAt('success')) {
                if (request()->getPost('next_action') && request()->getPost('next_action') == 'create-new') {
                    $this->redirect(['list_subscribers/create', 'list_uid' => $subscriber->list->list_uid]);
                }
                $this->redirect(['list_subscribers/update', 'list_uid' => $subscriber->list->list_uid, 'subscriber_uid' => $subscriber->subscriber_uid]);
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

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_subscribers', 'Add a new subscriber to your list.'),
            'pageHeading'     => t('list_subscribers', 'Add a new subscriber to your list.'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_subscribers', 'Subscribers') => createUrl('list_subscribers/index', ['list_uid' => $list->list_uid]),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('fieldsHtml', 'list', 'subscriber'));
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);

        if ($list->customer->getGroupOption('lists.can_edit_own_subscribers', 'yes') != 'yes') {
            notify()->addError(t('list_subscribers', 'You are not allowed to edit subscribers at this time!'));
            $this->redirect(['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }

        $listFields = ListField::model()->findAll([
            'condition' => 'list_id = :lid',
            'params'    => [':lid' => $list->list_id],
            'order'     => 'sort_order ASC',
        ]);

        if (empty($listFields)) {
            throw new CHttpException(404, t('list', 'Your mail list does not have any field defined.'));
        }

        $usedTypes = [];
        foreach ($listFields as $field) {
            $usedTypes[] = $field->type->type_id;
        }
        $criteria = new CDbCriteria();
        $criteria->addInCondition('type_id', $usedTypes);

        /** @var ListFieldType[] $types */
        $types = ListFieldType::model()->findAll($criteria);

        /** @var ListFieldBuilderType[] $instances */
        $instances = [];

        /** @var CWebApplication $app */
        $app = app();

        foreach ($types as $type) {
            if (empty($type->identifier) || !is_file((string)Yii::getPathOfAlias($type->class_alias) . '.php')) {
                continue;
            }

            $component = $app->getWidgetFactory()->createWidget($this, $type->class_alias, [
                'fieldType'     => $type,
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

            /** @var Customer $customer */
            $customer = customer()->getModel();

            $transaction = db()->beginTransaction();

            try {
                $attributes = (array)request()->getPost($subscriber->getModelName(), []);
                if (empty($subscriber->ip_address)) {
                    $subscriber->ip_address = (string)request()->getUserHostAddress();
                }
                if (isset($attributes['status']) && in_array($attributes['status'], array_keys($subscriber->getStatusesList()))) {
                    $subscriber->status = (string)$attributes['status'];
                } else {
                    $subscriber->status = ListSubscriber::STATUS_UNCONFIRMED;
                }

                // since 1.3.5
                if ($subscriber->status == ListSubscriber::STATUS_CONFIRMED) {
                    if ($customer->getGroupOption('lists.can_mark_blacklisted_as_confirmed', 'yes') === 'yes') {

                        // global blacklist and customer blacklist
                        $subscriber->removeFromBlacklistByEmail();
                    } else {

                        // only customer blacklist
                        CustomerEmailBlacklist::model()->deleteAllByAttributes([
                            'customer_id' => $subscriber->list->customer_id,
                            'email'       => $subscriber->email,
                        ]);
                    }
                }

                if (!$subscriber->save()) {
                    if ($subscriber->hasErrors()) {
                        throw new Exception($subscriber->shortErrors->getAllAsString());
                    }
                    throw new Exception(t('app', 'Temporary error, please contact us if this happens too often!'));
                }

                // raise event
                $this->callbacks->onSubscriberSave(new CEvent($this->callbacks, [
                    'fields' => &$fields,
                ]));

                // if no error thrown but still there are errors in any of the instances, stop.
                foreach ($instances as $instance) {
                    if (!empty($instance->errors)) {
                        throw new Exception(t('app', 'Your form has a few errors. Please fix them and try again!'));
                    }
                }

                // add the default success message
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));

                // raise event. at this point everything seems to be fine.
                $this->callbacks->onSubscriberSaveSuccess(new CEvent($this->callbacks, [
                    'instances'     => $instances,
                    'subscriber'    => $subscriber,
                    'list'          => $list,
                ]));

                // since 1.9.5
                if ($subscriber->getIsConfirmed()) {
                    $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_SUBSCRIBE);
                } elseif ($subscriber->getIsUnsubscribed()) {
                    $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_UNSUBSCRIBE);
                }

                $transaction->commit();
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
                ]));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'   => $this,
                'success'      => notify()->getHasSuccess(),
                'subscriber'   => $subscriber,
            ]));

            if ($collection->itemAt('success')) {
                if (request()->getPost('next_action') && request()->getPost('next_action') == 'create-new') {
                    $this->redirect(['list_subscribers/create', 'list_uid' => $subscriber->list->list_uid]);
                }
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

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_subscribers', 'Update existing list subscriber.'),
            'pageHeading'     => t('list_subscribers', 'Update existing list subscriber.'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_subscribers', 'Subscribers') => createUrl('list_subscribers/index', ['list_uid' => $list->list_uid]),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('fieldsHtml', 'list', 'subscriber'));
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCampaigns($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);

        $model = new CampaignDeliveryLog('search');
        $model->campaign_id   = -1;
        $model->subscriber_id = (int)$subscriber->subscriber_id;
        $model->status        = null;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('list_subscribers', 'Subscriber campaigns'),
            'pageHeading'     => t('list_subscribers', 'Subscriber campaigns'),
            'pageBreadcrumbs' => [
                t('lists', 'Lists') => createUrl('lists/index'),
                $list->name . ' ' => createUrl('lists/overview', ['list_uid' => $list->list_uid]),
                t('list_subscribers', 'Subscribers') => createUrl('list_subscribers/index', ['list_uid' => $list->list_uid]),
                t('list_subscribers', 'Campaigns') => createUrl('list_subscribers/campaigns', ['list_uid' => $list_uid, 'subscriber_uid' => $subscriber_uid]),
                t('app', 'View all'),
            ],
        ]);

        $this->render('campaigns', compact('model', 'list', 'subscriber'));
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCampaigns_export($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber  = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);
        $inCampaigns = [];

        $logs = CampaignDeliveryLog::model()->findAllByAttributes([
            'subscriber_id' => $subscriber->subscriber_id,
        ]);
        foreach ($logs as $log) {
            $inCampaigns[] = (int)$log->campaign_id;
        }

        $logs = CampaignDeliveryLogArchive::model()->findAllByAttributes([
            'subscriber_id' => $subscriber->subscriber_id,
        ]);
        foreach ($logs as $log) {
            $inCampaigns[] = (int)$log->campaign_id;
        }

        $inCampaigns = array_unique($inCampaigns);
        if (empty($inCampaigns)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        $criteria = new CDbCriteria();
        $criteria->addInCondition('campaign_id', $inCampaigns);

        $models = Campaign::model()->findAll($criteria);
        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('campaigns.csv');

        $attributes = AttributeHelper::removeSpecialAttributes($models[0]->attributes);

        /** @var callable $callback */
        $callback   = [$models[0], 'getAttributeLabel'];
        $attributes = array_map($callback, array_keys($attributes));

        $attributes = CMap::mergeArray($attributes, [
            $models[0]->getAttributeLabel('group_id'),
            $models[0]->getAttributeLabel('list_id'),
            $models[0]->getAttributeLabel('segment_id'),
        ]);

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertOne($attributes);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->attributes);
                $attributes = CMap::mergeArray($attributes, [
                    $model->group_id ? $model->group->name : '',
                    $model->list_id ? $model->list->name : '',
                    $model->segment_id ? $model->segment->name : '',
                ]);
                $csvWriter->insertOne($attributes);
            }
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * Create a campaign for this subscriber only
     *
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     */
    public function actionCampaign_for_subscriber($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (!($customer->getGroupOption('lists.can_segment_lists', 'yes') == 'yes')) {
            $this->redirect(['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }

        $segment = new ListSegment();
        $segment->list_id        = (int)$list->list_id;
        $segment->name           = $subscriber->email . ' @ ' . dateFormatter()->formatDateTime(time());
        $segment->operator_match = ListSegment::OPERATOR_MATCH_ALL;
        if (!$segment->save()) {
            notify()->addError(t('list_subscribers', 'Unable to create campaign for subscriber!'));
            $this->redirect(['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }

        $operator = ListSegmentOperator::model()->findByAttributes([
            'slug' => ListSegmentOperator::IS,
        ]);
        if (empty($operator)) {
            notify()->addError(t('list_subscribers', 'Unable to create campaign for subscriber!'));
            $this->redirect(['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }

        $field = ListField::model()->findByAttributes([
            'list_id' => $list->list_id,
            'tag'     => 'EMAIL',
        ]);
        if (empty($field)) {
            notify()->addError(t('list_subscribers', 'Unable to create campaign for subscriber!'));
            $this->redirect(['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }

        $condition = new ListSegmentCondition();
        $condition->segment_id  = (int)$segment->segment_id;
        $condition->operator_id = (int)$operator->operator_id;
        $condition->field_id    = (int)$field->field_id;
        $condition->value       = $subscriber->email;

        if (!$condition->save()) {
            notify()->addError(t('list_subscribers', 'Unable to create campaign for subscriber!'));
            $this->redirect(['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }

        $campaign = new Campaign();
        $campaign->customer_id = (int)$list->customer_id;
        $campaign->name        = t('campaigns', 'Send only to {name}', ['{name}' => $subscriber->email]);
        $campaign->list_id     = (int)$list->list_id;
        $campaign->segment_id  = (int)$segment->segment_id;

        if (!$campaign->save(false)) {
            notify()->addError(t('list_subscribers', 'Unable to create campaign for subscriber!'));
            $this->redirect(['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }

        $this->redirect(['campaigns/update', 'campaign_uid' => $campaign->campaign_uid]);
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);

        if ($subscriber->getCanBeDeleted()) {
            $subscriber->delete();

            /** @var Customer $customer */
            $customer = customer()->getModel();

            /** @var CustomerActionLogBehavior $logAction */
            $logAction = $customer->getLogAction();
            $logAction->subscriberDeleted($subscriber);
        }

        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('list_subscribers', 'Your list subscriber was successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'list'       => $list,
            'subscriber' => $subscriber,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionDisable($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);

        if ($subscriber->getCanBeDisabled()) {
            $subscriber->saveStatus(ListSubscriber::STATUS_DISABLED);
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('list_subscribers', 'Your list subscriber was successfully disabled!'));
            $this->redirect(request()->getPost('returnUrl', ['list_subscribers/index', 'list_uid' => $list->list_uid]));
        }
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionUnsubscribe($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);

        if ($subscriber->getCanBeUnsubscribed()) {
            $subscriber->saveStatus(ListSubscriber::STATUS_UNSUBSCRIBED);

            // since 1.9.5
            $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_UNSUBSCRIBE);
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('list_subscribers', 'Your list subscriber was successfully unsubscribed!'));
            $this->redirect(request()->getPost('returnUrl', ['list_subscribers/index', 'list_uid' => $list->list_uid]));
        }
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws Throwable
     */
    public function actionSubscribe($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);
        $oldStatus  = $subscriber->status;

        if ($subscriber->getCanBeApproved()) {
            $subscriber->saveStatus(ListSubscriber::STATUS_CONFIRMED);
            $subscriber->handleApprove(true)->handleWelcome(true);
        } elseif ($subscriber->getCanBeConfirmed()) {
            $subscriber->saveStatus(ListSubscriber::STATUS_CONFIRMED);
            // since 1.9.5
            $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_SUBSCRIBE);
        }

        if (!request()->getIsAjaxRequest()) {
            if ($oldStatus == ListSubscriber::STATUS_UNSUBSCRIBED) {
                notify()->addSuccess(t('list_subscribers', 'Your list unsubscriber was successfully subscribed back!'));
            } elseif ($oldStatus == ListSubscriber::STATUS_UNAPPROVED) {
                notify()->addSuccess(t('list_subscribers', 'Your list subscriber has been approved and notified!'));
            } else {
                notify()->addSuccess(t('list_subscribers', 'Your list subscriber has been confirmed!'));
            }
            $this->redirect(request()->getPost('returnUrl', ['list_subscribers/index', 'list_uid' => $list->list_uid]));
        }
    }


    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionBlacklist($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $customer = $list->customer;

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);

        $canBlacklist =
            $customer->getGroupOption('lists.can_use_own_blacklist', 'no') == 'yes' &&
            !$subscriber->getStatusIs(ListSubscriber::STATUS_BLACKLISTED);

        if (!$canBlacklist) {
            notify()->addError(t('list_subscribers', 'You are not allowed to blacklist this subscriber!'));
            $this->redirect(request()->getPost('returnUrl', ['list_subscribers/index', 'list_uid' => $list->list_uid]));
        }

        $blacklist = CustomerEmailBlacklist::findByEmailWithCustomerId((string)$subscriber->email, (int)$customer->customer_id);
        if (!empty($blacklist)) {
            notify()->addError(t('list_subscribers', 'This subscriber is already blacklisted!'));
            $this->redirect(request()->getPost('returnUrl', ['list_subscribers/index', 'list_uid' => $list->list_uid]));
        }

        $transaction = db()->beginTransaction();
        $success = false;
        try {
            if ($subscriber->addToCustomerBlacklist(t('list_subscribers', 'Manually blacklisted'))) {
                $subscriber->saveStatus(ListSubscriber::STATUS_BLACKLISTED);
            }

            $transaction->commit();
            $success = true;
        } catch (Exception $e) {
            $transaction->rollback();
        }

        if ($success) {
            Lists::flushSubscribersCountCacheBySubscriberIds([$subscriber->subscriber_id]);
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('list_subscribers', 'Your list subscriber was successfully blacklisted!'));
            $this->redirect(request()->getPost('returnUrl', ['list_subscribers/index', 'list_uid' => $list->list_uid]));
        }
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CHttpException
     * @throws CException
     */
    public function actionBlacklist_ip($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);

        if (empty($subscriber->ip_address)) {
            notify()->addError(t('list_subscribers', 'This subscriber does not have a valid IP address!'));
            return;
        }

        $customer = $list->customer;

        $ip = CustomerIpBlacklist::findByIpWithCustomerId((string)$subscriber->ip_address, (int)$customer->customer_id);
        if (!empty($ip)) {
            notify()->addError(t('list_subscribers', 'This IP address is already blacklisted!'));
            return;
        }

        if (!$subscriber->blacklistIp()) {
            notify()->addError(t('list_subscribers', 'The IP address cannot be saved!'));
            return;
        }

        notify()->addSuccess(t('list_subscribers', 'The IP address({ip_address}) was successfully blacklisted!', ['{ip_address}' => $subscriber->ip_address]));
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws Throwable
     */
    public function actionBulk_action($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $subscriber = new ListSubscriber();
        $action     = request()->getPost('action');

        if (!in_array($action, array_keys($subscriber->getBulkActionsList()))) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        /** @var array $selectedSubscribers */
        $selectedSubscribers = (array)request()->getPost('bulk_select', []);
        $selectedSubscribers = array_values($selectedSubscribers);
        $selectedSubscribers = array_map('intval', $selectedSubscribers);

        // since 1.3.5.9
        $redirect = null;
        if (!request()->getIsAjaxRequest()) {
            $redirect = request()->getPost('returnUrl', ['list_subscribers/index', 'list_uid' => $list->list_uid]);
        }
        hooks()->doAction('controller_action_bulk_action', $collection = new CAttributeCollection([
            'controller' => $this,
            'redirect'   => $redirect,
            'list'       => $list,
            'action'     => $action,
            'data'       => $selectedSubscribers,
        ]));
        /** @var array $selectedSubscribers */
        $selectedSubscribers = (array)$collection->itemAt('data');
        //

        if (!empty($selectedSubscribers)) {
            $criteria = new CDbCriteria();
            $criteria->compare('list_id', (int)$list->list_id);
            $criteria->addInCondition('subscriber_id', $selectedSubscribers);

            if ($action == ListSubscriber::BULK_SUBSCRIBE) {
                $statusNotIn          = [ListSubscriber::STATUS_CONFIRMED];
                $canMarkBlAsConfirmed = $customer->getGroupOption('lists.can_mark_blacklisted_as_confirmed', 'no') === 'yes';

                $criteria->addNotInCondition('status', $statusNotIn);
                $subscribers = ListSubscriber::model()->findAll($criteria);

                foreach ($subscribers as $subscriber) {

                    // save the flag here
                    $approve    = $subscriber->getIsUnapproved();
                    $initStatus = $subscriber->status;

                    // confirm the subscriber
                    $subscriber->saveStatus(ListSubscriber::STATUS_CONFIRMED);

                    // and if the above flag is bool, proceed with approval stuff
                    if ($approve) {
                        $subscriber->handleApprove(true)->handleWelcome(true);
                    }

                    // finally remove from blacklist
                    if ($initStatus == ListSubscriber::STATUS_BLACKLISTED) {
                        if ($canMarkBlAsConfirmed) {

                            // global blacklist and customer blacklist
                            $subscriber->removeFromBlacklistByEmail();
                        } else {

                            // only customer blacklist
                            CustomerEmailBlacklist::model()->deleteAllByAttributes([
                                'customer_id' => $subscriber->list->customer_id,
                                'email'       => $subscriber->email,
                            ]);
                        }
                    }

                    // 1.3.8.8 - remove from moved table
                    ListSubscriberListMove::model()->deleteAllByAttributes([
                        'source_subscriber_id' => $subscriber->subscriber_id,
                    ]);
                }
            } elseif ($action == ListSubscriber::BULK_UNSUBSCRIBE) {
                $criteria->addNotInCondition('status', [ListSubscriber::STATUS_BLACKLISTED, ListSubscriber::STATUS_MOVED]);

                ListSubscriber::model()->updateAll([
                    'status'        => ListSubscriber::STATUS_UNSUBSCRIBED,
                    'last_updated'  => MW_DATETIME_NOW,
                ], $criteria);
            } elseif ($action == ListSubscriber::BULK_DISABLE) {
                $criteria->addInCondition('status', [ListSubscriber::STATUS_CONFIRMED]);

                ListSubscriber::model()->updateAll([
                    'status'        => ListSubscriber::STATUS_DISABLED,
                    'last_updated'  => MW_DATETIME_NOW,
                ], $criteria);
            } elseif ($action == ListSubscriber::BULK_UNCONFIRM) {
                $criteria->addInCondition('status', [ListSubscriber::STATUS_CONFIRMED]);

                ListSubscriber::model()->updateAll([
                    'status'        => ListSubscriber::STATUS_UNCONFIRMED,
                    'last_updated'  => MW_DATETIME_NOW,
                ], $criteria);
            } elseif ($action == ListSubscriber::BULK_RESEND_CONFIRMATION_EMAIL) {
                $criteria->addInCondition('status', [ListSubscriber::STATUS_UNCONFIRMED]);
                $subscribers = ListSubscriber::model()->findAll($criteria);

                foreach ($subscribers as $subscriber) {
                    $pageType = ListPageType::model()->findBySlug('subscribe-confirm-email');
                    if (empty($pageType)) {
                        continue;
                    }

                    $page = ListPage::model()->findByAttributes([
                        'list_id' => $subscriber->list_id,
                        'type_id' => $pageType->type_id,
                    ]);

                    $content = !empty($page->content) ? $page->content : $pageType->content;
                    $subject = !empty($page->email_subject) ? $page->email_subject : $pageType->email_subject;
                    $list    = $subscriber->list;

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

                    // since 1.5.2
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

                    $email = new TransactionalEmail();
                    $email->customer_id = (int)$customer->customer_id;
                    $email->to_name     = $subscriber->email;
                    $email->to_email    = $subscriber->email;
                    $email->from_name   = $list->default->from_name;
                    $email->subject     = $subject;
                    $email->body        = $content;
                    $email->save();
                }
            } elseif ($action == ListSubscriber::BULK_DELETE) {
                ListSubscriber::model()->deleteAll($criteria);
            } elseif ($action == ListSubscriber::BULK_BLACKLIST_IP) {
                $criteria->addCondition('ip_address IS NOT NULL AND ip_address != ""');
                $subscribers = ListSubscriber::model()->findAll($criteria);

                foreach ($subscribers as $subscriber) {
                    $subscriber->blacklistIp();
                }
            }

            // since 1.6.4
            $list->flushSubscribersCountCache();
        }

        if (!request()->getIsAjaxRequest()) {
            notify()->addSuccess(t('app', 'Bulk action completed successfully!'));
        }

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * @param string $list_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws Throwable
     */
    public function actionBulk_from_source($list_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        $model      = new ListSubscriberBulkFromSource();
        $redirect   = ['list_subscribers/index', 'list_uid' => $list_uid];

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $emailAddresses    = [];
        $model->attributes = (array)request()->getPost($model->getModelName(), []);

        if (!in_array($model->status, array_keys($model->getBulkActionsList()))) {
            $this->redirect($redirect);
        }

        if (!empty($model->bulk_from_text)) {
            $lines = explode("\n", $model->bulk_from_text);
            foreach ($lines as $line) {
                $emails = explode(',', $line);
                $emails = array_map('trim', $emails);
                foreach ($emails as $email) {
                    if (FilterVarHelper::email($email)) {
                        $emailAddresses[] = $email;
                    }
                }
            }
        }
        $emailAddresses = array_unique($emailAddresses);

        $model->bulk_from_file = CUploadedFile::getInstance($model, 'bulk_from_file');
        if (!empty($model->bulk_from_file)) {
            if (!$model->validate()) {
                notify()->addError($model->shortErrors->getAllAsString());
            } else {
                $csvReader = League\Csv\Reader::createFromPath($model->bulk_from_file->tempName, 'r');
                $csvReader->setDelimiter(StringHelper::detectCsvDelimiter($model->bulk_from_file->tempName));
                /** @var array $row */
                foreach ($csvReader->getRecords() as $row) {
                    $row = (array)ioFilter()->stripPurify($row);
                    foreach ($row as $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $emails = explode(',', (string)$value);
                        $emails = array_map('trim', $emails);
                        foreach ($emails as $email) {
                            if (FilterVarHelper::email($email)) {
                                $emailAddresses[] = $email;
                            }
                        }
                    }
                }
            }
        }
        $emailAddresses = array_unique($emailAddresses);

        $total = 0;
        while (!empty($emailAddresses)) {
            $emails = array_splice($emailAddresses, 0, 10);

            $criteria = new CDbCriteria();
            $criteria->compare('list_id', (int)$list->list_id);
            $criteria->addInCondition('email', $emails);

            if ($model->status == ListSubscriber::BULK_SUBSCRIBE) {
                $statusNotIn          = [ListSubscriber::STATUS_CONFIRMED];
                $canMarkBlAsConfirmed = $customer->getGroupOption('lists.can_mark_blacklisted_as_confirmed', 'no') === 'yes';

                $criteria->addNotInCondition('status', $statusNotIn);
                $subscribers = ListSubscriber::model()->findAll($criteria);

                foreach ($subscribers as $subscriber) {

                    // save the flag here
                    $approve    = $subscriber->getIsUnapproved();
                    $initStatus = $subscriber->status;

                    // confirm the subscriber
                    $subscriber->saveStatus(ListSubscriber::STATUS_CONFIRMED);

                    // and if the above flag is bool, proceed with approval stuff
                    if ($approve) {
                        $subscriber->handleApprove(true)->handleWelcome(true);
                    }

                    // finally remove from blacklist
                    if ($initStatus == ListSubscriber::STATUS_BLACKLISTED) {
                        if ($canMarkBlAsConfirmed) {

                            // global blacklist and customer blacklist
                            $subscriber->removeFromBlacklistByEmail();
                        } else {

                            // only customer blacklist
                            CustomerEmailBlacklist::model()->deleteAllByAttributes([
                                'customer_id' => $subscriber->list->customer_id,
                                'email'       => $subscriber->email,
                            ]);
                        }
                    }

                    // 1.3.8.8 - remove from moved table
                    ListSubscriberListMove::model()->deleteAllByAttributes([
                        'source_subscriber_id' => $subscriber->subscriber_id,
                    ]);
                }
            } elseif ($model->status == ListSubscriber::BULK_UNSUBSCRIBE) {
                $criteria->addNotInCondition('status', [ListSubscriber::STATUS_BLACKLISTED, ListSubscriber::STATUS_MOVED]);

                ListSubscriber::model()->updateAll([
                    'status'        => ListSubscriber::STATUS_UNSUBSCRIBED,
                    'last_updated'  => MW_DATETIME_NOW,
                ], $criteria);
            } elseif ($model->status == ListSubscriber::BULK_DISABLE) {
                $criteria->addInCondition('status', [ListSubscriber::STATUS_CONFIRMED]);

                ListSubscriber::model()->updateAll([
                    'status' => ListSubscriber::STATUS_DISABLED,
                    'last_updated' => MW_DATETIME_NOW,
                ], $criteria);
            } elseif ($model->status == ListSubscriber::BULK_RESEND_CONFIRMATION_EMAIL) {
                $criteria->addInCondition('status', [ListSubscriber::STATUS_UNCONFIRMED]);
                $subscribers = ListSubscriber::model()->findAll($criteria);

                foreach ($subscribers as $subscriber) {
                    $pageType = ListPageType::model()->findBySlug('subscribe-confirm-email');
                    if (empty($pageType)) {
                        continue;
                    }

                    $page = ListPage::model()->findByAttributes([
                        'list_id' => $subscriber->list_id,
                        'type_id' => $pageType->type_id,
                    ]);

                    $content = !empty($page->content) ? $page->content : $pageType->content;
                    $subject = !empty($page->email_subject) ? $page->email_subject : $pageType->email_subject;
                    $list    = $subscriber->list;

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

                    // since 1.5.2
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

                    $email = new TransactionalEmail();
                    $email->customer_id = (int)$customer->customer_id;
                    $email->to_name     = $subscriber->email;
                    $email->to_email    = $subscriber->email;
                    $email->from_name   = $list->default->from_name;
                    $email->subject     = $subject;
                    $email->body        = $content;
                    $email->save();
                }
            } elseif ($model->status == ListSubscriber::BULK_UNCONFIRM) {
                $criteria->addInCondition('status', [ListSubscriber::STATUS_CONFIRMED]);

                ListSubscriber::model()->updateAll([
                    'status'        => ListSubscriber::STATUS_UNCONFIRMED,
                    'last_updated'  => MW_DATETIME_NOW,
                ], $criteria);
            } elseif ($model->status == ListSubscriber::BULK_DELETE) {
                ListSubscriber::model()->deleteAll($criteria);
            }

            $total += count($emails);
        }
        notify()->addSuccess(t('list_subscribers', 'Action completed, {count} subscribers were affected!', [
            '{count}'   => $total,
        ]));

        // since 1.6.4
        $list->flushSubscribersCountCache();

        $this->redirect($redirect);
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     */
    public function actionProfile($list_uid, $subscriber_uid)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['lists/all_subscribers']);
        }

        $list = Lists::model()->findByAttributes([
            'list_uid' => (string)$list_uid,
        ]);

        if (empty($list)) {
            return;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = ListSubscriber::model()->findByAttributes([
            'list_id'        => (int)$list->list_id,
            'subscriber_uid' => (string)$subscriber_uid,
        ]);

        if (empty($subscriber)) {
            return;
        }

        $this->renderPartial('_profile-in-modal', [
            'list'          => $list,
            'subscriber'    => $subscriber,
            'subscriberName'=> $subscriber->getFullName(),
            'optinHistory'  => !empty($subscriber->optinHistory) ? $subscriber->optinHistory : null,
            'optoutHistory' => $subscriber->status == ListSubscriber::STATUS_UNSUBSCRIBED && !empty($subscriber->optoutHistory) ? $subscriber->optoutHistory : null,
        ]);
    }

    /**
     * @param string $list_uid
     * @param string $subscriber_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionProfile_export($list_uid, $subscriber_uid)
    {
        /** @var Lists $list */
        $list = $this->loadListModel((string)$list_uid);

        /** @var ListSubscriber $subscriber */
        $subscriber  = $this->loadSubscriberModel((int)$list->list_id, (string)$subscriber_uid);
        $data        = $subscriber->getFullData();

        // Set the download headers
        HeaderHelper::setDownloadHeaders('subscriber-profile.csv');

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertOne(array_keys($data));
            $csvWriter->insertOne(array_values($data));
        } catch (Exception $e) {
        }

        app()->end();
    }

    /**
     * @param string $list_uid
     *
     * @return Lists
     * @throws CHttpException
     */
    public function loadListModel(string $list_uid): Lists
    {
        $model = Lists::model()->findByAttributes([
            'list_uid'      => $list_uid,
            'customer_id'   => (int)customer()->getId(),
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }

    /**
     * @param int $list_id
     * @param string $subscriber_uid
     *
     * @return ListSubscriber
     * @throws CHttpException
     */
    public function loadSubscriberModel(int $list_id, string $subscriber_uid): ListSubscriber
    {
        $model = ListSubscriber::model()->findByAttributes([
            'subscriber_uid'    => $subscriber_uid,
            'list_id'           => (int)$list_id,
        ]);

        if ($model === null) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
