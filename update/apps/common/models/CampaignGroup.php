<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CampaignGroup
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
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
class CampaignGroup extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{campaign_group}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['name', 'length', 'max' => 255],

            // The following rule is used by search().
            ['name', 'safe', 'on'=>'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'campaigns'       => [self::HAS_MANY, Campaign::class, 'group_id'],
            'campaignsCount'  => [self::STAT, Campaign::class, 'group_id'],
            'customer'        => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
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
     * @return CampaignGroup the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CampaignGroup $model */
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
     * @return CampaignGroup|null
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
     * @return array
     */
    public static function getForDropDown(): array
    {
        static $list;
        if ($list !== null) {
            return $list;
        }

        $attributes  = ['select' => 'group_id, name', 'limit' => 100];
        return $list = CampaignGroupCollection::findAll($attributes)->mapWithKeys(function (CampaignGroup $model) {
            return [$model->group_id => $model->name];
        })->all();
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
}
