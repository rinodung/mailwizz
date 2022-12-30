<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AllCustomersListsSubscribersFilters
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.3
 */

class AllCustomersListsSubscribersFilters extends ListSubscriber
{
    /**
     * flag for view list
     */
    const ACTION_VIEW = 'view';

    /**
     * flag for export
     */
    const ACTION_EXPORT = 'export';

    /**
     * flag for confirm
     */
    const ACTION_CONFIRM = 'confirm';

    /**
     * flag for disable
     */
    const ACTION_DISABLE = 'disable';

    /**
     * flag for unsubscribe
     */
    const ACTION_UNSUBSCRIBE = 'unsubscribe';

    /**
     * flag for blacklist
     */
    const ACTION_BLACKLIST = 'blacklist';

    /**
     * flag for delete
     */
    const ACTION_DELETE = 'delete';

    /**
     * flag for the result set batch size
     */
    const PROCESS_SUBSCRIBERS_BATCH_SIZE = 1000;
    const PROCESS_SUBSCRIBERS_CHUNK_SIZE = 500;

    /**
     * @var int - the user that does the action
     */
    public $user_id = 0;

    /**
     * @var array $customers customer_id => name(email)
     */
    public $customers = [];

    /**
     * @var array $lists list id => list name
     */
    public $lists = [];

    /**
     * @var array $statuses - subscriber statuses
     */
    public $statuses = [];

    /**
     * @var array $sources - import sources
     */
    public $sources = [];

    /**
     * @var string $unique - only unique subs
     */
    public $unique;

    /**
     * @var string $uid
     */
    public $uid;

    /**
     * @var string $ip
     */
    public $ip;

    /**
     * @var string $email
     */
    public $email;

    /**
     * @var string $action
     */
    public $action;

    /**
     * @var bool
     */
    public $hasSetFilters = false;

    /**
     * @var string
     */
    public $campaigns_action;

    /**
     * @var array
     */
    public $campaigns;

    /**
     * @var string
     */
    public $campaigns_atuc;

    /**
     * @var string
     */
    public $campaigns_atu;

    /**
     * @var string
     */
    public $date_added_start;

