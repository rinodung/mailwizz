<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * WebUser
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class WebUser extends BaseWebUser
{
    /**
     * @var Customer
     */
    private $_model;

    /**
     * @var mixed
     */
    private $_id;

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function setId($value)
    {
        $this->_id = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setName($value)
    {
        return $this;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return null;
    }

    /**
     * @return bool
     */
    public function getIsGuest()
    {
        return $this->getId() === null;
    }

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setReturnUrl($value)
    {
        return $this;
    }

    /**
     * @param null $defaultUrl
     *
     * @return null
     */
    public function getReturnUrl($defaultUrl=null)
    {
        return null;
    }

    /**
     * @param Customer $model
     *
     * @return $this
     */
    public function setModel(Customer $model): self
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
}
