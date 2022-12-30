<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OptionCustomerSubaccountPermissions
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

abstract class OptionCustomerSubaccountPermissions extends OptionBase
{
    /** @var null|Customer */
    private $_customer;

    /**
     * @return string
     */
    abstract public function getPermissionsCategoryName(): string;

    /**
     * @param Customer $parent
     *
     * @return bool
     */
    abstract public function getParentCustomerIsAllowedAccess(Customer $parent): bool;

    /**
     * Refresh the model properties
     *
     * @return void
     */
    public function refresh(): void
    {
        if (empty($this->_customer) || empty($this->_customer->parent_id)) {
            return;
        }

        foreach ($this->getAttributes() as $attributeName => $attributeValue) {
            $this->$attributeName = $this->getOption($attributeName, $this->$attributeName);
        }
    }

    /**
     * @return string
     */
    public function getCategoryName(): string
    {
        if (empty($this->_customer) || empty($this->_customer->parent_id)) {
            throw new Exception('Please provide a valid subaccount for which these permissions are applied!');
        }

        return sprintf(
            '%s.%d_%d.%s',
            'system.customer_subaccount_permissions',
            (int)$this->_customer->parent_id,
            (int)$this->_customer->customer_id,
            $this->getPermissionsCategoryName()
        );
    }

    /**
     * @param Customer $customer
     *
     * @param bool $refresh
     *
     * @return $this
     */
    public function setCustomer(Customer $customer, bool $refresh = true): self
    {
        $this->_customer = $customer;

        if ($refresh) {
            $this->refresh();
        }

        return $this;
    }
}
