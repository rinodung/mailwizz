<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PricePlan
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.4
 */

/**
 * This is the model class for table "{{price_plan}}".
 *
 * The followings are the available columns in table '{{price_plan}}':
 * @property integer|null $plan_id
 * @property string $plan_uid
 * @property integer $group_id
 * @property string $name
 * @property string $price
 * @property string $description
 * @property string $recommended
 * @property string $visible
 * @property integer $sort_order
 * @property string $status
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CustomerGroup $customerGroup
 * @property PricePlanOrder[] $pricePlanOrders
 */
class PricePlan extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{price_plan}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['group_id, name, price, recommended, status', 'required'],

            ['group_id', 'numerical', 'integerOnly' => true],
            ['group_id', 'exist', 'className' => CustomerGroup::class],
            ['name', 'length', 'max' => 50],
            ['price', 'numerical'],
            ['price', 'type', 'type' => 'float'],
            ['recommended, visible', 'in', 'range' => array_keys($this->getYesNoOptions())],
            ['status', 'in', 'range' => array_keys($this->getStatusesList())],
            ['sort_order', 'numerical', 'integerOnly' => true],

            // The following rule is used by search().
            ['name, group_id, price, status', 'safe', 'on'=>'search'],
            ['description', 'safe'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customerGroup'   => [self::BELONGS_TO, CustomerGroup::class, 'group_id'],
            'pricePlanOrders' => [self::HAS_MANY, PricePlanOrder::class, 'plan_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'plan_id'     => t('price_plans', 'Plan'),
            'plan_uid'    => t('price_plans', 'Plan uid'),
            'group_id'    => t('price_plans', 'Customer group'),
            'name'        => t('price_plans', 'Name'),
            'price'       => t('price_plans', 'Price'),
            'description' => t('price_plans', 'Description'),
            'recommended' => t('price_plans', 'Recommended'),
            'visible'     => t('price_plans', 'Visible'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * @return array help text for attributes
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'group_id'    => t('price_plans', 'The group where the customer will be moved after purchasing this plan. Make sure the group has proper permissions and limits'),
            'name'        => t('price_plans', 'The price plan name, used in customer display area, orders, etc'),
            'price'       => t('price_plans', 'The amount the customers will be charged when buying this plan'),
            'description' => t('price_plans', 'A detailed description about the price plan features'),
            'recommended' => t('price_plans', 'Whether this plan has the recommended badge on it'),
            'visible'     => t('price_plans', 'Whether this plan is visible in customers area'),
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

        $criteria->compare('name', $this->name, true);
        $criteria->compare('group_id', $this->group_id);
        $criteria->compare('price', $this->price, true);
        $criteria->compare('status', $this->status);

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'sort_order'  => CSort::SORT_ASC,
                    'plan_id'     => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PricePlan the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var PricePlan $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return PricePlan|null
     * @throws CException
     */
    public function copy(): ?self
    {
        $copied = null;

        if ($this->getIsNewRecord()) {
            return null;
        }

        $transaction = db()->beginTransaction();

        try {
            $pricePlan = clone $this;
            $pricePlan->setIsNewRecord(true);
            $pricePlan->plan_id      = null;
            $pricePlan->plan_uid     = '';
            $pricePlan->status       = self::STATUS_INACTIVE;
            $pricePlan->date_added   = MW_DATETIME_NOW;
            $pricePlan->last_updated = MW_DATETIME_NOW;

            if (preg_match('/\#(\d+)$/', $pricePlan->name, $matches)) {
                $counter = (int)$matches[1];
                $counter++;
                $pricePlan->name = (string)preg_replace('/#(\d+)$/', '#' . $counter, $pricePlan->name);
            } else {
                $pricePlan->name .= ' #1';
            }

            if (!$pricePlan->save(false)) {
                throw new CException($pricePlan->shortErrors->getAllAsString());
            }

            // whether this plan is shown only to certain customer groups
            $pricePlanGroupsDisplay = PricePlanCustomerGroupDisplay::model()->findAllByAttributes([
                'plan_id' => $this->plan_id,
            ]);
            foreach ($pricePlanGroupsDisplay as $pricePlanGroupDisplay) {
                $relation = new PricePlanCustomerGroupDisplay();
                $relation->plan_id  = (int)$pricePlan->plan_id;
                $relation->group_id = (int)$pricePlanGroupDisplay->group_id;
                $relation->save();
            }
            //

            $transaction->commit();
            $copied = $pricePlan;
        } catch (Exception $e) {
            $transaction->rollback();
        }

        return $copied;
    }

    /**
     * @param string $plan_uid
     *
     * @return PricePlan|null
     */
    public function findByUid(string $plan_uid): ?self
    {
        return self::model()->findByAttributes([
            'plan_uid' => $plan_uid,
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
        return (string)$this->plan_uid;
    }

    /**
     * @return string
     */
    public function getFormattedPrice(): string
    {
        /** @var Currency|null $currency */
        $currency = $this->getCurrency();

        /** @var string $code */
        $code = !empty($currency) && !empty($currency->code) ? $currency->code : '';

        return numberFormatter()->formatCurrency($this->price, $code);
    }

    /**
     * @return Currency|null
     */
    public function getCurrency(): ?Currency
    {
        return Currency::model()->findDefault();
    }

    /**
     * @return bool
     */
    public function getIsRecommended(): bool
    {
        return (string)$this->recommended === self::TEXT_YES;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        if (empty($this->plan_uid)) {
            $this->plan_uid = $this->generateUid();
        }

        return true;
    }
}
