<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerSuppressionList
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.4.4
 */

/**
 * This is the model class for table "{{customer_suppression_list}}".
 *
 * The followings are the available columns in table '{{customer_suppression_list}}':
 * @property integer $list_id
 * @property string $list_uid
 * @property integer $customer_id
 * @property string $name
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer $customer
 * @property CustomerSuppressionListEmail[] $emails
 */
class CustomerSuppressionList extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_suppression_list}}';
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
            ['name', 'safe', 'on' => 'search'],
        ];

        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customer'    => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'emails'      => [self::HAS_MANY, CustomerSuppressionListEmail::class, 'list_id'],
            'emailsCount' => [self::STAT, CustomerSuppressionListEmail::class, 'list_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'list_id'       => t('suppression_lists', 'List'),
            'list_uid'      => t('suppression_lists', 'List'),
            'customer_id'   => t('suppression_lists', 'Customer'),
            'name'          => t('suppression_lists', 'Name'),

            'emailsCount'   => t('suppression_lists', 'Emails count'),
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
        $criteria->order = 'list_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort' => [
                'defaultOrder' => [
                    'list_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerSuppressionList the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerSuppressionList $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $list_uid
     *
     * @return CustomerSuppressionList|null
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
     * @throws CDbException
     */
    public function touchLastUpdated(): void
    {
        $this->saveAttributes([
            'last_updated' => MW_DATETIME_NOW,
        ]);
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if ($this->getIsNewRecord()) {
            $this->list_uid = $this->generateUid();
        }

        return true;
    }
}
