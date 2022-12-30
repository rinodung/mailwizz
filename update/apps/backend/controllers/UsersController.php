<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UsersController
 *
 * Handles the actions for users related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class UsersController extends Controller
{
    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete', // we only allow deletion via POST request
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available users
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $user = new User('search');
        $user->unsetAttributes();

        // for filters.
        $user->attributes = (array)request()->getQuery($user->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('users', 'View users'),
            'pageHeading'     => t('users', 'View users'),
            'pageBreadcrumbs' => [
                t('users', 'Users') => createUrl('users/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('user'));
    }

    /**
     * Create a new user
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $user = new User();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($user->getModelName(), []))) {
            $user->attributes = $attributes;
            if (!$user->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'user'       => $user,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['users/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('users', 'Create new user'),
            'pageHeading'     => t('users', 'Create new user'),
            'pageBreadcrumbs' => [
                t('users', 'Users') => createUrl('users/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('user'));
    }

    /**
     * Update existing user
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $user = User::model()->findByPk((int)$id);

        if (empty($user)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ((int)$user->user_id === (int)user()->getId()) {
            $this->redirect(['account/index']);
        }

        if (!$user->getIsRemovable() && (int)$user->user_id !== (int)user()->getId()) {
            notify()->addWarning(t('users', 'You are not allowed to update the master administrator!'));
            $this->redirect(['users/index']);
        }

        $user->confirm_email = $user->email;

        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($user->getModelName(), []))) {
            $user->attributes = $attributes;
            if (!$user->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller' => $this,
                'success'    => notify()->getHasSuccess(),
                'user'       => $user,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['users/update', 'id' => $user->user_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('users', 'Update user'),
            'pageHeading'     => t('users', 'Update user'),
            'pageBreadcrumbs' => [
                t('users', 'Users') => createUrl('users/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('user', 'twoFaSettings'));
    }

    /**
     * 2FA for existing user
     *
     * @param int $id
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function action2fa($id)
    {
        /** @var OptionTwoFactorAuth $twoFaSettings */
        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

        /** @var UserForTwoFactorAuth|null $user */
        $user = UserForTwoFactorAuth::model()->findByPk((int)$id);

        if (empty($user)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        // make sure 2FA is enabled
        if (!$twoFaSettings->getIsEnabled()) {
            notify()->addWarning(t('app', '2FA is not enabled in this system!'));
            $this->redirect(['update', 'id' => $user->user_id]);
        }

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
                'customer'  => $user,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['users/2fa', 'id' => $user->user_id]);
            }
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
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('users', 'Update user'),
            'pageHeading'     => t('users', 'Update user'),
            'pageBreadcrumbs' => [
                t('users', 'Users') => createUrl('users/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('2fa', compact('user', 'qrCodeUri'));
    }

    /**
     * Delete existing user
     *
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($id)
    {
        $user = User::model()->findByPk((int)$id);

        if (empty($user)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($user->getIsRemovable()) {
            $user->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['users/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $user,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }
}
