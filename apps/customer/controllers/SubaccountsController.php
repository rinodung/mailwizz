<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SubaccountsController
 *
 * Handles the actions for subaccounts related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class SubaccountsController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        // subaccounts are not allowed here
        if (is_subaccount()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if (
            $customer->getGroupOption('subaccounts.enabled', 'no') != 'yes' ||
            (int)$customer->getGroupOption('subaccounts.max_subaccounts', -1) === 0
        ) {
            $this->redirect(['dashboard/index']);
            return;
        }

        /** @var CWebApplication $app */
        $app = app();

        $this->addPageStyle(['src' => apps()->getBaseUrl('assets/js/datetimepicker/css/bootstrap-datetimepicker.min.css')]);
        $this->addPageScript(['src' => apps()->getBaseUrl('assets/js/datetimepicker/js/bootstrap-datetimepicker.min.js')]);

        $languageCode = LanguageHelper::getAppLanguageCode();

        $languageFile = apps()->getBaseUrl('assets/js/datetimepicker/js/locales/bootstrap-datetimepicker.' . $languageCode . '.js');
        if ($app->getLanguage() != $app->sourceLanguage && is_file($languageFile)) {
            $this->addPageScript(['src' => $languageFile]);
        }

        $this->onBeforeAction = [$this, '_registerJuiBs'];
        $this->addPageScript(['src' => AssetsUrl::js('subaccounts.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available subaccounts
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $customer = new Customer('search-subaccounts');
        $customer->unsetAttributes();

        $customer->attributes = (array)request()->getQuery($customer->getModelName(), []);
        $customer->parent_id  = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'View subaccounts'),
            'pageHeading'     => t('customers', 'View subaccounts'),
            'pageBreadcrumbs' => [
                t('customers', 'Subaccounts') => createUrl('subaccounts/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('customer'));
    }

    /**
     * Create a new subaccount
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        /** @var Customer $customer */
        $customer = customer()->getModel();

        /** @var int $customerSubaccountsCount */
        $customerSubaccountsCount = (int)Customer::model()->countByAttributes([
            'parent_id' => (int)customer()->getId(),
        ]);

        /** @var int $maxSubaccounts */
        $maxSubaccounts = (int)$customer->getGroupOption('subaccounts.max_subaccounts', -1);

        if ($maxSubaccounts > 0 && $customerSubaccountsCount >= $maxSubaccounts) {
            notify()->addWarning(t('subaccounts', 'You have reached the maximum number of subaccounts you can create!'));
            $this->redirect(['subaccounts/index']);
            return;
        }

        $customer = new Customer('insert-subaccount');
        $customer->parent_id = (int)customer()->getId();
        $customer->onAfterSave = [$this, '_sendEmailDetails'];

        $permissions = [
            'listsPermissions'          => container()->get(OptionCustomerSubaccountPermissionsLists::class),
            'campaignsPermissions'      => container()->get(OptionCustomerSubaccountPermissionsCampaigns::class),
            'serversPermissions'        => container()->get(OptionCustomerSubaccountPermissionsServers::class),
            'surveysPermissions'        => container()->get(OptionCustomerSubaccountPermissionsSurveys::class),
            'apiKeysPermissions'        => container()->get(OptionCustomerSubaccountPermissionsApiKeys::class),
            'domainsPermissions'        => container()->get(OptionCustomerSubaccountPermissionsDomains::class),
            'emailTemplatesPermissions' => container()->get(OptionCustomerSubaccountPermissionsEmailTemplates::class),
            'blacklistsPermissions'     => container()->get(OptionCustomerSubaccountPermissionsBlacklists::class),
        ];
        $permissions = collect($permissions)->each(function (OptionCustomerSubaccountPermissions $model) use ($customer) {
            $model->setCustomer($customer);
        })->filter(function (OptionCustomerSubaccountPermissions $model) {
            /** @var Customer $customer */
            $customer = customer()->getModel();
            return $model->getParentCustomerIsAllowedAccess($customer);
        })->all();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($customer->getModelName(), []))) {
            $customer->attributes = $attributes;
            $customer->parent_id  = (int)customer()->getId();

            if (!$customer->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            collect($permissions)->filter(function (?OptionCustomerSubaccountPermissions $model = null) {
                return !empty($model);
            })->each(function (OptionCustomerSubaccountPermissions $model) use ($customer) {
                $model->attributes = (array)request()->getPost($model->getModelName(), []);
                $model->setCustomer($customer, false);
            });

            if (!$customer->hasErrors()) {
                collect($permissions)->filter(function (?OptionCustomerSubaccountPermissions $model = null) {
                    return !empty($model);
                })->each(function (OptionCustomerSubaccountPermissions $model) {
                    $model->save();
                });
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'customer'  => $customer,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['subaccounts/index']);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Create new subaccount'),
            'pageHeading'     => t('customers', 'Create new subaccount'),
            'pageBreadcrumbs' => [
                t('customers', 'Subaccounts') => createUrl('subaccounts/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->setData($permissions);

        $this->render('form', compact('customer'));
    }

    /**
     * Update existing subaccount
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $customer = Customer::model()->findByAttributes([
            'customer_id' => (int)$id,
            'parent_id'   => (int)customer()->getId(),
        ]);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $customer->setScenario('update-subaccount');
        $customer->confirm_email = $customer->email;
        $customer->onAfterSave = [$this, '_sendEmailDetails'];

        $permissions = [
            'listsPermissions'          => container()->get(OptionCustomerSubaccountPermissionsLists::class),
            'campaignsPermissions'      => container()->get(OptionCustomerSubaccountPermissionsCampaigns::class),
            'serversPermissions'        => container()->get(OptionCustomerSubaccountPermissionsServers::class),
            'surveysPermissions'        => container()->get(OptionCustomerSubaccountPermissionsSurveys::class),
            'apiKeysPermissions'        => container()->get(OptionCustomerSubaccountPermissionsApiKeys::class),
            'domainsPermissions'        => container()->get(OptionCustomerSubaccountPermissionsDomains::class),
            'emailTemplatesPermissions' => container()->get(OptionCustomerSubaccountPermissionsEmailTemplates::class),
            'blacklistsPermissions'     => container()->get(OptionCustomerSubaccountPermissionsBlacklists::class),
        ];
        $permissions = collect($permissions)->each(function (OptionCustomerSubaccountPermissions $model) use ($customer) {
            $model->setCustomer($customer);
        })->filter(function (OptionCustomerSubaccountPermissions $model) {
            /** @var Customer $customer */
            $customer = customer()->getModel();
            return $model->getParentCustomerIsAllowedAccess($customer);
        })->all();

        /** @var OptionTwoFactorAuth $twoFaSettings */
        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($customer->getModelName(), []))) {
            $customer->attributes = $attributes;
            $customer->parent_id  = (int)customer()->getId();

            if (!$customer->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            collect($permissions)->filter(function (?OptionCustomerSubaccountPermissions $model = null) {
                return !empty($model);
            })->each(function (OptionCustomerSubaccountPermissions $model) use ($customer) {
                $model->attributes = (array)request()->getPost($model->getModelName(), []);
                $model->setCustomer($customer, false);
            });

            if (!$customer->hasErrors()) {
                collect($permissions)->filter(function (?OptionCustomerSubaccountPermissions $model = null) {
                    return !empty($model);
                })->each(function (OptionCustomerSubaccountPermissions $model) {
                    $model->save();
                });
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'customer'  => $customer,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['subaccounts/update', 'id' => $customer->customer_id]);
                return;
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Update subaccount'),
            'pageHeading'     => t('customers', 'Update subaccount'),
            'pageBreadcrumbs' => [
                t('customers', 'Subaccounts') => createUrl('subaccounts/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->setData($permissions);

        $this->render('form', compact('customer', 'twoFaSettings'));
    }

    /**
     * 2FA for existing subaccount
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function action2fa($id)
    {
        /** @var OptionTwoFactorAuth $twoFaSettings */
        $twoFaSettings  = container()->get(OptionTwoFactorAuth::class);

        /** @var CustomerForTwoFactorAuth|null $customer */
        $customer = CustomerForTwoFactorAuth::model()->findByAttributes([
            'customer_id' => (int)$id,
            'parent_id'   => (int)customer()->getId(),
        ]);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        // make sure 2FA is enabled
        if (!$twoFaSettings->getIsEnabled()) {
            notify()->addWarning(t('app', '2FA is not enabled in this system!'));
            $this->redirect(['update', 'id' => $customer->customer_id]);
            return;
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($customer->getModelName(), []))) {
            $customer->attributes = $attributes;
            $customer->parent_id  = (int)customer()->getId();

            if (!$customer->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'customer'  => $customer,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['subaccounts/2fa', 'id' => $customer->customer_id]);
                return;
            }
        }

        // make sure we have the secret
        if (empty($customer->twofa_secret)) {
            $manager = new Da\TwoFA\Manager();
            $customer->twofa_secret = (string)$manager->generateSecretKey(64);
            $customer->save(false);
        }

        // we need to create our time-based one time password secret uri
        $company   = $twoFaSettings->companyName . ' / Customer';
        $totp      = new Da\TwoFA\Service\TOTPSecretKeyUriGeneratorService($company, $customer->email, $customer->twofa_secret);
        $qrCode    = new Da\TwoFA\Service\QrCodeDataUriGeneratorService($totp->run());
        $qrCodeUri = $qrCode->run();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Update subaccount'),
            'pageHeading'     => t('customers', 'Update subaccount'),
            'pageBreadcrumbs' => [
                t('customers', 'Subaccounts') => createUrl('subaccounts/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('2fa', compact('customer', 'qrCodeUri'));
    }

    /**
     * Delete existing subaccount
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
        /** @var Customer|null $customer */
        $customer = Customer::model()->findByAttributes([
            'customer_id' => (int)$id,
            'parent_id'   => (int)customer()->getId(),
        ]);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($customer->getIsRemovable()) {
            $customer->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['subaccounts/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $customer,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     *  Autocomplete for search
     *
     * @param string $term
     *
     * @return void
     * @throws CException
     */
    public function actionAutocomplete($term)
    {
        if (!request()->getIsAjaxRequest()) {
            $this->redirect(['subaccounts/index']);
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'customer_id, first_name, last_name, email';
        $criteria->compare((string)(new CDbExpression('CONCAT(first_name, " ", last_name)')), $term, true);
        $criteria->compare('email', $term, true, 'OR');
        $criteria->compare('parent_id', (int)customer()->getId());
        $criteria->limit = 10;

        $this->renderJson(CustomerCollection::findAll($criteria)->map(function (Customer $model) {
            return [
                'customer_id' => $model->customer_id,
                'value'       => $model->getFullName(),
            ];
        })->all());
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _sendEmailDetails(CEvent $event)
    {
        /** @var Customer $customer */
        $customer = $event->sender;

        if ($customer->email_details != Customer::TEXT_YES || empty($customer->fake_password)) {
            return;
        }

        $params  = CommonEmailTemplate::getAsParamsArrayBySlug(
            'account-details',
            [
                'subject' => t('customers', 'Your account details!'),
            ],
            [
                '[LOGIN_URL]'       => apps()->getAppUrl('customer', 'guest/index', true),
                '[LOGIN_EMAIL]'     => $customer->email,
                '[LOGIN_PASSWORD]'  => $customer->fake_password,
            ]
        );

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        $email = new TransactionalEmail();
        $email->customer_id = (int)$customer->customer_id;
        $email->to_name     = $customer->getFullName();
        $email->to_email    = $customer->email;
        $email->from_name   = $common->getSiteName();
        $email->subject     = $params['subject'];
        $email->body        = $params['body'];
        $email->save();

        notify()->addSuccess(t('customers', 'The account details have been sent to the customer email address!'));
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['index', 'create', 'update'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
