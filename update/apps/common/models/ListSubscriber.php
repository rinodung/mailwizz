<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * ListSubscriber
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "list_subscriber".
 *
 * The followings are the available columns in table 'list_subscriber':
 * @property integer|null $subscriber_id
 * @property integer|null $list_id
 * @property string $subscriber_uid
 * @property string $email
 * @property string $source
 * @property string $status
 * @property string $ip_address
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CampaignBounceLog[] $bounceLogs
 * @property CampaignDeliveryLog[] $deliveryLogs
 * @property CampaignDeliveryLog[] $deliveryLogsSent
 * @property CampaignDeliveryLogArchive[] $deliveryLogsArchive
 * @property CampaignForwardFriend[] $forwardFriends
 * @property CampaignTrackOpen[] $trackOpens
 * @property CampaignTrackUnsubscribe[] $trackUnsubscribes
 * @property CampaignTrackUrl[] $trackUrls
 * @property EmailBlacklist $emailBlacklist
 * @property ListFieldValue[] $fieldValues
 * @property Lists $list
 * @property ListSubscriberFieldCache $fieldsCache
 * @property ListSubscriberOptinHistory $optinHistory
 * @property ListSubscriberOptoutHistory $optoutHistory
 *
 * Getters:
 * @property bool $isUnconfirmed
 * @property bool $isConfirmed
 */
class ListSubscriber extends ActiveRecord
{
    /**
     * Statuses list
     */
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_UNCONFIRMED = 'unconfirmed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';
    const STATUS_BLACKLISTED = 'blacklisted';
    const STATUS_UNAPPROVED = 'unapproved';
    const STATUS_DISABLED = 'disabled';
    const STATUS_MOVED = 'moved';

    /**
     * Sources list
     */
    const SOURCE_WEB = 'web';
    const SOURCE_API = 'api';
    const SOURCE_IMPORT = 'import';

    /**
     * Bulk actions
     */
    const BULK_SUBSCRIBE = 'subscribe';
    const BULK_UNSUBSCRIBE = 'unsubscribe';
    const BULK_DISABLE = 'disable';
    const BULK_DELETE = 'delete';
    const BULK_BLACKLIST = 'blacklist';
    const BULK_BLACKLIST_IP = 'blacklist-ip';
    const BULK_UNCONFIRM = 'unconfirm';
    const BULK_RESEND_CONFIRMATION_EMAIL = 'resend-confirmation-email';

    /**
     * Campaign filters
     */
    const CAMPAIGN_FILTER_ACTION_DID_OPEN = 1;
    const CAMPAIGN_FILTER_ACTION_DID_CLICK = 2;
    const CAMPAIGN_FILTER_ACTION_DID_NOT_OPEN = 3;
    const CAMPAIGN_FILTER_ACTION_DID_NOT_CLICK = 4;

    /**
     * General filters
     */
    const FILTER_TIME_UNIT_DAY = 1;
    const FILTER_TIME_UNIT_WEEK = 2;
    const FILTER_TIME_UNIT_MONTH = 3;
    const FILTER_TIME_UNIT_YEAR = 4;

    /**
     * @var int
     */
    public $counter = 0;

    /**
     * @var array
     */
    public $listIds = [];

    /**
     * @var ListSubscriberOptinHistory|null $_optinHistory - the optin history for the subscriber
     * We use it instead of relations because is easier to null it this way
     */
    protected $_optinHistory;

    /**
     * @var ListSubscriberOptoutHistory|null $_optoutHistory - the optout history for the subscriber
     * We use it instead of relations because is easier to null it this way
     */
    protected $_optoutHistory;

    /**
     * @since 1.5.2
     * @var array
     */
    protected static $_listSubscriberActions = [];

    /**
     * @since 1.6.7
     * @var string
     */
    protected $subscribersCountSubscriberLastStatus = '';

    /**
     * @var int|null
     */
    private $_lastCampaignDeliveryLogId;

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = [];

        if (app_param('send.campaigns.command.useTempQueueTables', false)) {
            $behaviors['toQueueTable'] = [
                'class' => 'common.components.db.behaviors.SubscriberToCampaignQueueTableBehavior',
            ];
        }

