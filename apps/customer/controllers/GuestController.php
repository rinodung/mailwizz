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
     * @throws CException
     */
    public function init()
    {
        parent::init();
        $this->onBeforeAction = [$this, '_registerJuiBs'];
        $this->addPageScript(['src' => AssetsUrl::js('guest.js')]);

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
        if (!in_array($action->getId(), ['error']) && !customer()->getIsGuest()) {
            $this->redirect(['dashboard/index']);
        }
        return parent::beforeAction($action);
    }

    /**
     * Display the login form
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     * @throws Da\TwoFA\Exception\InvalidSecretKeyException
     */
    public function actionIndex()
    {
        /** @var CustomerLogin $model */
        $model = new CustomerLogin();

        if (GuestFailAttempt::model()->setBaseInfo()->getHasTooManyFailuresWithThrottle()) {
            throw new CHttpException(403, t('app', 'Your access to this resource is forbidden.'));
        }

        /** @var OptionCustomization $optionCustomization */
        $optionCustomization = container()->get(OptionCustomization::class);

        // since 1.5.1
        $loginBgImage = $optionCustomization->getCustomerLoginBgUrl(5000, 5000);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;

            // mark the initial status, not logged in
            $loggedIn = false;

            /** @var Customer $customer */
            $customer = $model->getModel();

            if ($model->validate()) {
                /** @var OptionTwoFactorAuth $twoFaSettings */
                $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

                // when 2FA is disabled system wide or per account
                if (!$twoFaSettings->getIsEnabled() || !$customer->getTwoFaEnabled()) {
                    $loggedIn = $model->authenticate();
                } else {

                    // set the right scenario
                    $model->setScenario('twofa-login');

                    // when the 2FA code has been posted
                    if ($model->twofa_code) {
                        $manager = new Da\TwoFA\Manager();

                        if (!$customer->twofa_timestamp) {
                            $customer->twofa_timestamp = $manager->getTimestamp();
                            $customer->save(false);
                        }

                        /** @var int $previousTs */
                        $previousTs = $customer->twofa_timestamp;

                        $timestamp = $manager
                            ->setCycles(5)
                            ->verify($model->twofa_code, $customer->twofa_secret, $previousTs); // @phpstan-ignore-line

                        if ($timestamp && ($loggedIn = $model->authenticate())) {
                            $customer->twofa_timestamp = (int)$timestamp;
                            $customer->save(false);
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

                /** @var Customer $customer */
                $customer = customer()->getModel();

                if (is_subaccount()) {
                    /** @var Customer $customer */
                    $customer = subaccount()->customer();
                }

                // since 1.3.6.2
                CustomerLoginLog::addNew($customer);

                $this->redirect(customer()->getReturnUrl());
            }

            GuestFailAttempt::registerByPlace('Customer login');
        }

        /** @var OptionCustomerRegistration $registration */
        $registration        = container()->get(OptionCustomerRegistration::class);
        $registrationEnabled = $registration->getIsEnabled();
        $facebookEnabled     = $registration->getIsFacebookEnabled();
        $twitterEnabled      = $registration->getIsTwitterEnabled();

        $this->setData([
            'pageMetaTitle' => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Please login'),
            'pageHeading'   => t('customers', 'Please login'),
        ]);

        $this->render('login', compact('model', 'registrationEnabled', 'facebookEnabled', 'twitterEnabled', 'loginBgImage'));
    }

    /**
     * Display the registration form
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionRegister()
    {
        $model   = new Customer('register');
        $company = new CustomerCompany('register');

        /** @var OptionCustomerRegistration $registration */
        $registration = container()->get(OptionCustomerRegistration::class);

        if (!$registration->getIsEnabled()) {
            $this->redirect(['guest/index']);
        }

        if (GuestFailAttempt::model()->setBaseInfo()->getHasTooManyFailuresWithThrottle()) {
            throw new CHttpException(403, t('app', 'Your access to this resource is forbidden.'));
        }

        $facebookEnabled    = $registration->getIsFacebookEnabled();
        $twitterEnabled     = $registration->getIsTwitterEnabled();
        $companyRequired    = $registration->getIsCompanyRequired();
        $mustConfirmEmail   = $registration->getRequireEmailConfirmation();
        $requireApproval    = $registration->getRequireApproval();
        $defaultCountry     = $registration->getDefaultCountry();
        $defaultTimezone    = $registration->getDefaultTimezone();

        if (!empty($defaultCountry)) {
            $company->country_id = (int)$defaultCountry;
        }

        if (!empty($defaultTimezone)) {
            $model->timezone = $defaultTimezone;
        }
        //

        // 1.5.5
        $newsletterApiEnabled     = $registration->getApiEnabled();
        $newsletterApiConsentText = $registration->getApiConsentText();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;
            $model->status     = Customer::STATUS_PENDING_CONFIRM;

            hooks()->addAction('controller_action_save_data', [$this, '_checkEmailDomainForbidden'], 100);
            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => true,
                'model'     => $model,
            ]));

            if ($collection->itemAt('success')) {
                $transaction = db()->beginTransaction();

                try {
                    if ($model->hasErrors() || !$model->save()) {
                        throw new Exception(CHtml::errorSummary($model), 422);
                    }

                    if (EmailBlacklist::isBlacklisted($model->email)) {
                        throw new Exception(t('customers', 'This email address is blacklisted!'), 422);
                    }

                    if ($companyRequired) {
                        $company->attributes  = (array)request()->getPost($company->getModelName(), []);
                        $company->customer_id = (int)$model->customer_id;
                        if (!$company->save()) {
                            throw new Exception(CHtml::errorSummary($company), 422);
                        }
                    }

                    // 1.3.6.3
                    if ($mustConfirmEmail) {
                        $this->_sendRegistrationConfirmationEmail($model, $company);
                    }

                    $this->_sendNewCustomerNotifications($model, $company);

                    // 1.3.7
                    $this->_subscribeToEmailList($model);

                    // 1.3.6.3
                    if (!$mustConfirmEmail) {
                        $transaction->commit();
                        $this->redirect(['guest/confirm_registration', 'key' => $model->confirmation_key]);
                    }

                    if (notify()->getIsEmpty()) {
                        if ($mustConfirmEmail) {
                            notify()->addSuccess(t('customers', 'Congratulations, your account has been created, check your inbox for email confirmation! Please note that sometimes the email might land in spam/junk!'));
                        } else {
                            notify()->addSuccess(t('customers', 'Congratulations, your account has been created, you can login now!'));
                        }
                    }
                    $transaction->commit();
                    $this->redirect(['guest/index']);
                } catch (Exception $e) {
                    $transaction->rollback();
                    GuestFailAttempt::registerByPlace('Customer register');

                    // 1.9.13
                    if ((int)$e->getCode() === 422) {
                        notify()->addError($e->getMessage());
                    }
                }
            }
        }

        $this->setData([
            'pageMetaTitle' => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Please register'),
            'pageHeading'   => t('customers', 'Please register'),
        ]);

        $this->render('register', compact('model', 'company', 'companyRequired', 'facebookEnabled', 'twitterEnabled', 'newsletterApiEnabled', 'newsletterApiConsentText'));
    }

    /**
     * @param string $key
     *
     * @return void
     * @throws CException
     */
    public function actionConfirm_registration($key)
    {
        $model = Customer::model()->findByAttributes([
            'confirmation_key' => $key,
            'status'           => Customer::STATUS_PENDING_CONFIRM,
        ]);

        if (empty($model)) {
            $this->redirect(['guest/index']);
        }

        /** @var OptionCustomerRegistration $registration */
        $registration = container()->get(OptionCustomerRegistration::class);
        if ($group = $registration->getDefaultGroup()) {
            $model->group_id = (int)$group->group_id;
        }

        $requireApproval = $registration->getRequireApproval();
        $model->status   = !$requireApproval ? Customer::STATUS_ACTIVE : Customer::STATUS_PENDING_ACTIVE;
        if (!$model->save(false)) {
            $this->redirect(['guest/index']);
        }

        if ($requireApproval) {
            notify()->addSuccess(t('customers', 'Congratulations, you have successfully confirmed your account.'));
            notify()->addSuccess(t('customers', 'You will be able to login once an administrator will approve it.'));
            $this->redirect(['guest/index']);
        }

        /** @var OptionCommon $optionCommon */
        $optionCommon = container()->get(OptionCommon::class);

        /** @var OptionEmailTemplate $optionEmailTemplate */
        $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

        // send welcome email if needed
        $sendWelcome        = $registration->getSendWelcomeEmail();
        $sendWelcomeSubject = $registration->getWelcomeEmailSubject();
        $sendWelcomeContent = $registration->getWelcomeEmailContent();
        if (!empty($sendWelcome) && !empty($sendWelcomeSubject) && !empty($sendWelcomeContent)) {
            $searchReplace = [
                '[FIRST_NAME]' => $model->first_name,
                '[LAST_NAME]'  => $model->last_name,
                '[FULL_NAME]'  => $model->getFullName(),
                '[EMAIL]'      => $model->email,
            ];
            $sendWelcomeSubject = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $sendWelcomeSubject);
            $sendWelcomeContent = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $sendWelcomeContent);

            $searchReplace = [
                '[SITE_NAME]'       => $optionCommon->getSiteName(),
                '[SITE_TAGLINE]'    => $optionCommon->getSiteTagline(),
                '[CURRENT_YEAR]'    => date('Y'),
                '[CONTENT]'         => $sendWelcomeContent,
            ];
            $emailTemplate = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $optionEmailTemplate->common);

            $email = new TransactionalEmail();
            $email->sendDirectly = $registration->getSendEmailDirect();
            $email->to_name      = $model->getFullName();
            $email->to_email     = $model->email;
            $email->from_name    = $optionCommon->getSiteName();
            $email->subject      = $sendWelcomeSubject;
            $email->body         = $emailTemplate;
            $email->save();
        }

        $identity = new CustomerIdentity($model->email, $model->password);
        $identity->setId($model->customer_id);
        $identity->setAutoLoginToken($model);

        if (!customer()->login($identity, 3600 * 24 * 30)) {
            $this->redirect(['guest/index']);
        }

        notify()->addSuccess(t('customers', 'Congratulations, your account is now ready to use.'));
        notify()->addSuccess(t('customers', 'Please start by filling your account and company info.'));
        $this->redirect(['account/index']);
    }

    /**
     * Display the "Forgot password" form
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionForgot_password()
    {
        $model = new CustomerPasswordReset();

        if (GuestFailAttempt::model()->setBaseInfo()->getHasTooManyFailuresWithThrottle()) {
            throw new CHttpException(403, t('app', 'Your access to this resource is forbidden.'));
        }

        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($model->getModelName(), []))) {
            $model->attributes = $attributes;

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => $model->validate(),
                'model'     => $model,
            ]));

            if ($collection->itemAt('success')) {
                $customer = Customer::model()->findByAttributes(['email' => $model->email]);
                $model->customer_id = (int)$customer->customer_id;
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
                $email->customer_id  = (int)$customer->customer_id;
                $email->to_name      = $customer->getFullName();
                $email->to_email     = $customer->email;
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
            'pageMetaTitle' => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Retrieve a new password for your account.'),
            'pageHeading'   => t('customers', 'Retrieve a new password for your account.'),
        ]);

        $this->render('forgot_password', compact('model'));
    }

    /**
     * @param string $reset_key
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionReset_password($reset_key)
    {
        $model = CustomerPasswordReset::model()->findByAttributes([
            'reset_key' => $reset_key,
            'status'    => CustomerPasswordReset::STATUS_ACTIVE,
        ]);

        if (empty($model)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $randPassword   = StringHelper::random();
        $hashedPassword = passwordHasher()->hash($randPassword);

        Customer::model()->updateByPk((int)$model->customer_id, ['password' => $hashedPassword]);
        $model->status = CustomerPasswordReset::STATUS_USED;
        $model->save();

        $customer = Customer::model()->findByPk($model->customer_id);

        // since 1.3.9.3
        hooks()->doAction('customer_controller_guest_reset_password', $collection = new CAttributeCollection([
            'customer'      => $customer,
            'passwordReset' => $model,
            'randPassword'  => $randPassword,
            'hashedPassword'=> $hashedPassword,
            'sendEmail'     => true,
            'redirect'      => ['guest/index'],
        ]));

        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        if (!empty($collection->sendEmail)) {
            $params = CommonEmailTemplate::getAsParamsArrayBySlug(
                'new-login-info',
                [
                    'subject' => t('app', 'Your new login info!'),
                ],
                [
                    '[LOGIN_EMAIL]'     => $customer->email,
                    '[LOGIN_PASSWORD]'  => $randPassword,
                    '[LOGIN_URL]'       => createAbsoluteUrl('guest/index'),
                ]
            );

            $email               = new TransactionalEmail();
            $email->sendDirectly = $registration->getSendEmailDirect();
            $email->customer_id  = (int)$customer->customer_id;
            $email->to_name      = $customer->getFullName();
            $email->to_email     = $customer->email;
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
     * @return void
     * @throws CException
     * @throws Facebook\Exceptions\FacebookSDKException
     */
    public function actionFacebook()
    {
        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        if (!$registration->getIsEnabled() || !$registration->getIsFacebookEnabled()) {
            $this->redirect(['guest/index']);
        }

        $appID     = (string)$registration->facebook_app_id;
        $appSecret = (string)$registration->facebook_app_secret;

        if (strlen($appID) < 15 || strlen($appSecret) < 32) {
            $this->redirect(['guest/index']);
        }

        $facebook  = new Facebook\Facebook([
            'app_id'                => $appID,
            'app_secret'            => $appSecret,
            'default_graph_version' => 'v2.7',
        ]);

        $helper = $facebook->getRedirectLoginHelper();

        if (!request()->getQuery('code')) {
            $this->redirect($helper->getLoginUrl(createAbsoluteUrl('guest/facebook'), ['email', 'public_profile']));
        }

        if (!($token = $helper->getAccessToken())) {
            $this->redirect(['guest/index']);
        }

        /** @var \Facebook\GraphNodes\GraphUser|null $user */
        $user = null;

        // let's see if the user is logged into facebook and he has approved our app.
        try {
            $response  = $facebook->get('/me?locale=en_US&fields=first_name,last_name,email', $token);
            $user      = $response->getGraphUser();
        } catch (Exception $e) {
        }

        // the user needs to approve our application.
        if (empty($user)) {
            $this->redirect(['guest/index']);
            return;
        }

        // if we are here, means the customer approved the app
        // create the default attributes.
        $attributes = [
            'oauth_uid'     => $user->getId(),
            'oauth_provider'=> 'facebook',
            'first_name'    => $user->getFirstName(),
            'last_name'     => $user->getLastName(),
            'email'         => $user->getEmail(),
        ];
        $attributes = (array)ioFilter()->stripClean($attributes);

        // DO NOT $customer->attributes = $attributes because most of them will not be assigned
        $customer = new Customer();
        foreach ($attributes as $key => $value) {
            if (empty($value)) {
                notify()->addError(t('customers', 'Unable to retrieve all your account data!'));
                $this->redirect(['guest/index']);
            }
            $customer->setAttribute($key, $value);
        }

        $exists = Customer::model()->findByAttributes([
            'oauth_uid'         => $customer->oauth_uid,
            'oauth_provider'    => 'facebook',
        ]);

        if (!empty($exists)) {
            if ($exists->status == Customer::STATUS_ACTIVE) {
                $identity = new CustomerIdentity($exists->email, $exists->password);
                $identity->setId($exists->customer_id);
                $identity->setAutoLoginToken($exists);
                customer()->login($identity, 3600 * 24 * 30);
                $this->redirect(['dashboard/index']);
            }
            notify()->addError(t('customers', 'Your account is not active!'));
            $this->redirect(['guest/index']);
        }

        // if another customer with same email address, do nothing
        $exists = Customer::model()->findByAttributes(['email' => $customer->email]);
        if (!empty($exists)) {
            notify()->addError(t('customers', 'There is another account using this email address, please fill in the form to recover your password!'));
            $this->redirect(['guest/forgot_password']);
        }

        $requireApproval         = $registration->getRequireApproval();
        $randPassword            = StringHelper::random(8);
        $customer->fake_password = $randPassword;
        $customer->status        = !$requireApproval ? Customer::STATUS_ACTIVE : Customer::STATUS_PENDING_ACTIVE;
        $customer->avatar        = $this->fetchCustomerRemoteImage('https://graph.facebook.com/' . $customer->oauth_uid . '/picture?height=400&type=large&width=400');

        if ($group = $registration->getDefaultGroup()) {
            $customer->group_id = (int)$group->group_id;
        }

        // finally try to save the customer.
        if (!$customer->save(false)) {
            notify()->addError(t('customers', 'Unable to save your account, please contact us if this error persists!'));
            $this->redirect(['guest/index']);
        }

        // create the email for customer
        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
            'new-login-info',
            [
                'subject' => t('app', 'Your new login info!'),
            ],
            [
                '[LOGIN_EMAIL]'     => $customer->email,
                '[LOGIN_PASSWORD]'  => $randPassword,
                '[LOGIN_URL]'       => createAbsoluteUrl('guest/index'),
            ]
        );

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        $email = new TransactionalEmail();
        $email->sendDirectly = $registration->getSendEmailDirect();
        $email->to_name      = $customer->getFullName();
        $email->to_email     = $customer->email;
        $email->from_name    = $common->getSiteName();
        $email->subject      = $params['subject'];
        $email->body         = $params['body'];
        $email->save();

        // notify admins
        $this->_sendNewCustomerNotifications($customer, new CustomerCompany());

        if ($requireApproval) {
            notify()->addSuccess(t('customers', 'Congratulations, your account has been successfully created.'));
            notify()->addSuccess(t('customers', 'You will be able to login once an administrator will approve it.'));
            $this->redirect(['guest/index']);
        }

        // the customer has been saved, we need to log him in, should work okay...
        $identity = new CustomerIdentity($customer->email, $customer->password);
        $identity->setId($customer->customer_id);
        $identity->setAutoLoginToken($customer);
        customer()->login($identity, 3600 * 24 * 30);
        $this->redirect(['dashboard/index']);
    }

    /**
     * @return void
     * @throws CException
     * @throws Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function actionTwitter()
    {
        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        if (!$registration->getIsEnabled() || !$registration->getIsTwitterEnabled()) {
            $this->redirect(['guest/index']);
        }

        $appConsumerKey     = (string)$registration->twitter_app_consumer_key;
        $appConsumerSecret  = (string)$registration->twitter_app_consumer_secret;
        $requireApproval    = $registration->getRequireApproval();

        if (strlen($appConsumerKey) < 20 || strlen($appConsumerSecret) < 40) {
            $this->redirect(['guest/index']);
        }

        /** @var array $session */
        $session = session();

        // only if not done already.
        if (!isset($session['access_token'])) {
            // when the app is not approved.
            if (request()->getQuery('do') != 'get-request-token') {
                $twitterOauth = new Abraham\TwitterOAuth\TwitterOAuth($appConsumerKey, $appConsumerSecret);
                $requestToken = $twitterOauth->oauth('oauth/request_token', ['oauth_callback' => createAbsoluteUrl('guest/twitter', ['do'=>'get-request-token'])]);

                if (empty($requestToken)) {
                    $this->redirect(['guest/index']);
                }

                $session['oauth_token']        = $requestToken['oauth_token'];
                $session['oauth_token_secret'] = $requestToken['oauth_token_secret'];

                $this->redirect($twitterOauth->url('oauth/authorize', ['oauth_token' => $requestToken['oauth_token']]));
            }

            //when the request is made...
            if (!request()->getQuery('oauth_verifier') || empty($session['oauth_token']) || empty($session['oauth_token_secret'])) {
                $this->redirect(['guest/index']);
            }

            $twitterOauth = new Abraham\TwitterOAuth\TwitterOAuth($appConsumerKey, $appConsumerSecret, (string)$session['oauth_token'], (string)$session['oauth_token_secret']);
            $accessToken  = $twitterOauth->oauth('oauth/access_token', ['oauth_verifier' => request()->getQuery('oauth_verifier')]);

            if (empty($accessToken)) {
                $this->redirect(['guest/index']);
            }

            $session['access_token'] = $accessToken;
        }

        $accessToken = (array)$session['access_token'];
        $twitterOauth = new Abraham\TwitterOAuth\TwitterOAuth($appConsumerKey, $appConsumerSecret, (string)$accessToken['oauth_token'], (string)$accessToken['oauth_token_secret']);

        /** @var stdClass $_user */
        $_user = $twitterOauth->get('account/verify_credentials');

        if (!empty($_user->errors)) {
            $this->redirect(['guest/index']);
        }

        $firstName = $lastName = trim($_user->name);
        if (strpos($_user->name, ' ') !== false) {
            $names = explode(' ', $_user->name);
            if (count($names) >= 2) {
                $firstName = array_shift($names);
                $lastName  = implode(' ', $names);
            }
        }

        $attributes = [
            'oauth_uid'      => !empty($_user->id) ? $_user->id : null,
            'oauth_provider' => 'twitter',
            'first_name'     => $firstName,
            'last_name'      => $lastName,
        ];
        $attributes = (array)ioFilter()->stripClean($attributes);

        $customer = new Customer();
        foreach ($attributes as $key => $value) {
            if (empty($value)) {
                notify()->addError(t('customers', 'Unable to retrieve all your account data!'));
                $this->redirect(['guest/index']);
            }
            $customer->setAttribute($key, $value);
        }

        $exists = Customer::model()->findByAttributes([
            'oauth_uid'         => $customer->oauth_uid,
            'oauth_provider'    => 'twitter',
        ]);

        if (!empty($exists)) {
            if ($exists->status == Customer::STATUS_ACTIVE) {
                $identity = new CustomerIdentity($exists->email, $exists->password);
                $identity->setId($exists->customer_id);
                $identity->setAutoLoginToken($exists);
                customer()->login($identity, 3600 * 24 * 30);
                $this->redirect(['dashboard/index']);
            }
            notify()->addError(t('customers', 'Your account is not active!'));
            $this->redirect(['guest/index']);
        }

        if (!request()->getIsPostRequest()) {
            $this->setData('pageHeading', t('customers', 'Enter your email address'));
            $this->render('twitter-email', compact('customer'));
            return;
        }

        if (($attributes = (array)request()->getPost($customer->getModelName(), []))) {
            $customer->email = (string)($attributes['email'] ?? '');
        }

        if (!FilterVarHelper::email($customer->email)) {
            notify()->addError(t('customers', 'Invalid email address provided!'));
            $this->setData('pageHeading', t('customers', 'Enter your email address'));
            $this->render('twitter-email', compact('customer'));
            return;
        }

        // if another customer with same email address, do nothing
        $exists = Customer::model()->findByAttributes(['email' => $customer->email]);
        if (!empty($exists)) {
            notify()->addError(t('customers', 'There is another account using this email address, please fill in the form to recover your password!'));
            $this->redirect(['guest/forgot_password']);
        }

        // create a random 8 chars password for the customer, and assign the active status.
        $randPassword            = StringHelper::random(8);
        $customer->fake_password = $randPassword;
        $customer->status        = !$requireApproval ? Customer::STATUS_ACTIVE : Customer::STATUS_PENDING_ACTIVE;
        $customer->avatar        = $this->fetchCustomerRemoteImage($_user->profile_image_url);

        if ($group = $registration->getDefaultGroup()) {
            $customer->group_id = (int)$group->group_id;
        }

        // finally try to save the customer.
        if (!$customer->save(false)) {
            notify()->addError(t('customers', 'Unable to save your account, please contact us if this error persists!'));
            $this->redirect(['guest/index']);
        }

        // create the email for customer
        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
            'new-login-info',
            [
                'subject' => t('app', 'Your new login info!'),
            ],
            [
                '[LOGIN_EMAIL]'     => $customer->email,
                '[LOGIN_PASSWORD]'  => $randPassword,
                '[LOGIN_URL]'       => createAbsoluteUrl('guest/index'),
            ]
        );

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        $email = new TransactionalEmail();
        $email->sendDirectly = $registration->getSendEmailDirect();
        $email->to_name      = $customer->getFullName();
        $email->to_email     = $customer->email;
        $email->from_name    = $common->getSiteName();
        $email->subject      = $params['subject'];
        $email->body         = $params['body'];
        $email->save();

        // notify admins
        $this->_sendNewCustomerNotifications($customer, new CustomerCompany());

        if ($requireApproval) {
            notify()->addSuccess(t('customers', 'Congratulations, your account has been successfully created.'));
            notify()->addSuccess(t('customers', 'You will be able to login once an administrator will approve it.'));
            $this->redirect(['guest/index']);
        }

        // the customer has been saved, we need to log him in, should work okay...
        $identity = new CustomerIdentity($customer->email, $customer->password);
        $identity->setId($customer->customer_id);
        $identity->setAutoLoginToken($customer);
        customer()->login($identity, 3600 * 24 * 30);
        $this->redirect(['dashboard/index']);
    }

    /**
     * Display country zones
     *
     * @return void
     * @throws CException
     */
    public function actionZones_by_country()
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['guest/index']);
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'zone_id, name';
        $criteria->compare('country_id', (int)request()->getQuery('country_id'));
        $criteria->order = 'name ASC';

        $this->renderJson([
            'zones' => ZoneCollection::findAll($criteria)->map(function (Zone $zone) {
                return [
                    'zone_id'  => $zone->zone_id,
                    'name'     => $zone->name,
                ];
            })->toArray(),
        ]);
    }

    /**
     * Callback for controller_action_save_data action hook
     *
     * @param CAttributeCollection $collection
     *
     * @return void
     */
    public function _checkEmailDomainForbidden(CAttributeCollection $collection)
    {
        if (!$collection->itemAt('success')) {
            return;
        }

        /** @var Customer $model */
        $model = $collection->itemAt('model');

        $email = $model->email;
        if (empty($email) || !FilterVarHelper::email($email)) {
            return;
        }

        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);
        if (!($domains = $registration->getForbiddenDomainsList())) {
            return;
        }

        $emailDomain = explode('@', $email);
        $emailDomain = strtolower((string)$emailDomain[1]);

        foreach ($domains as $domain) {
            if (strpos($emailDomain, $domain) === 0) {
                notify()->addError(t('customers', 'We\'re sorry, but we don\'t accept registrations from {domain}. <br />Please use another email address, preferably not from a free service!', [
                    '{domain}' => $emailDomain,
                ]));
                $collection->add('success', false);
                break;
            }
        }
    }

    /**
     * Called when the application is offline
     *
     * @return void
     * @throws CHttpException
     */
    public function actionOffline()
    {
        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        if ($common->getIsSiteOnline()) {
            $this->redirect(['dashboard/index']);
        }

        throw new CHttpException(503, (string)$common->site_offline_message);
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
                echo html_encode($error['message']);
            } else {
                $this->setData([
                    'pageMetaTitle' => t('app', 'Error {code}!', ['{code}' => (int)$error['code']]),
                ]);
                $this->render('error', $error);
            }
        }
    }

    /**
     * Callback to register Jquery ui bootstrap only for certain actions
     *
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['register'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }

    /**
     * Callback after success registration to send the confirmation email
     *
     * @param Customer $customer
     * @param CustomerCompany $company
     *
     * @return void
     * @throws CException
     */
    protected function _sendRegistrationConfirmationEmail(Customer $customer, CustomerCompany $company)
    {
        /** @var OptionUrl */
        $url = container()->get(OptionUrl::class);

        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
            'customer-confirm-registration',
            [
                'subject' => t('customers', 'Please confirm your account!'),
            ],
            [
                '[CONFIRMATION_URL]' => $url->getCustomerUrl('guest/confirm-registration/' . $customer->confirmation_key),
            ]
        );

        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        $email = new TransactionalEmail();
        $email->sendDirectly = $registration->getSendEmailDirect();
        $email->to_name      = $customer->getFullName();
        $email->to_email     = $customer->email;
        $email->from_name    = $common->getSiteName();
        $email->subject      = $params['subject'];
        $email->body         = $params['body'];
        $email->save();
    }

    /**
     * Callback after success registration to send the notification emails to admin users
     *
     * @param Customer $customer
     * @param CustomerCompany $company
     *
     * @return void
     * @throws CException
     */
    protected function _sendNewCustomerNotifications(Customer $customer, CustomerCompany $company)
    {
        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        if (!($recipients = $registration->getNewCustomersRegistrationNotificationTo())) {
            return;
        }

        $customerInfo = [];
        foreach ($customer->getAttributes(['first_name', 'last_name', 'email']) as $attributeName => $attributeValue) {
            $customerInfo[] = $customer->getAttributeLabel($attributeName) . ': ' . $attributeValue;
        }
        $customerInfo = implode('<br />', $customerInfo);

        /** @var OptionUrl */
        $url = container()->get(OptionUrl::class);

        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
            'new-customer-registration',
            [
                'subject' => t('customers', 'New customer registration!'),
            ],
            [
                '[CUSTOMER_URL]' => $url->getBackendUrl('customers/update/id/' . $customer->customer_id),
                '[CUSTOMER_INFO]'=> $customerInfo,
            ]
        );

        foreach ($recipients as $recipient) {
            if (!FilterVarHelper::email($recipient)) {
                continue;
            }
            $email = new TransactionalEmail();
            $email->sendDirectly = $registration->getSendEmailDirect();
            $email->to_name      = $recipient;
            $email->to_email     = $recipient;
            $email->from_name    = $common->getSiteName();
            $email->subject      = $params['subject'];
            $email->body         = $params['body'];
            $email->save();
        }
    }

    /**
     * @param string $url
     *
     * @return string
     * @throws CException
     */
    protected function fetchCustomerRemoteImage(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        try {
            $response = (string)(new GuzzleHttp\Client())->get($url)->getBody();
        } catch (Exception $e) {
            $response = '';
        }

        if (empty($response)) {
            return '';
        }

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.avatars');
        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        if (!file_exists($storagePath) || !is_dir($storagePath)) {
            return '';
        }

        $tempDir = FileSystemHelper::getTmpDirectory();
        $name    = StringHelper::random(20);

        if (!file_exists($tempDir) || !is_dir($tempDir)) {
            return '';
        }

        if (!file_put_contents($tempDir . '/' . $name, $response)) {
            return '';
        }

        if (($info = ImageHelper::getImageSize($tempDir . '/' . $name)) === false) {
            unlink($tempDir . '/' . $name);
            return '';
        }

        if (empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            unlink($tempDir . '/' . $name);
            return '';
        }

        /** @var FileExtensionMimes $extensionMimes */
        $extensionMimes = app()->getComponent('extensionMimes');

        $mimes = [];
        $mimes['jpg'] = $extensionMimes->get('jpg')->toArray();
        $mimes['png'] = $extensionMimes->get('png')->toArray();
        $mimes['gif'] = $extensionMimes->get('gif')->toArray();

        $extension = null;
        foreach ($mimes as $_extension => $_mimes) {
            if (in_array($info['mime'], $_mimes)) {
                $extension = $_extension;
                break;
            }
        }

        if ($extension === null) {
            unlink($tempDir . '/' . $name);
            return '';
        }

        if (!copy($tempDir . '/' . $name, $storagePath . '/' . $name . '.' . $extension)) {
            unlink($tempDir . '/' . $name);
            return '';
        }

        return '/frontend/assets/files/avatars/' . $name . '.' . $extension;
    }

    /**
     * @param Customer $customer
     *
     * @return void
     * @throws ReflectionException
     * @since 1.3.7
     */
    protected function _subscribeToEmailList(Customer $customer)
    {
        /** @var OptionCustomerRegistration $registration */
        $registration = container()->get(OptionCustomerRegistration::class);

        $apiEnabled     = $registration->getApiEnabled();
        $apiUrl         = (string)$registration->api_url;
        $apiKey         = (string)$registration->api_key;
        $listUids       = (string)$registration->api_list_uid;
        $consentText    = $registration->getApiConsentText();

        if (empty($apiEnabled) || empty($apiUrl) || empty($apiKey) || empty($listUids)) {
            return;
        }

        if (!empty($consentText) && (empty($customer->newsletter_consent) || $consentText != $customer->newsletter_consent)) {
            return;
        }

        \EmsApi\Base::setConfig(new \EmsApi\Config([
            'apiUrl'    => $apiUrl,
            'apiKey'    => $apiKey,
        ]));

        $lists    = CommonHelper::getArrayFromString((string)$listUids, ',');
        $endpoint = new \EmsApi\Endpoint\ListSubscribers();

        foreach ($lists as $list) {
            $endpoint->create($list, [
                'EMAIL'    => $customer->email,
                'FNAME'    => $customer->first_name,
                'LNAME'    => $customer->last_name,
                'CONSENT'  => $customer->newsletter_consent,
                'details'  => [
                    'ip_address' => (string)request()->getUserHostAddress(),
                    'user_agent' => StringHelper::truncateLength((string)request()->getUserAgent(), 255),
                ],
            ]);
        }
    }
}
