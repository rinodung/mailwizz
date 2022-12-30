<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Tax
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.5
 */

/**
 * This is the model class for table "{{price_plan_tax}}".
 *
 * The followings are the available columns in table '{{price_plan_tax}}':
 * @property integer $tax_id
 * @property integer $country_id
 * @property integer $zone_id
 * @property string $name
 * @property string $percent
 * @property string $is_global
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property PricePlanOrder[] $pricePlanOrders
 * @property Country $country
 * @property Zone $zone
 */
class Tax extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{tax}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name, percent, is_global, status', 'required'],
            ['country_id, zone_id', 'numerical', 'integerOnly' => true],
            ['name', 'length', 'max' => 100],
            ['percent', 'numerical'],
            ['percent', 'type', 'type' => 'float'],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],
            ['is_global', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['country_id', 'exist', 'className' => Country::class],
            ['zone_id', 'exist', 'className' => Zone::class],

            // The following rule is used by search().
            ['country_id, zone_id, name, percent, is_global, status', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'pricePlanOrders'    => [self::HAS_MANY, PricePlanOrder::class, 'tax_id'],
            'country'            => [self::BELONGS_TO, Country::class, 'country_id'],
            'zone'               => [self::BELONGS_TO, Zone::class, 'zone_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'tax_id'     => t('taxes', 'Tax'),
            'country_id' => t('taxes', 'Country'),
            'zone_id'    => t('taxes', 'Zone'),
            'name'       => t('taxes', 'Name'),
            'percent'    => t('taxes', 'Percent'),
            'is_global'  => t('taxes', 'Is global'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'country_id' => t('taxes', 'The country for which this tax applies'),
            'zone_id'    => t('taxes', 'The zone/state for which this tax applies'),
            'name'       => t('taxes', 'The name of this tax'),
            'percent'    => t('taxes', 'How much from the total amount of the order this max means, use a number'),
            'is_global'  => t('taxes', 'Whether this tax is global, i.e: applies for customers that don\'t match other taxes'),
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
        $criteria->with = [];

        if ($this->country_id) {
            if (is_string($this->country_id)) {
                $criteria->with['country'] = [
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                    'condition'=> '(country.name LIKE :c01 OR country.code LIKE :c01)',
                    'params'   => [':c01' => '%' . $this->country_id . '%'],
                ];
            } else {
                $criteria->compare('t.country_id', (int)$this->country_id);
            }
        }

        if ($this->zone_id) {
            if (is_string($this->zone_id)) {
                $criteria->with['zone'] = [
                    'together' => true,
                    'joinType' => 'INNER JOIN',
                    'condition'=> '(zone.name LIKE :z01 OR zone.code LIKE :z01)',
                    'params'   => [':z01' => '%' . $this->zone_id . '%'],
                ];
            } else {
                $criteria->compare('t.zone_id', (int)$this->zone_id);
            }
        }

        $criteria->compare('t.name', $this->name, true);
        $criteria->compare('t.percent', $this->percent, true);
        $criteria->compare('t.is_global', $this->is_global);
        $criteria->compare('t.status', $this->status);

        $criteria->order = 't.tax_id DESC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    't.tax_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Tax the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var Tax $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return string
     */
    public function getFormattedPercent(): string
    {
        return formatter()->formatNumber($this->percent) . '%';
    }

    /**
     * @return array
     */
    public static function getAsDropdownOptions(): array
    {
        static $options;
        if ($options !== null) {
            return $options;
        }
        $options = [];
        $taxes   = self::model()->findAll(['select' => 'tax_id, name, percent', 'order' => 'name ASC']);
        foreach ($taxes as $tax) {
            $options[$tax->tax_id] = $tax->name . '(' . $tax->getFormattedPercent() . ')';
        }
        return $options;
    }

    /**
     * @return void
     */
    protected function afterSave()
    {
        if ((string)$this->is_global === self::TEXT_YES) {
            $criteria = new CDbCriteria();
            $criteria->addCondition('tax_id != :tid');
            $criteria->params = [
                ':tid' => (int)$this->tax_id,
            ];

            $criteria = hooks()->applyFilters('model_tax_after_save_before_update_all_criteria', $criteria);

            self::model()->updateAll(['is_global' => self::TEXT_NO], $criteria);
        }

        parent::afterSave();
    }
}
