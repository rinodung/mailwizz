<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PricePlanOrderTransaction
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

/**
 * This is the model class for table "{{price_plan_order_transaction}}".
 *
 * The followings are the available columns in table '{{price_plan_order_transaction}}':
 * @property integer|null $transaction_id
 * @property string|null $transaction_uid
 * @property integer|null $order_id
 * @property string $payment_gateway_name
 * @property string $payment_gateway_transaction_id
 * @property string $payment_gateway_response
 * @property string $status
 * @property string|CDbExpression $date_added
 *
 * The followings are the available model relations:
 * @property PricePlanOrder $order
 */
class PricePlanOrderTransaction extends ActiveRecord
{
    /**
     * Status list
     */
    const STATUS_FAILED = 'failed';
    const STATUS_SUCCESS = 'success';
    const STATUS_PENDING_RETRY = 'pending-retry';

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{price_plan_order_transaction}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [

            // The following rule is used by search().
            ['payment_gateway_name, payment_gateway_transaction_id, status', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'order' => [self::BELONGS_TO, PricePlanOrder::class, 'order_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'transaction_id'                 => t('orders', 'Transaction'),
            'transaction_uid'                => t('orders', 'Transaction uid'),
            'order_id'                       => t('orders', 'Order'),
            'payment_gateway_name'           => t('orders', 'Payment gateway name'),
            'payment_gateway_transaction_id' => t('orders', 'Payment gateway transaction'),
            'payment_gateway_response'       => t('orders', 'Payment gateway response'),
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

        $criteria->compare('order_id', $this->order_id);
        $criteria->compare('payment_gateway_name', $this->payment_gateway_name, true);
        $criteria->compare('payment_gateway_transaction_id', $this->payment_gateway_transaction_id, true);
        $criteria->compare('status', $this->status);

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'transaction_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PricePlanOrderTransaction the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var PricePlanOrderTransaction $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $transaction_uid
     *
     * @return PricePlanOrderTransaction|null
     */
    public function findByUid(string $transaction_uid): ?self
    {
        return self::model()->findByAttributes([
            'transaction_uid' => $transaction_uid,
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
     * @return string
     */
    public function getUid(): string
    {
        return (string)$this->transaction_uid;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if (empty($this->transaction_uid)) {
            $this->transaction_uid = $this->generateUid();
        }

        return true;
    }
}
