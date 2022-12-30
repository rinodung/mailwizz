<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignBounceLog
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "campaign_bounce_log".
 *
 * The followings are the available columns in table 'campaign_bounce_log':
 * @property string $log_id
 * @property integer|string $campaign_id
 * @property integer|string $subscriber_id
 * @property string $message
 * @property string $bounce_type
 * @property string $processed
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property ListSubscriber $subscriber
 */
class CampaignBounceLog extends ActiveRecord
{
    /**
     * Flags for bounce types
     */
    const BOUNCE_INTERNAL = 'internal';
    const BOUNCE_SOFT = 'soft';
    const BOUNCE_HARD = 'hard';

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
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_bounce_log}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['bounce_type, date_added', 'safe', 'on' => 'customer-search'],
            ['customer_id, campaign_id, list_id, segment_id, subscriber_id, message, processed, bounce_type, date_added', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaign'   => [self::BELONGS_TO, Campaign::class, 'campaign_id'],
            'subscriber' => [self::BELONGS_TO, ListSubscriber::class, 'subscriber_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'log_id'        => t('campaigns', 'Log'),
            'campaign_id'   => t('campaigns', 'Campaign'),
            'subscriber_id' => t('campaigns', 'Subscriber'),
            'message'       => t('campaigns', 'Message'),
            'processed'     => t('campaigns', 'Processed'),
            'bounce_type'   => t('campaigns', 'Bounce type'),

            // search
            'customer_id'   => t('campaigns', 'Customer'),
            'list_id'       => t('campaigns', 'List'),
            'segment_id'    => t('campaigns', 'Segment'),
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
    public function customerSearch()
    {
        $criteria = new CDbCriteria();
        $criteria->compare('campaign_id', (int)$this->campaign_id);
        $criteria->compare('bounce_type', $this->bounce_type);
        $criteria->compare('date_added', $this->date_added);

        return new CActiveDataProvider(get_class($this), [
            'criteria' => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar' => 'page',
            ],
            'sort' => [
                'defaultOrder' => [
                    'log_id' => CSort::SORT_DESC,
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
        $criteria->select = 't.message, t.processed, t.bounce_type, t.date_added';
        $criteria->with = [
            'campaign' => [
                'select' => 'campaign.name, campaign.list_id, campaign.segment_id',
                'joinType' => 'INNER JOIN',
                'together' => true,
                'with' => [
                    'list' => [
                        'select' => 'list.name',
                        'joinType' => 'INNER JOIN',
                        'together' => true,
                    ],
                    'customer' => [
                        'select' => 'customer.customer_id, customer.first_name, customer.last_name',
                        'joinType' => 'INNER JOIN',
                        'together' => true,
                    ],
                ],
            ],
            'subscriber' => [
                'select' => 'subscriber.email',
                'joinType' => 'INNER JOIN',
                'together' => true,
            ],
        ];

        if (!empty($this->customer_id) && is_numeric($this->customer_id)) {
            $criteria->with['campaign']['with']['customer'] = array_merge($criteria->with['campaign']['with']['customer'], [
                'condition' => 'customer.customer_id = :customerId',
                'params' => [':customerId' => $this->customer_id],
            ]);
        } elseif (!empty($this->customer_id) && is_string($this->customer_id)) {
            $criteria->with['campaign']['with']['customer'] = array_merge($criteria->with['campaign']['with']['customer'], [
                'condition' => 'CONCAT(customer.first_name, " ", customer.last_name) LIKE :customerName',
                'params' => [':customerName' => '%' . $this->customer_id . '%'],
            ]);
        }

        if (!empty($this->campaign_id) && is_numeric($this->campaign_id)) {
            $criteria->with['campaign'] = array_merge($criteria->with['campaign'], [
                'condition' => 'campaign.campaign_id = :campaignId',
                'params' => [':campaignId' => $this->campaign_id],
            ]);
        } elseif (!empty($this->campaign_id) && is_string($this->campaign_id)) {
            $criteria->with['campaign'] = array_merge($criteria->with['campaign'], [
                'condition' => 'campaign.name LIKE :campaignName',
                'params' => [':campaignName' => '%' . $this->campaign_id . '%'],
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
            $criteria->with['campaign']['with']['segment'] = [
                'condition' => 'segment.segment_id = :segmentId',
                'params' => [':segmentId' => $this->segment_id],
            ];
        } elseif (!empty($this->segment_id) && is_string($this->segment_id)) {
            $criteria->with['campaign']['with']['segment'] = [
                'condition' => 'segment.name LIKE :segmentId',
                'params' => [':segmentId' => '%' . $this->segment_id . '%'],
            ];
        }

        if (!empty($this->subscriber_id) && is_numeric($this->subscriber_id)) {
            $criteria->with['subscriber'] = array_merge($criteria->with['subscriber'], [
                'condition' => 'subscriber.subscriber_id = :subscriberId',
                'params' => [':subscriberId' => $this->subscriber_id],
            ]);
        } elseif (!empty($this->subscriber_id) && is_string($this->subscriber_id)) {
            $criteria->with['subscriber'] = array_merge($criteria->with['subscriber'], [
                'condition' => 'subscriber.email LIKE :subscriberId',
                'params' => [':subscriberId' => '%' . $this->subscriber_id . '%'],
            ]);
        }

        $criteria->compare('t.message', $this->message, true);
        $criteria->compare('t.processed', $this->processed);
        $criteria->compare('t.bounce_type', $this->bounce_type);
        $criteria->compare('t.date_added', $this->date_added);

        $criteria->order = 't.log_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria' => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar' => 'page',
            ],
            'sort' => [
                'defaultOrder' => [
                    't.log_id' => CSort::SORT_DESC,
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
        $criteria->order = 't.log_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria' => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar' => 'page',
            ],
            'sort' => [
                'defaultOrder' => [
                    't.log_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignBounceLog the static model class
     */
    public static function model($className = __CLASS__)
    {
        /** @var CampaignBounceLog $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getBounceTypesArray(): array
    {
        $types = [
            self::BOUNCE_INTERNAL => t('campaigns', self::BOUNCE_INTERNAL),
            self::BOUNCE_SOFT     => t('campaigns', self::BOUNCE_SOFT),
            self::BOUNCE_HARD     => t('campaigns', self::BOUNCE_HARD),
        ];

        return (array)hooks()->applyFilters('campaign_bounce_logs_get_bounce_types_list', $types);
    }

    /**
     * @return bool
     */
    public function looksLikeInternalBounce(): bool
    {
        if ($this->bounce_type == self::BOUNCE_INTERNAL) {
            return true;
        }

        if (empty($this->message)) {
            return false;
        }

        $rules = [
            '/unsolicited mail/i',
            '/(spam|block(ed)?)/i',
            '/(DNSBL|RBL|CDRBL|Blacklist)/i',
        ];

        foreach ($rules as $rule) {
            if (preg_match($rule, $this->message)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->looksLikeInternalBounce()) {
            $this->bounce_type = self::BOUNCE_INTERNAL;
        }

        return parent::beforeSave();
    }
}
