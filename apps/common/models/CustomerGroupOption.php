<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerGroupOption
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

/**
 * This is the model class for table "customer_group_option".
 *
 * The followings are the available columns in table 'customer_group_option':
 * @property integer $option_id
 * @property integer $group_id
 * @property string $code
 * @property integer $is_serialized
 * @property mixed $value
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property CustomerGroup $group
 */
class CustomerGroupOption extends ActiveRecord
{
    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_group_option}}';
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
            'group' => [self::BELONGS_TO, CustomerGroup::class, 'group_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'option_id'      => t('customers', 'Option'),
            'code'           => t('customers', 'Code'),
            'is_serialized'  => t('customers', 'Is serialized'),
            'value'          => t('customers', 'Value'),
        ];
        return CMap::mergeArray($labels, parent::attributeLabels());
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerGroupOption the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerGroupOption $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return bool
     */
    protected function beforeSave()
    {
        $this->is_serialized = 0;
        if ($this->value !== null && !is_string($this->value)) {
            $this->value = @serialize($this->value);
            $this->is_serialized = 1;
        }
        return parent::beforeSave();
    }

    /**
     * @return void
     */
    protected function afterSave()
    {
        if ($this->is_serialized) {
            $this->value = unserialize((string)$this->value);
        }
        parent::afterSave();
    }

    /**
     * @return void
     */
    protected function afterFind()
    {
        if ($this->is_serialized) {
            $this->value = unserialize((string)$this->value);
        }
        parent::afterFind();
    }
}
