<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerApiKey
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "customer_api_key".
 *
 * The followings are the available columns in table 'customer_api_key':
 * @property integer $key_id
 * @property integer $customer_id
 * @property string $name
 * @property string $description
 * @property string $key
 * @property string $ip_whitelist
 * @property string $ip_blacklist
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class CustomerApiKey extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_api_key}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, description, ip_whitelist, ip_blacklist', 'length', 'max' => 255],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customer' => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'key_id'        => t('api_keys', 'Key'),
            'customer_id'   => t('api_keys', 'Customer'),
            'name'          => t('api_keys', 'Name'),
            'description'   => t('api_keys', 'Description'),
            'key'           => t('api_keys', 'Api key'),
            'ip_whitelist'  => t('api_keys', 'Ip whitelist'),
            'ip_blacklist'  => t('api_keys', 'Ip blacklist'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'name'          => t('api_keys', 'My site'),
            'description'   => t('api_keys', 'This key is used on my site'),
            'ip_whitelist'  => t('api_keys', '123.123.123.123, 111.111.111.111'),
            'ip_blacklist'  => t('api_keys', '231.231.231.231, 222.222.222.222'),
        ];
        return CMap::mergeArray(parent::attributePlaceholders(), $placeholders);
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

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => $this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'key_id'   => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerApiKey the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerApiKey $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function generateUniqueKey(): string
    {
        $key = StringHelper::randomSha1();

        $model = self::model()->findByAttributes([
            'key' => $key,
        ]);

        if (!empty($model)) {
            return $this->generateUniqueKey();
        }

        return $key;
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if ($this->getIsNewRecord()) {
            if (empty($this->key)) {
                $this->key = $this->generateUniqueKey();
            }
        }

        $lists = ['ip_whitelist', 'ip_blacklist'];
        foreach ($lists as $list) {
            $_list = CommonHelper::getArrayFromString((string)$this->$list);
            foreach ($_list as $index => $ip) {
                if (!FilterVarHelper::ip($ip)) {
                    unset($_list[$index]);
                }
            }
            $this->$list = implode(', ', $_list);
        }

        return parent::beforeValidate();
    }
}
