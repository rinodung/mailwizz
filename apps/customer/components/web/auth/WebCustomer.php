<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * WebCustomer
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class WebCustomer extends BaseWebUser
{
    /**
     * @var Customer|null
     */
    protected $_model;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        if ($this->getState('__customer_impersonate')) {
            hooks()->addFilter('customer_controller_after_render', [$this, '_showImpersonatingNotice']);
        }

        // in case the logged in customer has been deleted while logged in.
        if ($this->getId() > 0 && !$this->getModel()) {
            $this->setId(null);
        }
    }

    /**
     * @param Customer|null $model
     *
     * @return WebCustomer
     */
    public function setModel(?Customer $model): self
    {
        $this->_model = $model;
        return $this;
    }

    /**
     * @return Customer|null
     */
    public function getModel(): ?Customer
    {
        if ($this->_model !== null) {
            return $this->_model;
        }
        return $this->_model = Customer::model()->findByPk((int)$this->getId());
    }

    /**
     * @param string $output
     *
     * @return string
     */
    public function _showImpersonatingNotice(string $output): string
    {
        /** @var Customer $customer */
        $customer = $this->getModel();

        if (is_subaccount()) {
            /** @var Customer $customer */
            $customer = subaccount()->customer();
        }

        $content = t('users', 'You are impersonating the customer {customerName}.', [
            '{customerName}' => $customer->getFullName() ? $customer->getFullName() : $customer->email,
        ]);

        $content .= '<hr />';

        $content .= t('users', 'Please click {linkBack} to logout in order to finish impersonating.', [
            '{linkBack}' => CHtml::link(t('app', 'here'), ['account/logout']),
        ]);

        $append = CHtml::tag('div', ['class' => 'impersonate-sticky-info no-print'], $content);

        return (string)str_replace('</body>', $append . '</body>', $output);
    }

    /**
     * @return bool
     */
    protected function beforeLogout()
    {
        if ($this->allowAutoLogin) {
            CustomerAutoLoginToken::model()->deleteAllByAttributes([
                'customer_id' => (int)$this->getId(),
            ]);
        }
        return true;
    }

    /**
     * @param mixed $id
     * @param array $states
     * @param bool $fromCookie
     *
     * @return bool
     */
    protected function beforeLogin($id, $states, $fromCookie)
    {
        if (!$fromCookie) {
            return true;
        }

        if ($this->allowAutoLogin) {
            if (empty($states['__customer_auto_login_token'])) {
                return false;
            }

            $autoLoginToken = CustomerAutoLoginToken::model()->findByAttributes([
                'customer_id'    => (int)$id,
                'token'          => $states['__customer_auto_login_token'],
            ]);

            if (empty($autoLoginToken)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param bool $fromCookie
     *
     * @return void
     */
    protected function afterLogin($fromCookie)
    {
        /** @var Customer|null $customer */
        $customer = $this->getModel();

        if (is_subaccount()) {
            /** @var Customer|null $customer */
            $customer = subaccount()->customer();
        }

        if (!empty($customer)) {
            $customer->updateLastLogin();
        }
    }
}
