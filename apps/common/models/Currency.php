<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Currency
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

/**
 * This is the model class for table "{{currency}}".
 *
 * The followings are the available columns in table '{{currency}}':
 * @property integer $currency_id
 * @property string $name
 * @property string $code
 * @property string $value
 * @property string $is_default
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property PricePlanOrder[] $pricePlanOrders
 */
class Currency extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{currency}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, code, is_default, status', 'required'],
            ['name', 'length', 'max' => 100],
            ['code', 'length', 'is' => 3],
            ['code', 'match', 'pattern' => '/[A-Z]{3}/'],
            ['code', 'unique'],
            ['is_default', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],

            ['name, code, is_default, status', 'safe', 'on' => 'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'pricePlanOrders' => [self::HAS_MANY, PricePlanOrder::class, 'currency_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'currency_id'    => t('currencies', 'Currency'),
            'name'           => t('currencies', 'Name'),
            'code'           => t('currencies', 'Code'),
            'value'          => t('currencies', 'Value'),
            'is_default'     => t('currencies', 'Is default'),
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
        $criteria->compare('name', $this->name, true);
        $criteria->compare('code', $this->code, true);
        $criteria->compare('is_default', $this->is_default);
        $criteria->compare('status', $this->status);

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'currency_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Currency the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Currency $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @param string $code
     *
     * @return Currency|null
     */
    public function findByCode(string $code): ?self
    {
        return self::model()->findByAttributes([
            'code' => $code,
        ]);
    }

    /**
     * @return Currency|null
     */
    public function findDefault(): ?self
    {
        $currency = self::model()->findByAttributes([
            'is_default' => self::TEXT_YES,
        ]);

        if (!empty($currency)) {
            return $currency;
        }

        $currency = self::model()->findByAttributes([
            'code' => 'USD',
        ]);

        if (empty($currency)) {
            $currency = new self();
            $currency->code = 'USD';
            $currency->name = 'US Dollar';
        }

        $currency->is_default = self::TEXT_YES;
        if ($currency->save()) {
            return $currency;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function getIsRemovable(): bool
    {
        return (string)$this->is_default !== self::TEXT_YES;
    }

    /**
     * @return bool
     */
    protected function beforeValidate()
    {
        if ($this->code !== null) {
            try {
                numberFormatter()->formatCurrency(10.00, $this->code);
            } catch (Exception $e) {
                $this->addError('code', t('currencies', 'Unrecognized currecy code!'));
            }
        }
        if ($this->is_default == self::TEXT_NO) {
            $hasDefault = self::model()->countByAttributes(['is_default' => self::TEXT_YES]);
            if (empty($hasDefault)) {
                $this->is_default = self::TEXT_YES;
            }
        }
        $this->value = '1.00000000';
        return parent::beforeValidate();
    }

    /**
     * @return void
     */
    protected function afterSave()
    {
        if ($this->is_default == self::TEXT_YES) {
            self::model()->updateAll(['is_default' => self::TEXT_NO], ['condition' => 'currency_id != :cid', 'params' => [':cid' => (int)$this->currency_id]]);
        }
        parent::afterSave();
    }
}
