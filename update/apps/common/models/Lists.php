<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Lists
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list".
 *
 * The followings are the available columns in table 'list':
 * @property integer|null $list_id
 * @property integer|string $customer_id
 * @property string $list_uid
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property string $visibility
 * @property string $opt_in
 * @property string $opt_out
 * @property string $welcome_email
 * @property string $removable
 * @property string $subscriber_require_approval
 * @property string $subscriber_404_redirect
 * @property string $subscriber_exists_redirect
 * @property string $meta_data
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign[] $campaigns
 * @property int $campaignsCount
 * @property CampaignOpenActionListField[] $campaignOpenActionListFields
 * @property CampaignSentActionListField[] $campaignSentActionListFields
 * @property CampaignOpenActionSubscriber[] $campaignOpenActionSubscribers
 * @property CampaignSentActionSubscriber[] $campaignSentActionSubscribers
 * @property CampaignTemplateUrlActionListField[] $campaignTemplateUrlActionListFields
 * @property CampaignTemplateUrlActionSubscriber[] $campaignTemplateUrlActionSubscribers
 * @property Customer $customer
 * @property ListCompany $company
 * @property ListCustomerNotification $customerNotification
 * @property ListDefault $default
 * @property ListField[] $fields
 * @property int $fieldsCount
 * @property ListPageType[] $pageTypes
 * @property int $pageTypesCount
 * @property ListSegment[] $segments
 * @property int $segmentsCount
 * @property ListSubscriber[] $subscribers
 * @property int $subscribersCount
 * @property int $confirmedSubscribers
 * @property int $confirmedSubscribersCount
 * @property ListSubscriberAction[] $subscriberSourceActions
 * @property ListSubscriberAction[] $subscriberTargetActions
 * @property ListUrlImport[] $urlImports
 * @property ListOpenGraph $openGraph
 *
 * @property int $activeSegmentsCount
 */
class Lists extends ActiveRecord
{
    /**
     * Visibility flags
     */
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';

    /**
     * Opt in/out flags
     */
    const OPT_IN_SINGLE = 'single';
    const OPT_IN_DOUBLE = 'double';
    const OPT_OUT_SINGLE = 'single';
    const OPT_OUT_DOUBLE = 'double';

    /**
     * Status flags
     */
    const STATUS_PENDING_DELETE = 'pending-delete';
    const STATUS_ARCHIVED = 'archived';

    /**
     * @var array
     */
    public $copyListFieldsMap = [];

    /**
     * Used for search
     *
     * @var string
     */
    public $default_from_name;

    /**
     * Used for search
     *
     * @var string
     */
    public $default_from_email;

    /**
     * @var array
     *
     * Whether we are allowed to update the counters
     * This exists because on bulk import we disable the functionality for performance
     * then at the end of the import process, we update the counters again.
     *
     * We use a static variable so that the enable/disable persist in the entire process regardless from where they have been set.
     * This helps for when we access $subscriber->list for the first time when we previously have set the cache to false/true
     */
    protected static $_subscribersCountCacheEnabled = [];

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, description, opt_in, opt_out', 'required'],

