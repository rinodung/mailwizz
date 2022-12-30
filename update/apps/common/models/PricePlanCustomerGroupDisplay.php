<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PricePlanCustomerGroupDisplay
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.2
 */

/**
 * This is the model class for table "{{price_plan_customer_group_display}}".
 *
 * The followings are the available columns in table '{{price_plan_customer_group_display}}':
 * @property integer $plan_id
 * @property integer $group_id
 */
class PricePlanCustomerGroupDisplay extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{price_plan_customer_group_display}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['plan_id', 'exist', 'className' => PricePlan::class],
            ['group_id', 'exist', 'className' => CustomerGroup::class],
        ];
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'plan'  => [self::BELONGS_TO, PricePlan::class, 'plan_id'],
            'group' => [self::BELONGS_TO, CustomerGroup::class, 'group_id'],
        ];

        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'group_id' => t('customers', 'Customer group(s) visibility'),
        ];
    }

    /**
     * @return array
     */
    public function attributeHelpTexts()
    {
        $texts = [
            'group_id'    => t('customers', 'If no group is selected, all customers will see this plan. If one or more groups are selected, then just customers within these groups will see the plan.'),
        ];

        return CMap::mergeArray($texts, parent::attributeHelpTexts());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return PricePlanCustomerGroupDisplay the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var PricePlanCustomerGroupDisplay $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return array
     */
    public function getCustomerGroupsList(): array
    {
        return CustomerGroupCollection::findAll()->mapWithKeys(function (CustomerGroup $group) {
            return [$group->group_id => $group->name];
        })->all();
    }
}
