<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailVerificationProvidersHandler
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */
class EmailVerificationProvidersHandler
{
    /**
     * @var ExtensionInit
     */
    protected $extension;

    /**
     * @var array
     */
    protected $_checkers = [];

    /**
     * EmailVerificationProvidersHandler constructor.
     * @param ExtensionInit $extension
     */
    public function __construct(ExtensionInit $extension)
    {
        $this->extension = $extension;
    }

    /**
     * @return EmailVerificationProvider[]
     */
    public function getEmailCheckersProviders(): array
    {
        if (!empty($this->_checkers)) {
            return $this->_checkers;
        }

        return $this->_checkers = [
            new EmailVerificationProvider($this->extension, 'bulk_email_checker'),
            new EmailVerificationProvider($this->extension, 'email_list_verify'),
            new EmailVerificationProvider($this->extension, 'everifier_org'),
            new EmailVerificationProvider($this->extension, 'kickbox'),
            new EmailVerificationProvider($this->extension, 'emailable'),
            new EmailVerificationProvider($this->extension, 'zerobounce'),
        ];
    }

    /**
     * @return array
     */
    public function getEmailCheckersProvidersList(): array
    {
        static $providers = [];
        if (!empty($providers)) {
            return $providers;
        }

        foreach ($this->getEmailCheckersProviders() as $provider) {
            /** @var EmailVerificationExtBaseCommon $model */
            $model = container()->get($provider->getCommonClassName());
            if ($this->extension->isAppName('customer')) {
                /** @var Customer $customer */
                $customer = customer()->getModel();

                if (!$provider->getHasCustomerAreaAccess($customer)) {
                    continue;
                }

                /** @var EmailVerificationExtBaseCommon $model */
                $model = container()->get($provider->getCustomerClassName());
            }

            $providers[] = [
                'id'          => $provider->getId(),
                'model'       => $model,
                'name'        => $model->getName(),
                'description' => $model->getDescription(),
                'enabled'     => t('app', ucfirst($model->enabled)),
                'url'         => $this->extension->createUrl($provider->getId() . '/index'),
            ];
        }

        return $providers;
    }

    /**
     * @return CArrayDataProvider
     */
    public function getAsDataProvider(): CArrayDataProvider
    {
        $providers = [];
        foreach ($this->getEmailCheckersProvidersList() as $data) {
            $providers[] = [
                'id'          => $data['id'],
                'name'        => $data['name'],
                'description' => $data['description'],
                'enabled'     => $data['enabled'],
                'url'         => $data['url'],
            ];
        }

        return new CArrayDataProvider($providers, [
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);
    }

    /**
     * Register the common models in container for singleton access
     */
    public function registerEmailCheckersProviders(): void
    {
        foreach ($this->getEmailCheckersProviders() as $provider) {
            $provider->register();
        }
    }

    /**
     * Handle all backend related tasks
     *
     * @return void
     */
    public function backendApp(): void
    {
        $controllerMaps = [];
        $rules[] = ['providers/index', 'pattern' => 'extensions/email-verification/providers'];
        $controllerMaps['providers'] = [
            'class' => 'backend.controllers.EmailVerificationExtBackendProvidersController',
        ];

        foreach ($this->getEmailCheckersProviders() as $provider) {
            $rules[] = $provider->getBackendUrlRules();
            $controllerMaps[$provider->getId()] = $provider->getBackendControllerMaps();
        }

        $this->extension->addUrlRules($rules);

        $this->extension->addControllerMap($controllerMaps);

        // add the menu item
        hooks()->addFilter('backend_left_navigation_menu_items', [$this, '_registerBackendMenuItem']);
    }

    /**
     * @param array $items
     *
     * @return array
     */
    public function _registerBackendMenuItem(array $items): array
    {
        /** @var string $route */
        $route = app()->getController()->getRoute();

        $menuItems[] = [
            'url'    => [$this->extension->getRoute('providers/index')],
            'label'  => t('app', 'Providers'),
            'active' => strpos($route, $this->extension->getRoute('providers')) === 0, ];

        foreach ($this->getEmailCheckersProviders() as $provider) {
            /** @var EmailVerificationExtBaseCommon $model */
            $model = container()->get($provider->getCommonClassName());

            $menuItems[] = [
                'url'    => [$this->extension->getRoute($provider->getId() . '/index')],
                'label'  => t('app', $model->getName()),
                'active' => strpos($route, $this->extension->getRoute($provider->getId())) === 0,
            ];
        }

        $items['email-verification'] = [
            'name'      => $this->extension->t('Email verification'),
            'icon'      => 'glyphicon-check',
            'active'    => $this->extension->getRoute(''),
            'route'     => null,
            'items'     => $menuItems,
        ];

        return $items;
    }

    /**
     * Handle all customer related tasks
     *
     * @return void
     */
    public function customerApp(): void
    {
        /** @var Customer|null $customer */
        $customer = customer()->getModel();
        if (empty($customer)) {
            return;
        }

        // subaccounts don't have access here
        if (is_subaccount()) {
            return;
        }

        $controllerMaps = [];
        $rules[] = ['providers/index', 'pattern' => 'extensions/email-verification/providers'];
        $controllerMaps['providers'] =  [
            'class' => 'customer.controllers.EmailVerificationExtCustomerProvidersController',
        ];

        foreach ($this->getEmailCheckersProviders() as $provider) {
            if (!$provider->getHasCustomerAreaAccess($customer)) {
                continue;
            }

            $rules[] = $provider->getCustomerUrlRules();
            $controllerMaps[$provider->getId()] = $provider->getCustomerControllerMaps();
        }

        $this->extension->addUrlRules($rules);

        $this->extension->addControllerMap($controllerMaps);

        // add the menu item
        hooks()->addFilter('customer_left_navigation_menu_items', [$this, '_customerLeftNavigationMenuItems']);
    }

    /**
     * @param array $items
     * @return array
     */
    public function _customerLeftNavigationMenuItems(array $items): array
    {
        $route = app()->getController()->getRoute();

        $menuItems[] = [
            'url'    => [$this->extension->getRoute('providers/index')],
            'label'  => t('app', 'Providers'),
            'active' => strpos($route, $this->extension->getRoute('providers')) === 0,
        ];

        /** @var Customer $customer */
        $customer = customer()->getModel();

        foreach ($this->getEmailCheckersProviders() as $provider) {
            /** @var EmailVerificationExtBaseCommon $commonModel */
            $commonModel = container()->get($provider->getCommonClassName());

            if (!$provider->getHasCustomerAreaAccess($customer)) {
                continue;
            }
            $menuItems[] = [
                'url'    => [$this->extension->getRoute($provider->getId() . '/index')],
                'label'  => t('app', $commonModel->getName()),
                'active' => strpos($route, $this->extension->getRoute($provider->getId())) === 0,
            ];
        }

        $items['email-verification'] = [
            'name'      => $this->extension->t('Email verification'),
            'icon'      => 'glyphicon-floppy-saved',
            'active'    => $this->extension->getRoute(''),
            'route'     => null,
            'items'     => $menuItems,
        ];

        return $items;
    }

    /**
     * @return void
     */
    public function runCheckers(): void
    {
        foreach ($this->getEmailCheckersProviders() as $provider) {

            /** @var EmailVerificationExtBaseCommon $model */
            $model = container()->get($provider->getCommonClassName());

            if (!$model->getIsEnabled()) {
                continue;
            }

            $model->addFilter();
        }
    }
}
