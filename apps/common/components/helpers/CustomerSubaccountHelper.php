<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerSubaccountHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class CustomerSubaccountHelper
{
    /**
     * @return Customer|null
     */
    public function customer(): ?Customer
    {
        static $subaccount = null;
        static $subaccountQueried = false;

        if ($subaccountQueried) {
            return $subaccount;
        }

        /** @var WebCustomer|null $customer */
        $customer = customer();

        if (empty($customer)) {
            $subaccountQueried = true;
            return $subaccount;
        }

        if (!(int)$customer->getState('__subaccount_customer_id', 0)) {
            $subaccountQueried = true;
            return $subaccount;
        }

        $subaccountQueried = true;

        return $subaccount = Customer::model()->findByAttributes([
            'customer_id'   => (int)$customer->getState('__subaccount_customer_id', 0),
            'status'        => Customer::STATUS_ACTIVE,
        ]);
    }

    /**
     * @return bool
     */
    public function canManageLists(): bool
    {
        if (!$this->customer()) {
            return false;
        }

        /** @var OptionCustomerSubaccountPermissionsLists $permission */
        $permission = container()->get(OptionCustomerSubaccountPermissionsLists::class);

        /** @var Customer $subaccount */
        $subaccount = $this->customer();
        $permission->setCustomer($subaccount);

        return $permission->getCanManage();
    }

    /**
     * @return bool
     */
    public function canManageCampaigns(): bool
    {
        if (!$this->customer()) {
            return false;
        }

        /** @var OptionCustomerSubaccountPermissionsCampaigns $permission */
        $permission = container()->get(OptionCustomerSubaccountPermissionsCampaigns::class);

        /** @var Customer $subaccount */
        $subaccount = $this->customer();
        $permission->setCustomer($subaccount);

        return $permission->getCanManage();
    }

    /**
     * @return bool
     */
    public function canManageServers(): bool
    {
        if (!$this->customer()) {
            return false;
        }

        /** @var OptionCustomerSubaccountPermissionsServers $permission */
        $permission = container()->get(OptionCustomerSubaccountPermissionsServers::class);

        /** @var Customer $subaccount */
        $subaccount = $this->customer();
        $permission->setCustomer($subaccount);

        return $permission->getCanManage();
    }

    /**
     * @return bool
     */
    public function canManageSurveys(): bool
    {
        if (!$this->customer()) {
            return false;
        }

        /** @var OptionCustomerSubaccountPermissionsSurveys $permission */
        $permission = container()->get(OptionCustomerSubaccountPermissionsSurveys::class);

        /** @var Customer $subaccount */
        $subaccount = $this->customer();
        $permission->setCustomer($subaccount);

        return $permission->getCanManage();
    }

    /**
     * @return bool
     */
    public function canManageApiKeys(): bool
    {
        if (!$this->customer()) {
            return false;
        }

        /** @var OptionCustomerSubaccountPermissionsApiKeys $permission */
        $permission = container()->get(OptionCustomerSubaccountPermissionsApiKeys::class);

        /** @var Customer $subaccount */
        $subaccount = $this->customer();
        $permission->setCustomer($subaccount);

        return $permission->getCanManage();
    }

    /**
     * @return bool
     */
    public function canManageDomains(): bool
    {
        if (!$this->customer()) {
            return false;
        }

        /** @var OptionCustomerSubaccountPermissionsDomains $permission */
        $permission = container()->get(OptionCustomerSubaccountPermissionsDomains::class);

        /** @var Customer $subaccount */
        $subaccount = $this->customer();
        $permission->setCustomer($subaccount);

        return $permission->getCanManage();
    }

    /**
     * @return bool
     */
    public function canManageEmailTemplates(): bool
    {
        if (!$this->customer()) {
            return false;
        }

        /** @var OptionCustomerSubaccountPermissionsEmailTemplates $permission */
        $permission = container()->get(OptionCustomerSubaccountPermissionsEmailTemplates::class);

        /** @var Customer $subaccount */
        $subaccount = $this->customer();
        $permission->setCustomer($subaccount);

        return $permission->getCanManage();
    }

    /**
     * @return bool
     */
    public function canManageBlacklists(): bool
    {
        if (!$this->customer()) {
            return false;
        }

        /** @var OptionCustomerSubaccountPermissionsBlacklists $permission */
        $permission = container()->get(OptionCustomerSubaccountPermissionsBlacklists::class);

        /** @var Customer $subaccount */
        $subaccount = $this->customer();
        $permission->setCustomer($subaccount);

        return $permission->getCanManage();
    }
}
