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
 * @since 1.3.4.3
 */

/**
 * This is the model class for table "customer_group".
 *
 * The followings are the available columns in table 'customer_group':
 * @property integer|null $group_id
 * @property string $name
 * @property string $is_default
 * @property string|CDbExpression $date_added
 * @property string|CDbExpression $last_updated
 *
 * The followings are the available model relations:
 * @property Customer[] $customers
 * @property CustomerGroupOption[] $options
 * @property Customer[] $customersCount
 * @property DeliveryServer[] $deliveryServers
 * @property PricePlan[] $pricePlans
 */
class CustomerGroup extends ActiveRecord
{
    /**
     * @var bool
     */
    public $preDeleteCheckDone = false;

    /**
     * @return string
     */
    public function tableName()
    {
        return '{{customer_group}}';
    }

    /**
     * @return array
     */
    public function rules()
    {
        $rules = [
            ['name', 'required'],
            ['name', 'length', 'max' => 255],
            // The following rule is used by search().
            ['name', 'safe', 'on'=>'search'],
        ];
        return CMap::mergeArray($rules, parent::rules());
    }

    /**
     * @return array
     */
    public function relations()
    {
        $relations = [
            'customers'       => [self::HAS_MANY, Customer::class, 'group_id'],
            'options'         => [self::HAS_MANY, CustomerGroupOption::class, 'group_id'],
            'customersCount'  => [self::STAT, Customer::class, 'group_id'],
            'deliveryServers' => [self::MANY_MANY, DeliveryServer::class, 'delivery_server_to_customer_group(group_id, server_id)'],
            'pricePlans'      => [self::HAS_MANY, PricePlan::class, 'group_id'],
        ];
        return CMap::mergeArray($relations, parent::relations());
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        $labels = [
            'group_id'   => t('customers', 'Group'),
            'name'       => t('customers', 'Name'),
            'is_default' => t('customers', 'Is default'),

            'customersCount' => t('customers', 'Customers count'),
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
        $criteria->order = 'name ASC';

        return new CActiveDataProvider(get_class($this), [
            'criteria'   => $criteria,
            'pagination' => [
                'pageSize' => $this->paginationOptions->getPageSize(),
                'pageVar'  => 'page',
            ],
            'sort'=>[
                'defaultOrder' => [
                    'group_id'  => CSort::SORT_DESC,
                ],
            ],
        ]);
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return CustomerGroup the static model class
     */
    public static function model($className=__CLASS__)
    {
        /** @var CustomerGroup $model */
        $model = parent::model($className);

        return $model;
    }

    /**
     * @return CustomerGroup|null
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
            $group = clone $this;
            $group->setIsNewRecord(true);
            $group->group_id     = null;
            $group->date_added   = MW_DATETIME_NOW;
            $group->last_updated = MW_DATETIME_NOW;

            if (preg_match('/\#(\d+)$/', $group->name, $matches)) {
                $counter = (int)$matches[1];
                $counter++;
                $group->name = (string)preg_replace('/#(\d+)$/', '#' . $counter, $group->name);
            } else {
                $group->name .= ' #1';
            }

            if (!$group->save(false)) {
                throw new CException($group->shortErrors->getAllAsString());
            }

            $options = CustomerGroupOption::model()->findAllByAttributes([
                'group_id' => $this->group_id,
            ]);

            foreach ($options as $option) {
                $option = clone $option;
                $option->setIsNewRecord(true);
                $option->option_id    = null;
                $option->group_id     = (int)$group->group_id;
                $option->date_added   = MW_DATETIME_NOW;
                $option->last_updated = MW_DATETIME_NOW;
                if (!$option->save()) {
                    throw new Exception($option->shortErrors->getAllAsString());
                }
            }

            /** @var DeliveryServerToCustomerGroup[] $deliveryServers */
            $deliveryServers = DeliveryServerToCustomerGroup::model()->findAllByAttributes([
                'group_id' => $this->group_id,
            ]);

            foreach ($deliveryServers as $server) {
                $_server = new DeliveryServerToCustomerGroup();
                $_server->group_id  = (int)$group->group_id;
                $_server->server_id = (int)$server->server_id;
                if (!$_server->save()) {
                    throw new Exception($_server->shortErrors->getAllAsString());
                }
            }

            $transaction->commit();
            $copied = $group;
        } catch (Exception $e) {
            $transaction->rollback();
        }

        return $copied;
    }

    /**
     * @param string $optionCode
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getOptionValue(string $optionCode, $defaultValue = null)
    {
        static $loaded = [];
        if (!isset($loaded[$this->group_id])) {
            $loaded[$this->group_id] = [];
        }

        if (array_key_exists($optionCode, $loaded[$this->group_id])) {
            return $loaded[$this->group_id][$optionCode];
        }
        $criteria = new CDbCriteria();
        $criteria->select = 't.value, t.is_serialized';
        $criteria->compare('t.group_id', (int)$this->group_id);
        $criteria->compare('t.code', $optionCode);
        $model = CustomerGroupOption::model()->find($criteria);
        return $loaded[$this->group_id][$optionCode] = !empty($model) ? $model->value : $defaultValue;
    }

    /**
     * @return array
     */
    public static function getGroupsList(): array
    {
        $criteria = new CDbCriteria();
        $criteria->select = 't.group_id, t.name';
        $criteria->order = 't.name ASC';
        return self::model()->findAll($criteria);
    }

    /**
     * @return array
     */
    public static function getGroupsArray(): array
    {
        static $_options;
        if ($_options !== null) {
            return $_options;
        }

        return $_options = collect(self::getGroupsList())->mapWithKeys(function (CustomerGroup $group) {
            return [$group->group_id => $group->name];
        })->all();
    }

    /**
     * @throws CDbException
     */
    public function resetSendingQuota(): void
    {
        db()->createCommand('
            DELETE qm FROM {{customer_quota_mark}} qm 
                INNER JOIN {{customer}} c ON c.customer_id = qm.customer_id 
                INNER JOIN {{customer_group}} g ON g.group_id = c.group_id
            WHERE g.group_id = :gid
        ')->execute([':gid' => (int)$this->group_id]);
    }

    /**
     * @return bool
     */
    protected function beforeDelete()
    {
        if (!$this->preDeleteCheckDone) {
            $this->preDeleteCheckDone = true;
            $denyOptions  = ['system.customer_registration.default_group', 'system.customer_sending.move_to_group_id'];
            foreach ($denyOptions as $option) {
                if ((int)$this->group_id == (int)options()->get($option)) {
                    return $this->preDeleteCheckDone = false;
                }
            }

            $criteria = new CDbCriteria();
            $criteria->compare('t.code', 'system.customer_sending.move_to_group_id');
            $criteria->compare('t.value', $this->group_id);
            $criteria->addCondition('t.group_id != :gid');
            $criteria->params[':gid'] = (int)$this->group_id;
            $model = CustomerGroupOption::model()->find($criteria);
            if (!empty($model)) {
                return $this->preDeleteCheckDone = false;
            }
        }

        return parent::beforeDelete();
    }
}
