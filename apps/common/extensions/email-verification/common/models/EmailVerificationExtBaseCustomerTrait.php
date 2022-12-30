<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailVerificationExtBaseCustomerTrait
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */
trait EmailVerificationExtBaseCustomerTrait
{
    /**
     * @var Customer|null
     */
    private $_customer;

    /**
     * @param Customer $customer
     */
    public function setCustomer(Customer $customer): void
    {
        $this->_customer = $customer;
        $this->refresh();
    }

    /**
     * @return Customer|null
     */
    public function getCustomer(): ?Customer
    {
        if (!empty($this->_customer)) {
            return $this->_customer;
        }

        return $this->_customer = app()->hasComponent('customer') ? customer()->getModel() : null;
    }

    /**
     * @inheritDoc
     */
    public function getCategoryName(): string
    {
        /** @var Customer|null $customer */
        $customer = $this->getCustomer();

        /** @var int $customerId */
        $customerId = !empty($customer) ? (int)$customer->customer_id : 0;

        return $this->getOptionsPrefix() . '_customer_' . $customerId;
    }

    /**
     * @inheritDoc
     */
    public function refresh(): void
    {
        if (!$this->getCustomer()) {
            return;
        }
        parent::refresh();
    }
}
