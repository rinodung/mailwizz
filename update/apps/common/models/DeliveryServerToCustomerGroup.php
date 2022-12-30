<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerGroup
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.9
 */

/**
 * This is the model class for table "delivery_server_to_customer_group".
 *
 * The followings are the available columns in table 'delivery_server_to_customer_group':
 * @property integer $server_id
 * @property integer $group_id
 */
class DeliveryServerToCustomerGroup extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{delivery_server_to_customer_group}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['server_id, group_id', 'required'],
            ['server_id, group_id', 'numerical', 'integerOnly'=>true],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'server_id' => t('servers', 'Server'),
            'group_id'  => t('servers', 'Customer group'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return DeliveryServerToCustomerGroup the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var DeliveryServerToCustomerGroup $model */
        $model = parent::model($className);

        return $model;
    }
}
