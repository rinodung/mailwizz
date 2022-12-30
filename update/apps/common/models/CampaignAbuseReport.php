<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignAbuseReport
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5
 */

/**
 * This is the model class for table "{{campaign_abuse_report}}".
 *
 * The followings are the available columns in table '{{campaign_abuse_report}}':
 * @property integer $report_id
 * @property integer $customer_id
 * @property integer $campaign_id
 * @property integer $list_id
 * @property integer $subscriber_id
 * @property string $customer_info
 * @property string $campaign_info
 * @property string $list_info
 * @property string $subscriber_info
 * @property string $reason
 * @property string $log
 * @property string $ip_address
 * @property string $user_agent
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property Customer $customer
 * @property Lists $list
 * @property ListSubscriber $subscriber
 */
class CampaignAbuseReport extends ActiveRecord
{
    /**
     * Flag
     */
    const BULK_ACTION_BLACKLIST = 'blacklist';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_abuse_report}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['reason', 'required'],
            ['reason', 'length', 'max' => 255],

            ['customer_id, campaign_id, list_id, customer_info, campaign_info, list_info, subscriber_info, reason, log, ip_address, user_agent', 'safe', 'on' => 'search'],
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
            'customer'   => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'list'       => [self::BELONGS_TO, Lists::class, 'list_id'],
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
            'report_id'       => t('campaigns', 'Report'),
            'customer_id'     => t('campaigns', 'Customer'),
            'campaign_id'     => t('campaigns', 'Campaign'),
            'list_id'         => t('campaigns', 'List'),
            'subscriber_id'   => t('campaigns', 'Subscriber'),
            'customer_info'   => t('campaigns', 'Customer'),
            'campaign_info'   => t('campaigns', 'Campaign'),
            'list_info'       => t('campaigns', 'List'),
            'subscriber_info' => t('campaigns', 'Subscriber'),
            'reason'          => t('campaigns', 'Reason'),
            'log'             => t('campaigns', 'Log'),
            'ip_address'      => t('campaigns', 'Ip address'),
            'user_agent'      => t('campaigns', 'User agent'),
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

        $criteria->compare('customer_id', $this->customer_id);
        $criteria->compare('campaign_id', $this->campaign_id);
        $criteria->compare('list_id', $this->list_id);

        $criteria->compare('customer_info', $this->customer_info, true);
        $criteria->compare('campaign_info', $this->campaign_info, true);
        $criteria->compare('list_info', $this->list_info, true);
        $criteria->compare('subscriber_info', $this->subscriber_info, true);
        $criteria->compare('reason', $this->reason, true);

        $criteria->compare('ip_address', $this->ip_address, true);
        $criteria->compare('user_agent', $this->user_agent, true);

        $criteria->order = 'report_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'report_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignAbuseReport the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignAbuseReport $model */
        $model = parent::model($className);

        return $model;
    }

    public function getBulkActionsList(): array
    {
        return CMap::mergeArray(parent::getBulkActionsList(), [
            self::BULK_ACTION_BLACKLIST => t('app', 'Blacklist'),
        ]);
    }

    /**
     * @param string $log
     *
     * @return $this
     */
    public function addLog(string $log): self
    {
        $this->log .= '[' . date('Y-m-d H:i:s') . '] - ' . $log . "\n";
        return $this;
    }

    /**
     * @return string
     */
    public function getCampaignInfoWithLink(): string
    {
        if (apps()->isAppName('customer') && !empty($this->campaign)) {
            return CHtml::link((string)$this->campaign_info, [
                'campaigns/overview',
                'campaign_uid' => (string)$this->campaign->campaign_uid,
            ]);
        }
        return $this->campaign_info;
    }

    /**
     * @return string
     */
    public function getListInfoWithLink(): string
    {
        if (apps()->isAppName('customer') && !empty($this->list)) {
            return CHtml::link((string)$this->list_info, [
                'lists/overview',
                'list_uid' => (string)$this->list->list_uid,
            ]);
        }
        return (string)$this->list_info;
    }

    /**
     * @return string
     */
    public function getSubscriberInfoWithLink(): string
    {
        if (apps()->isAppName('customer') && !empty($this->list) && !empty($this->subscriber)) {
            return CHtml::link((string)$this->subscriber->getDisplayEmail(), [
                'list_subscribers/update',
                'list_uid'          => (string)$this->list->list_uid,
                'subscriber_uid'    => (string)$this->subscriber->subscriber_uid,
            ]);
        }
        return (string)$this->subscriber_info;
    }
}
