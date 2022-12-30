<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignComplainLog
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

/**
 * This is the model class for table "campaign_complain_log".
 *
 * The followings are the available columns in table 'campaign_complain_log':
 * @property string $log_id
 * @property integer|string $campaign_id
 * @property integer|string $subscriber_id
 * @property string $message
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property ListSubscriber $subscriber
 */
class CampaignComplainLog extends ActiveRecord
{
    /**
     * @var int
     */
    public $customer_id = 0;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_complain_log}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [

            ['campaign_id, subscriber_id, message, date_added', 'safe', 'on' => 'search'],
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
        $criteria->compare('t.campaign_id', (int)$this->campaign_id);

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
    public function searchCustomer()
    {
        $criteria = new CDbCriteria();
        $criteria->with = [];

        $criteria->with['campaign'] = [
            'joinType'  => 'INNER JOIN',
            'together'  => true,
            'condition' => 'campaign.customer_id = :ccid',
            'params'    => [
                ':ccid' => (int)$this->customer_id,
            ],
        ];
        $criteria->with['subscriber'] = [
            'joinType' => 'INNER JOIN',
            'together' => true,
            'with'     => [
                'list' => [
                    'joinType'  => 'INNER JOIN',
                    'together'  => true,
                    'condition' => 'list.customer_id = :lcid',
                    'params'    => [
                        ':lcid' => (int)$this->customer_id,
                    ],
                ],
            ],
        ];

        if (!empty($this->campaign_id)) {
            $campaignId = (string)$this->campaign_id;
            if (is_numeric($campaignId)) {
                $criteria->compare('t.campaign_id', (int)$campaignId);
            } else {
                $criteria->addCondition('(campaign.name LIKE :campaign_id OR campaign.campaign_uid LIKE :campaign_id)');
                $criteria->params[':campaign_id'] = '%' . $campaignId . '%';
            }
        }

        if (!empty($this->subscriber_id)) {
            $subscriberId = (string)$this->subscriber_id;
            if (is_numeric($subscriberId)) {
                $criteria->compare('t.subscriber_id', (int)$subscriberId);
            } else {
                $criteria->addCondition('(subscriber.email LIKE :subscriber_id OR subscriber.subscriber_uid LIKE :subscriber_id)');
                $criteria->params[':subscriber_id'] = '%' . $subscriberId . '%';
            }
        }

        $criteria->compare('message', $this->message, true);

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
}
