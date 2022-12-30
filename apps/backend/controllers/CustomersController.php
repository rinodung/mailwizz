<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomersController
 *
 * Handles the actions for customers related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CustomersController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
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
        $this->addPageScript(['src' => AssetsUrl::js('customers.js')]);
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, reset_sending_quota',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available customers
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $customer = new Customer('search');
        $customer->unsetAttributes();

        $customer->attributes = (array)request()->getQuery($customer->getModelName(), []);

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'View customers'),
            'pageHeading'     => t('customers', 'View customers'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('customer'));
    }

    /**
     * Create a new customer
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $customer = new Customer();
        $customer->onAfterSave = [$this, '_sendEmailDetails'];

        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($customer->getModelName(), []))) {
            $customer->attributes = $attributes;
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
                $this->redirect(['customers/index']);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Create new user'),
            'pageHeading'     => t('customers', 'Create new customer'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('app', 'Create new'),
            ],
        ]);

        $this->render('form', compact('customer', 'twoFaSettings'));
    }

    /**
     * Update existing customer
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $customer = Customer::model()->findByPk((int)$id);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $customer->confirm_email = $customer->email;

        $this->setData('initCustomerStatus', $customer->status);
        $customer->onAfterSave = [$this, '_sendEmailNotification'];
        $customer->onAfterSave = [$this, '_sendEmailDetails'];

        $twoFaSettings = container()->get(OptionTwoFactorAuth::class);

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($customer->getModelName(), []))) {
            $customer->attributes = $attributes;
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
                $this->redirect(['customers/update', 'id' => $customer->customer_id]);
            }
        }

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Update customer'),
            'pageHeading'     => t('customers', 'Update customer'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('customer', 'twoFaSettings'));
    }

    /**
     * 2FA for existing customer
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
        $customer = CustomerForTwoFactorAuth::model()->findByPk((int)$id);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        // make sure 2FA is enabled
        if (!$twoFaSettings->getIsEnabled()) {
            notify()->addWarning(t('app', '2FA is not enabled in this system!'));
            $this->redirect(['update', 'id' => $customer->customer_id]);
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($customer->getModelName(), []))) {
            $customer->attributes = $attributes;
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
                $this->redirect(['customers/2fa', 'id' => $customer->customer_id]);
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
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('customers', 'Update customer'),
            'pageHeading'     => t('customers', 'Update customer'),
            'pageBreadcrumbs' => [
                t('customers', 'Customers') => createUrl('customers/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('2fa', compact('customer', 'qrCodeUri'));
    }

    /**
     * Export all known data about an existing customer
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionExport($id)
    {
        $customer = Customer::model()->findByPk((int)$id);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        notify()->addSuccess(t('customers', 'The request has been registered!'));
        $this->redirect(['index']);
    }

    /**
     * Delete existing customer
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
        $customer = Customer::model()->findByPk((int)$id);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if ($customer->getIsRemovable()) {
            $customer->delete();
        }

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['customers/index']);
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
     * Impersonate (login as) this customer
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionImpersonate($id)
    {
        /** @var Customer|null $customer */
        $customer = Customer::model()->findByPk((int)$id);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        Yii::import('customer.components.web.auth.*');
        $identity = new CustomerIdentity($customer->email, '');
        $identity->impersonate = true;

        if (!$identity->authenticate() || !customer()->login($identity)) {
            notify()->addError(t('app', 'Unable to impersonate the customer!'));
            $this->redirect(['customers/index']);
        }

        customer()->setState('__customer_impersonate', true);
        notify()->clearAll()->addSuccess(t('app', 'You are using the customer account for {customerName}!', [
            '{customerName}' => $customer->getFullName(),
        ]));

        // since 1.7.6
        $redirectUrl = apps()->getAppUrl('customer', 'dashboard/index', true);
        $redirectUrl = hooks()->applyFilters('redirect_url_after_impersonate_customer', $redirectUrl, $customer);
        //

        // since 2.1.4
        customer()->setState('__customer_impersonate_return_url', null);
        $forceRedirectUrl = request()->getQuery('redirectUrl', '');
        if (!empty($forceRedirectUrl) && is_string($forceRedirectUrl)) {
            $forceRedirectUrl = urldecode($forceRedirectUrl);
            if (UrlHelper::belongsToCustomerApp($forceRedirectUrl)) {
                $redirectUrl = $forceRedirectUrl;

                $returnUrl = request()->getQuery('returnUrl', '');
                if (!empty($returnUrl) && is_string($returnUrl)) {
                    $returnUrl = urldecode($returnUrl);
                    if (UrlHelper::belongsToBackendApp($returnUrl)) {
                        customer()->setState('__customer_impersonate_return_url', $returnUrl);
                    }
                }
            }
        }
        //

        $this->redirect($redirectUrl);
    }

    /**
     * Reset sending quota
     *
     * @param int $id
     *
     * @return void
     * @throws CHttpException
     */
    public function actionReset_sending_quota($id)
    {
        $customer = Customer::model()->findByPk((int)$id);

        if (empty($customer)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $customer->resetSendingQuota();

        notify()->addSuccess(t('customers', 'The sending quota has been successfully reseted!'));

        if (!request()->getIsAjaxRequest()) {
            $this->redirect(request()->getPost('returnUrl', ['customers/index']));
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
            $this->redirect(['customers/index']);
        }

        $criteria = new CDbCriteria();
        $criteria->select = 'customer_id, first_name, last_name, email';
        $criteria->compare((string)(new CDbExpression('CONCAT(first_name, " ", last_name)')), $term, true);
        $criteria->compare('email', $term, true, 'OR');
        $criteria->limit = 10;

        if (request()->getQuery('for-parent')) {
            $criteria->addCondition('parent_id IS NULL');
        }

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
    public function _sendEmailNotification(CEvent $event)
    {
        if ($this->getData('initCustomerStatus') != Customer::STATUS_PENDING_ACTIVE) {
            return;
        }

        $customer = $event->sender;
        if ($customer->status != Customer::STATUS_ACTIVE) {
            return;
        }

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        /** @var OptionCustomerRegistration */
        $registration = container()->get(OptionCustomerRegistration::class);

        $params  = CommonEmailTemplate::getAsParamsArrayBySlug(
            'account-approved',
            [
                'subject' => t('customers', 'Your account has been approved!'),
            ],
            [
                '[LOGIN_URL]' => apps()->getAppUrl('customer', 'guest/index', true),
            ]
        );

        $email = new TransactionalEmail();
        $email->sendDirectly = $registration->getSendEmailDirect();
        $email->to_name      = $customer->getFullName();
        $email->to_email     = $customer->email;
        $email->from_name    = $common->getSiteName();
        $email->subject      = $params['subject'];
        $email->body         = $params['body'];
        $email->save();

        // send welcome email if needed
        $sendWelcome        = $registration->getSendWelcomeEmail();
        $sendWelcomeSubject = $registration->getWelcomeEmailSubject();
        $sendWelcomeContent = $registration->getWelcomeEmailContent();
        if (!empty($sendWelcome) && !empty($sendWelcomeSubject) && !empty($sendWelcomeContent)) {
            $searchReplace = [
                '[FIRST_NAME]' => $customer->first_name,
                '[LAST_NAME]'  => $customer->last_name,
                '[FULL_NAME]'  => $customer->getFullName(),
                '[EMAIL]'      => $customer->email,
            ];

            $sendWelcomeSubject = str_replace(array_keys($searchReplace), array_values($searchReplace), $sendWelcomeSubject);
            $sendWelcomeContent = str_replace(array_keys($searchReplace), array_values($searchReplace), $sendWelcomeContent);

            /** @var OptionEmailTemplate $optionEmailTemplate */
            $optionEmailTemplate = container()->get(OptionEmailTemplate::class);

            /** @var OptionCommon $optionCommon */
            $optionCommon = container()->get(OptionCommon::class);

            $searchReplace = [
                '[SITE_NAME]'       => $optionCommon->getSiteName(),
                '[SITE_TAGLINE]'    => $optionCommon->getSiteTagline(),
                '[CURRENT_YEAR]'    => date('Y'),
                '[CONTENT]'         => $sendWelcomeContent,
            ];
            $emailTemplate = (string)str_replace(array_keys($searchReplace), array_values($searchReplace), $optionEmailTemplate->common);

            $email = new TransactionalEmail();
            $email->sendDirectly = $registration->getSendEmailDirect();
            $email->to_name      = $customer->getFullName();
            $email->to_email     = $customer->email;
            $email->from_name    = $common->getSiteName();
            $email->subject      = $sendWelcomeSubject;
            $email->body         = $emailTemplate;
            $email->save();
        }

        notify()->addSuccess(t('customers', 'A notification email has been sent for this customer!'));
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