        return CMap::mergeArray($behaviors, parent::behaviors());
    }

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{list_subscriber}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['status', 'in', 'range' => array_keys($this->getFilterStatusesList())],
            ['list_id, subscriber_uid, email, source, ip_address, status', 'safe', 'on' => 'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'bounceLogs'            => [self::HAS_MANY, CampaignBounceLog::class, 'subscriber_id'],
            'deliveryLogs'          => [self::HAS_MANY, CampaignDeliveryLog::class, 'subscriber_id'],
            'deliveryLogsSent'      => [self::HAS_MANY, CampaignDeliveryLog::class, 'subscriber_id'],
            'deliveryLogsArchive'   => [self::HAS_MANY, CampaignDeliveryLogArchive::class, 'subscriber_id'],
            'forwardFriends'        => [self::HAS_MANY, CampaignForwardFriend::class, 'subscriber_id'],
            'trackOpens'            => [self::HAS_MANY, CampaignTrackOpen::class, 'subscriber_id'],
            'trackUnsubscribes'     => [self::HAS_MANY, CampaignTrackUnsubscribe::class, 'subscriber_id'],
            'trackUrls'             => [self::HAS_MANY, CampaignTrackUrl::class, 'subscriber_id'],
            'emailBlacklist'        => [self::HAS_ONE, EmailBlacklist::class, 'subscriber_id'],
            'fieldValues'           => [self::HAS_MANY, ListFieldValue::class, 'subscriber_id'],
            'list'                  => [self::BELONGS_TO, Lists::class, 'list_id'],
            'fieldsCache'           => [self::HAS_ONE, ListSubscriberFieldCache::class, 'subscriber_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'subscriber_id'     => t('list_subscribers', 'Subscriber ID'),
            'list_id'           => t('list_subscribers', 'List'),
            'subscriber_uid'    => t('list_subscribers', 'Unique ID'),
            'email'             => t('list_subscribers', 'Email'),
            'source'            => t('list_subscribers', 'Source'),
            'ip_address'        => t('list_subscribers', 'Ip address'),
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

        if (!empty($this->list_id)) {
            $criteria->compare('t.list_id', (int)$this->list_id);
        } elseif (!empty($this->listIds)) {
            $criteria->addInCondition('t.list_id', array_map('intval', $this->listIds));
        }

        $criteria->compare('t.subscriber_uid', $this->subscriber_uid);
        $criteria->compare('t.email', $this->email, true);
        $criteria->compare('t.source', $this->source);
        $criteria->compare('t.ip_address', $this->ip_address, true);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.subscriber_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    't.subscriber_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return ListSubscriber the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var ListSubscriber $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $subscriber_uid
     *
     * @return ListSubscriber|null
     */
    public function findByUid(string $subscriber_uid): ?self
    {
        return self::model()->findByAttributes([
            'subscriber_uid' => $subscriber_uid,
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
     * @param array $params
     * @return bool|EmailBlacklistCheckInfo
     * @throws CException
     */
    public function getIsBlacklisted(array $params = [])
    {
        // since 1.3.5.5
        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_DISABLE_SUBSCRIBER_BLACKLIST_CHECK') &&
            MW_PERF_LVL & MW_PERF_LVL_DISABLE_SUBSCRIBER_BLACKLIST_CHECK
        ) {
            return false;
        }

        // check since 1.3.4.7
        if ($this->getStatusIs(self::STATUS_BLACKLISTED)) {
            return new EmailBlacklistCheckInfo([
                'email'       => $this->email,
                'blacklisted' => true,
                'reason'      => t('email_blacklist', 'Blacklisted in email list'),
            ]);
        }

        $blCheckInfo = EmailBlacklist::isBlacklisted($this->email, $this, null, $params);

        // added since 1.3.4.7
        if ($blCheckInfo !== false && $this->getIsConfirmed()) {
            $this->saveStatus(self::STATUS_BLACKLISTED);
        }

        return $blCheckInfo;
    }

    /**
     * @param string $reason
     *
     * @return bool
     * @throws CException
     */
    public function addToBlacklist(string $reason = ''): bool
    {
        if ($added = EmailBlacklist::addToBlacklist($this, $reason)) {
            $this->status = self::STATUS_BLACKLISTED;
        }
        return (bool)$added;
    }

    /**
     * @param string $reason
     *
     * @return bool
     */
    public function addToCustomerBlacklist(string $reason = ''): bool
    {
        $customerEmailBlacklist              = new CustomerEmailBlacklist();
        $customerEmailBlacklist->customer_id = (int)$this->list->customer_id;
        $customerEmailBlacklist->email       = $this->email;
        $customerEmailBlacklist->reason      = $reason;

        if ($added = $customerEmailBlacklist->save()) {
            $this->status = self::STATUS_BLACKLISTED;
        }

        return $added;
    }

    /**
     * @return bool
     * @throws CDbException
     */
    public function removeFromBlacklistByEmail(): bool
    {
        if ($this->getStatusIs(self::STATUS_BLACKLISTED)) {
            return false;
        }

        $global   = EmailBlacklist::removeByEmail($this->email);
        $customer = true;

        if (!empty($this->list)) {
            $customer = CustomerEmailBlacklist::model()->deleteAllByAttributes([
                'customer_id' => $this->list->customer_id,
                'email'       => $this->email,
            ]);
        }

        return $global && $customer;
    }

    /**
     * @return bool
     */
    public function blacklistIp(): bool
    {
        static $ips = [];

        if (empty($this->ip_address)) {
            return false;
        }

        $customerId = (int)$this->list->customer_id;

        if (!isset($ips[$customerId])) {
            $ips[$customerId] = [];
        }
        if (in_array($this->ip_address, $ips[$customerId])) {
            return true;
        }

        $ip = CustomerIpBlacklist::findByIpWithCustomerId((string)$this->ip_address, (int)$customerId);
        if (!empty($ip)) {
            $ips[$customerId][] = $this->ip_address;
            return true;
        }

        $ip = new CustomerIpBlacklist();
        $ip->customer_id = $customerId;
        $ip->ip_address  = $this->ip_address;

        if ($saved = $ip->save()) {
            $ips[$customerId][] = $this->ip_address;
        }
        return $saved;
    }

    /**
     * @return bool
     */
    public function getCanBeConfirmed(): bool
    {
        return !in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_BLACKLISTED]);
    }

    /**
     * @return bool
     */
    public function getCanBeUnsubscribed(): bool
    {
        return !in_array($this->status, [self::STATUS_BLACKLISTED]);
    }

    /**
     * @return bool
     */
    public function getCanBeDeleted(): bool
    {
        return $this->getRemovable();
    }

    /**
     * @return bool
     */
    public function getCanBeEdited(): bool
    {
        return $this->getEditable();
    }

    /**
     * @return bool
     */
    public function getCanBeApproved(): bool
    {
        return $this->getStatusIs(self::STATUS_UNAPPROVED);
    }

    /**
     * @return bool
     */
    public function getIsUnapproved(): bool
    {
        return $this->getStatusIs(self::STATUS_UNAPPROVED);
    }

    /**
     * @return bool
     */
    public function getIsConfirmed(): bool
    {
        return $this->getStatusIs(self::STATUS_CONFIRMED);
    }

    /**
     * @return bool
     */
    public function getIsUnconfirmed(): bool
    {
        return $this->getStatusIs(self::STATUS_UNCONFIRMED);
    }

    /**
     * @return bool
     */
    public function getIsUnsubscribed(): bool
    {
        return $this->getStatusIs(self::STATUS_UNSUBSCRIBED);
    }

    /**
     * @return bool
     */
    public function getIsDisabled(): bool
    {
        return $this->getStatusIs(self::STATUS_DISABLED);
    }

    /**
     * @return bool
     */
    public function getCanBeDisabled(): bool
    {
        return $this->getStatusIs(self::STATUS_CONFIRMED);
    }

    /**
     * @return bool
     */
    public function getIsMoved(): bool
    {
        return $this->getStatusIs(self::STATUS_MOVED);
    }

    /**
     * @return bool
     */
    public function getIsImported(): bool
    {
        return (string)$this->source === self::SOURCE_IMPORT;
    }

    /**
     * @return bool
     */
    public function getRemovable(): bool
    {
        $removable = true;
        if (!empty($this->list_id) && !empty($this->list) && !empty($this->list->customer_id) && !empty($this->list->customer)) {
            $removable = $this->list->customer->getGroupOption('lists.can_delete_own_subscribers', 'yes') === 'yes';
        }
        return $removable;
    }

    /**
     * @return bool
     */
    public function getEditable(): bool
    {
        $editable = true;
        if (!empty($this->list_id) && !empty($this->list) && !empty($this->list->customer_id) && !empty($this->list->customer)) {
            $editable = $this->list->customer->getGroupOption('lists.can_edit_own_subscribers', 'yes') === 'yes';
        }
        return $editable;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->subscriber_uid;
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return [
            self::STATUS_CONFIRMED      => t('list_subscribers', ucfirst(self::STATUS_CONFIRMED)),
            self::STATUS_UNCONFIRMED    => t('list_subscribers', ucfirst(self::STATUS_UNCONFIRMED)),
            self::STATUS_UNSUBSCRIBED   => t('list_subscribers', ucfirst(self::STATUS_UNSUBSCRIBED)),
        ];
    }

    /**
     * @return array
     */
    public function getFilterStatusesList(): array
    {
        return array_merge($this->getStatusesList(), [
            self::STATUS_UNAPPROVED  => t('list_subscribers', ucfirst(self::STATUS_UNAPPROVED)),
            self::STATUS_BLACKLISTED => t('list_subscribers', ucfirst(self::STATUS_BLACKLISTED)),
            self::STATUS_DISABLED    => t('list_subscribers', ucfirst(self::STATUS_DISABLED)),
            self::STATUS_MOVED       => t('list_subscribers', ucfirst(self::STATUS_MOVED)),
        ]);
    }

    /**
     * @return array
     */
    public function getBulkActionsList(): array
    {
        $list = [
            self::BULK_SUBSCRIBE                 => t('list_subscribers', ucfirst(self::BULK_SUBSCRIBE)),
            self::BULK_UNSUBSCRIBE               => t('list_subscribers', ucfirst(self::BULK_UNSUBSCRIBE)),
            self::BULK_UNCONFIRM                 => t('list_subscribers', ucfirst(self::BULK_UNCONFIRM)),
            self::BULK_RESEND_CONFIRMATION_EMAIL => t('list_subscribers', 'Resend confirmation email'),
            self::BULK_DISABLE                   => t('list_subscribers', ucfirst(self::BULK_DISABLE)),
            self::BULK_DELETE                    => t('list_subscribers', ucfirst(self::BULK_DELETE)),
            self::BULK_BLACKLIST_IP              => t('list_subscribers', 'Blacklist IPs'),
        ];

        if (!$this->getCanBeDeleted()) {
            unset($list[self::BULK_DELETE]);
        }

        return $list;
    }

    /**
     * @return array
     */
    public function getSourcesList(): array
    {
        return [
            self::SOURCE_API    => t('list_subscribers', ucfirst(self::SOURCE_API)),
            self::SOURCE_IMPORT => t('list_subscribers', ucfirst(self::SOURCE_IMPORT)),
            self::SOURCE_WEB    => t('list_subscribers', ucfirst(self::SOURCE_WEB)),
        ];
    }

    /**
     * @return ListSubscriber
     */
    public function getShallowCopy(): self
    {
        $copy = new self();
        foreach ($this->attributes as $key => $value) {
            $copy->$key = $value;
        }

        $copy->list_id        = null;
        $copy->subscriber_id  = null;
        $copy->subscriber_uid = $this->generateUid();
        $copy->date_added     = MW_DATETIME_NOW;
        $copy->last_updated   = MW_DATETIME_NOW;

        return $copy;
    }

    /**
     * Since 1.3.6.3 it will also update custom fields value.
     *
     * @param int $listId
     * @param bool $doTransaction
     * @param bool $notify
     *
     * @return ListSubscriber|null
     * @throws CDbException
     * @throws CException
     */
    public function copyToList(int $listId, bool $doTransaction = true, bool $notify = false): ?self
    {
        $mutexKey = __METHOD__ . ':' . $listId . ':' . $this->email;
        if (!mutex()->acquire($mutexKey)) {
            return null;
        }

        $listId = (int)$listId;
        if (empty($listId) || $listId == $this->list_id) {
            mutex()->release($mutexKey);
            return null;
        }

        static $targetLists      = [];
        static $cacheFieldModels = [];

        if (isset($targetLists[$listId]) || array_key_exists($listId, $targetLists)) {
            $targetList = $targetLists[$listId];
        } else {
            $targetList = $targetLists[$listId] = Lists::model()->findByPk($listId);
        }

        if (empty($targetList)) {
            mutex()->release($mutexKey);
            return null;
        }

        /** @var ListSubscriber|null $subscriber */
        $subscriber = self::model()->findByAttributes([
            'list_id' => $targetList->list_id,
            'email'   => $this->email,
        ]);

        $subscriberExists = !empty($subscriber);
        if (!$subscriberExists) {
            /** @var ListSubscriber $subscriber */
            $subscriber = $this->getShallowCopy();
            $subscriber->list_id = (int)$targetList->list_id;
            $subscriber->addRelatedRecord('list', $targetList, false);
        }

        // 1.3.7.3
        if ($subscriber->getIsMoved()) {
            $subscriber->status = self::STATUS_CONFIRMED;
        }

        $transaction = null;
        if ($doTransaction) {
            $transaction = db()->beginTransaction();
        }

        try {
            $isNewRecord = $subscriber->getIsNewRecord();

            if ($isNewRecord && !$subscriber->save()) {
                throw new Exception(CHtml::errorSummary($subscriber));
            }

            // 1.3.8.8 - not sure about this 100%, so leave it disabled for now
            /*if ($isNewRecord && !empty($this->optinHistory)) {
                $optinHistory = clone $this->optinHistory;
                $optinHistory->subscriber_id = (int)$subscriber->subscriber_id;
                $optinHistory->save(false);
            }*/

            $cacheListsKey = (int)$this->list_id . '|' . (int)$targetList->list_id;
            if (!isset($cacheFieldModels[$cacheListsKey])) {
                // the custom fields for source list
                $sourceFields = ListField::model()->findAllByAttributes([
                    'list_id' => $this->list_id,
                ]);

                // the custom fields for target list
                $targetFields = ListField::model()->findAllByAttributes([
                    'list_id' => $targetList->list_id,
                ]);

                // get only the same fields
                $_fieldModels = [];
                foreach ($sourceFields as $srcIndex => $sourceField) {
                    foreach ($targetFields as $trgIndex => $targetField) {
                        if ($sourceField->tag == $targetField->tag && $sourceField->type_id == $targetField->type_id) {
                            $_fieldModels[] = [$sourceField, $targetField];
                            unset($sourceFields[$srcIndex], $targetFields[$trgIndex]);
                            break;
                        }
                    }
                }
                $cacheFieldModels[$cacheListsKey] = $_fieldModels;
                unset($sourceFields, $targetFields, $_fieldModels);
            }
            $fieldModels = $cacheFieldModels[$cacheListsKey];

            if (empty($fieldModels)) {
                throw new Exception('No field models found, something went wrong!');
            }

            foreach ($fieldModels as $models) {
                [$source, $target] = $models;

                $sourceValues = ListFieldValue::model()->findAllByAttributes([
                    'subscriber_id' => $this->subscriber_id,
                    'field_id'      => $source->field_id,
                ]);

                ListFieldValue::model()->deleteAllByAttributes([
                    'subscriber_id' => $subscriber->subscriber_id,
                    'field_id'      => $target->field_id,
                ]);

                foreach ($sourceValues as $sourceValue) {
                    $targetValue                = clone $sourceValue;
                    $targetValue->value_id      = null;
                    $targetValue->field_id      = (int)$target->field_id;
                    $targetValue->subscriber_id = (int)$subscriber->subscriber_id;
                    $targetValue->date_added    = MW_DATETIME_NOW;
                    $targetValue->last_updated  = MW_DATETIME_NOW;
                    $targetValue->setIsNewRecord(true);
                    if (!$targetValue->save()) {
                        throw new Exception(CHtml::errorSummary($targetValue));
                    }
                }
                unset($models, $source, $target, $sourceValues, $sourceValue);
            }
            unset($fieldModels);

            // since 1.9.12
            if ($subscriber->getIsConfirmed()) {
                $subscriber->takeListSubscriberAction(ListSubscriberAction::ACTION_SUBSCRIBE);
            }

            if ($doTransaction) {
                $transaction->commit();
            }
        } catch (Exception $e) {
            if ($doTransaction) {
                $transaction->rollback();
            } elseif (!empty($subscriber->subscriber_id)) {
                $subscriber->delete();
            }
            $subscriber = null;
        }

        if ($subscriber && $notify && !$subscriberExists) {
            $subscriber->sendCreatedNotifications();
        }

        mutex()->release($mutexKey);

        return $subscriber;
    }

    /**
     * @param int $listId
     * @param bool $doTransaction
     * @param bool $notify
     *
     * @return ListSubscriber|null
     * @throws CDbException
     * @throws CException
     */
    public function moveToList(int $listId, bool $doTransaction = true, bool $notify = false): ?self
    {
        $mutexKey = __METHOD__ . ':' . $listId . ':' . $this->email;
        if (!mutex()->acquire($mutexKey)) {
            return null;
        }

        if (!($subscriber = $this->copyToList((int)$listId, $doTransaction, $notify))) {
            mutex()->release($mutexKey);
            return null;
        }

        $exists = ListSubscriberListMove::model()->findByAttributes([
            'source_subscriber_id'  => $this->subscriber_id,
            'source_list_id'        => $this->list_id,
            'destination_list_id'   => $listId,
        ]);

        if (!empty($exists)) {
            $this->saveStatus(ListSubscriber::STATUS_MOVED);
            mutex()->release($mutexKey);
            return $subscriber;
        }

        $move = new ListSubscriberListMove();
        $move->source_subscriber_id      = (int)$this->subscriber_id;
        $move->source_list_id            = (int)$this->list_id;
        $move->destination_subscriber_id = (int)$subscriber->subscriber_id;
        $move->destination_list_id       = (int)$listId;

        try {
            $move->save(false);
            $this->saveStatus(ListSubscriber::STATUS_MOVED);
        } catch (Exception $e) {
            mutex()->release($mutexKey);
            return null;
        }

        mutex()->release($mutexKey);
        return $subscriber;
    }

    /**
     * @param string $status
     *
     * @return bool
     * @throws Exception
     */
    public function saveStatus(string $status = ''): bool
    {
        if (empty($this->subscriber_id)) {
            return false;
        }

        if ($status && $status === (string)$this->status) {
            return true;
        }

        if ($status) {
            $this->status = $status;
        }

        // since 1.6.4
        if ($this->status != $this->subscribersCountSubscriberLastStatus) {
            $this->subscribersCountSubscriberLastStatus = $this->status;

            if (!empty($this->list_id) && !empty($this->list)) {
                if ($this->getIsConfirmed()) {
                    $this->list->incrementSubscribersCount(self::STATUS_CONFIRMED);
                } else {
                    $this->list->decrementSubscribersCount(self::STATUS_CONFIRMED);
                }
            }
            //
        }

        $attributes = ['status' => $this->status];
        $this->last_updated = $attributes['last_updated'] = MW_DATETIME_NOW;

        // 1.7.9
        hooks()->doAction($this->buildHookName(['suffix' => 'before_savestatus']), $this);
        //

        $result = (bool)db()->createCommand()->update($this->tableName(), $attributes, 'subscriber_id = :id', [':id' => (int)$this->subscriber_id]);

        // 1.7.9
        hooks()->doAction($this->buildHookName(['suffix' => 'after_savestatus']), $this, $result);
        //

        return $result;
    }

    /**
     * @param string $actionName
     */
    public function takeListSubscriberAction(string $actionName): void
    {
        if ($this->getIsNewRecord() || empty($this->list_id)) {
            return;
        }

        if ($actionName == ListSubscriberAction::ACTION_SUBSCRIBE && !$this->getStatusIs(self::STATUS_CONFIRMED)) {
            return;
        }

        if ($actionName == ListSubscriberAction::ACTION_UNSUBSCRIBE && $this->getStatusIs(self::STATUS_CONFIRMED)) {
            return;
        }

        $allowedActions = array_keys(ListSubscriberAction::model()->getActions());
        if (!in_array($actionName, $allowedActions)) {
            return;
        }

        // since 1.5.2 - add local cache
        $hash = (int)$this->list_id . '_' . $actionName;
        if (!isset(self::$_listSubscriberActions[$hash])) {
            $criteria = new CDbCriteria();
            $criteria->select = 'target_list_id';
            $criteria->compare('source_list_id', (int)$this->list_id);
            $criteria->compare('source_action', $actionName);
            self::$_listSubscriberActions[$hash] = ListSubscriberAction::model()->findAll($criteria);
        }

        if (empty(self::$_listSubscriberActions[$hash])) {
            return;
        }

        $lists = [];
        foreach (self::$_listSubscriberActions[$hash] as $list) {
            $lists[] = (int)$list->target_list_id;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('email', $this->email);
        $criteria->addInCondition('list_id', $lists);
        $criteria->addInCondition('status', [self::STATUS_CONFIRMED]);

        self::model()->updateAll(['status' => self::STATUS_UNSUBSCRIBED], $criteria);

        // 1.6.4
        Lists::flushSubscribersCountCacheByListsIds($lists);
    }

    /**
     * @return array
     * @throws CException
     */
    public function loadAllCustomFieldsWithValues(): array
    {
        $fields = [];
        foreach (ListField::getAllByListId((int)$this->list_id) as $field) {
            $values = db()->createCommand()
                ->select('value')
                ->from('{{list_field_value}}')
                ->where('subscriber_id = :sid AND field_id = :fid', [
                    ':sid' => (int)$this->subscriber_id,
                    ':fid' => (int)$field['field_id'],
                ])
                ->queryAll();

            $value = [];
            foreach ($values as $val) {
                $value[] = $val['value'];
            }
            $fields['[' . $field['tag'] . ']'] = implode(', ', $value);
        }

        return $fields;
    }

    /**
     * @param bool $refresh
     *
     * @return array
     * @throws CException
     */
    public function getAllCustomFieldsWithValues(bool $refresh = false): array
    {
        static $fields = [];

        if (empty($this->subscriber_id)) {
            return [];
        }

        if ($refresh && isset($fields[$this->subscriber_id])) {
            unset($fields[$this->subscriber_id]);
        }

        if (isset($fields[$this->subscriber_id])) {
            return $fields[$this->subscriber_id];
        }

        $fields[$this->subscriber_id] = [];

        if (
            (defined('MW_PERF_LVL') && MW_PERF_LVL) &&
            defined('MW_PERF_LVL_ENABLE_SUBSCRIBER_FIELD_CACHE') &&
            MW_PERF_LVL & MW_PERF_LVL_ENABLE_SUBSCRIBER_FIELD_CACHE
        ) {
            if (!$refresh && !empty($this->fieldsCache)) {
                return $fields[$this->subscriber_id] = (array)$this->fieldsCache->data;
            }

            if ($refresh) {
                ListSubscriberFieldCache::model()->deleteAllByAttributes([
                    'subscriber_id' => $this->subscriber_id,
                ]);
            }

            $data  = $this->loadAllCustomFieldsWithValues();
            $model = new ListSubscriberFieldCache();
            $model->subscriber_id = (int)$this->subscriber_id;
            $model->data = $data;

            try {
                if (!$model->save()) {
                    throw new Exception('Not saved!');
                }
            } catch (Exception $e) {
            }
            $this->addRelatedRecord('fieldsCache', $model, false);

            return $fields[$this->subscriber_id]= $model->data;
        }

        return $fields[$this->subscriber_id] = $this->loadAllCustomFieldsWithValues();
    }

    /**
     * @param string $field
     *
     * @return mixed|null
     * @throws CException
     */
    public function getCustomFieldValue(string $field)
    {
        $field  = '[' . strtoupper((string)str_replace(['[', ']'], '', $field)) . ']';
        $fields = $this->getAllCustomFieldsWithValues();
        $value  = isset($fields[$field]) || array_key_exists($field, $fields) ? $fields[$field] : null;
        unset($fields);
        return $value;
    }

    /**
     * @return void
     * @throws CException
     * @throws \MaxMind\Db\Reader\InvalidDatabaseException
     * @since 1.9.26
     *
     */
    public function updateGeoLocationFields()
    {
        if (
            empty($this->subscriber_id) ||
            empty($this->list_id) ||
            empty($this->ip_address) ||
            !FilterVarHelper::ip($this->ip_address)
        ) {
            return;
        }

        $location = IpLocation::findByIp($this->ip_address);
        if (empty($location)) {
            return;
        }

        /** @var array<int, ListField> $fields */
        static $fields = [];
        if (!isset($fields[(int)$this->list_id])) {
            $fields[(int)$this->list_id] = [];
            $criteria = new CDbCriteria();
            $criteria->with = [];
            $criteria->select = 't.field_id, t.tag, t.default_value';
            $criteria->with['type'] = [
                'joinType' => 'INNER JOIN',
                'together' => true,
            ];
            $criteria->compare('t.list_id', (int)$this->list_id);
            $criteria->addInCondition('type.identifier', ['geocity', 'geocountry', 'geostate']);

            $models = ListField::model()->findAll($criteria);
            foreach ($models as $model) {
                $fields[(int)$this->list_id][] = $model;
            }
        }

        if (empty($fields[(int)$this->list_id])) {
            return;
        }

        /** @var ListField $field */
        foreach ($fields[(int)$this->list_id] as $field) {
            $value = db()->createCommand()
                ->select('value_id, value')
                ->from('{{list_field_value}}')
                ->where('subscriber_id = :sid AND field_id = :fid', [
                    ':sid' => (int)$this->subscriber_id,
                    ':fid' => (int)$field->field_id,
                ])
                ->queryRow();

            if ($field->type->identifier === 'geocountry') {
                $newValue = $location->country_name;
            } elseif ($field->type->identifier === 'geostate') {
                $newValue = $location->zone_name;
            } elseif ($field->type->identifier === 'geocity') {
                $newValue = $location->city_name;
            } else {
                $newValue = ListField::parseDefaultValueTags((string)$field->default_value, $this);
            }

            $data = [
                'field_id'      => (int)$field->field_id,
                'subscriber_id' => (int)$this->subscriber_id,
                'value'         => (string)$newValue,
                'date_added'    => new CDbExpression('NOW()'),
                'last_updated'  => new CDbExpression('NOW()'),
            ];

            $command = db()->createCommand();
            if (empty($value)) {
                $command->insert('{{list_field_value}}', $data);
            } else {
                unset($data['date_added']);
                if (empty($value['value'])) {
                    $command->update('{{list_field_value}}', $data, 'value_id = :vid', [':vid' => $value['value_id']]);
                }
            }
        }
    }

    /**
     * @return void
     * @throws CException
     */
    public function handleFieldsDefaultValues(): void
    {
        if (empty($this->subscriber_id) || empty($this->list_id)) {
            return;
        }

        /** @var array<int, ListField> $fields */
        static $fields = [];
        if (!isset($fields[(int)$this->list_id])) {
            $fields[(int)$this->list_id] = [];
            $criteria = new CDbCriteria();
            $criteria->with = [];
            $criteria->select = 't.field_id, t.tag, t.default_value';
            $criteria->with['type'] = [
                'joinType' => 'INNER JOIN',
                'together' => true,
            ];
            $criteria->compare('t.list_id', (int)$this->list_id);
            $criteria->addCondition('t.default_value != ""');

            $models = ListField::model()->findAll($criteria);
            foreach ($models as $model) {
                $fields[(int)$this->list_id][] = $model;
            }
        }

        if (empty($fields[(int)$this->list_id])) {
            return;
        }

        /** @var ListField $field */
        foreach ($fields[(int)$this->list_id] as $field) {
            $value = db()->createCommand()
                ->select('value_id, value')
                ->from('{{list_field_value}}')
                ->where('subscriber_id = :sid AND field_id = :fid', [
                     ':sid' => (int)$this->subscriber_id,
                     ':fid' => (int)$field->field_id,
                 ])
                ->queryRow();

            if (!empty($value) && $value['value'] != '') {
                continue;
            }

            $data = [
                'field_id'      => (int)$field->field_id,
                'subscriber_id' => (int)$this->subscriber_id,
                'value'         => ListField::parseDefaultValueTags((string)$field->default_value, $this),
                'date_added'    => new CDbExpression('NOW()'),
                'last_updated'  => new CDbExpression('NOW()'),
            ];

            $command = db()->createCommand();
            if (empty($value)) {
                $command->insert('{{list_field_value}}', $data);
            } else {
                unset($data['date_added']);
                $command->update('{{list_field_value}}', $data, 'value_id = :vid', [':vid' => $value['value_id']]);
            }
        }
    }

    /**
     * @param Campaign $campaign
     * @return bool
     */
    public function hasOpenedCampaign(Campaign $campaign): bool
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', (int)$campaign->campaign_id);
        $criteria->compare('subscriber_id', (int)$this->subscriber_id);
        return CampaignTrackOpen::model()->count($criteria) > 0;
    }

    /**
     * @param int $campaignId
     * @return bool
     */
    public function hasOpenedCampaignById(int $campaignId): bool
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', (int)$campaignId);
        $criteria->compare('subscriber_id', (int)$this->subscriber_id);
        return CampaignTrackOpen::model()->count($criteria) > 0;
    }

    /**
     * @param bool $forcefully
     *
     * @return ListSubscriber
     * @throws CException
     * @throws Throwable
     */
    public function handleApprove(bool $forcefully = false): self
    {
        if (!$forcefully && !$this->getCanBeApproved()) {
            return $this;
        }

        if (empty($this->list_id) || empty($this->list) || $this->list->subscriber_require_approval != Lists::TEXT_YES) {
            return $this;
        }

        /** @var ListPageType|null $pageType */
        $pageType = ListPageType::model()->findBySlug('subscribe-confirm-approval-email');

        if (empty($pageType)) {
            return $this;
        }

        /** @var DeliveryServer|null $server */
        $server = DeliveryServer::pickServer(0, $this->list);
        if (empty($server)) {

            // since 2.1.4
            $this->handleSendApprovalEmailFail();

            return $this;
        }

        $page = ListPage::model()->findByAttributes([
            'list_id' => $this->list_id,
            'type_id' => $pageType->type_id,
        ]);

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $_content         = !empty($page->content) ? $page->content : $pageType->content;
        $_subject         = !empty($page->email_subject) ? $page->email_subject : $pageType->email_subject;
        $updateProfileUrl = $optionUrl->getFrontendUrl('lists/' . $this->list->list_uid . '/update-profile/' . $this->subscriber_uid);
        $unsubscribeUrl   = $optionUrl->getFrontendUrl('lists/' . $this->list->list_uid . '/unsubscribe/' . $this->subscriber_uid);
        $searchReplace    = [
            '[LIST_NAME]'           => $this->list->display_name,
            '[LIST_DISPLAY_NAME]'   => $this->list->display_name,
            '[LIST_INTERNAL_NAME]'  => $this->list->name,
            '[LIST_UID]'            => $this->list->list_uid,
            '[COMPANY_NAME]'        => !empty($this->list->company) ? $this->list->company->name : null,
            '[UPDATE_PROFILE_URL]'  => $updateProfileUrl,
            '[UNSUBSCRIBE_URL]'     => $unsubscribeUrl,
            '[COMPANY_FULL_ADDRESS]'=> !empty($this->list->company) ? nl2br($this->list->company->getFormattedAddress()) : null,
            '[CURRENT_YEAR]'        => date('Y'),
        ];

        $subscriberCustomFields = $this->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }

        $_content = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $_content);
        $_subject = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $_subject);

        // 1.5.3
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $_content = CampaignHelper::parseByTemplateEngine($_content, $searchReplace);
            $_subject = CampaignHelper::parseByTemplateEngine($_subject, $searchReplace);
        }

        $params = [
            'to'        => $this->email,
            'fromName'  => $this->list->default->from_name,
            'subject'   => $_subject,
            'body'      => $_content,
        ];

        $sent = false;
        for ($i = 0; $i < 3; ++$i) {
            if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($this->list)->sendEmail($params)) {
                break;
            }
            if (!($server = DeliveryServer::pickServer((int)$server->server_id, $this->list))) {
                break;
            }
        }

        // since 2.1.4
        if (!$sent) {
            $this->handleSendApprovalEmailFail();
        }

        return $this;
    }

    /**
     * @param bool $forcefully
     *
     * @return ListSubscriber
     * @throws CException
     * @throws Throwable
     */
    public function handleWelcome(bool $forcefully = false): self
    {
        if (!$forcefully && !$this->getIsConfirmed()) {
            return $this;
        }

        if (empty($this->list_id) || empty($this->list) || $this->list->welcome_email != Lists::TEXT_YES) {
            return $this;
        }

        /** @var ListPageType|null $pageType */
        $pageType = ListPageType::model()->findBySlug('welcome-email');

        if (empty($pageType)) {
            return $this;
        }

        /** @var DeliveryServer|null $server */
        $server = DeliveryServer::pickServer(0, $this->list);
        if (empty($server)) {

            // since 2.1.4
            $this->handleSendWelcomeEmailFail();

            return $this;
        }

        $page = ListPage::model()->findByAttributes([
            'list_id' => $this->list_id,
            'type_id' => $pageType->type_id,
        ]);

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        $_content         = !empty($page->content) ? $page->content : $pageType->content;
        $_subject         = !empty($page->email_subject) ? $page->email_subject : $pageType->email_subject;
        $updateProfileUrl = $optionUrl->getFrontendUrl('lists/' . $this->list->list_uid . '/update-profile/' . $this->subscriber_uid);
        $unsubscribeUrl   = $optionUrl->getFrontendUrl('lists/' . $this->list->list_uid . '/unsubscribe/' . $this->subscriber_uid);
        $searchReplace    = [
            '[LIST_NAME]'           => $this->list->display_name,
            '[LIST_DISPLAY_NAME]'   => $this->list->display_name,
            '[LIST_INTERNAL_NAME]'  => $this->list->name,
            '[LIST_UID]'            => $this->list->list_uid,
            '[COMPANY_NAME]'        => !empty($this->list->company) ? $this->list->company->name : null,
            '[UPDATE_PROFILE_URL]'  => $updateProfileUrl,
            '[UNSUBSCRIBE_URL]'     => $unsubscribeUrl,
            '[COMPANY_FULL_ADDRESS]'=> !empty($this->list->company) ? nl2br($this->list->company->getFormattedAddress()) : null,
            '[CURRENT_YEAR]'        => date('Y'),
        ];

        // since 1.3.5.9
        $subscriberCustomFields = $this->getAllCustomFieldsWithValues();
        foreach ($subscriberCustomFields as $field => $value) {
            $searchReplace[$field] = $value;
        }
        //

        $_content = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $_content);
        $_subject = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $_subject);

        // 1.5.3
        if (CampaignHelper::isTemplateEngineEnabled()) {
            $_content = CampaignHelper::parseByTemplateEngine($_content, $searchReplace);
            $_subject = CampaignHelper::parseByTemplateEngine($_subject, $searchReplace);
        }

        $params = [
            'to'        => $this->email,
            'fromName'  => $this->list->default->from_name,
            'subject'   => $_subject,
            'body'      => $_content,
        ];

        $sent = false;
        for ($i = 0; $i < 3; ++$i) {
            if ($sent = $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($this->list)->sendEmail($params)) {
                break;
            }

            /** @var DeliveryServer|null $server */
            $server = DeliveryServer::pickServer((int)$server->server_id, $this->list);

            if (empty($server)) {
                break;
            }
        }

        // since 2.1.4
        if (!$sent) {
            $this->handleSendWelcomeEmailFail();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getCampaignFilterActions(): array
    {
        return [
            self::CAMPAIGN_FILTER_ACTION_DID_OPEN      => t('list_subscribers', 'Did open'),
            self::CAMPAIGN_FILTER_ACTION_DID_CLICK     => t('list_subscribers', 'Did click'),
            self::CAMPAIGN_FILTER_ACTION_DID_NOT_OPEN  => t('list_subscribers', 'Did not open'),
            self::CAMPAIGN_FILTER_ACTION_DID_NOT_CLICK => t('list_subscribers', 'Did not click'),
        ];
    }

    /**
     * @return array
     */
    public function getFilterTimeUnits(): array
    {
        return [
            self::FILTER_TIME_UNIT_DAY   => t('list_subscribers', 'Days'),
            self::FILTER_TIME_UNIT_WEEK  => t('list_subscribers', 'Weeks'),
            self::FILTER_TIME_UNIT_MONTH => t('list_subscribers', 'Months'),
            self::FILTER_TIME_UNIT_YEAR  => t('list_subscribers', 'Years'),
        ];
    }

    /**
     * @param int $in
     *
     * @return string
     */
    public function getFilterTimeUnitValueForDb(int $in): string
    {
        if ($in == self::FILTER_TIME_UNIT_DAY) {
            return 'DAY';
        }
        if ($in == self::FILTER_TIME_UNIT_WEEK) {
            return 'WEEK';
        }
        if ($in == self::FILTER_TIME_UNIT_MONTH) {
            return 'MONTH';
        }
        if ($in == self::FILTER_TIME_UNIT_YEAR) {
            return 'YEAR';
        }
        return 'MONTH';
    }

    /**
     * @return string
     */
    public function getGridViewHtmlStatus(): string
    {
        if ($this->getIsMoved()) {
            $moved = ListSubscriberListMove::model()->findByAttributes([
                'source_subscriber_id'  => $this->subscriber_id,
                'source_list_id'        => $this->list_id,
            ]);

            if (!empty($moved)) {
                $url = 'javascript:;';
                if (apps()->isAppName('customer')) {
                    $url = createUrl('list_subscribers/update', [
                        'list_uid'       => $moved->destinationList->list_uid,
                        'subscriber_uid' => $moved->destinationSubscriber->subscriber_uid,
                    ]);
                }
                $where = CHtml::link($moved->destinationList->name, $url, ['target' => '_blank', 'title' => t('app', 'View')]);
                return ucfirst(t('list_subscribers', $this->status)) . ': ' . $where;
            }
        }

        return ucfirst(t('list_subscribers', $this->status));
    }

    /**
     * @return $this
     * @throws CException
     */
    public function sendCreatedNotifications(): self
    {
        $canContinue = false;
        if (
            !empty($this->list) &&
            !empty($this->list->customerNotification) &&
            $this->list->customerNotification->subscribe == ListCustomerNotification::TEXT_YES &&
            !empty($this->list->customerNotification->subscribe_to)
        ) {
            $canContinue = true;
        }

        if (!$canContinue) {
            return $this;
        }

        /** @var DeliveryServer|null $server */
        $server = DeliveryServer::pickServer(0, $this->list, ['useFor' => DeliveryServer::USE_FOR_LIST_EMAILS]);
        if (empty($server)) {
            return $this;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        //
        $fieldsTags = [];
        $fields     = [];
        $listFields = ListField::model()->findAll([
            'select'    => 'field_id, label, tag',
            'condition' => 'list_id = :lid',
            'order'     => 'sort_order ASC',
            'params'    => [':lid' => (int)$this->list->list_id],
        ]);
        foreach ($listFields as $field) {
            $fieldValues = ListFieldValue::model()->findAll([
                'select'    => 'value',
                'condition' => 'subscriber_id = :sid AND field_id = :fid',
                'params'    => [':sid' => (int)$this->subscriber_id, ':fid' => (int)$field->field_id],
            ]);
            $values = [];
            foreach ($fieldValues as $value) {
                $values[] = $value->value;
            }
            $fields[$field->label] = implode(', ', $values);
            $fieldsTags['[' . $field->tag . ']'] = implode(', ', $values);
        }
        //

        $submittedData = [];
        foreach ($fields as $key => $value) {
            $submittedData[] = sprintf('%s: %s', $key, $value);
        }
        $submittedData = implode('<br />', $submittedData);

        $params  = CommonEmailTemplate::getAsParamsArrayBySlug(
            'new-list-subscriber',
            [
                'fromName'  => $this->list->default->from_name,
                'subject'   => t('lists', 'New list subscriber!'),
            ],
            CMap::mergeArray($fieldsTags, [
                '[LIST_NAME]'      => $this->list->name,
                '[DETAILS_URL]'    => $optionUrl->getCustomerUrl(sprintf('lists/%s/subscribers/%s/update', $this->list->list_uid, $this->subscriber_uid)),
                '[SUBMITTED_DATA]' => $submittedData,
            ])
        );

        $recipients = explode(',', $this->list->customerNotification->subscribe_to);
        $recipients = array_map('trim', $recipients);

        foreach ($recipients as $recipient) {
            if (!FilterVarHelper::email($recipient)) {
                continue;
            }
            $params['to'] = [$recipient => $this->list->customer->getFullName()];
            $server->setDeliveryFor(DeliveryServer::DELIVERY_FOR_LIST)->setDeliveryObject($this->list)->sendEmail($params);
        }

        return $this;
    }

    /**
     * @param string $time
     * @return bool
     * @throws CException
     */
    public function getIsInactiveInTimePeriod(string $time = '-90 days'): bool
    {
        // did the subscriber received any campaign at all?
        $sql = 'SELECT subscriber_id FROM {{campaign_delivery_log}} WHERE subscriber_id = :sid AND DATE(date_added) >= :da LIMIT 1';
        $row = db()->createCommand($sql)->queryRow(true, [
            ':sid' => $this->subscriber_id,
            ':da'  => date('Y-m-d', (int)strtotime($time)),
        ]);
        $campaignDeliveryLog        = !empty($row['subscriber_id']);
        $campaignDeliveryLogArchive = false;

        if (!$campaignDeliveryLog) {
            $sql = 'SELECT subscriber_id FROM {{campaign_delivery_log_archive}} WHERE subscriber_id = :sid AND DATE(date_added) >= :da LIMIT 1';
            $row = db()->createCommand($sql)->queryRow(true, [
                ':sid' => $this->subscriber_id,
                ':da'  => date('Y-m-d', (int)strtotime($time)),
            ]);
            $campaignDeliveryLogArchive = !empty($row['subscriber_id']);
        }

        if (!$campaignDeliveryLog && !$campaignDeliveryLogArchive) {
            return false;
        }
        //

        // did the subscriber opened a campaign?
        $sql = 'SELECT subscriber_id FROM {{campaign_track_open}} WHERE subscriber_id = :sid AND DATE(date_added) >= :da LIMIT 1';
        $row = db()->createCommand($sql)->queryRow(true, [
            ':sid' => $this->subscriber_id,
            ':da'  => date('Y-m-d', (int)strtotime($time)),
        ]);

        if (!empty($row['subscriber_id'])) {
            return false;
        }

        // did the subscriber clicked a campaign?
        $sql = 'SELECT subscriber_id FROM {{campaign_track_url}} WHERE subscriber_id = :sid AND DATE(date_added) >= :da LIMIT 1';
        $row = db()->createCommand($sql)->queryRow(true, [
            ':sid' => $this->subscriber_id,
            ':da'  => date('Y-m-d', (int)strtotime($time)),
        ]);

        if (!empty($row['subscriber_id'])) {
            return false;
        }

        return true;
    }

    /**
     * @since 1.3.8.8
     * @return string
     */
    public function getDisplayEmail(): string
    {
        if (apps()->isAppName('backend')) {
            return (string)$this->email;
        }

        if ($this->getIsNewRecord() || empty($this->list_id) || empty($this->list->customer_id)) {
            return (string)$this->email;
        }

        $customer = $this->list->customer;
        if ($customer->getGroupOption('common.mask_email_addresses', 'no') == 'yes') {
            return StringHelper::maskEmailAddress((string)$this->email);
        }

        if (apps()->isAppName('frontend')) {
            /** @var CWebApplication $app */
            $app = app();

            /** @var Campaign|null $campaign */
            $campaign = $app->getController()->getData('campaign');

            if (!empty($campaign)) {
                if ($campaign->shareReports->share_reports_mask_email_addresses == CampaignOptionShareReports::TEXT_YES) {
                    return StringHelper::maskEmailAddress((string)$this->email);
                }
            }
        }

        return (string)$this->email;
    }

    /**
     * @return ListSubscriberOptinHistory|null
     */
    public function getOptinHistory(): ?ListSubscriberOptinHistory
    {
        if ($this->_optinHistory !== null) {
            return $this->_optinHistory;
        }

        return $this->_optinHistory = ListSubscriberOptinHistory::model()->findByAttributes([
            'subscriber_id' => (int)$this->subscriber_id,
        ]);
    }

    /**
     * @since 1.3.8.8
     * @return $this
     */
    public function createOptinHistory(): self
    {
        if (is_cli()) {
            return $this;
        }

        try {
            if ($this->getOptinHistory()) {
                $this->removeOptinHistory();
                $this->removeOptoutHistory();
            }

            $optinHistory                   = new ListSubscriberOptinHistory();
            $optinHistory->subscriber_id    = (int)$this->subscriber_id;
            $optinHistory->optin_ip         = (string)request()->getUserHostAddress();
            $optinHistory->optin_user_agent = StringHelper::truncateLength((string)request()->getUserAgent(), 255);
            $optinHistory->optin_date       = MW_DATETIME_NOW;
            $optinHistory->save(false);

            $this->_optinHistory = $optinHistory;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @since 1.3.8.8
     * @return $this
     */
    public function confirmOptinHistory(): self
    {
        if (is_cli()) {
            return $this;
        }

        try {
            if (!$this->getOptinHistory()) {
                $this->createOptinHistory();
            }

            /** @var ListSubscriberOptinHistory $optinHistory */
            $optinHistory = $this->getOptinHistory();

            $optinHistory->confirm_ip         = (string)request()->getUserHostAddress();
            $optinHistory->confirm_user_agent = StringHelper::truncateLength((string)request()->getUserAgent(), 255);
            $optinHistory->confirm_date       = MW_DATETIME_NOW;
            $optinHistory->save(false);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return void
     */
    public function removeOptinHistory(): void
    {
        $this->_optinHistory = null;
        ListSubscriberOptinHistory::model()->deleteAllByAttributes([
            'subscriber_id' => (int)$this->subscriber_id,
        ]);
    }

    /**
     * @return ListSubscriberOptoutHistory|null
     */
    public function getOptoutHistory(): ?ListSubscriberOptoutHistory
    {
        if ($this->_optoutHistory !== null) {
            return $this->_optoutHistory;
        }

        $this->_optoutHistory = ListSubscriberOptoutHistory::model()->findByAttributes([
            'subscriber_id' => (int)$this->subscriber_id,
        ]);

        return !empty($this->_optoutHistory) ? $this->_optoutHistory : null;
    }

    /**
     * @since 1.3.9.8
     * @return $this
     */
    public function createOptoutHistory(): self
    {
        if (is_cli()) {
            return $this;
        }

        try {
            if ($this->getOptoutHistory()) {
                $this->removeOptoutHistory();
            }

            $optoutHistory                    = new ListSubscriberOptoutHistory();
            $optoutHistory->subscriber_id     = (int)$this->subscriber_id;
            $optoutHistory->optout_ip         = (string)request()->getUserHostAddress();
            $optoutHistory->optout_user_agent = StringHelper::truncateLength((string)request()->getUserAgent(), 255);
            $optoutHistory->optout_date       = MW_DATETIME_NOW;
            $optoutHistory->save(false);

            $this->_optoutHistory = $optoutHistory;
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @since 1.3.9.8
     * @return $this
     */
    public function confirmOptoutHistory(): self
    {
        if (is_cli()) {
            return $this;
        }

        try {
            if (!$this->getOptoutHistory()) {
                $this->createOptoutHistory();
            }

            /** @var ListSubscriberOptoutHistory $optoutHistory */
            $optoutHistory = $this->getOptoutHistory();

            $optoutHistory->confirm_ip         = (string)request()->getUserHostAddress();
            $optoutHistory->confirm_user_agent = StringHelper::truncateLength((string)request()->getUserAgent(), 255);
            $optoutHistory->confirm_date       = MW_DATETIME_NOW;
            $optoutHistory->save(false);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $this;
    }

    /**
     * @return void
     */
    public function removeOptoutHistory(): void
    {
        $this->_optoutHistory = null;
        ListSubscriberOptoutHistory::model()->deleteAllByAttributes([
            'subscriber_id' => (int)$this->subscriber_id,
        ]);
    }

    /**
     * @param int $size
     * @return string
     */
    public function getAvatarUrl(int $size = 120): string
    {
        return sprintf('https://www.gravatar.com/avatar/%s?f=y&d=mm&s=%d', md5($this->email), (int)$size);
    }

    /**
     * @return string
     * @throws CException
     */
    public function getFullName(): string
    {
        $subscriberName = sprintf('%s %s', (string)$this->getCustomFieldValue('FNAME'), (string)$this->getCustomFieldValue('LNAME'));
        $subscriberName = trim((string)$subscriberName);
        if (!empty($subscriberName)) {
            return $subscriberName;
        }

        $subscriberName = sprintf('%s %s', (string)$this->getCustomFieldValue('FIRST_NAME'), (string)$this->getCustomFieldValue('LAST_NAME'));
        $subscriberName = trim((string)$subscriberName);
        if (!empty($subscriberName)) {
            return $subscriberName;
        }

        $subscriberName = (string)$this->getCustomFieldValue('NAME');
        if (!empty($subscriberName)) {
            return $subscriberName;
        }

        $subscriberName = '';
        if (!empty($this->email)) {
            $subscriberName = explode('@', $this->email);
            $subscriberName = $subscriberName[0];
            $subscriberName = (string)str_replace(['_', '-', '.'], ' ', $subscriberName);
            $subscriberName = ucwords(strtolower((string)$subscriberName));
        }

        return $subscriberName;
    }

    /**
     * @return array
     */
    public function getEmailMxRecords(): array
    {
        return NetDnsHelper::getHostMxRecords($this->getEmailHostname());
    }

    /**
     * @return string
     */
    public function getEmailHostname(): string
    {
        if (empty($this->email) || strpos($this->email, '@') === false) {
            return '';
        }
        $hostname = explode('@', $this->email);
        return $hostname[1];
    }

    /**
     * @param string $ipAddress
     * @return bool
     */
    public function saveIpAddress(string $ipAddress = ''): bool
    {
        if (empty($this->subscriber_id)) {
            return false;
        }

        if ($ipAddress && $ipAddress === (string)$this->ip_address) {
            return true;
        }

        if ($ipAddress) {
            $this->ip_address = $ipAddress;
        }
        $attributes = ['ip_address' => $this->ip_address];
        $this->last_updated = $attributes['last_updated'] = MW_DATETIME_NOW;
        return (bool)db()->createCommand()->update($this->tableName(), $attributes, 'subscriber_id = :id', [':id' => (int)$this->subscriber_id]);
    }

    /**
     * @return array
     * @throws CException
     */
    public function getFullData(): array
    {
        $data = [];

        $customFields = $this->getAllCustomFieldsWithValues();
        foreach ($customFields as $key => $value) {
            $data[(string)str_replace(['[', ']'], '', $key)] = $value;
        }

        foreach (['source', 'status', 'ip_address', 'date_added'] as $key) {
            $data[strtoupper((string)$key)] = $this->$key;
        }

        $optinData = [
            'optin_ip'          => '',
            'optin_date'        => '',
            'optin_confirm_ip'  => '',
            'optin_confirm_date'=> '',
        ];
        foreach ($optinData as $key => $value) {
            $data[strtoupper((string)$key)] = $value;
        }
        if (!empty($this->optinHistory)) {
            foreach ($optinData as $key => $value) {
                $tag = strtoupper((string)$key);
                if (in_array($key, ['optin_confirm_ip', 'optin_confirm_date'])) {
                    $key = (string)str_replace('optin_', '', $key);
                }
                $data[$tag] = $this->optinHistory->$key;
            }
        }

        $optoutData = [
            'optout_ip'           => '',
            'optout_date'         => '',
            'optout_confirm_ip'   => '',
            'optout_confirm_date' => '',
        ];
        foreach ($optoutData as $key => $value) {
            $data[strtoupper((string)$key)] = $value;
        }
        if ($this->getStatusIs(self::STATUS_UNSUBSCRIBED) && !empty($this->optoutHistory)) {
            foreach ($optoutData as $key => $value) {
                $tag = strtoupper((string)$key);
                if (in_array($key, ['optout_confirm_ip', 'optout_confirm_date'])) {
                    $key = (string)str_replace('optout_', '', $key);
                }
                $data[$tag] = $this->optoutHistory->$key;
            }
        }

        // 1.9.2
        $data['EMAIL'] = $this->getDisplayEmail();

        return $data;
    }

    /**
     * @param string $format
     * @return string
     */
    public function getLastOpenDate(string $format = 'Y-m-d H:i:s'): string
    {
        $criteria = new CDbCriteria();
        $criteria->compare('subscriber_id', (int)$this->subscriber_id);
        $criteria->select = 'campaign_id, date_added';
        $criteria->order  = 'id DESC';
        $criteria->limit  = 1;

        /** @var CampaignTrackOpen|null $model */
        $model = CampaignTrackOpen::model()->find($criteria);

        return !empty($model) ? (string)date($format, (int)strtotime((string)$model->date_added)) : '';
    }

    /**
     * @param string $format
     * @return string
     */
    public function getLastClickDate(string $format = 'Y-m-d H:i:s'): string
    {
        $criteria = new CDbCriteria();
        $criteria->compare('subscriber_id', (int)$this->subscriber_id);
        $criteria->select = 'subscriber_id, date_added';
        $criteria->order  = 'id DESC';
        $criteria->limit  = 1;

        /** @var CampaignTrackUrl|null $model */
        $model = CampaignTrackUrl::model()->find($criteria);

        return !empty($model) ? (string)date($format, (int)strtotime((string)$model->date_added)) : '';
    }

    /**
     * @param string $format
     * @return string
     */
    public function getLastSendDate(string $format = 'Y-m-d H:i:s'): string
    {
        $criteria = new CDbCriteria();
        $criteria->compare('subscriber_id', (int)$this->subscriber_id);
        $criteria->select = 'campaign_id, date_added';
        $criteria->order  = 'log_id DESC';
        $criteria->limit  = 1;

        /** @var CampaignDeliveryLog|null $model */
        $model = CampaignDeliveryLog::model()->find($criteria);

        return !empty($model) ? (string)date($format, (int)strtotime((string)$model->date_added)) : '';
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     */
    public function unsubscribeByCampaign(Campaign $campaign): bool
    {
        try {
            $this->saveStatus(self::STATUS_UNSUBSCRIBED);

            $count = CampaignTrackUnsubscribe::model()->countByAttributes([
                'campaign_id'   => (int)$campaign->campaign_id,
                'subscriber_id' => (int)$this->subscriber_id,
            ]);

            if (empty($count)) {
                $ipAddress = !is_cli() ? (string)request()->getUserHostAddress() : '127.0.0.1';
                $userAgent = !is_cli() ? StringHelper::truncateLength((string)request()->getUserAgent(), 255) : 'CLI';

                $trackUnsubscribe = new CampaignTrackUnsubscribe();
                $trackUnsubscribe->campaign_id   = (int)$campaign->campaign_id;
                $trackUnsubscribe->subscriber_id = (int)$this->subscriber_id;
                $trackUnsubscribe->note          = 'Unsubscribed via Web Hook!';
                $trackUnsubscribe->ip_address    = $ipAddress;
                $trackUnsubscribe->user_agent    = $userAgent;
                $trackUnsubscribe->save(false);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param int $id
     * @return void
     */
    public function setLastCampaignDeliveryLogId(int $id): void
    {
        $this->_lastCampaignDeliveryLogId = $id;
    }

    /**
     * @return int|null
     */
    public function getLastCampaignDeliveryLogId(): ?int
    {
        return $this->_lastCampaignDeliveryLogId;
    }

    /**
     * @return void
     */
    protected function handleSendApprovalEmailFail(): void
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        /** @var Lists $list */
        $list = $this->list;

        $messageTitle   = 'Unable to send email';
        $messageContent = 'Sending the approval email for one of the {list} list subscriber failed because the system was not able to find a suitable delivery server to send the email';

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
     * @return void
     */
    protected function handleSendWelcomeEmailFail(): void
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        /** @var Lists $list */
        $list = $this->list;

        $messageTitle   = 'Unable to send email';
        $messageContent = 'Sending the welcome email for one of the {list} list subscriber failed because the system was not able to find a suitable delivery server to send the email';

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
     * @return bool
     */
    protected function beforeSave()
    {
        if (empty($this->subscriber_uid)) {
            $this->subscriber_uid = $this->generateUid();
        }

        // since 1.6.4
        if (!empty($this->list_id) && !empty($this->list) && $this->list->getSubscribersCountCacheEnabled()) {

            // new subscriber
            if ($this->getIsNewRecord()) {

                // new record, add to all count
                $this->list->incrementSubscribersCount();

                // if confirmed, also add to confirmed count
                if ($this->getIsConfirmed()) {
                    $this->list->incrementSubscribersCount(self::STATUS_CONFIRMED);
                }

                // existing subscriber
            } else {

                // make sure the increment and decrement happens only once per status regardless of how many times it is called.
                if ($this->status != $this->subscribersCountSubscriberLastStatus) {

                    // if now confirmed, but was not before
                    if ($this->getIsConfirmed() && $this->afterFindStatus !== self::STATUS_CONFIRMED) {
                        $this->list->incrementSubscribersCount(self::STATUS_CONFIRMED);

                    // if not confirmed anymore, but used to be
                    } elseif (!$this->getIsConfirmed() && $this->afterFindStatus == self::STATUS_CONFIRMED) {
                        $this->list->decrementSubscribersCount(self::STATUS_CONFIRMED);
                    }
                }
            }

            // update the status to current one
            $this->subscribersCountSubscriberLastStatus = $this->status;
        }
        //

        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterDelete()
    {
        // since 1.6.4
        if (!empty($this->list)) {
            $this->list->decrementSubscribersCount();
            if ($this->getIsConfirmed()) {
                $this->list->decrementSubscribersCount(self::STATUS_CONFIRMED);
            }
        }
        //

        parent::afterDelete();
    }
}
