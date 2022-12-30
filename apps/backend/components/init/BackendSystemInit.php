<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * BackendSystemInit
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class BackendSystemInit extends CApplicationComponent
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
     * @return void
     * @throws CException
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
    public function _runOnBeginRequest(CEvent $event): void
    {
        if ($this->_hasRanOnBeginRequest) {
            return;
        }

        // a safety hook for logged in vs not logged in users.
        hooks()->addAction('backend_controller_init', [$this, '_checkControllerAccess']);

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
    public function _runOnEndRequest(CEvent $event): void
    {
        if ($this->_hasRanOnEndRequest) {
            return;
        }

        // and mark the event as completed.
        $this->_hasRanOnEndRequest = true;
    }

    /**
     * Callback for user_controller_init and user_before_controller_action action.
     */
    public function _checkControllerAccess(): void
    {
        static $_unprotectedControllersHookDone = false;
        static $_hookCalled = false;

        if ($_hookCalled || !($controller = app()->getController())) {
            return;
        }

        $_hookCalled = true;
        $unprotectedControllers = (array)app_param('unprotectedControllers', []);

        if (!$_unprotectedControllersHookDone) {
            app_param_set('unprotectedControllers', $unprotectedControllers);
            $_unprotectedControllersHookDone = true;
        }

        if (!in_array($controller->id, $unprotectedControllers) && !user()->getId()) {
            // make sure we set a return url to the previous page that required the user to be logged in.
            user()->setReturnUrl(request()->getRequestUri());
            // and redirect to the login url.
            $controller->redirect(user()->loginUrl);
        }

        // since 1.3.5, user permission to controller action, aka route
        if (!in_array($controller->id, $unprotectedControllers) && user()->getId()) {
            $controller->onBeforeAction = [$this, '_checkRouteAccess'];
        }

        // check version update right before executing the action!
        //$controller->onBeforeAction = [$this, '_checkUpdateVersion'];

        // check app wide messages
        $controller->onBeforeAction = [$this, '_checkAppWideMessages'];

        // 1.5.1 - check if we pulsate the info icons to draq attention to them
        if (user()->getId() && app_param('backend.pulsate_info.enabled', true)) {
            $controller->onBeforeAction = [$this, '_checkMakeIconsPulsate'];
        }
    }

    /**
     * Register the assets
     *
     * @return void
     */
    public function registerAssets(): void
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

        // 1.3.7.3
        app()->getController()->getData('bodyClasses')->add('sidebar-mini');
        $sidebarStatus = $_COOKIE['sidebar_status'] ?? '';
        $sidebarStatus = empty($sidebarStatus) || $sidebarStatus == 'closed' ? 'sidebar-collapse' : '';
        if ($sidebarStatus) {
            app()->getController()->getData('bodyClasses')->add($sidebarStatus);
        }
        //

        // since 1.3.5.4 - skin
        $skinName = null;

        /** @var OptionCustomization $optionCustomization */
        $optionCustomization = container()->get(OptionCustomization::class);

        if ($_skinName = $optionCustomization->getBackendSkin()) {
            if (is_file((string)Yii::getPathOfAlias('root.backend.assets.css') . '/' . $_skinName . '.css')) {
                $styles->add(['src' => apps()->getBaseUrl('backend/assets/css/' . $_skinName . '.css'), 'priority' => -1000]);
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
     * @param CEvent $event
     *
     * @throws CHttpException
     * @return void
     */
    public function _checkRouteAccess(CEvent $event): void
    {
        Yii::trace('Checking route access permission for controller ' . $event->sender->id . ', and action ' . $event->sender->action->id);

        /** @var User $user */
        $user = user()->getModel();
        if ($user->hasRouteAccess($event->sender->route)) {
            return;
        }

        $message = t('user_groups', 'You do not have the permission to access this resource!');
        if (request()->getIsAjaxRequest()) {
            $event->sender->renderJson([
                'status'  => 'error',
                'message' => $message,
            ]);
        }

        throw new CHttpException(403, $message);
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _checkUpdateVersion(CEvent $event): void
    {
        $controller = $event->sender;

        if (request()->getIsAjaxRequest()) {
            return;
        }

        if (in_array($controller->id, ['update', 'guest'])) {
            return;
        }

        if ($controller->id == 'dashboard' && $controller->getAction() && $controller->getAction()->id != 'index') {
            return;
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        $checkEnabled   = $common->getCheckVersionUpdate();
        $currentVersion = $common->version;
        $updateVersion  = (string)$common->getAttribute('version_update.current_version', $currentVersion);

        if (!$checkEnabled || !$updateVersion || !version_compare($updateVersion, $currentVersion, '>')) {
            return;
        }

        notify()->addWarning('<strong><u>' . t('app', 'Version {version} is now available for download. Please update your application!', [
            '{version}' => $updateVersion,
        ]) . '</u></strong>');
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _checkAppWideMessages(CEvent $event): void
    {
        $controller = $event->sender;

        if (request()->getIsAjaxRequest()) {
            return;
        }

        if (in_array($controller->id, ['update', 'guest'])) {
            return;
        }

        /** @var OptionLicense $license */
        $license = container()->get(OptionLicense::class);

        if ($error = $license->getErrorMessage()) {
            notify()->addError($error);
        }
    }

    /**
     * @param CEvent $event
     *
     * @throws CException
     * @return void
     */
    public function _checkMakeIconsPulsate(CEvent $event): void
    {
        /** @var BaseController $controller */
        $controller = $event->sender;
        $key        = sprintf('system.pulsate_info.users.%d.start_ts', (int)user()->getId());

        if (options()->get($key, 0) == 0) {
            options()->set($key, time());
        }

        $showItTs       = 3600 * 24 * 7; // one week should be enough
        $pulsateStartTs = options()->get($key, 0);

        if (($pulsateStartTs + $showItTs) < time()) {
            return;
        }

        $scripts = $controller->getPageScripts();
        $scripts->insertAt(0, ['src' => apps()->getBaseUrl('assets/js/pulsate/pulsate.min.js'), 'priority' => -1000]);
        $scripts->add(['src' => apps()->getBaseUrl('assets/js/pulsate/trigger.js'), 'priority' => -1000]);
    }
}
