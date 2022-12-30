<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerLoginLog
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.2
 */

/**
 * This is the model class for table "{{customer_login_log}}".
 *
 * The followings are the available columns in table '{{customer_login_log}}':
 * @property string $log_id
 * @property integer|string $customer_id
 * @property int $location_id
 * @property string $ip_address
 * @property string $user_agent
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property Customer $customer
 * @property IpLocation $location
 */
class CustomerLoginLog extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_login_log}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['customer_id, ip_address', 'safe', 'on'=>'search'],
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
            'location' => [self::BELONGS_TO, IpLocation::class, 'location_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'log_id'      => t('customers', 'Log'),
            'customer_id' => t('customers', 'Customer'),
            'location_id' => t('customers', 'Location'),
            'ip_address'  => t('customers', 'Ip address'),
            'user_agent'  => t('customers', 'User agent'),

            'countryName' => t('customers', 'Country'),
            'zoneName'    => t('customers', 'Zone'),
            'cityName'    => t('customers', 'City'),
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

        if (!empty($this->customer_id)) {
            $customerId = (string)$this->customer_id;
            if (is_numeric($customerId)) {
                $criteria->compare('t.customer_id', $customerId);
            } else {
                $criteria->with['customer'] = [
                    'condition' => 'customer.email LIKE :name OR customer.first_name LIKE :name OR customer.last_name LIKE :name',
                    'params'    => [':name' => '%' . $customerId . '%'],
                ];
            }
        }

        $criteria->compare('t.ip_address', $this->ip_address, true);
        $criteria->order = 't.log_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
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
     * @return CustomerLoginLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerLoginLog $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param Customer $customer
     *
     * @return bool
     * @throws CException
     */
    public static function addNew(Customer $customer): bool
    {
        if (is_cli()) {
            return false;
        }

        $model = new self();
        $model->customer_id = (int)$customer->customer_id;
        $model->ip_address  = (string)request()->getUserHostAddress();
        $model->user_agent  = substr((string)request()->getUserAgent(), 0, 255);

        $model->addRelatedRecord('customer', $customer, false);

        hooks()->doAction('customer_login_log_add_new_before_save', new CAttributeCollection([
            'model' => $model,
        ]));

        $saved = $model->save();

        hooks()->doAction('customer_login_log_add_after_after_save', new CAttributeCollection([
            'model' => $model,
            'saved' => $saved,
        ]));

        return (bool)$saved;
    }

    /**
     * @return string
     */
    public function getCountryName(): string
    {
        if (empty($this->location_id) || empty($this->location) || empty($this->location->country_name)) {
            return '';
        }
        return (string)$this->location->country_name;
    }

    /**
     * @return string
     */
    public function getZoneName(): string
    {
        if (empty($this->location_id) || empty($this->location) || empty($this->location->zone_name)) {
            return '';
        }
        return (string)$this->location->zone_name;
    }

    /**
     * @return string
     */
    public function getCityName(): string
    {
        if (empty($this->location_id) || empty($this->location) || empty($this->location->city_name)) {
            return '';
        }
        return (string)$this->location->city_name;
    }
}
