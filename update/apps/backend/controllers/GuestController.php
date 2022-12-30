<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * GuestController
 *
 * Handles the actions for guest related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class GuestController extends Controller
{
    /**
     * @var string
     */
    public $layout = 'guest';

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        /** @var CList $bodyClasses */
        $bodyClasses = $this->getData('bodyClasses');

        $bodyClasses->add('hold-transition login-page');
    }

    /**
     * @param CAction $action
     *
     * @return bool
     * @throws CException
     */
    public function beforeAction($action)
    {
        if (!in_array($action->getId(), ['error']) && !user()->getIsGuest()) {
            $this->redirect(['dashboard/index']);
        }
        return parent::beforeAction($action);
    }

    /**
     * Display the login form so that a guest can login and become an administrator
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws Da\TwoFA\Exception\InvalidSecretKeyException
     */
    public function actionIndex()
    {
        /** @var UserLogin $model */
        $model = new UserLogin();

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        if (GuestFailAttempt::model()->setBaseInfo()->getHasTooManyFailuresWithThrottle()) {
            throw new CHttpException(403, t('app', 'Your access to this resource is forbidden.'));
        }

        /** @var OptionCustomization $customize */
        $customize    = container()->get(OptionCustomization::class);
        $loginBgImage = $customize->getBackendLoginBgUrl(5000, 5000);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;

            // mark the initial status, not logged in
            $loggedIn = false;

            if ($model->validate()) {
                /** @var OptionTwoFactorAuth $twoFaSettings */
                $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

                /** @var User $user */
                $user = $model->getModel();

                // when 2FA is disabled system wide or per account
                if (!$twoFaSettings->getIsEnabled() || !$user->getTwoFaEnabled()) {
                    $loggedIn = $model->authenticate();
                } else {

                    // set the right scenario
                    $model->setScenario('twofa-login');

                    // when the 2FA code has been posted
                    if ($model->twofa_code) {
                        $manager = new Da\TwoFA\Manager();

                        if (!$user->twofa_timestamp) {
                            $user->saveAttributes([
                                'twofa_timestamp' => $manager->getTimestamp(),
                            ]);
                        }

                        /** @var int $previousTs */
                        $previousTs = $user->twofa_timestamp;

                        $timestamp = $manager
                            ->setCycles(5)
                            /** @phpstan-ignore-next-line */
                            ->verify($model->twofa_code, $user->twofa_secret, $previousTs);

                        if ($timestamp && ($loggedIn = $model->authenticate())) {
                            $user->saveAttributes([
                                'twofa_timestamp' => $timestamp,
                            ]);
                        } else {
                            $model->addError('twofa_code', t('customers', 'The 2FA code you have provided is not valid!'));
                        }
                    }

                    // render the form to enter the 2FA only if not logged in
                    if (!$loggedIn) {
                        $this->render('login-2fa', [
                            'model'         => $model,
                            'loginBgImage'  => $loginBgImage,
                        ]);
                        return;
                    }
                }
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => $loggedIn,
                'model'     => $model,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(user()->getReturnUrl());
            }

            if (version_compare($common->version, '1.3.5', '>=')) {
                GuestFailAttempt::registerByPlace('Backend login');
            }
        }

        $this->setData([
            'pageMetaTitle' => $this->getData('pageMetaTitle') . ' | ' . t('users', 'Please login'),
            'pageHeading'   => t('users', 'Please login'),
        ]);

        $this->render('login', compact('model', 'loginBgImage'));
    }

    /**
     * Display the form to retrieve a forgotten password.
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionForgot_password()
    {
        /** @var UserPasswordReset */
        $model = new UserPasswordReset();

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        if (GuestFailAttempt::model()->setBaseInfo()->getHasTooManyFailuresWithThrottle()) {
            throw new CHttpException(403, t('app', 'Your access to this resource is forbidden.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => $model->validate(),
                'model'     => $model,
            ]));

            if (!$collection->itemAt('success')) {
                if (version_compare($common->version, '1.3.5', '>=')) {
                    GuestFailAttempt::registerByPlace('Backend forgot password');
                }
            } else {
                $user = User::model()->findByAttributes(['email' => $model->email]);
                $model->user_id = (int)$user->user_id;
                $model->save(false);

                $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                    'password-reset-request',
                    [
                        'subject' => t('customers', 'Password reset request!'),
                    ],
                    [
                        '[CONFIRMATION_URL]' => createAbsoluteUrl('guest/reset_password', ['reset_key' => $model->reset_key]),
                    ]
                );

                $email = new TransactionalEmail();
                $email->sendDirectly = $registration->getSendEmailDirect();
                $email->to_name      = $user->getFullName();
                $email->to_email     = $user->email;
                $email->from_name    = $common->getSiteName();
                $email->subject      = $params['subject'];
                $email->body         = $params['body'];
                $email->save();

                notify()->addSuccess(t('app', 'Please check your email address.'));
                $model->unsetAttributes();
                $model->email = '';
            }
        }

        $this->setData([
            'pageMetaTitle' => $this->getData('pageMetaTitle') . ' | ' . t('users', 'Retrieve a new password for your account.'),
        ]);

        $this->render('forgot_password', compact('model'));
    }

    /**
     * Reached from email, will reset the password for given user and send a new one via email.
     *
     * @param string $reset_key
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionReset_password($reset_key)
    {
        $model = UserPasswordReset::model()->findByAttributes([
            'reset_key' => $reset_key,
            'status'    => UserPasswordReset::STATUS_ACTIVE,
        ]);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $randPassword   = StringHelper::random();
        $hashedPassword = passwordHasher()->hash($randPassword);

        User::model()->updateByPk((int)$model->user_id, ['password' => $hashedPassword]);
        $model->status = UserPasswordReset::STATUS_USED;
        $model->save();

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        $user = User::model()->findByPk($model->user_id);

        // since 1.3.9.3
        hooks()->doAction('backend_controller_guest_reset_password', $collection = new CAttributeCollection([
            'user'          => $user,
            'passwordReset' => $model,
            'randPassword'  => $randPassword,
            'hashedPassword'=> $hashedPassword,
            'sendEmail'     => true,
            'redirect'      => ['guest/index'],
        ]));

        if (!empty($collection->sendEmail)) {
            $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                'new-login-info',
                [
                    'subject' => t('app', 'Your new login info!'),
                ],
                [
                    '[LOGIN_EMAIL]'     => $user->email,
                    '[LOGIN_PASSWORD]'  => $randPassword,
                    '[LOGIN_URL]'       => createAbsoluteUrl('guest/index'),
                ]
            );

            $email               = new TransactionalEmail();
            $email->sendDirectly = $registration->getSendEmailDirect();
            $email->to_name      = $user->getFullName();
            $email->to_email     = $user->email;
            $email->from_name    = $common->getSiteName();
            $email->subject      = $params['subject'];
            $email->body         = $params['body'];
            $email->save();
        }

        if (!empty($collection->itemAt('redirect'))) {
            notify()->addSuccess(t('app', 'Your new login has been successfully sent to your email address.'));
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * The error handler
     *
     * @return void
     */
    public function actionError()
    {
        if ($error = app()->getErrorHandler()->error) {
            if (request()->getIsAjaxRequest()) {
                echo html_encode((string)$error['message']);
            } else {
                $this->setData([
                    'pageMetaTitle' => t('app', 'Error {code}!', ['{code}' => (int)$error['code']]),
                ]);
                $this->render('error', $error);
            }
        }
    }
}
