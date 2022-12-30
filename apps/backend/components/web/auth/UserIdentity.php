<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UserIdentity
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class UserIdentity extends BaseUserIdentity
{
    /**
     * @return bool
     */
    public function authenticate()
    {
        $user = User::model()->findByAttributes([
            'email'  => $this->email,
            'status' => User::STATUS_ACTIVE,
        ]);

        if (empty($user) || !passwordHasher()->check($this->password, $user->password)) {
            $this->errorMessage = t('users', 'Invalid login credentials.');
            $this->errorCode    = self::ERROR_UNKNOWN_IDENTITY;
            return false;
        }

        $this->setId($user->user_id);
        $this->setAutoLoginToken($user);
        $this->errorCode = self::ERROR_NONE;

        return true;
    }

    /**
     * @param User $user
     *
     * @return $this
     */
    public function setAutoLoginToken(User $user)
    {
        $token = StringHelper::randomSha1();
        $this->setState('__user_auto_login_token', $token);

        UserAutoLoginToken::model()->deleteAllByAttributes([
            'user_id' => (int)$user->user_id,
        ]);

        $autologinToken          = new UserAutoLoginToken();
        $autologinToken->user_id = (int)$user->user_id;
        $autologinToken->token   = $token;
        $autologinToken->save();

        return $this;
    }
}
