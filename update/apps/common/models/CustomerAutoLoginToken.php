<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerAutoLoginToken
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This is the model class for table "customer_auto_login_token".
 *
 * The followings are the available columns in table 'customer_auto_login_token':
 * @property integer $token_id
 * @property integer $customer_id
 * @property string $token
 *
 * The followings are the available model relations:
 * @property Customer $customer
 */
class CustomerAutoLoginToken extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_auto_login_token}}';
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
            'token_id'    => t('customers', 'Token'),
            'customer_id' => t('customers', 'Customer'),
            'token'       => t('customers', 'Token'),
        ];

        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerAutoLoginToken the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerAutoLoginToken $model */
        $model = parent::model($className);

        return $model;
    }
}