    /**
     * @var string
     */
    public $date_added_end;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['customers', '_validateMultipleCustomersSelection'],
            ['lists', '_validateMultipleListsSelection'],
            ['statuses', '_validateMultipleStatusesSelection'],
            ['sources', '_validateMultipleSourcesSelection'],
            ['action', 'in', 'range' => array_keys($this->getActionsList())],
            ['unique', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['campaigns_action', 'in', 'range' => array_keys($this->getCampaignFilterActions())],
            ['campaigns_atu', 'in', 'range' => array_keys($this->getFilterTimeUnits())],
            ['campaigns_atuc', 'numerical', 'integerOnly' => true, 'min' => 1, 'max' => 1024],
            ['uid, email, ip, campaigns', 'safe'],
            ['date_added_start, date_added_end', 'date', 'format' => 'yyyy-M-d'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return CMap::mergeArray(parent::attributeLabels(), [
            'customer_id'    => t('list_subscribers', 'Customer'),
            'lists'          => t('list_subscribers', 'Lists'),
            'statuses'       => t('list_subscribers', 'Statuses'),
            'sources'        => t('list_subscribers', 'Sources'),
            'action'         => t('list_subscribers', 'Action'),
            'unique'         => t('list_subscribers', 'Unique'),
            'uid'            => t('list_subscribers', 'Unique ID'),
            'email'          => t('list_subscribers', 'Email'),
            'ip'             => t('list_subscribers', 'Ip Address'),

            'campaigns'         => t('list_subscribers', 'Campaigns'),
            'campaigns_action'  => t('list_subscribers', 'Campaigns Action'),
            'campaigns_atuc'    => '',
            'campaigns_atu'     => '',

            'date_added_start' => t('list_subscribers', 'Date added start'),
            'date_added_end'   => t('list_subscribers', 'Date added end'),
        ]);
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        return [
            'uid'   => 'jm338w77e4eea',
            'email' => 'name@domain.com',
            'ip'    => '123.123.123.100',
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        return true;
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return AllCustomersListsSubscribersFilters the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var AllCustomersListsSubscribersFilters $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getCustomersList(): array
    {
        $customersList = [];

        $criteria = new CDbCriteria();
        $criteria->addNotInCondition('status', [Customer::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);

        $customers = Customer::model()->findAll($criteria);
        foreach ($customers as $customer) {
            $customersList[$customer->customer_id] = $customer->getFullName() . '(' . $customer->email . ')';
        }

        return $customersList;
    }

    /**
     * @return array
     */
    public function getListsList(): array
    {
        $criteria = new CDbCriteria();
        $criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);

        return ListsCollection::findAll($criteria)->mapWithKeys(function (Lists $list) {
            return [$list->list_id => $list->name . '(' . $list->display_name . ')'];
        })->all();
    }

    /**
     * @inheritDoc
     */
    public function getStatusesList(): array
    {
        return $this->getEmptySubscriberModel()->getFilterStatusesList();
    }

    /**
     * @return array
     */
    public function getSourcesList(): array
    {
        return $this->getEmptySubscriberModel()->getSourcesList();
    }

    /**
     * @return array
     */
    public function getActionsList(): array
    {
        return [
            self::ACTION_VIEW        => t('list_subscribers', ucfirst(self::ACTION_VIEW)),
            self::ACTION_EXPORT      => t('list_subscribers', ucfirst(self::ACTION_EXPORT)),
            self::ACTION_CONFIRM     => t('list_subscribers', ucfirst(self::ACTION_CONFIRM)),
            self::ACTION_DISABLE     => t('list_subscribers', ucfirst(self::ACTION_DISABLE)),
            self::ACTION_UNSUBSCRIBE => t('list_subscribers', ucfirst(self::ACTION_UNSUBSCRIBE)),
            self::ACTION_BLACKLIST   => t('list_subscribers', ucfirst(self::ACTION_BLACKLIST)),
            self::ACTION_DELETE      => t('list_subscribers', ucfirst(self::ACTION_DELETE)),
        ];
    }

    /**
     * @return bool
     */
    public function getIsViewAction(): bool
    {
        return empty($this->action) || (string)$this->action === self::ACTION_VIEW;
    }

    /**
     * @return bool
     */
    public function getIsExportAction(): bool
    {
        return (string)$this->action === self::ACTION_EXPORT;
    }

    /**
     * @return bool
     */
    public function getIsConfirmAction(): bool
    {
        return (string)$this->action === self::ACTION_CONFIRM;
    }

    /**
     * @return bool
     */
    public function getIsUnsubscribeAction(): bool
    {
        return (string)$this->action === self::ACTION_UNSUBSCRIBE;
    }

    /**
     * @return bool
     */
    public function getIsDisableAction(): bool
    {
        return (string)$this->action === self::ACTION_DISABLE;
    }

    /**
     * @return bool
     */
    public function getIsBlacklistAction(): bool
    {
        return (string)$this->action === self::ACTION_BLACKLIST;
    }

    /**
     * @return bool
     */
    public function getIsDeleteAction(): bool
    {
        return (string)$this->action === self::ACTION_DELETE;
    }

    /**
     * @return ListSubscriber
     */
    public function getEmptySubscriberModel(): ListSubscriber
    {
        static $subscriber;
        if ($subscriber !== null) {
            return $subscriber;
        }
        return $subscriber = new ListSubscriber();
    }

    /**
     * @return Generator
     */
    public function getSubscribersIds(): Generator
    {
        $criteria = $this->buildSubscribersCriteria();
        $criteria->select = 't.subscriber_id';
        $criteria->limit  = self::PROCESS_SUBSCRIBERS_BATCH_SIZE;
        $criteria->offset = 0;

        while (true) {
            $models = ListSubscriber::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield (int)$model->subscriber_id;
            }

            $criteria->offset = (int)$criteria->offset + (int)$criteria->limit;
        }
    }

    /**
     * @return array
     */
    public function getSubscribersIdsChunks(): array
    {
        return array_chunk(iterator_to_array($this->getSubscribersIds()), self::PROCESS_SUBSCRIBERS_CHUNK_SIZE);
    }

    /**
     * @return Generator
     */
    public function getSubscribers(): Generator
    {
        $criteria = $this->buildSubscribersCriteria();
        $criteria->limit  = self::PROCESS_SUBSCRIBERS_BATCH_SIZE;
        $criteria->offset = 0;

        while (true) {
            $models = ListSubscriber::model()->findAll($criteria);
            if (empty($models)) {
                break;
            }

            foreach ($models as $model) {
                yield $model;
            }

            $criteria->offset = (int)$criteria->offset + (int)$criteria->limit;
        }
    }

    /**
     * @param bool $isCount
     * @return CDbCriteria
     */
    public function buildSubscribersCriteria(bool $isCount = false): CDbCriteria
    {
        $customers  = array_filter(array_unique(array_map('intval', $this->customers)));
        $lists      = array_filter(array_unique(array_map('intval', $this->lists)));
        $criteria   = new CDbCriteria();
        $criteria->with = [];

        if (!empty($customers)) {
            $criteria->with['list'] = [
                'select'    => false,
                'joinType'  => 'INNER JOIN',
            ];
            $criteria->addInCondition('list.customer_id', $customers);
        }

        // 1.8.8
        if (empty($lists)) {
            $_criteria = new CDbCriteria();
            $_criteria->select = 'list_id';
            if (!empty($customers)) {
                $_criteria->addInCondition('customer_id', $customers);
            }
            $_criteria->addNotInCondition('status', [Lists::STATUS_PENDING_DELETE, Lists::STATUS_ARCHIVED]);
            $lists = ListsCollection::findAll($_criteria)->map(function (Lists $list) {
                return $list->list_id;
            })->toArray();
        }

        if (!empty($lists)) {
            $criteria->addInCondition('t.list_id', $lists);
        }

        $criteria->compare('t.subscriber_uid', $this->uid, true);

        // 1.3.7.1
        if (!empty($this->email)) {
            if (strpos($this->email, ',') !== false) {
                $emails = CommonHelper::getArrayFromString((string)$this->email);
                foreach ($emails as $index => $email) {
                    if (!FilterVarHelper::email($email)) {
                        unset($emails[$index]);
                    }
                }
                if (!empty($emails)) {
                    $criteria->addInCondition('t.email', $emails);
                }
            } else {
                $criteria->compare('t.email', $this->email, true);
            }
        }
        //

        $criteria->compare('t.ip_address', $this->ip, true);

        if (!empty($this->statuses) && is_array($this->statuses)) {
            $criteria->addInCondition('t.status', $this->statuses);
        }

        if (!empty($this->sources) && is_array($this->sources)) {
            $criteria->addInCondition('t.source', $this->sources);
        }

        if (!empty($this->date_added_start)) {
            $criteria->compare('DATE(t.date_added)', '>=' . date('Y-m-d', (int)strtotime($this->date_added_start)));
        }

        if (!empty($this->date_added_end)) {
            $criteria->compare('DATE(t.date_added)', '<=' . date('Y-m-d', (int)strtotime($this->date_added_end)));
        }

        if (!empty($this->campaigns_action)) {
            $action = $this->campaigns_action;

            $campaignIds = array_filter(array_unique(array_map('intval', (array)$this->campaigns)));

            if (empty($campaignIds)) {
                $campaignIds = array_keys($this->getCampaignsList());
            }

            if (empty($campaignIds)) {
                $campaignIds = [0];
            }

            $atu  = $this->getFilterTimeUnitValueForDb((int)$this->campaigns_atu);
            $atuc = (int)$this->campaigns_atuc;
            $atuc = $atuc > 1024 ? 1024 : $atuc;
            $atuc = $atuc < 0 ? 0 : $atuc;

            if (in_array($action, [self::CAMPAIGN_FILTER_ACTION_DID_OPEN, self::CAMPAIGN_FILTER_ACTION_DID_NOT_OPEN])) {
                $rel = [
                    'select'   => false,
                    'together' => true,
                ];

                if ($action == self::CAMPAIGN_FILTER_ACTION_DID_OPEN) {
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

            if (in_array($action, [self::CAMPAIGN_FILTER_ACTION_DID_CLICK, self::CAMPAIGN_FILTER_ACTION_DID_NOT_CLICK])) {
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

                if ($action == self::CAMPAIGN_FILTER_ACTION_DID_CLICK) {
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
                $this->unique = self::TEXT_YES;
            }

            if (in_array($action, [self::CAMPAIGN_FILTER_ACTION_DID_OPEN, self::CAMPAIGN_FILTER_ACTION_DID_NOT_OPEN, self::CAMPAIGN_FILTER_ACTION_DID_CLICK, self::CAMPAIGN_FILTER_ACTION_DID_NOT_CLICK])) {
                $criteria->with['deliveryLogs'] = [
                    'joinType'  => 'LEFT JOIN',
                ];
                $criteria->with['deliveryLogsArchive'] = [
                    'joinType'  => 'LEFT JOIN',
                ];
                $criteria->addCondition('(
	                EXISTS(SELECT subscriber_id FROM {{campaign_delivery_log}} WHERE subscriber_id = t.subscriber_id LIMIT 1)
	                OR
	                EXISTS(SELECT subscriber_id FROM {{campaign_delivery_log_archive}} WHERE subscriber_id = t.subscriber_id LIMIT 1)
	            )');
            }
        }

        if ($this->unique == self::TEXT_YES) {
            $criteria->group = 't.email';
        } else {
            $criteria->group = '';
        }

        $criteria->order  = 't.subscriber_id DESC';

        // 1.5.0
        if ($isCount && $this->unique == self::TEXT_YES) {
            $criteria->select = 'COUNT(DISTINCT(t.email)) as count';
            $criteria->group  = '';
        }

        return $criteria;
    }

    /**
     * @return CActiveDataProvider
     * @throws CException
     */
    public function getActiveDataProvider(): CActiveDataProvider
    {
        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $this->buildSubscribersCriteria(),
            'countCriteria' => $this->buildSubscribersCriteria(true),
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
     * @return array
     */
    public function getCampaignsList(): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id, name';
        $criteria->addNotInCondition('status', [Campaign::STATUS_PENDING_DELETE, Campaign::STATUS_DRAFT]);

        if (!empty($this->customers)) {
            $customerIds = array_filter(array_unique(array_map('intval', (array)$this->customers)));
            if (empty($customerIds)) {
                $customerIds = [0];
            }
            $criteria->addInCondition('customer_id', $customerIds);
        }

        $criteria->order = 'campaign_id DESC';

        return CampaignCollection::findAll($criteria)->mapWithKeys(function (Campaign $campaign) {
            return [$campaign->campaign_id => $campaign->name];
        })->all();
    }

    /**
     * Confirm subscribers matching the criteria
     */
    public function confirmSubscribers(): void
    {
        array_map([$this, 'confirmSubscribersByIds'], $this->getSubscribersIdsChunks());
    }

    /**
     * @param array $subscribersIds
     *
     * @throws CException
     */
    public function confirmSubscribersByIds(array $subscribersIds = []): void
    {
        if (empty($subscribersIds)) {
            return;
        }

        try {
            $subscribersIds = array_filter(array_unique(array_map('intval', $subscribersIds)));

            // get all blacklisted subscribers
            $command     = db()->createCommand();
            $subscribers = $command->select('email')->from('{{list_subscriber}}')->where(['and',
                ['in', 'subscriber_id', $subscribersIds],
                ['in', 'status', [ListSubscriber::STATUS_BLACKLISTED]],
            ])->queryAll();

            if (!empty($subscribers)) {
                $emails = [];
                foreach ($subscribers as $subscriber) {
                    $emails[] = $subscriber['email'];
                }

                $emails = array_chunk($emails, 100);

                foreach ($emails as $emailsChunk) {

                    // delete from customer blacklist
                    db()->createCommand()->delete('{{customer_email_blacklist}}', ['and',
                        ['in', 'email', $emailsChunk],
                    ]);

                    db()->createCommand()->delete('{{email_blacklist}}', ['and',
                        ['in', 'email', $emailsChunk],
                    ]);
                }
            }

            // statuses that are not allowed to be marked confirmed
            $notInStatus = [
                ListSubscriber::STATUS_CONFIRMED,
                ListSubscriber::STATUS_UNSUBSCRIBED,
            ];

            $command = db()->createCommand();
            $command->update('{{list_subscriber}}', [
                'status'        => ListSubscriber::STATUS_CONFIRMED,
                'last_updated'  => MW_DATETIME_NOW,
            ], ['and',
                ['in', 'subscriber_id', $subscribersIds],
                ['not in', 'status', $notInStatus],
            ]);


            // 1.3.8.8 - remove from moved table
            $_criteria = new CDbCriteria();
            $_criteria->addInCondition('source_subscriber_id', $subscribersIds);
            ListSubscriberListMove::model()->deleteAll($_criteria);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        // since 1.6.4
        Lists::flushSubscribersCountCacheBySubscriberIds($subscribersIds);
    }

    /**
     * Unsubscribe subscribers matching the criteria
     */
    public function unsubscribeSubscribers(): void
    {
        array_map([$this, 'unsubscribeSubscribersByIds'], $this->getSubscribersIdsChunks());
    }

    /**
     * @param array $subscribersIds
     *
     * @throws CException
     */
    public function unsubscribeSubscribersByIds(array $subscribersIds = []): void
    {
        if (empty($subscribersIds)) {
            return;
        }

        $subscribersIds = array_filter(array_unique(array_map('intval', $subscribersIds)));
        try {
            $command = db()->createCommand();
            $command->update('{{list_subscriber}}', [
                'status'        => ListSubscriber::STATUS_UNSUBSCRIBED,
                'last_updated'  => MW_DATETIME_NOW,
            ], ['and',
                ['in', 'subscriber_id', $subscribersIds],
                ['in', 'status', [ListSubscriber::STATUS_CONFIRMED]],
            ]);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        // since 1.6.4
        Lists::flushSubscribersCountCacheBySubscriberIds($subscribersIds);
    }

    /**
     * Disable subscribers matching the criteria
     */
    public function disableSubscribers(): void
    {
        array_map([$this, 'disableSubscribersByIds'], $this->getSubscribersIdsChunks());
    }

    /**
     * @param array $subscribersIds
     *
     * @throws CException
     */
    public function disableSubscribersByIds(array $subscribersIds = []): void
    {
        if (empty($subscribersIds)) {
            return;
        }

        $subscribersIds = array_filter(array_unique(array_map('intval', $subscribersIds)));
        try {
            $command = db()->createCommand();
            $command->update('{{list_subscriber}}', [
                'status'        => ListSubscriber::STATUS_DISABLED,
                'last_updated'  => MW_DATETIME_NOW,
            ], ['and',
                ['in', 'subscriber_id', $subscribersIds],
                ['in', 'status', [ListSubscriber::STATUS_CONFIRMED]],
            ]);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        // since 1.6.4
        Lists::flushSubscribersCountCacheBySubscriberIds($subscribersIds);
    }

    /**
     * Blacklist subscribers matching the criteria
     */
    public function blacklistSubscribers(): void
    {
        array_map([$this, 'blacklistSubscribersById'], $this->getSubscribersIdsChunks());
    }

    /**
     * @param array $subscribersIds
     *
     * @throws CException
     */
    public function blacklistSubscribersById(array $subscribersIds = []): void
    {
        if (empty($subscribersIds)) {
            return;
        }

        $subscribersIds = array_filter(array_unique(array_map('intval', $subscribersIds)));

        try {
            $command = db()->createCommand();
            $command->update('{{list_subscriber}}', [
                'status'        => ListSubscriber::STATUS_BLACKLISTED,
                'last_updated'  => MW_DATETIME_NOW,
            ], ['and',
                ['in', 'subscriber_id', $subscribersIds],
                ['not in', 'status', [ListSubscriber::STATUS_BLACKLISTED, ListSubscriber::STATUS_MOVED]],
            ]);

            foreach ($subscribersIds as $subscriberId) {
                try {
                    $subscriber = ListSubscriber::model()->findByPk((int)$subscriberId);
                    $customerEmailBlacklist              = new CustomerEmailBlacklist();
                    $customerEmailBlacklist->customer_id = $subscriber->list->customer_id;
                    $customerEmailBlacklist->email       = $subscriber->email;
                    $customerEmailBlacklist->save();
                } catch (Exception $e) {
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
                }
            }
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        // since 1.6.4
        Lists::flushSubscribersCountCacheBySubscriberIds($subscribersIds);
    }

    /**
     * Delete subscribers matching the criteria
     */
    public function deleteSubscribers(): int
    {
        return array_sum(array_map([$this, 'deleteSubscribersByIds'], $this->getSubscribersIdsChunks()));
    }

    /**
     * @param array $subscribersIds
     * @return int
     * @throws CException
     */
    public function deleteSubscribersByIds(array $subscribersIds = []): int
    {
        if (empty($subscribersIds)) {
            return 0;
        }

        $subscribersIds = array_filter(array_unique(array_map('intval', $subscribersIds)));

        // since 1.6.4
        Lists::flushSubscribersCountCacheBySubscriberIds($subscribersIds);

        $command = db()->createCommand();
        $subscribers = $command->select('email')->from('{{list_subscriber}}')->where(['and',
            ['in', 'subscriber_id', $subscribersIds],
            ['in', 'status', [ListSubscriber::STATUS_BLACKLISTED]],
        ])->queryAll();

        if (!empty($subscribers)) {
            $emails = [];
            foreach ($subscribers as $subscriber) {
                $emails[] = $subscriber['email'];
            }
            $emails = array_chunk($emails, 100);
            foreach ($emails as $emailsChunk) {
                $command = db()->createCommand();
                $command->delete('{{customer_email_blacklist}}', ['and',
                    ['in', 'email', $emailsChunk],
                ]);
            }
        }

        $count = 0;
        try {
            $command = db()->createCommand();
            $count   = $command->delete('{{list_subscriber}}', ['and',
                ['in', 'subscriber_id', $subscribersIds],
            ]);
        } catch (Exception $e) {
            Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);
        }

        return $count;
    }

    /**
     * @return string
     */
    public function getDatePickerFormat(): string
    {
        return 'yy-mm-dd';
    }

    /**
     * @return string
     */
    public function getDatePickerLanguage(): string
    {
        $language = app()->getLanguage();
        if (strpos($language, '_') === false) {
            return $language;
        }
        $language = explode('_', $language);

        return $language[0];
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateMultipleCustomersSelection(string $attribute, array $params = []): void
    {
        $values = $this->$attribute;
        if (empty($values) || !is_array($values)) {
            $values = [];
        }

        foreach ($values as $value) {
            if (!Customer::model()->findByPk($value)) {
                $this->addError($attribute, t('list_subscribers', 'Invalid customer identifier!'));
                break;
            }
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateMultipleListsSelection(string $attribute, array $params = []): void
    {
        $values = $this->$attribute;
        if (empty($values) || !is_array($values)) {
            $values = [];
        }

        foreach ($values as $value) {
            if (!Lists::model()->findByPk($value)) {
                $this->addError($attribute, t('list_subscribers', 'Invalid list identifier!'));
                break;
            }
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     */
    public function _validateMultipleStatusesSelection(string $attribute, array $params = []): void
    {
        $values = $this->$attribute;
        if (empty($values) || !is_array($values)) {
            return;
        }

        $this->$attribute = $values = array_filter(array_unique(array_values($values)));
        if (empty($values)) {
            return;
        }

        $statuses = array_keys($this->getStatusesList());

        foreach ($values as $value) {
            if (!in_array($value, $statuses)) {
                $this->addError($attribute, t('list_subscribers', 'Invalid subscriber status!'));
                break;
            }
        }
    }

    /**
     * @param string $attribute
     * @param array $params
     *
     * @return void
     */
    public function _validateMultipleSourcesSelection(string $attribute, array $params = []): void
    {
        $values = $this->$attribute;
        if (empty($values) || !is_array($values)) {
            return;
        }

        $this->$attribute = $values = array_filter(array_unique(array_values($values)));
        if (empty($values)) {
            return;
        }

        $statuses = array_keys($this->getSourcesList());

        foreach ($values as $value) {
            if (!in_array($value, $statuses)) {
                $this->addError($attribute, t('list_subscribers', 'Invalid list source!'));
                break;
            }
        }
    }
}
