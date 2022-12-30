<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerUsageLog
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.3.1
 */

/**
 * This is the model class for table "{{delivery_server_usage_log}}".
 *
 * The followings are the available columns in table '{{delivery_server_usage_log}}':
 * @property string $log_id
 * @property integer $server_id
 * @property integer|string $customer_id
 * @property string $delivery_for
 * @property string $customer_countable
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property DeliveryServer $server
 * @property Customer $customer
 */
class DeliveryServerUsageLog extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{delivery_server_usage_log}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['customer_id, server_id, delivery_for', 'safe', 'on' => 'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        return [
            'server'     => [self::BELONGS_TO, DeliveryServer::class, 'server_id'],
            'customer'   => [self::BELONGS_TO, Customer::class, 'customer_id'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'log_id'                => t('servers', 'Log'),
            'server_id'             => t('servers', 'Server'),
            'customer_id'           => t('servers', 'Customer'),
            'delivery_for'          => t('servers', 'Delivery for'),
            'customer_countable'    => t('servers', 'Countable for customer'),
        ];

        return CMap::mergeArray(parent::attributeLabels(), $labels);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerUsageLog the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerUsageLog $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
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
                $criteria->with = [
                    'customer' => [
                        'joinType'  => 'INNER JOIN',
                        'condition' => 'CONCAT(customer.first_name, " ", customer.last_name) LIKE :name',
                        'params'    => [
                            ':name'    => '%' . $customerId . '%',
                        ],
                    ],
                ];
            }
        }
        $criteria->compare('t.server_id', $this->server_id);
        $criteria->compare('t.delivery_for', $this->delivery_for);

        return new CActiveDataProvider(get_class($this), [
            'criteria'      => $criteria,
            'pagination'    => [
                'pageSize'  => (int)$this->paginationOptions->getPageSize(),
                'pageVar'   => 'page',
            ],
            'sort'=>[
                'defaultOrder'  => [
                    'log_id' => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * @return bool
     */
    public function getIsCustomerCountable(): bool
    {
        return !empty($this->customer_id) && (string)$this->customer_countable === self::TEXT_YES;
    }

    /**
     * @return array
     */
    public static function getDeliveryServersAsOptions(): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 'server_id, name, hostname';
        $criteria->order = 'name ASC';
        $servers = DeliveryServer::model()->findAll($criteria);
        $options = [];
        foreach ($servers as $server) {
            $options[$server->server_id] = $server->name . '(' . $server->hostname . ')';
        }
        return $options;
    }
}
