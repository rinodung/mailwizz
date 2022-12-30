<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerGroupModelHandlerBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

/**
 * @property ActiveRecord $owner
 */
class CustomerGroupModelHandlerBehavior extends CBehavior
{

    /**
     * @var string
     */
    public $categoryName;

    /**
     * @var array
     */
    public $exceptAttributes = [];
    /**
     * @var CustomerGroup
     */
    private $_group;

    /**
     * @return bool
     */
    public function save()
    {
        /** @var CustomerGroup|null $group */
        $group = $this->getGroup();

        if (!$this->owner->validate() || empty($group) || empty($group->group_id)) {
            return false;
        }

        try {
            foreach ($this->getAttributesList() as $attributeName => $attributeValue) {
                $code = $this->categoryName . '.' . $attributeName;
                $option = CustomerGroupOption::model()->findByAttributes([
                    'group_id'  => $group->group_id,
                    'code'      => $code,
                ]);
                if (empty($option)) {
                    $option = new CustomerGroupOption();
                    $option->group_id = $group->group_id;
                    $option->code = $code;
                }
                $option->value = $attributeValue;
                if (!$option->save()) {
                    throw new Exception(CHtml::errorSummary($option));
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param CustomerGroup $group
     *
     * @return $this
     */
    public function setGroup(CustomerGroup $group): self
    {
        $this->_group = $group;
        if (!empty($this->_group->group_id)) {
            $codes = [];
            foreach ($this->getAttributesList() as $key => $value) {
                $codes[] = $this->categoryName . '.' . $key;
            }
            $criteria = new CDbCriteria();
            $criteria->compare('group_id', (int)$this->_group->group_id);
            $criteria->addInCondition('code', $codes);
            $options = CustomerGroupOption::model()->findAll($criteria);
            foreach ($options as $option) {
                $attributeName = explode('.', $option->code);
                $attributeName = end($attributeName);
                $this->owner->$attributeName = $option->value;
            }
        }
        return $this;
    }

    /**
     * @return CustomerGroup|null
     */
    public function getGroup(): ?CustomerGroup
    {
        return $this->_group;
    }

    /**
     * @return array
     */
    public function getAttributesList(): array
    {
        $attributes = $this->owner->getAttributes();
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->exceptAttributes)) {
                unset($attributes[$key]);
            }
        }
        return $attributes;
    }
}
