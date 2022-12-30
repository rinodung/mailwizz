<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PricePlanOrderNote
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

/**
 * This is the model class for table "{{price_plan_order_note}}".
 *
 * The followings are the available columns in table '{{price_plan_order_note}}':
 * @property integer $note_id
 * @property integer $order_id
 * @property integer $customer_id
 * @property integer $user_id
 * @property string $note
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property PricePlanOrder $order
 * @property Customer $customer
 * @property User $user
 */
class PricePlanOrderNote extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{price_plan_order_note}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['note', 'required'],
            ['note', 'length', 'max'=>255],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'order'     => [self::BELONGS_TO, PricePlanOrder::class, 'order_id'],
            'customer'  => [self::BELONGS_TO, Customer::class, 'customer_id'],
            'user'      => [self::BELONGS_TO, User::class, 'user_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'note_id'    => t('orders', 'Note'),
            'order_id'   => t('orders', 'Order'),
            'note'       => t('orders', 'Note'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributePlaceholders()
    {
        $placeholders = [
            'note'       => t('orders', 'If you have particular notes about this order, please type them here...'),
        ];

        return CMap::mergeArray($placeholders, parent::attributePlaceholders());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'note'  => t('orders', 'If you have particular notes about this order, please type them here...'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
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

        $criteria->compare('t.order_id', $this->order_id);
        $criteria->order = 't.note_id ASC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.note_id'  => CSort::SORT_ASC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PricePlanOrderNote the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var PricePlanOrderNote $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        if (!empty($this->user_id)) {
            return $this->user->getFullName() . ' (' . t('orders', 'Admin') . ')';
        }
        if (!empty($this->customer_id)) {
            return $this->customer->getFullName() . ' (' . t('orders', 'Customer') . ')';
        }
        return '';
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getAuthorAndDate(): string
    {
        $out = '';
        if (($author = $this->getAuthor())) {
            $out .= t('orders', 'By {author} at ', ['{author}' => $author]);
        }
        $out .= $this->dateTimeFormatter->getDateAdded();
        return $out;
    }
}
