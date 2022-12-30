<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerQuotaMark
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

/**
 * This is the model class for table "{{customer_quota_mark}}".
 *
 * The followings are the available columns in table '{{customer_quota_mark}}':
 * @property string $mark_id
 * @property integer $customer_id
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class CustomerQuotaMark extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_quota_mark}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [];

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
            'mark_id'     => t('customers', 'Mark'),
            'customer_id' => t('customers', 'Customer'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerQuotaMark the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerQuotaMark $model */
        $model = parent::model($className);

        return $model;
    }
}
