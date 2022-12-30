<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignSendGroup
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

/**
 * This is the model class for table "{{campaign_group}}".
 *
 * The followings are the available columns in table '{{campaign_group}}':
 * @property integer $group_id
 * @property string $group_uid
 * @property integer $customer_id
 * @property string $name
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Campaign[] $campaigns
 * @property integer $campaignsCount
 * @property Customer $customer
 */
class CampaignSendGroup extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_send_group}}';
    }

    /**
     * @return array
     * @throws CException
     */
    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['name', 'length', 'max' => 190],
            ['name', '_validateUniqueName'],

            // The following rule is used by search().
            ['name', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     * @throws CException
     */
    public function relations()
    {
        $relations = [
            'campaigns'       => [self::HAS_MANY, Campaign::class, 'send_group_id'],
            'campaignsCount'  => [self::STAT, Campaign::class, 'send_group_id'],
            'customer'        => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     * @throws CException
     */
    public function attributeLabels()
    {
        $labels = [
            'group_id'       => t('campaigns', 'Group'),
            'group_uid'      => t('campaigns', 'Group uid'),
            'customer_id'    => t('campaigns', 'Customer'),
            'name'           => t('campaigns', 'Name'),

            'campaignsCount' => t('campaigns', 'Campaigns count'),
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
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->compare('name', $this->name, true);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'  => [
                'defaultOrder'  => [
                    'group_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CampaignSendGroup the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignSendGroup $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->group_uid;
    }

    /**
     * @param string $group_uid
     *
     * @return CampaignSendGroup|null
     */
    public function findByUid(string $group_uid): ?self
    {
        return self::model()->findByAttributes([
            'group_uid' => $group_uid,
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
     * @param string $attribute
     * @param array $params
     *
     * @return void
     */
    public function _validateUniqueName($attribute, $params = [])
    {
        if ($this->hasErrors('name') || $this->hasErrors('customer_id')) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('name', $this->name);
        $criteria->compare('customer_id', (int)$this->customer_id);
        $criteria->compare('group_id', '<>' . (int)$this->group_id);
        $model = self::model()->find($criteria);

        if (!empty($model)) {
            $this->addError($attribute, t('campaigns', 'The name "{name}" has already been taken!', [
                '{name}' => html_encode($this->name),
            ]));
        }
    }

    /**
     * @param string $emailAddress
     *
     * @return bool
     */
    public function hasSentToEmailAddress(string $emailAddress): bool
    {
        /** @var Campaign[] $campaigns */
        $campaigns = $this->getCampaignsWithCache();
        if (empty($campaigns)) {
            return false;
        }

        /** @var array $campaignsIds */
        $campaignsIds = collect($campaigns)->map(function (Campaign $campaign) {
            return $campaign->campaign_id;
        })->all();

        /** @var array $listsIds */
        $listsIds = collect($campaigns)->map(function (Campaign $campaign) {
            return $campaign->list_id;
        })->all();

        $criteria = new CDbCriteria();
        $criteria->select = 'subscriber_id';
        $criteria->addInCondition('list_id', $listsIds);
        $criteria->compare('email', $emailAddress);

        /** @var array $subscribersIds */
        $subscribersIds = ListSubscriberCollection::findAll($criteria)->map(function (ListSubscriber $subscriber) {
            return $subscriber->subscriber_id;
        })->all();

        $criteria = new CDbCriteria();
        $criteria->addInCondition('subscriber_id', $subscribersIds);
        $criteria->addInCondition('campaign_id', $campaignsIds);

        return CampaignDeliveryLog::model()->count($criteria) > 0;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if ($this->getIsNewRecord() && empty($this->group_uid)) {
            $this->group_uid = $this->generateUid();
        }

        return parent::beforeSave();
    }

    /**
     * @return array
     */
    private function getCampaignsWithCache(): array
    {
        static $campaigns = [];
        if (isset($campaigns[(int)$this->group_id])) {
            return $campaigns[(int)$this->group_id];
        }
        $campaigns[(int)$this->group_id] = [];

        $criteria = new CDbCriteria();
        $criteria->select = 'campaign_id, list_id';
        $criteria->compare('send_group_id', (int)$this->group_id);
        $criteria->order = 'campaign_id ASC';

        return $campaigns[(int)$this->group_id] = Campaign::model()->findAll($criteria);
    }
}
