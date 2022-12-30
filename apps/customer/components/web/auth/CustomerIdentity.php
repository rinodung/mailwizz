<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerIdentity
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CustomerIdentity extends BaseUserIdentity
{
    /**
     * @var bool
     */
    public $impersonate = false;

    /**
     * @return bool
     * @throws CException
     */
    public function authenticate()
    {
        /** @var Customer|null $customer */
        $customer = Customer::model()->findByAttributes([
            'email' => $this->email,
        ]);

        if (empty($customer)) {
            $this->errorCode    = self::ERROR_UNKNOWN_IDENTITY;
            $this->errorMessage = t('customers', 'Invalid login credentials.');
            return false;
        }

        // since 1.3.9.5
        if (!$this->impersonate && in_array($customer->status, [Customer::STATUS_PENDING_DISABLE, Customer::STATUS_DISABLED])) {
            $status = $customer->status;
            $customer->saveStatus(Customer::STATUS_ACTIVE);
            hooks()->doAction('customer_login_with_disabled_account', new CAttributeCollection([
                'customer'      => $customer,
                'identity'      => $this,
                'initialStatus' => $status,
            ]));
        }

        if ($customer->status != Customer::STATUS_ACTIVE) {
            $this->errorCode    = self::ERROR_UNKNOWN_IDENTITY;
            $this->errorMessage = t('customers', 'Invalid login credentials.');
            return false;
        }

        if (!$this->impersonate && !passwordHasher()->check($this->password, $customer->password)) {
            $this->errorCode    = self::ERROR_UNKNOWN_IDENTITY;
            $this->errorMessage = t('customers', 'Invalid login credentials.');
            return false;
        }

        // 2.0.0 - subaccount addition
        // Known issue - if the parent logs out manually, auto-login will fail for subaccounts
        // If this is a subaccount:
        if (!empty($customer->parent_id) && !empty($customer->parent)) {
            $parent = $customer->parent;
            if (!$parent->getIsActive()) {
                $this->errorCode    = self::ERROR_UNKNOWN_IDENTITY;
                $this->errorMessage = t('customers', 'Parent account is disabled, subaccounts are disabled as well!');
                return false;
            }

            $this->setId($parent->customer_id);
            $this->setAutoLoginToken($parent);

            $this->setState('__subaccount_customer_id', $customer->customer_id);
        } else {

            // this is a parent account
            $this->setId($customer->customer_id);
            $this->setAutoLoginToken($customer);
        }
        //

        $this->errorCode = self::ERROR_NONE;
        return true;
    }

    /**
     * @param Customer $customer
     *
     * @return $this
     */
    public function setAutoLoginToken(Customer $customer)
    {
        $token = StringHelper::randomSha1();
        $this->setState('__customer_auto_login_token', $token);

        CustomerAutoLoginToken::model()->deleteAllByAttributes([
            'customer_id' => (int)$customer->customer_id,
        ]);

        $autoLoginToken              = new CustomerAutoLoginToken();
        $autoLoginToken->customer_id = (int)$customer->customer_id;
        $autoLoginToken->token       = $token;
        $autoLoginToken->save();

        return $this;
    }
}