            ['name, display_name, description', 'length', 'min' => 2, 'max' => 255],
            ['visibility', 'in', 'range' => [self::VISIBILITY_PUBLIC, self::VISIBILITY_PRIVATE]],
            ['opt_in', 'in', 'range' => array_keys($this->getOptInArray())],
            ['opt_out', 'in', 'range' => array_keys($this->getOptOutArray())],
            ['welcome_email, subscriber_require_approval', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['subscriber_404_redirect, subscriber_exists_redirect', 'url'],

            ['isSelectAllAtActionWhenSubscribe, isSelectAllAtActionWhenUnsubscribe', 'safe'],

            ['list_uid, customer_id, name, display_name, opt_in, opt_out, status, default_from_name, default_from_email', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaigns' => [self::HAS_MANY, Campaign::class, 'list_id'],
            'campaignsCount' => [self::STAT, Campaign::class, 'list_id'],
            'campaignOpenActionListFields' => [self::HAS_MANY, CampaignOpenActionListField::class, 'list_id'],
            'campaignSentActionListFields' => [self::HAS_MANY, CampaignSentActionListField::class, 'list_id'],
            'campaignOpenActionSubscribers' => [self::HAS_MANY, CampaignOpenActionSubscriber::class, 'list_id'],
            'campaignSentActionSubscribers' => [self::HAS_MANY, CampaignSentActionSubscriber::class, 'list_id'],
            'campaignTemplateUrlActionListFields' => [self::HAS_MANY, CampaignTemplateUrlActionListField::class, 'list_id'],
            'campaignTemplateUrlActionSubscribers' => [self::HAS_MANY, CampaignTemplateUrlActionSubscriber::class, 'list_id'],
            'customer' => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'company' => [self::HAS_ONE, ListCompany::class, 'list_id'],
            'customerNotification' => [self::HAS_ONE, ListCustomerNotification::class, 'list_id'],
            'default' => [self::HAS_ONE, ListDefault::class, 'list_id'],
            'fields' => [self::HAS_MANY, ListField::class, 'list_id', 'order' => 'sort_order ASC'],
            'fieldsCount' => [self::STAT, ListField::class, 'list_id'],
            'pageTypes' => [self::MANY_MANY, ListPageType::class, '{{list_page}}(list_id, type_id)'],
            'pageTypesCount' => [self::STAT, ListPageType::class, '{{list_page}}(list_id, type_id)'],
            'segments' => [self::HAS_MANY, ListSegment::class, 'list_id'],
            'segmentsCount' => [self::STAT, ListSegment::class, 'list_id'],
            'activeSegmentsCount' => [self::STAT, ListSegment::class, 'list_id', 'condition' => 't.status = :s', 'params' => [':s' => ListSegment::STATUS_ACTIVE]],
            'subscribers' => [self::HAS_MANY, ListSubscriber::class, 'list_id'],
            'confirmedSubscribers' => [self::HAS_MANY, ListSubscriber::class, 'list_id', 'condition' => 't.status = :s', 'params' => [':s' => ListSubscriber::STATUS_CONFIRMED]],

            'subscriberSourceActions' => [self::HAS_MANY, ListSubscriberAction::class, 'source_list_id'],
            'subscriberTargetActions' => [self::HAS_MANY, ListSubscriberAction::class, 'target_list_id'],

            'urlImports' => [self::HAS_MANY, ListUrlImport::class, 'list_id'],

            'openGraph' => [self::HAS_ONE, ListOpenGraph::class, 'list_id'],

        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'list_id'       => t('lists', 'ID'),
            'customer_id'   => t('lists', 'Customer'),
            'list_uid'      => t('lists', 'Unique ID'),
            'name'          => t('lists', 'Name'),
            'display_name'  => t('lists', 'Display name'),
            'description'   => t('lists', 'Description'),
            'visibility'    => t('lists', 'Visibility'),
            'opt_in'        => t('lists', 'Opt in'),
            'opt_out'       => t('lists', 'Opt out'),
            'welcome_email' => t('lists', 'Welcome email'),
            'removable'     => t('lists', 'Removable'),
            'subscriber_require_approval' => t('lists', 'Sub. require approval'),
            'subscribers_count'           => t('lists', 'Subscribers count'),
            'subscriber_404_redirect'     => t('lists', 'Sub. not found redirect'),
            'subscriber_exists_redirect'  => t('lists', 'Sub. exists redirect'),
            'meta_data'                   => t('lists', 'Meta data'),

            'default_from_name' => t('lists', 'From name'),
            'default_from_email' => t('lists', 'From email'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     * @throws CException
     */
    public function search()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        if (!empty($this->customer_id)) {
            $customerId = (string)$this->customer_id;
            if (is_numeric($customerId)) {
                $criteria->compare('t.customer_id', $customerId);
            } else {
                $criteria->with['customer'] = [
                    'condition' => 'customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name',
                    'params'    => [':name' => '%' . $customerId . '%'],
                ];
            }
        }

        if (!empty($this->default_from_name) || !empty($this->default_from_email)) {
            $criteria->with['default'] = [
                'together' => true,
                'joinType' => 'INNER JOIN',
            ];
            $criteria->compare('default.from_name', $this->default_from_name, true);
            $criteria->compare('default.from_email', $this->default_from_email, true);
        }

        $criteria->compare('t.list_uid', $this->list_uid);
        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.display_name', $this->display_name, true);
        $criteria->compare('t.opt_in', $this->opt_in);
        $criteria->compare('t.opt_out', $this->opt_out);

        if (empty($this->status)) {
            $criteria->addNotInCondition('t.status', [self::STATUS_PENDING_DELETE, self::STATUS_ARCHIVED]);
        } else {
            $criteria->compare('t.status', $this->status);
        }

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'attributes' => [
                    'list_id',
                    'customer_id',
                    'list_uid',
                    'name',
                    'display_name',
                    'date_added',
                    'last_updated',
                ],
                'defaultOrder'  => [
                    'list_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Lists the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Lists $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return void
     */
    public function afterSave()
    {
        $this->handleListActionsPropagationToTheOtherListActions();
        parent::afterSave();
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'name'                       => t('lists', 'Your mail list verbose name. It will be shown in your customer area sections.'),
            'display_name'               => t('lists', 'Your mail list display name. This name will be used in subscription forms and template tags parsing for campaigns.'),
            'description'                => t('lists', 'Please use an accurate list description, but keep it brief.'),
            'visibility'                 => t('lists', 'Public lists are shown on the website landing page, providing a way of getting new subscribers easily.'),
            'opt_in'                     => t('lists', 'Double opt-in will send a confirmation email while single opt-in will not.'),
            'opt_out'                    => t('lists', 'Double opt-out will send a confirmation email while single opt-out will not.'),
            'welcome_email'              => t('lists', 'Whether the subscriber should receive a welcome email as defined in your list pages.'),
            'subscriber_require_approval'=> t('lists', 'Whether the subscriber must be manually approved in the list.'),
            'subscriber_404_redirect'    => t('lists', 'Optionally, a url to redirect the visitor if the subscriber hasn\'t been found in the list or he isn\'t valid anymore.'),
            'subscriber_exists_redirect' => t('lists', 'Optionally, a url to redirect the visitor at subscription time if the subscriber email already exists in the list. You can use all the common custom tags here.'),

        ];
        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'name'                       => t('lists', 'List name, i.e: Newsletter subscribers.'),
            'description'                => t('lists', 'List detailed description, something your subscribers will easily recognize.'),
            'subscriber_404_redirect'    => 'http://domain.com/subscriber-not-found',
            'subscriber_exists_redirect' => 'http://domain.com/subscriber-exists',
        ];
        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @param string $list_uid
     *
     * @return Lists|null
     */
    public function findByUid(string $list_uid): ?self
    {
        return self::model()->findByAttributes([
            'list_uid' => $list_uid,
        ]);
    }

    /**
     * @return string
     */
    public function generateUid(): string
    {
        $unique = StringHelper::uniqid();
        $exists = $this->findByUid($unique);

        if (!empty($exists)) {
            return $this->generateUid();
        }

        return $unique;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->list_uid;
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_ACTIVE         => ucfirst(t('lists', self::STATUS_ACTIVE)),
            //self::STATUS_PENDING_DELETE => ucfirst(t('lists', self::STATUS_PENDING_DELETE)),
        ];
    }

    /**
     * @return array
     */
    public function getVisibilityOptions(): array
    {
        return [
            ''                          => t('app', 'Choose'),
            self::VISIBILITY_PUBLIC     => t('app', 'Public'),
            self::VISIBILITY_PRIVATE    => t('app', 'Private'),
        ];
    }

    /**
     * @return array
     */
    public function getOptInArray(): array
    {
        return [
            self::OPT_IN_DOUBLE => t('lists', 'Double opt-in'),
            self::OPT_IN_SINGLE => t('lists', 'Single opt-in'),
        ];
    }

    /**
     * @return array
     */
    public function getOptOutArray(): array
    {
        return [
            self::OPT_OUT_DOUBLE => t('lists', 'Double opt-out'),
            self::OPT_OUT_SINGLE => t('lists', 'Single opt-out'),
        ];
    }

    /**
     * @return bool
     */
    public function getCanBeDeleted(): bool
    {
        return $this->getIsRemovable();
    }

    /**
     * @return bool
     */
    public function getIsRemovable(): bool
    {
        if ($this->getIsPendingDelete()) {
            return false;
        }

        if ((string)$this->removable === self::TEXT_NO) {
            return false;
        }

        $removable = true;
        if (!empty($this->customer_id) && !empty($this->customer)) {
            $removable = $this->customer->getGroupOption('lists.can_delete_own_lists', 'yes') == 'yes';
        }
        return $removable;
    }

    /**
     * @return bool
     */
    public function getEditable()
    {
        return $this->getStatusIs(self::STATUS_ACTIVE);
    }

    /**
     * @return bool
     */
    public function getIsPendingDelete(): bool
    {
        return $this->getStatusIs(self::STATUS_PENDING_DELETE);
    }

    /**
     * @return bool
     */
    public function getIsArchived(): bool
    {
        return $this->getStatusIs(self::STATUS_ARCHIVED);
    }

    /**
     * @return string
     */
    public function getSubscribersExportCsvFileName(): string
    {
        return sprintf('list-subscribers-%s.csv', (string)$this->list_uid);
    }

    /**
     * @return Lists|null
     * @throws CException
     */
    public function copy(): ?self
    {
        $copied = null;

        if ($this->getIsNewRecord()) {
            return null;
        }

        $transaction = db()->beginTransaction();

        try {
            $list = clone $this;
            $list->setIsNewRecord(true);
            $list->list_id      = null;
            $list->list_uid     = $this->generateUid();
            $list->removable    = self::TEXT_YES;
            $list->date_added   = MW_DATETIME_NOW;
            $list->last_updated = MW_DATETIME_NOW;

            if (preg_match('/\#(\d+)$/', $list->name, $matches)) {
                $counter = (int)$matches[1];
                $counter++;
                $list->name = (string)preg_replace('/#(\d+)$/', '#' . $counter, $list->name);
            } else {
                $list->name .= ' #1';
            }

            if (!$list->save(false)) {
                throw new CException($list->shortErrors->getAllAsString());
            }

            $listDefault = !empty($this->default) ? clone $this->default : new ListDefault();
            $listDefault->setIsNewRecord(true);
            $listDefault->list_id     = (int)$list->list_id;
            $listDefault->save(false);

            $listCompany = !empty($this->company) ? clone $this->company : new ListCompany();
            $listCompany->setIsNewRecord(true);
            $listCompany->list_id     = (int)$list->list_id;
            $listCompany->save(false);

            $listCustomerNotification = !empty($this->customerNotification) ? clone $this->customerNotification : new ListCustomerNotification();
            $listCustomerNotification->setIsNewRecord(true);
            $listCustomerNotification->list_id = (int)$list->list_id;
            $listCustomerNotification->save(false);

            /** @var ListField[] $fields */
            $fields = !empty($this->fields) ? $this->fields : [];

            foreach ($fields as $field) {
                $oldFieldId = (int)$field->field_id;

                /** @var ListFieldOption[] $fieldOptions */
                $fieldOptions = !empty($field->options) ? $field->options : [];

                $field = clone $field;
                $field->setIsNewRecord(true);
                $field->field_id     = null;
                $field->list_id      = (int)$list->list_id;
                $field->date_added   = MW_DATETIME_NOW;
                $field->last_updated = MW_DATETIME_NOW;
                if (!$field->save(false)) {
                    continue;
                }

                $newFieldId = (int)$field->field_id;
                $this->copyListFieldsMap[$oldFieldId] = $newFieldId;

                foreach ($fieldOptions as $option) {
                    $option = clone $option;
                    $option->setIsNewRecord(true);
                    $option->option_id    = null;
                    $option->field_id     = (int)$field->field_id;
                    $option->date_added   = MW_DATETIME_NOW;
                    $option->last_updated = MW_DATETIME_NOW;
                    $option->save(false);
                }
            }

            $pages = ListPage::model()->findAllByAttributes(['list_id' => $this->list_id]);
            foreach ($pages as $page) {
                $page = clone $page;
                $page->setIsNewRecord(true);
                $page->list_id      = (int)$list->list_id;
                $page->date_added   = MW_DATETIME_NOW;
                $page->last_updated = MW_DATETIME_NOW;
                $page->save(false);
            }

            $segments = !empty($this->segments) ? $this->segments : [];
            foreach ($segments as $_segment) {
                if ($_segment->getIsPendingDelete()) {
                    continue;
                }

                $segment = clone $_segment;
                $segment->setIsNewRecord(true);
                $segment->list_id      = (int)$list->list_id;
                $segment->segment_id   = null;
                $segment->segment_uid  = '';
                $segment->date_added   = MW_DATETIME_NOW;
                $segment->last_updated = MW_DATETIME_NOW;
                if (!$segment->save(false)) {
                    continue;
                }

                $conditions = !empty($_segment->segmentConditions) ? $_segment->segmentConditions : [];
                foreach ($conditions as $_condition) {
                    if (!isset($this->copyListFieldsMap[$_condition->field_id])) {
                        continue;
                    }
                    $condition = clone $_condition;
                    $condition->setIsNewRecord(true);
                    $condition->condition_id = null;
                    $condition->segment_id   = (int)$segment->segment_id;
                    $condition->field_id     = $this->copyListFieldsMap[$_condition->field_id];
                    $condition->date_added   = MW_DATETIME_NOW;
                    $condition->last_updated = MW_DATETIME_NOW;
                    $condition->save(false);
                }
            }

            // 1.4.5 - actions
            $subscriberActions = ListSubscriberAction::model()->findAllByAttributes([
                'source_list_id' => $this->list_id,
            ]);
            foreach ($subscriberActions as $_action) {
                $action                 = clone $_action;
                $action->action_id      = null;
                $action->setIsNewRecord(true);
                $action->source_list_id = (int)$list->list_id;
                $action->save(false);
            }
            if ($this->getIsSelectAllAtActionWhenSubscribe()) {
                $subscriberAction = new ListSubscriberAction();
                $subscriberAction->source_list_id = (int)$list->list_id;
                $subscriberAction->source_action  = ListSubscriberAction::ACTION_SUBSCRIBE;
                $subscriberAction->target_list_id = (int)$this->list_id;
                $subscriberAction->target_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                $subscriberAction->save();
            }
            if ($this->getIsSelectAllAtActionWhenUnsubscribe()) {
                $subscriberAction = new ListSubscriberAction();
                $subscriberAction->source_list_id = (int)$list->list_id;
                $subscriberAction->source_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                $subscriberAction->target_list_id = (int)$this->list_id;
                $subscriberAction->target_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                $subscriberAction->save();
            }
            //

            $transaction->commit();
            $copied = $list;
            $copied->copyListFieldsMap = $this->copyListFieldsMap;
        } catch (Exception $e) {
            $transaction->rollback();
            $this->copyListFieldsMap = [];
        }

        /** @var Lists|null $copied */
        $copied = hooks()->applyFilters('models_lists_after_copy_list', $copied, $this);

        // since 2.1.4
        if ($copied) {
            $copied->flushSubscribersCountCache();
        }

        return $copied;
    }

    /**
     * @return string
     */
    public function getSubscriber404Redirect(): string
    {
        return !empty($this->subscriber_404_redirect) ? $this->subscriber_404_redirect : '';
    }

    /**
     * @param ListSubscriber|null $subscriber
     *
     * @return string
     * @throws CException
     */
    public function getSubscriberExistsRedirect(?ListSubscriber $subscriber = null): string
    {
        if (empty($this->subscriber_exists_redirect)) {
            return '';
        }

        if (empty($subscriber) || empty($subscriber->subscriber_id)) {
            return (string)$this->subscriber_exists_redirect;
        }

        $campaign = new Campaign();
        $campaign->list_id      = (int)$subscriber->list_id;
        $campaign->customer_id  = (int)$subscriber->list->customer_id;
        [, , $url] = CampaignHelper::parseContent($this->subscriber_exists_redirect, $campaign, $subscriber);

        return $url;
    }

    /**
     * @return array
     */
    public function findAllForSubscriberActions(): array
    {
        static $subscriberActionLists;
        if ($subscriberActionLists !== null) {
            return $subscriberActionLists;
        }
        $subscriberActionLists = [];

        $criteria = new CDbCriteria();
        $criteria->select = 'list_id, name';
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->addNotInCondition('list_id', [(int)$this->list_id]);
        $criteria->addNotInCondition('status', [self::STATUS_PENDING_DELETE, self::STATUS_ARCHIVED]);
        $_subscriberActionLists = self::model()->findAll($criteria);

        foreach ($_subscriberActionLists as $listModel) {
            $subscriberActionLists[$listModel->list_id] = $listModel->name;
        }

        return $subscriberActionLists;
    }

    /**
     * @param int $value
     *
     * @throws CException
     */
    public function setIsSelectAllAtActionWhenSubscribe(int $value): void
    {
        $this->modelMetaData->getModelMetaData()->add('is_select_all_at_action_when_subscribe', (int)$value);
    }

    /**
     * @return int
     * @throws CException
     */
    public function getIsSelectAllAtActionWhenSubscribe(): int
    {
        return (int)$this->modelMetaData->getModelMetaData()->itemAt('is_select_all_at_action_when_subscribe');
    }

    /**
     * @param int $value
     *
     * @throws CException
     */
    public function setIsSelectAllAtActionWhenUnsubscribe(int $value): void
    {
        $this->modelMetaData->getModelMetaData()->add('is_select_all_at_action_when_unsubscribe', (int)$value);
    }

    /**
     * @return int
     * @throws CException
     */
    public function getIsSelectAllAtActionWhenUnsubscribe(): int
    {
        return (int)$this->modelMetaData->getModelMetaData()->itemAt('is_select_all_at_action_when_unsubscribe');
    }

    /**
     * @return void
     */
    public function handleListActionsPropagationToTheOtherListActions(): void
    {
        try {
            $criteria = new CDbCriteria();
            $criteria->compare('customer_id', (int)$this->customer_id);
            $criteria->addNotInCondition('list_id', [$this->list_id]);
            $lists = self::model()->findAll($criteria);

            foreach ($lists as $list) {
                if ($list->getIsSelectAllAtActionWhenSubscribe()) {
                    $subscriberAction = new ListSubscriberAction();
                    $subscriberAction->source_list_id = (int)$list->list_id;
                    $subscriberAction->source_action  = ListSubscriberAction::ACTION_SUBSCRIBE;
                    $subscriberAction->target_list_id = (int)$this->list_id;
                    $subscriberAction->target_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                    $subscriberAction->save();
                }
                if ($list->getIsSelectAllAtActionWhenUnsubscribe()) {
                    $subscriberAction = new ListSubscriberAction();
                    $subscriberAction->source_list_id = (int)$list->list_id;
                    $subscriberAction->source_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                    $subscriberAction->target_list_id = (int)$this->list_id;
                    $subscriberAction->target_action  = ListSubscriberAction::ACTION_UNSUBSCRIBE;
                    $subscriberAction->save();
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     * @param array $subscribersIds
     * @param bool $rebuild
     *
     * @throws CException
     */
    public static function flushSubscribersCountCacheBySubscriberIds(array $subscribersIds = [], bool $rebuild = false): void
    {
        if (empty($subscribersIds)) {
            return;
        }

        $command = db()->createCommand();
        $rows    = $command->select('DISTINCT(list_id) as list_id')->from('{{list_subscriber}}')->where(['and',
            ['in', 'subscriber_id', $subscribersIds],
        ])->queryAll();

        $lists = [];
        foreach ($rows as $row) {
            $lists[] = $row['list_id'];
        }

        self::flushSubscribersCountCacheByListsIds($lists, $rebuild);
    }

    /**
     * @param array $listIds
     * @param bool $rebuild
     */
    public static function flushSubscribersCountCacheByListsIds(array $listIds = [], bool $rebuild = false): void
    {
        if (empty($listIds)) {
            return;
        }

        $listIds = array_filter(array_unique(array_map('intval', $listIds)));
        foreach ($listIds as $listId) {
            $list = new self();
            $list->list_id = $listId;
            $list->flushSubscribersCountCache(-1, $rebuild);
        }
    }

    /**
     * @param int $ttl
     * @param bool $rebuild
     */
    public function flushSubscribersCountCache(int $ttl = -1, bool $rebuild = false): void
    {
        if ($ttl >= 0) {
            $cacheKey = sha1(__FILE__) . '::flushSubscribersCountCache::' . $ttl . '::' . $this->list_id;
            if ($this->getCountersCacheAdapter()->get($cacheKey)) {
                return;
            }
            $this->getCountersCacheAdapter()->set($cacheKey, $cacheKey, $ttl);
        }

        $statuses = [
            '', ListSubscriber::STATUS_CONFIRMED,
        ];

        foreach ($statuses as $status) {

            // flush the cache
            $this->resetSubscribersCount($status);

            if ($rebuild) {

                // this rebuilds the cache as well
                $this->getSubscribersCount(false, $status);
            } else {

                // if not rebuild, mark it as needed for rebuild in future calls to increment*/decrement*
                $cacheKey = sha1(__FILE__) . '::flushSubscribersCountCacheShouldRebuild::' . $status . '::' . $this->list_id;
                $this->getCountersCacheAdapter()->set($cacheKey, $cacheKey);
            }
        }
    }

    /**
     * @param bool $fromCache
     * @param string $status
     *
     * @return int
     */
    public function getSubscribersCount(bool $fromCache = false, string $status = ''): int
    {
        $attributes = [
            'list_id' => (int)$this->list_id,
        ];

        if (!empty($status)) {
            $attributes['status'] = $status;
        }

        $cacheKey = $this->getSubscribersCountCacheKey($status);
        $mutexKey = $this->getSubscribersCountMutexKey($status);

        if ($fromCache === false) {
            $count = (int)ListSubscriber::model()->countByAttributes($attributes);

            if (mutex()->acquire($mutexKey, 5)) {
                $this->getCountersCacheAdapter()->set($cacheKey, $count);
                mutex()->release($mutexKey);
            }

            return (int)$count;
        }

        if (($count = $this->getCountersCacheAdapter()->get($cacheKey)) !== false) {
            return (int)$count;
        }

        $count = (int)ListSubscriber::model()->countByAttributes($attributes);

        if (mutex()->acquire($mutexKey, 5)) {
            $this->getCountersCacheAdapter()->set($cacheKey, $count);
            mutex()->release($mutexKey);
        }

        return (int)$count;
    }

    /**
     * @param bool $cache
     * @return int
     */
    public function getConfirmedSubscribersCount(bool $cache = false): int
    {
        return $this->getSubscribersCount($cache, ListSubscriber::STATUS_CONFIRMED);
    }

    /**
     * @param string $status
     *
     */
    public function incrementSubscribersCount(string $status = ''): void
    {
        $count = null;

        // in case we need to rebuild
        $rebuildKey = sha1(__FILE__) . '::flushSubscribersCountCacheShouldRebuild::' . $status . '::' . $this->list_id;
        if ($this->getCountersCacheAdapter()->get($rebuildKey)) {
            $this->getCountersCacheAdapter()->delete($rebuildKey);
            $count = $this->getSubscribersCount(false, $status); // this forces rebuild
        }
        //

        $cacheKey = $this->getSubscribersCountCacheKey($status);
        if (!mutex()->acquire($cacheKey, 5)) {
            return;
        }

        if ($count === null) {
            $count = (int)$this->getCountersCacheAdapter()->get($cacheKey);
        }

        $count++;

        $this->getCountersCacheAdapter()->set($cacheKey, $count);
        mutex()->release($cacheKey);
    }

    /**
     * @param string $status
     */
    public function decrementSubscribersCount(string $status = ''): void
    {
        $count = null;

        // in case we need to rebuild
        $rebuildKey = sha1(__FILE__) . '::flushSubscribersCountCacheShouldRebuild::' . $status . '::' . $this->list_id;
        if ($this->getCountersCacheAdapter()->get($rebuildKey)) {
            $this->getCountersCacheAdapter()->delete($rebuildKey);
            $count = (int)$this->getSubscribersCount(false, $status); // this forces rebuild
        }

        $cacheKey = $this->getSubscribersCountCacheKey($status);
        if (!mutex()->acquire($cacheKey, 5)) {
            return;
        }

        if ($count === null) {
            $count = (int)$this->getCountersCacheAdapter()->get($cacheKey);
        }

        $count--;

        $this->getCountersCacheAdapter()->set($cacheKey, $count > 0 ? $count : 0);
        mutex()->release($cacheKey);
    }

    /**
     * @param string $status
     *
     * @return bool
     */
    public function resetSubscribersCount(string $status = ''): bool
    {
        $cacheKey = $this->getSubscribersCountCacheKey($status);
        if (!mutex()->acquire($cacheKey, 5)) {
            return false;
        }

        $this->getCountersCacheAdapter()->delete($cacheKey);
        mutex()->release($cacheKey);

        return true;
    }

    /**
     * @param string $status
     *
     * @return string
     */
    public function getSubscribersCountCacheKey(string $status = ''): string
    {
        return sha1(__FILE__) . '::subscribersCount::' . $status . '::' . $this->list_id;
    }

    /**
     * @param string $status
     *
     * @return string
     */
    public function getSubscribersCountMutexKey(string $status = ''): string
    {
        return $this->getSubscribersCountCacheKey($status) . '.mutex';
    }

    /**
     * @return ICache
     */
    public function getCountersCacheAdapter(): ICache
    {
        /** @var ICache $adapter */
        $adapter = app()->getComponent(app_param('lists.counters.cache.adapter'));

        return $adapter;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setSubscribersCountCacheEnabled(bool $enabled = true): self
    {
        if (!isset(self::$_subscribersCountCacheEnabled[$this->list_id])) {
            self::$_subscribersCountCacheEnabled[$this->list_id] = $enabled;
        }
        self::$_subscribersCountCacheEnabled[$this->list_id] = $enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSubscribersCountCacheEnabled(): bool
    {
        return self::$_subscribersCountCacheEnabled[$this->list_id] ??
            true;
    }

    /**
     * @return string
     */
    public function getPublicCampaignsListUrl(): string
    {
        return (new OptionUrl())->getFrontendUrl(sprintf('lists/%s/campaigns', $this->list_uid));
    }

    /**
     * @return void
     * @throws CException
     */
    public function flushSubscribersCountCacheOnEndRequest(): void
    {
        $eventHandler = [$this, '_doFlushSubscribersCountCacheOnEndRequest'];
        if (app()->hasEventHandler('onEndRequest')) {
            if (app()->getEventHandlers('onEndRequest')->contains($eventHandler)) {
                return;
            }
        }
        app()->attachEventHandler('onEndRequest', $eventHandler);
    }

    /**
     * @return void
     * @param CEvent $event
     */
    public function _doFlushSubscribersCountCacheOnEndRequest(CEvent $event): void
    {
        try {
            $this->flushSubscribersCountCache(-1, true);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }
    }

    /**
     * @param int $customerId
     *
     * @return array
     */
    public static function getListIdsByCustomerId(int $customerId): array
    {
        return array_keys(self::getCustomerListsForDropdown($customerId));
    }

    /**
     * @param int $customerId
     *
     * @return array
     */
    public static function getCustomerListsForDropdown(int $customerId): array
    {
        static $lists = [];

        if (isset($lists[$customerId])) {
            return $lists[$customerId];
        }
        $lists[$customerId] = [];

        $criteria = new CDbCriteria();
        $criteria->select = 'list_id, name';
        $criteria->compare('customer_id', (int)$customerId);
        $criteria->addNotInCondition('status', [self::STATUS_PENDING_DELETE, self::STATUS_ARCHIVED]);
        $criteria->order = 'list_id DESC';

        $models = self::model()->findAll($criteria);
        foreach ($models as $model) {
            $lists[$customerId][$model->list_id] = $model->name;
        }
        unset($models);

        return $lists[$customerId];
    }

    /**
     * @param int|null $customerId
     *
     * @return array
     */
    public static function getListsForCampaignFilterDropdown(?int $customerId = null): array
    {
        $lists = [];

        $criteria = new CDbCriteria();
        $criteria->select = 'list_id, list_uid, name';

        if ($customerId) {
            $criteria->compare('customer_id', (int)$customerId);
        }

        $criteria->addNotInCondition('status', [self::STATUS_PENDING_DELETE, self::STATUS_ARCHIVED]);
        $criteria->order = 'list_id DESC';

        $models = self::model()->findAll($criteria);
        foreach ($models as $model) {
            $lists[$model->list_id] = $model->list_uid . ' - ' . $model->name;
        }
        unset($models);

        return $lists;
    }

    /**
     * @param \Carbon\Carbon $dateStart
     * @param \Carbon\Carbon $dateEnd
     * @return array
     * @throws CException
     */
    public function getSubscribersGrowthDataForChart(Carbon\Carbon $dateStart, Carbon\Carbon $dateEnd): array
    {
        $data = [
            'chartData'    => [],
            'chartOptions' => [],
        ];

        if ($dateStart->greaterThan($dateEnd)) {
            return $data;
        }

        $chartDataSets = [
            'confirmed' => [
                'type'            => 'bar',
                'label'           => t('list_subscribers', 'Confirmed'),
                'backgroundColor' => 'rgba(11, 183, 131,1)',
                'borderColor'     => 'rgba(11, 183, 131,1)',
                'data'            => [],
            ],
            'unconfirmed' => [
                'type'            => 'bar',
                'label'           => t('list_subscribers', 'Unconfirmed'),
                'backgroundColor' => '#323248',
                'borderColor'     => '#323248',
                'data'            => [],
            ],
            'unsubscribed' => [
                'type'            => 'bar',
                'label'           => t('list_subscribers', 'Unsubscribed'),
                'backgroundColor' => 'rgba(246, 78, 96,1)',
                'borderColor'     => 'rgba(246, 78, 96,1)',
                'data'            => [],
            ],
            'blacklisted' => [
                'type'            => 'bar',
                'label'           => t('list_subscribers', 'Blacklisted'),
                'backgroundColor' => 'rgba(255, 168, 0,1)',
                'borderColor'     => 'rgba(255, 168, 0,1)',
                'data'            => [],
            ],
        ];

        $unit = '';
        if ($dateEnd->diffInYears($dateStart) > 1) {
            $unit = 'year';
        } elseif ($dateEnd->diffInMonths($dateStart) > 0) {
            $unit = 'month';
        } elseif ($dateEnd->diffInDays($dateStart) > 0) {
            $unit = 'day';
        } elseif ($dateEnd->diffInHours($dateStart) > 0) {
            $unit = 'hour';
        }

        if (empty($unit)) {
            return $data;
        }

        $groupDateFormatMapping = [
            'hour'  => 'Y-m-d H:00:00',
            'day'   => 'Y-m-d',
            'month' => 'Y-m-01',
            'year'  => 'Y',
        ];

        $jsGroupDateFormatMapping = [
            'hour'  => 'Y-M-D H:00:00',
            'day'   => 'MMM D Y',
            'month' => 'MMM Y',
            'year'  => 'Y',
        ];
        $chartOptions = [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'title' => [
                    'text'    => t('list_subscribers', 'List growth'),
                    'display' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'type'    => 'time',
                    'display' => true,
                    'offset'  => true,
                    'time'    => [
                        'tooltipFormat' => $jsGroupDateFormatMapping[$unit],
                        'unit'          => $unit,
                    ],
                ],
            ],
        ];

        $dateFormat = $groupDateFormatMapping[$unit];

        $model = ListSubscriberCountHistory::model();
        $groupBy = sprintf('%s(date_added)', strtoupper($unit));

        $subQuery = db()->createCommand()
            ->select('MAX(date_added) as max')
            ->from($model->tableName())
            ->group($groupBy)
            ->where('list_id = :lid')
            ->andWhere('(date_added >= :dateStart AND date_added <= :dateEnd)');
        $subQuery->params[':dateStart'] = $dateStart->format('Y-m-d H:i:s');
        $subQuery->params[':dateEnd'] = $dateEnd->format('Y-m-d H:i:s');
        $subQuery->params[':lid'] = (int)$this->list_id;

        $groupedDatesInCriteria = (array)$subQuery->queryColumn();

        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$this->list_id);
        $criteria->addCondition('(t.date_added >= :dateStart AND t.date_added <= :dateEnd)');
        $criteria->addInCondition('t.date_added', $groupedDatesInCriteria);
        $criteria->params[':dateStart'] = $dateStart->format('Y-m-d H:i:s');
        $criteria->params[':dateEnd'] = $dateEnd->format('Y-m-d H:i:s');

        $listSubscriberCounters = ListSubscriberCountHistory::model()->findAll($criteria);

        $labels = [];
        foreach ($listSubscriberCounters as $counter) {
            /** @var Carbon\Carbon $fromFormat */
            $fromFormat = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $counter->date_added);
            $labels[] = $fromFormat->format($dateFormat);

            $chartDataSets['confirmed']['data'][]    = (int)$counter->confirmed_total;
            $chartDataSets['unconfirmed']['data'][]  = (int)$counter->unconfirmed_total;
            $chartDataSets['unsubscribed']['data'][] = (int)$counter->unsubscribed_total;
            $chartDataSets['blacklisted']['data'][]  = (int)$counter->blacklisted_total;
        }

        $chartData = [
            'labels'   => $labels,
            'datasets' => array_values($chartDataSets),
        ];

        $data['chartData']    = $chartData;
        $data['chartOptions'] = $chartOptions;

        return (array)$data;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->getIsNewRecord() && empty($this->list_uid)) {
            $this->list_uid = $this->generateUid();
        }

        if (empty($this->display_name)) {
            $this->display_name = $this->name;
        }

        return parent::beforeSave();
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        if (!$this->getIsPendingDelete() && !$this->getIsRemovable()) {
            return false;
        }

        if (!$this->getIsPendingDelete()) {
            $this->saveStatus(self::STATUS_PENDING_DELETE);

            // the campaigns
            CampaignCollection::findAllByAttributes(['list_id' => $this->list_id])->each(function (Campaign $campaign) {
                $campaign->saveStatus(Campaign::STATUS_PENDING_DELETE);
            });

            return false;
        }

        return parent::beforeDelete();
    }
}
