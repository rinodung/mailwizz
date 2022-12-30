<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignDeliveryLog
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "campaign_delivery_log".
 *
 * The followings are the available columns in table 'campaign_delivery_log':
 * @property string $log_id
 * @property integer|string $campaign_id
 * @property integer|string $subscriber_id
 * @property integer|string $server_id
 * @property string $message
 * @property string $processed
 * @property integer $retries
 * @property integer $max_retries
 * @property string $email_message_id
 * @property string $delivery_confirmed
 * @property string|null $status
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property ListSubscriber $subscriber
 * @property Campaign $campaign
 * @property DeliveryServer $server
 */
class CampaignDeliveryLog extends ActiveRecord
{
    /**
     * Flags for various statuses
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_FATAL_ERROR = 'fatal-error';
    const STATUS_TEMPORARY_ERROR = 'temporary-error';
    const STATUS_BLACKLISTED = 'blacklisted';
    const STATUS_SUPPRESSED = 'suppressed';
    const STATUS_GIVEUP = 'giveup';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_DOMAIN_POLICY_REJECT = 'ds-dp-reject';
    const STATUS_HANDLED_BY_OTHER_SEND_GROUP_CAMPAIGN = 'hdl-by-sg-cmp';

    /**
     * @var int|string
     */
    public $customer_id;

    /**
     * @var int|string
     */
    public $list_id;

    /**
     * @var int|string
     */
    public $segment_id;

