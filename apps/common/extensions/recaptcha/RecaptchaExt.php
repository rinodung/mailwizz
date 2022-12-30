<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * RecaptchaExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class RecaptchaExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Recaptcha';

    /**
     * @var string
     */
    public $description = 'Protect the public forms using Google\'s Recaptcha';

    /**
     * @var string
     */
    public $version = '2.0.0';

    /**
     * @var string
     */
    public $minAppVersion = '2.0.0';

    /**
     * @var string
     */
    public $author = 'MailWizz Development Team';

    /**
     * @var string
     */
    public $website = 'https://www.mailwizz.com/';

    /**
     * @var string
     */
    public $email = 'support@mailwizz.com';

    /**
     * @var array
     */
    public $allowedApps = ['backend', 'frontend', 'customer'];

    /**
     * @var bool
     */
    protected $_canBeDeleted = false;

    /**
     * @var bool
     */
    protected $_canBeDisabled = true;

    /**
     * @inheritDoc
     */
    public function run()
    {
        $this->importClasses('common.models.*');

        // register the common models in container for singleton access
        container()->add(RecaptchaExtCommon::class, RecaptchaExtCommon::class);
        container()->add(RecaptchaExtDomainsKeysPair::class, RecaptchaExtDomainsKeysPair::class);
        container()->add(RecaptchaExtListForm::class, RecaptchaExtListForm::class);

        if ($this->isAppName('backend')) {
            $this->addUrlRules([
                ['settings/index', 'pattern' => 'extensions/recaptcha/settings'],
                ['settings/<action>', 'pattern' => 'extensions/recaptcha/settings/*'],
            ]);

            $this->addControllerMap([
                'settings' => [
                    'class' => 'backend.controllers.RecaptchaExtBackendSettingsController',
                ],
            ]);
        }

        /** @var RecaptchaExtCommon $model */
        $model = $this->getCommonModel();

        if (!$model->getIsEnabled() || strlen($model->getCurrentDomainSiteKey()) < 20 || strlen($model->getCurrentDomainSecretKey()) < 20) {
            return;
        }

        if ($this->isAppName('frontend') && $model->getIsEnabledForListForms()) {
            hooks()->addAction('frontend_list_subscribe_at_transaction_start', [$this, '_listFormCheckSubmission']);
            hooks()->addFilter('frontend_list_subscribe_before_transform_list_fields', [$this, '_listFormAppendHtml']);

            hooks()->addAction('frontend_list_update_profile_at_transaction_start', [$this, '_listFormCheckSubmission']);
            hooks()->addFilter('frontend_list_update_profile_before_transform_list_fields', [$this, '_listFormAppendHtml']);
        }

        if ($this->isAppName('frontend') && $model->getIsEnabledForBlockEmailForm()) {
            $appName = apps()->getCurrentAppName();
            hooks()->addAction($appName . '_controller_lists_before_action', [$this, '_listsBlockAddressAction']);
        }

        if ($this->isAppName('customer') || $this->isAppName('backend')) {
            $appName = apps()->getCurrentAppName();
            hooks()->addAction($appName . '_controller_guest_before_action', [$this, '_guestActions']);
        }

        if ($this->isAppName('customer') && $model->getIsEnabledForListForms()) {
            hooks()->addAction('after_active_form_fields', [$this, '_customerListAfterActiveFormFields']);
            hooks()->addAction('controller_action_save_data', [$this, '_customerListControllerActionSaveData']);
        }
    }

    /**
     * @inheritDoc
     */
    public function getPageUrl()
    {
        return $this->createUrl('settings/index');
    }

    /**
     * Callback to respond to the action hook: frontend_list_subscribe_at_transaction_start
     * this is inside a try/catch block so we have to throw an exception on failure.
     *
     * @return void
     * @throws CException
     */
    public function _listFormCheckSubmission()
    {
        if (!$this->getListFormModel()->getIsEnabled()) {
            return;
        }

        /** @var RecaptchaExtCommon $model */
        $model = $this->getCommonModel();

        try {
            $response = (new GuzzleHttp\Client())->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret'   => $model->getCurrentDomainSecretKey(),
                    'response' => request()->getPost('g-recaptcha-response'),
                    'remoteip' => request()->getUserHostAddress(),
                ],
            ]);

            $response = (array)json_decode((string)$response->getBody(), true);
        } catch (Exception $e) {
            $response = [];
        }

        if (empty($response['success'])) {
            throw new Exception(t('lists', 'Invalid captcha response!'));
        }
    }

    /**
     * Callback to respond to the filter hook: frontend_list_subscribe_before_transform_list_fields
     *
     * @param string $content
     *
     * @return string
     * @throws CException
     */
    public function _listFormAppendHtml(string $content): string
    {
        if (!$this->getListFormModel()->getIsEnabled()) {
            return $content;
        }

        /** @var RecaptchaExtCommon $model */
        $model = $this->getCommonModel();

        $append  = sprintf('<script src="%s"></script>', 'https://www.google.com/recaptcha/api.js');
        $append .= sprintf('<div class="g-recaptcha pull-left" data-sitekey="%s"></div>', $model->getCurrentDomainSiteKey());
        $append .= '<div class="clearfix"><!-- --></div>';

        return (string)preg_replace('/\[LIST_FIELDS\]/', "[LIST_FIELDS]\n" . $append, $content, 1, $count);
    }

    /**
     * @param CAction $action
     *
     * @return void
     */
    public function _listsBlockAddressAction(CAction $action)
    {
        if (!in_array($action->getId(), ['block_address'])) {
            return;
        }

        /** @var RecaptchaExtCommon $model */
        $model = $this->getCommonModel();

        /** @var bool $canShow */
        $canShow = $model->getIsEnabledForBlockEmailForm();

        if (!$canShow) {
            return;
        }

        /** @var Controller $controller */
        $controller = $action->getController();

        /** @var CList $pageScripts */
        $pageScripts = $controller->getData('pageScripts');
        $pageScripts->add(['src' => 'https://www.google.com/recaptcha/api.js']);

        hooks()->addAction('controller_action_before_save_data', [$this, '_listsBlockAddressProcessForm']);
        hooks()->addAction('after_active_form_fields', [$this, '_listsBlockAddressFormAppendHtml']);
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     */
    public function _listsBlockAddressProcessForm(CAttributeCollection $collection)
    {
        $response = $this->getRecaptchaResponse();
        if (empty($response['success'])) {
            $collection->add('success', false);
            notify()->clearAll();
            notify()->addError(t('lists', 'Invalid captcha response!'));
            return;
        }
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     */
    public function _listsBlockAddressFormAppendHtml(CAttributeCollection $collection)
    {
        /** @var RecaptchaExtCommon $model */
        $model = $this->getCommonModel();

        $append  = sprintf('<div class="row"><hr /><div class="col-lg-12"><div class="pull-right g-recaptcha" data-sitekey="%s"></div></div></div>', $model->getCurrentDomainSiteKey());
        $append .= '<div class="clearfix"><!-- --></div>';
        echo $append;
    }

    /**
     * @param CAction $action
     *
     * @return void
     */
    public function _guestActions(CAction $action)
    {
        if (!in_array($action->getId(), ['index', 'register', 'forgot_password'])) {
            return;
        }

        /** @var RecaptchaExtCommon $model */
        $model = $this->getCommonModel();

        /** @var bool $canShow */
        $canShow = $model->getIsEnabledForRegistration() ||
                   $model->getIsEnabledForLogin() ||
                   $model->getIsEnabledForForgot();

        if (!$canShow) {
            return;
        }

        /** @var Controller $controller */
        $controller = $action->getController();

        /** @var CList $pageScripts */
        $pageScripts = $controller->getData('pageScripts');
        $pageScripts->add(['src' => 'https://www.google.com/recaptcha/api.js']);

        if ($model->getIsEnabledForRegistration() && $action->getId() == 'register') {
            hooks()->addAction('controller_action_save_data', [$this, '_guestProcessForm']);
            hooks()->addAction('after_active_form_fields', [$this, '_guestFormAppendHtml']);
        } elseif ($model->getIsEnabledForLogin() && $action->getId() == 'index') {
            hooks()->addAction('controller_action_save_data', [$this, '_guestProcessForm']);
            hooks()->addAction('after_active_form_fields', [$this, '_guestFormAppendHtml']);
        } elseif ($model->getIsEnabledForForgot() && $action->getId() == 'forgot_password') {
            hooks()->addAction('controller_action_save_data', [$this, '_guestProcessForm']);
            hooks()->addAction('after_active_form_fields', [$this, '_guestFormAppendHtml']);
        }
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     */
    public function _guestProcessForm(CAttributeCollection $collection)
    {
        $response = $this->getRecaptchaResponse();
        if (empty($response['success'])) {

            // in case they did not fill the captcha but entered correct login details
            if ($this->isAppName('backend') && !user()->getIsGuest()) {
                user()->logout();
            } elseif ($this->isAppName('customer') && !customer()->getIsGuest()) {
                customer()->logout();
            }

            $collection->add('success', false);
            notify()->addError(t('lists', 'Invalid captcha response!'));
            return;
        }
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     */
    public function _guestFormAppendHtml(CAttributeCollection $collection)
    {
        /** @var RecaptchaExtCommon $model */
        $model = $this->getCommonModel();

        $append  = sprintf('<div class="row"><hr /><div class="col-lg-12 g-recaptcha" data-sitekey="%s"></div></div>', $model->getCurrentDomainSiteKey());
        $append .= '<div class="clearfix"><!-- --></div>';
        echo $append;
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     * @throws CException
     */
    public function _customerListAfterActiveFormFields(CAttributeCollection $collection)
    {
        if (app()->getController()->getId() != 'lists') {
            return;
        }

        /** @var Controller $controller */
        $controller = $collection->itemAt('controller');

        $controller->renderFile($this->getPathOfAlias('customer.views.lists') . '/_form.php', [
            'model' => $this->getListFormModel(),
            'form'  => new CActiveForm(),
        ]);
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     * @throws CException
     */
    public function _customerListControllerActionSaveData(CAttributeCollection $collection)
    {
        if (app()->getController()->getId() != 'lists' || !$collection->itemAt('success')) {
            return;
        }

        $model = $this->getListFormModel();
        $model->attributes = (array)request()->getPost($model->getModelName(), []);

        if (!$model->save()) {
            $collection->add('success', false);
        }
    }

    /**
     * @return array
     */
    protected function getRecaptchaResponse(): array
    {
        /** @var RecaptchaExtCommon $model */
        $model = $this->getCommonModel();

        try {
            $response = (new GuzzleHttp\Client())->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret'   => $model->getCurrentDomainSecretKey(),
                    'response' => request()->getPost('g-recaptcha-response'),
                    'remoteip' => request()->getUserHostAddress(),
                ],
            ]);
            $response = json_decode((string)$response->getBody(), true);
        } catch (Exception $e) {
            $response = [];
        }

        return (array)$response;
    }

    /**
     * @return RecaptchaExtListForm
     * @throws CException
     */
    protected function getListFormModel(): RecaptchaExtListForm
    {
        /** @var RecaptchaExtListForm $model */
        $model = container()->get(RecaptchaExtListForm::class);
        $model->list_uid = (string)request()->getQuery('list_uid', '');
        $model->refresh();

        return $model;
    }

    /**
     * @return RecaptchaExtCommon
     */
    protected function getCommonModel(): RecaptchaExtCommon
    {
        /** @var RecaptchaExtCommon $model */
        $model = container()->get(RecaptchaExtCommon::class);

        /** @var RecaptchaExtDomainsKeysPair $pair */
        foreach ($model->getDomainsKeysPairs() as $pair) {
            if ($pair->getContainsCurrentDomain()) {
                $model->setCurrentDomainsKeysPair($pair);
                break;
            }
        }

        return $model;
    }
}
