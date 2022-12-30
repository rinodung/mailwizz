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
     * @var User|null
     */
    protected $_model;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        // in case the user was logged in then deleted.
        if ($this->getId() > 0 && !$this->getModel()) {
            $this->setId(null);
        }
    }

    /**
     * @return User|null
     */
    public function getModel(): ?User
    {
        if ($this->_model !== null) {
            return $this->_model;
        }
        return $this->_model = User::model()->findByPk((int)$this->getId());
    }

    /**
     * @return bool
     */
    protected function beforeLogout()
    {
        if ($this->allowAutoLogin) {
            UserAutoLoginToken::model()->deleteAllByAttributes([
                'user_id' => (int)$this->getId(),
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
            if (empty($states['__user_auto_login_token'])) {
                return false;
            }

            $autoLoginToken = UserAutoLoginToken::model()->findByAttributes([
                'user_id'   => (int)$id,
                'token'     => $states['__user_auto_login_token'],
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
        /** @var User|null $model */
        $model = $this->getModel();

        if (!empty($model)) {
            $model->updateLastLogin();
        }
    }
}
