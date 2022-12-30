<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CustomerSystemInit
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class CustomerSystemInit extends CApplicationComponent
{
    /**
     * @var bool
     */
    protected $_hasRanOnBeginRequest = false;

    /**
     * @var bool
     */
    protected $_hasRanOnEndRequest = false;

    /**
     * @throws CException
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        app()->attachEventHandler('onBeginRequest', [$this, '_runOnBeginRequest']);
        app()->attachEventHandler('onEndRequest', [$this, '_runOnEndRequest']);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _runOnBeginRequest(CEvent $event)
    {
        if ($this->_hasRanOnBeginRequest) {
            return;
        }

        // a safety hook for logged in vs not logged in users.
        hooks()->addAction('customer_controller_init', [$this, '_checkControllerAccess']);

        // display a global notification message to logged in customers
        hooks()->addAction('customer_controller_init', [$this, '_displayNotificationMessage']);

        /** @var CWebApplication $app */
        $app = app();

        // register core assets if not cli mode and no theme active
        if (!is_cli() && (!$app->hasComponent('themeManager') || !$app->getTheme())) {
            $this->registerAssets();
        }

        // and mark the event as completed.
        $this->_hasRanOnBeginRequest = true;
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _runOnEndRequest(CEvent $event)
    {
        if ($this->_hasRanOnEndRequest) {
            return;
        }

        // and mark the event as completed.
        $this->_hasRanOnEndRequest = true;
    }

    /**
     * Callback for customer_controller_init and customer_before_controller_action action.
     *
     * @return void
     * @throws CException
     */
    public function _checkControllerAccess()
    {
        static $_unprotectedControllersHookDone = false;
        static $_hookCalled = false;

        if ($_hookCalled) {
            return;
        }

        $controller = app()->getController();
        $_hookCalled = true;
        $unprotectedControllers = (array)app_param('unprotectedControllers', []);

        if (!$_unprotectedControllersHookDone) {
            app_param_set('unprotectedControllers', $unprotectedControllers);
            $_unprotectedControllersHookDone = true;
        }

        if (!in_array($controller->id, $unprotectedControllers) && !customer()->getId()) {
            // make sure we set a return url to the previous page that required the customer to be logged in.
            customer()->setReturnUrl(request()->getRequestUri());

            // and redirect to the login url.
            $controller->redirect(customer()->loginUrl);
        }

        // since 1.3.4.9, check sending quota here with a probability of 50%
        // experimental for now, might get removed in the future.
        if (rand(0, 100) >= 50 && customer()->getId() && !request()->getIsPostRequest() && !request()->getIsAjaxRequest()) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            $customer->getIsOverQuota();
        }

        // 1.5.1 - check if we pulsate the info icons to drag attention to them
        if (customer()->getId() && app_param('customer.pulsate_info.enabled', true)) {
            $controller->onBeforeAction = [$this, '_checkMakeIconsPulsate'];
        }

        // 1.9.1 - if the customer has been marked as any status but active, issue a forced logout
        if (
            customer()->getId() &&
            !request()->getIsPostRequest() &&
            !request()->getIsAjaxRequest()
        ) {

            /** @var Customer $customer */
            $customer = customer()->getModel();

            if (!$customer->getIsActive()) {

                // make sure the auto-login cookie is removed as well
                customer()->identityCookie = null;

                // log the customer out
                customer()->logout();

                // make sure we set a return url to the previous page that required the customer to be logged in.
                customer()->setReturnUrl(request()->getRequestUri());

                // and redirect to the login url.
                $controller->redirect(customer()->loginUrl);
            }
        }
    }

    /**
     * Callback for customer_controller_init.
     *
     * @return void
     */
    public function _displayNotificationMessage()
    {
        if (request()->getIsAjaxRequest()) {
            return;
        }

        if (!customer()->getId() || !($customer = customer()->getModel())) {
            return;
        }

        if (in_array(app()->getController()->id, (array)app_param('unprotectedControllers', []))) {
            return;
        }

        $notification = (string)$customer->getGroupOption('common.notification_message', '');
        if (strlen(strip_tags($notification)) > 0) {
            $hash = sha1('customer_notification_' . $notification);
            if (empty($_COOKIE[$hash])) {
                $notificationHtml = sprintf('<span class="customer-notification-message" data-hash="%s">%s</span>', $hash, $notification);
                notify()->addInfo($notificationHtml);
            }
        }
    }

    /**
     * Register assets
     *
     * @return void
     */
    public function registerAssets()
    {
        hooks()->addFilter('register_scripts', [$this, '_registerScripts']);
        hooks()->addFilter('register_styles', [$this, '_registerStyles']);
    }

    /**
     * @param CList $scripts
     *
     * @return CList
     * @throws CException
     */
    public function _registerScripts(CList $scripts)
    {
        $scripts->mergeWith([
            ['src' => apps()->getBaseUrl('assets/js/bootstrap.min.js'), 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/js/knockout.min.js'), 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/js/notify.js'), 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/js/adminlte.js'), 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/js/cookie.js'), 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/js/select2/js/select2.full.min.js'), 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/js/app.js'), 'priority' => -1000],
            ['src' => AssetsUrl::js('app.js'), 'priority' => -1000],
        ]);

        // since 1.3.4.8
        if (is_file(AssetsPath::js('app-custom.js'))) {
            $version = filemtime(AssetsPath::js('app-custom.js'));
            $scripts->mergeWith([
                ['src' => AssetsUrl::js('app-custom.js') . '?v=' . $version, 'priority' => -1000],
            ]);
        }

        return $scripts;
    }

    /**
     * @param CList $styles
     *
     * @return CList
     * @throws CException
     */
    public function _registerStyles(CList $styles)
    {
        $styles->mergeWith([
            ['src' => apps()->getBaseUrl('assets/css/bootstrap.min.css'), 'priority' => -1000],
            ['src' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css', 'priority' => -1000],
            ['src' => 'https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css', 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/js/select2/css/select2.min.css'), 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/css/adminlte.css'), 'priority' => -1000],
            ['src' => AssetsUrl::css('style.css'), 'priority' => -1000],
        ]);

        // since 1.3.5.4 - skin
        $skinName = null;

        /** @var OptionCustomization $optionCustomization */
        $optionCustomization = container()->get(OptionCustomization::class);

        if ($_skinName = $optionCustomization->getCustomerSkin()) {
            if (is_file((string)Yii::getPathOfAlias('root.customer.assets.css') . '/' . $_skinName . '.css')) {
                $styles->add(['src' => apps()->getBaseUrl('customer/assets/css/' . $_skinName . '.css'), 'priority' => -1000]);
                $skinName = $_skinName;
            } elseif (is_file((string)Yii::getPathOfAlias('root.assets.css') . '/' . $_skinName . '.css')) {
                $styles->add(['src' => apps()->getBaseUrl('assets/css/' . $_skinName . '.css'), 'priority' => -1000]);
                $skinName = $_skinName;
            } else {
                $_skinName = null;
            }
        }
        if (!$skinName) {
            $styles->add(['src' => apps()->getBaseUrl('assets/css/skin-blue.css'), 'priority' => -1000]);
            $skinName = 'skin-blue';
        }
        app()->getController()->getData('bodyClasses')->add($skinName);
        // end 1.3.5.4

        // 1.3.7.3
        app()->getController()->getData('bodyClasses')->add('sidebar-mini');
        $sidebarStatus = $_COOKIE['sidebar_status'] ?? '';
        $sidebarStatus = empty($sidebarStatus) || $sidebarStatus == 'closed' ? 'sidebar-collapse' : '';
        if ($sidebarStatus) {
            app()->getController()->getData('bodyClasses')->add($sidebarStatus);
        }
        //

        // since 2.1.8
        if ($skinName == 'skin-blue') {
            app()->getController()->getData('bodyClasses')->add('supports-dark-mode');

            $skinDarkMode = $_COOKIE['skin_dark_mode'] ?? '';
            if (!empty($skinDarkMode)) {
                app()->getController()->getData('bodyClasses')->add('dark');
            }
        }
        app()->getController()->getData('bodyClasses')->add(sprintf('app-%s', apps()->getCurrentAppName()));
        //

        // since 1.3.4.8
        if (is_file(AssetsPath::css('style-custom.css'))) {
            $version = filemtime(AssetsPath::css('style-custom.css'));
            $styles->mergeWith([
                ['src' => AssetsUrl::css('style-custom.css') . '?v=' . $version, 'priority' => -1000],
            ]);
        }

        return $styles;
    }

    /**
     * @since 1.5.1
     * @param CEvent $event
     *
     * @return void
     */
    public function _checkMakeIconsPulsate(CEvent $event)
    {
        $controller = $event->sender;
        $key        = sprintf('system.pulsate_info.customers.%d.start_ts', (int)customer()->getId());

        if (options()->get($key, 0) == 0) {
            options()->set($key, time());
        }

        $showItTs       = 3600 * 24 * 7; // one week should be enough
        $pulsateStartTs = options()->get($key, 0);

        if (($pulsateStartTs + $showItTs) < time()) {
            return;
        }

        $scripts = $controller->getData('pageScripts');
        $scripts->insertAt(0, ['src' => apps()->getBaseUrl('assets/js/pulsate/pulsate.min.js'), 'priority' => -1000]);
        $scripts->add(['src' => apps()->getBaseUrl('assets/js/pulsate/trigger.js'), 'priority' => -1000]);
    }
}
