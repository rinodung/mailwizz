<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AccountController
 *
 * Handles the actions for account related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class AccountController extends Controller
{
    /**
     * Default action, allowing to update the account
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        /** @var User $user */
        $user = user()->getModel();
        $user->confirm_email = $user->email;

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($user->getModelName(), []))) {
            $user->attributes = $attributes;
            if (!$user->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'user'      => $user,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['account/index']);
            }
        }

        /** @var OptionTwoFactorAuth $twoFaSettings */
        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('users', 'Update account'),
            'pageHeading'     => t('users', 'Update account'),
            'pageBreadcrumbs' => [
                t('users', 'Users') => createUrl('users/index'),
                t('users', 'Update account'),
            ],
        ]);

        $this->render('index', compact('user', 'twoFaSettings'));
    }

    /**
     * Update the account 2fa settings
     *
     * @return void
     * @throws CException
     */
    public function action2fa()
    {
        /** @var OptionTwoFactorAuth $twoFaSettings */
        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

        // make sure 2FA is enabled
        if (!$twoFaSettings->getIsEnabled()) {
            notify()->addWarning(t('app', '2FA is not enabled in this system!'));
            $this->redirect(['index']);
        }

        /** @var UserForTwoFactorAuth */
        $user = UserForTwoFactorAuth::model()->findByPk((int)user()->getId());

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($user->getModelName()))) {
            $user->attributes = $attributes;

            if ($user->save()) {
                notify()->addSuccess(t('users', 'User info successfully updated!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'user'      => $user,
            ]));
        }

        // make sure we have the secret
        if (empty($user->twofa_secret)) {
            $manager = new Da\TwoFA\Manager();
            $user->twofa_secret = (string)$manager->generateSecretKey(64);
            $user->save(false);
        }

        // we need to create our time-based one time password secret uri
        $company   = $twoFaSettings->companyName . ' / Backend';
        $totp      = new Da\TwoFA\Service\TOTPSecretKeyUriGeneratorService($company, $user->email, $user->twofa_secret);
        $qrCode    = new Da\TwoFA\Service\QrCodeDataUriGeneratorService($totp->run());
        $qrCodeUri = $qrCode->run();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('users', '2FA'),
            'pageHeading'     => t('users', '2FA'),
            'pageBreadcrumbs' => [
                t('customers', 'Account') => createUrl('account/index'),
                t('customers', '2FA') => createUrl('account/2fa'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('2fa', compact('user', 'qrCodeUri'));
    }

    /**
     * Log the user out from the application
     *
     * @return void
     */
    public function actionLogout()
    {
        user()->logout();
        $this->redirect(user()->loginUrl);
    }

    /**
     * Save the grid view columns for this user
     *
     * @return void
     * @throws CException
     */
    public function actionSave_grid_view_columns()
    {
        $model      = request()->getPost('model');
        $controller = request()->getPost('controller');
        $action     = request()->getPost('action');
        $columns    = request()->getPost('columns', []);

        if (!($redirect = request()->getServer('HTTP_REFERER'))) {
            $redirect = ['dashboard/index'];
        }

        if (!request()->getIsPostRequest()) {
            $this->redirect($redirect);
        }

        if (empty($model) || empty($controller) || empty($action) || empty($columns) || !is_array($columns)) {
            $this->redirect($redirect);
        }

        $optionKey = sprintf('%s:%s:%s', (string)$model, (string)$controller, (string)$action);
        $userId    = (int)user()->getId();
        $optionKey = sprintf('system.views.grid_view_columns.users.%d.%s', $userId, $optionKey);
        options()->set($optionKey, (array)$columns);

        notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
        $this->redirect($redirect);
    }
}
