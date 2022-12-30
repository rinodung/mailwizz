<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailBlacklistCheckInfo
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.2
 */

class EmailBlacklistCheckInfo extends CMap
{

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getBlacklisted() && $this->getReason() ? $this->getReason() : '';
    }
    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->contains('email') ? (string)$this->itemAt('email') : '';
    }

    /**
     * @param string $value
     *
     * @return EmailBlacklistCheckInfo
     * @throws CException
     */
    public function setEmail(string $value): self
    {
        $this->add('email', (string)$value);
        return $this;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->contains('reason') ? (string)$this->itemAt('reason') : '';
    }

    /**
     * @param string $value
     *
     * @return EmailBlacklistCheckInfo
     * @throws CException
     */
    public function setReason(string $value): self
    {
        $this->add('reason', $value);
        return $this;
    }

    /**
     * @return bool
     */
    public function getBlacklisted(): bool
    {
        return $this->contains('blacklisted') && $this->itemAt('blacklisted') !== false;
    }

    /**
     * @param bool $value
     *
     * @return EmailBlacklistCheckInfo
     * @throws CException
     */
    public function setBlacklisted(bool $value): self
    {
        $this->add('blacklisted', (bool)$value);
        return $this;
    }

    /**
     * @return bool
     */
    public function getCustomerBlacklist(): bool
    {
        return $this->contains('customerBlacklist') && $this->itemAt('customerBlacklist') !== false;
    }

    /**
     * @param bool $value
     *
     * @return EmailBlacklistCheckInfo
     * @throws CException
     */
    public function setCustomerBlacklist(bool $value): self
    {
        $this->add('customerBlacklist', (bool)$value);
        return $this;
    }
}
