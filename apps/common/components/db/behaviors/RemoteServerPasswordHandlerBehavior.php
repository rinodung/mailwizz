<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * RemoteServerPasswordHandlerBehavior
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.4
 *
 */

/**
 * @property DeliveryServer|BounceServer $owner
 */
class RemoteServerPasswordHandlerBehavior extends CActiveRecordBehavior
{

    /**
     * @var string
     */
    public $passwordField = 'password';

    /**
     * @var phpseclib\Crypt\AES
     */
    protected $_cipher;

    /**
     * @var string
     */
    protected $_plainTextPassword;

    /**
     * @param CModelEvent $event
     *
     * @return void
     */
    public function beforeSave($event)
    {
        $passwordField = $this->passwordField;
        if (empty($this->owner->$passwordField)) {
            return;
        }
        $this->_plainTextPassword    = $this->owner->$passwordField;
        $this->owner->$passwordField = base64_encode((string)$this->getCipher()->encrypt($this->owner->$passwordField));
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function afterSave($event)
    {
        $passwordField = $this->passwordField;
        if (empty($this->owner->$passwordField)) {
            return;
        }
        $this->owner->$passwordField = $this->_plainTextPassword;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function afterFind($event)
    {
        $passwordField = $this->passwordField;
        if (empty($this->owner->$passwordField)) {
            return;
        }

        $password = (string)base64_decode($this->owner->$passwordField, true);
        if (base64_encode((string)$password) !== $this->owner->$passwordField) {
            return;
        }

        $this->owner->$passwordField = $this->getCipher()->decrypt($password);
    }

    /**
     * @return phpseclib\Crypt\AES
     */
    protected function getCipher(): phpseclib\Crypt\AES
    {
        if ($this->_cipher !== null) {
            return $this->_cipher;
        }

        $this->_cipher = new phpseclib\Crypt\AES();
        $this->_cipher->iv = '';
        $this->_cipher->setKeyLength(128);
        $this->_cipher->setKey('abcdefghqrstuvwxyz123456ijklmnop');
        return $this->_cipher;
    }
}
