<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PasswordHasher
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

use Hautelook\Phpass\PasswordHash;

class PasswordHasher extends CApplicationComponent
{

    /**
     * @var int
     */
    public $iterationCount = 13;

    /**
     * @var bool
     */
    public $portableHashes = true;

    /**
     * @var PasswordHash
     */
    protected $_passwordHash;

    /**
     * @param string $password
     *
     * @return string
     */
    public function hash(string $password): string
    {
        return $this->getPasswordHash()->HashPassword($password);
    }

    /**
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function check(string $password, string $hash): bool
    {
        return $this->getPasswordHash()->CheckPassword($password, $hash);
    }

    /**
     * @return PasswordHash
     */
    public function getPasswordHash(): PasswordHash
    {
        if ($this->_passwordHash === null) {
            $this->_passwordHash = new PasswordHash((int)$this->iterationCount, (bool)$this->portableHashes);
        }
        return $this->_passwordHash;
    }
}
