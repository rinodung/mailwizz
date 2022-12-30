<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignForwardFriend
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.7
 */

/**
 * This is the model class for table "{{campaign_forward_friend}}".
 *
 * The followings are the available columns in table '{{campaign_forward_friend}}':
 * @property integer $forward_id
 * @property integer|string $campaign_id
 * @property integer|string|null $subscriber_id
 * @property string $to_email
 * @property string $to_name
 * @property string $from_email
 * @property string $from_name
 * @property string $subject
 * @property string $message
 * @property string $ip_address
 * @property string $user_agent
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign $campaign
 * @property ListSubscriber $subscriber
 */
class CampaignForwardFriend extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_forward_friend}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['to_email, to_name, from_email, from_name, subject', 'required'],
            ['to_email, to_name, from_email, from_name', 'length', 'max' => 150],
            ['to_email, from_email', 'email', 'validateIDN' => true],
            ['subject', 'length', 'max' => 255],
            ['message', 'length', 'max' => 10000],

            // The following rule is used by search().
            ['campaign_id, subscriber_id, to_email, to_name, from_email, from_name, subject, ip_address', 'safe', 'on'=>'search'],
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
            'forward_id'     => t('campaigns', 'Forward'),
            'campaign_id'    => t('campaigns', 'Campaign'),
            'subscriber_id'  => t('campaigns', 'Subscriber'),
            'to_email'       => t('campaigns', 'To email'),
            'to_name'        => t('campaigns', 'To name'),
            'from_email'     => t('campaigns', 'From email'),
            'from_name'      => t('campaigns', 'From name'),
            'subject'        => t('campaigns', 'Subject'),
            'message'        => t('campaigns', 'Message'),
            'ip_address'     => t('campaigns', 'Ip address'),
            'user_agent'     => t('campaigns', 'User agent'),
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

        if (!empty($this->campaign_id)) {
            $campaignId = (string)$this->campaign_id;
            if (is_numeric($campaignId)) {
                $criteria->compare('t.campaign_id', $campaignId);
            } else {
                $criteria->with['campaign'] = [
                    'together'  => true,
                    'joinType'  => 'INNER JOIN',
                    'condition' => '(campaign.campaign_uid = :cmp OR campaign.name = :cmp)',
                    'params'    => [':cmp' => $campaignId],
                ];
            }
        }

        if (!empty($this->subscriber_id)) {
            $subscriberId = (string)$this->subscriber_id;
            if (is_numeric($subscriberId)) {
                $criteria->compare('t.subscriber_id', $subscriberId);
            } else {
                $criteria->with['subscriber'] = [
                    'together'  => true,
                    'joinType'  => 'INNER JOIN',
                    'condition' => '(subscriber.subscriber_uid = :sub OR subscriber.email = :sub)',
                    'params'    => [':sub' => $subscriberId],
                ];
            }
        }

        $criteria->compare('t.to_email', $this->to_email, true);
        $criteria->compare('t.to_name', $this->to_name, true);
        $criteria->compare('t.from_email', $this->from_email, true);
        $criteria->compare('t.from_name', $this->from_name, true);
        $criteria->compare('t.subject', $this->subject, true);
        $criteria->compare('t.ip_address', $this->ip_address, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.forward_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignForwardFriend the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignForwardFriend $model */
        $model = parent::model($className);

        return $model;
    }
}