    /**
     * @return array
     */
    public function behaviors()
    {
        $behaviors = [];

        if (app_param('send.campaigns.command.useTempQueueTables', false)) {
            $behaviors['toQueueTable'] = [
                'class' => 'common.components.db.behaviors.CampaignSentToCampaignQueueTableBehavior',
            ];
        }

        return CMap::mergeArray($behaviors, parent::behaviors());
    }

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_delivery_log}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['delivery_confirmed, status', 'safe', 'on' => 'customer-search'],
            ['customer_id, campaign_id, list_id, segment_id, subscriber_id, server_id, message, processed, delivery_confirmed, status', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'subscriber' => [self::BELONGS_TO, ListSubscriber::class, 'subscriber_id'],
            'campaign'   => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'server'     => [self::BELONGS_TO, DeliveryServer::class, 'server_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'log_id'             => t('campaigns', 'Log'),
            'campaign_id'        => t('campaigns', 'Campaign'),
            'subscriber_id'      => t('campaigns', 'Subscriber'),
            'message'            => t('campaigns', 'Message'),
            'processed'          => t('campaigns', 'Processed'),
            'email_message_id'   => t('campaigns', 'Message ID'),
            'delivery_confirmed' => t('campaigns', 'Sent'),
            'server_id'          => t('campaigns', 'Delivery server'),

            // search
            'customer_id'   => t('campaigns', 'Customer'),
            'list_id'       => t('campaigns', 'List'),
            'segment_id'    => t('campaigns', 'Segment'),
        ];

        $labels = CMap::mergeArray($labels, parent::attributeLabels());

        return CMap::mergeArray($labels, [
            'status' => t('campaigns', 'Processed status'),
        ]);
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
    public function customerSearch()
    {
        $criteria = new CDbCriteria();

        // for BC
        $campaignId = (int)$this->campaign_id;
        if ($campaignId >= 0) {
            $criteria->compare('campaign_id', $campaignId);
        }

        if (!empty($this->subscriber_id)) {
            $criteria->compare('subscriber_id', (int)$this->subscriber_id);
        }

        $criteria->compare('delivery_confirmed', $this->delivery_confirmed);
        $criteria->compare('status', $this->status);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'log_id'    => CSort::SORT_DESC,
                ],
            ],
        ]);
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
        $criteria->with = [
            'campaign' => [
                'select'   => 'campaign.campaign_uid, campaign.name, campaign.list_id, campaign.segment_id',
                'joinType' => 'INNER JOIN',
                'together' => true,
                'with'     => [
                    'list' => [
                        'select'    => 'list.list_uid, list.name',
                        'joinType'  => 'INNER JOIN',
                        'together'  => true,
                    ],
                    'customer' => [
                        'select'    => 'customer.customer_id, customer.first_name, customer.last_name',
                        'joinType'  => 'INNER JOIN',
                        'together'  => true,
                    ],
                ],
            ],
            'subscriber' => [
                'select'    => 'subscriber.subscriber_uid, subscriber.email',
                'joinType'  => 'INNER JOIN',
                'together'  => true,
            ],
            'server' => [
                'select'    => 'server.name, server.hostname, server.type',
                'joinType'  => 'LEFT JOIN',
                'together'  => true,
            ],
        ];

        if (!empty($this->customer_id) && is_numeric($this->customer_id)) {
            $criteria->with['campaign']['with']['customer'] = array_merge($criteria->with['campaign']['with']['customer'], [
                'condition' => 'customer.customer_id = :customerId',
                'params'    => [':customerId' => $this->customer_id],
            ]);
        } elseif (!empty($this->customer_id) && is_string($this->customer_id)) {
            $criteria->with['campaign']['with']['customer'] = array_merge($criteria->with['campaign']['with']['customer'], [
                'condition' => 'CONCAT(customer.first_name, " ", customer.last_name) LIKE :customerName',
                'params'    => [':customerName' => '%' . $this->customer_id . '%'],
            ]);
        }

        if (!empty($this->campaign_id) && is_numeric($this->campaign_id)) {
            $criteria->with['campaign'] = array_merge($criteria->with['campaign'], [
                'condition' => 'campaign.campaign_id = :campaignId',
                'params'    => [':campaignId' => $this->campaign_id],
            ]);
        } elseif (!empty($this->campaign_id) && is_string($this->campaign_id)) {
            $criteria->with['campaign'] = array_merge($criteria->with['campaign'], [
                'condition' => 'campaign.name LIKE :campaignName',
                'params'    => [':campaignName' => '%' . $this->campaign_id . '%'],
            ]);
        }

        if (!empty($this->list_id) && is_numeric($this->list_id)) {
            $criteria->with['campaign']['with']['list']['condition'] = 'list.list_id = :listId';
            $criteria->with['campaign']['with']['list']['params']    = [':listId' => $this->list_id];
        } elseif (!empty($this->list_id) && is_string($this->list_id)) {
            $criteria->with['campaign']['with']['list']['condition'] = 'list.name LIKE :listName';
            $criteria->with['campaign']['with']['list']['params']    = [':listName' => '%' . $this->list_id . '%'];
        }

        if (!empty($this->segment_id) && is_numeric($this->segment_id)) {
            $criteria->with['campaign']['with']['segment']['condition'] = 'segment.segment_id = :segmentId';
            $criteria->with['campaign']['with']['segment']['params']    = [':segmentId' => $this->segment_id];
        } elseif (!empty($this->segment_id) && is_string($this->segment_id)) {
            $criteria->with['campaign']['with']['segment']['condition'] = 'segment.name LIKE :segmentId';
            $criteria->with['campaign']['with']['segment']['params']    = [':segmentId' => '%' . $this->segment_id . '%'];
        }

        if (!empty($this->subscriber_id) && is_numeric($this->subscriber_id)) {
            $criteria->with['subscriber'] = array_merge($criteria->with['subscriber'], [
                'condition' => 'subscriber.subscriber_id = :subscriberId',
                'params'    => [':subscriberId' => $this->subscriber_id],
            ]);
        } elseif (!empty($this->subscriber_id) && is_string($this->subscriber_id)) {
            $criteria->with['subscriber'] = array_merge($criteria->with['subscriber'], [
                'condition' => 'subscriber.email LIKE :subscriberId',
                'params'    => [':subscriberId' => '%' . $this->subscriber_id . '%'],
            ]);
        }

        if (!empty($this->server_id) && is_numeric($this->server_id)) {
            $criteria->with['server'] = array_merge($criteria->with['server'], [
                'condition' => 'server.server_id = :serverId',
                'params'    => [':serverId' => $this->server_id],
            ]);
        } elseif (!empty($this->server_id) && is_string($this->server_id)) {
            $criteria->with['server'] = array_merge($criteria->with['server'], [
                'condition' => '(server.name LIKE :serverId OR server.hostname LIKE :serverId)',
                'params'    => [':serverId' => '%' . $this->server_id . '%'],
            ]);
        }

        $criteria->compare('t.message', $this->message, true);
        $criteria->compare('t.processed', $this->processed);
        $criteria->compare('t.delivery_confirmed', $this->delivery_confirmed);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.log_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    't.log_id'    => CSort::SORT_DESC,
                ],
            ],
        ]);
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
    public function searchLight()
    {
        $criteria = new CDbCriteria();
        $criteria->order  = 't.log_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    't.log_id'    => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignDeliveryLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignDeliveryLog $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getStatusesArray(): array
    {
        return [
            self::STATUS_SUCCESS                                => t('campaigns', 'Success'),
            self::STATUS_ERROR                                  => t('campaigns', 'Error'),
            self::STATUS_TEMPORARY_ERROR                        => t('campaigns', 'Temporary error'),
            self::STATUS_FATAL_ERROR                            => t('campaigns', 'Fatal error'),
            self::STATUS_GIVEUP                                 => t('campaigns', 'Giveup'),
            self::STATUS_BLACKLISTED                            => t('campaigns', 'Blacklisted'),
            self::STATUS_SUPPRESSED                             => t('campaigns', 'Suppressed'),
            self::STATUS_BLOCKED                                => t('campaigns', 'Blocked'),
            self::STATUS_DOMAIN_POLICY_REJECT                   => t('campaigns', 'Domain policy reject'),
            self::STATUS_HANDLED_BY_OTHER_SEND_GROUP_CAMPAIGN   => t('campaigns', 'Handled by other send group campaign'),
        ];
    }

    /**
     * @return array
     */
    public function getStatusesList(): array
    {
        return $this->getStatusesArray();
    }

    /**
     * @param string $messageId
     *
     * @return $this|null
     */
    public function findByEmailMessageId(string $messageId): ?self
    {
        return self::model()->findByAttributes([
            'email_message_id' => trim((string)str_replace(['<', '>'], '', $messageId)),
        ]);
    }

    /**
     * @return array
     */
    public function toApiResponse(): array
    {
        return [
            'campaign'  => [
                'campaign_uid'  => $this->campaign->campaign_uid,
                'list'          => [
                    'list_uid' => $this->campaign->list->list_uid,
                ],
            ],
            'subscriber' => [
                'subscriber_uid' => $this->subscriber->subscriber_uid,
                'email'          => $this->subscriber->getDisplayEmail(),
            ],
            'message'               => $this->message,
            'retries'               => $this->retries,
            'max_retries'           => $this->max_retries,
            'email_message_id'      => $this->email_message_id,
            'delivery_confirmed'    => $this->delivery_confirmed,
            'status'                => $this->status,
            'date_added'            => $this->date_added,
        ];
    }

    /**
     * @return bool
     * @throws CException
     */
    public static function getArchiveEnabled(): bool
    {
        if (($log_id = cache()->get(sha1(__METHOD__))) === false) {
            $sql = 'SELECT log_id FROM {{campaign_delivery_log_archive}} WHERE `status` = :st AND processed = :pr LIMIT 1';
            $row = db()->createCommand($sql)->queryRow(true, [':st' => self::STATUS_SUCCESS, ':pr' => self::TEXT_NO]);
            $log_id = !empty($row['log_id']) ? $row['log_id'] : 0;
            cache()->set(sha1(__METHOD__), $log_id, 3600);
        }
        return !empty($log_id);
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->getStatusIs(self::STATUS_TEMPORARY_ERROR)) {
            $this->retries++;
            if ($this->retries >= $this->max_retries) {
                $this->status = self::STATUS_GIVEUP;
            }
        }
        return parent::beforeSave();
    }
}
