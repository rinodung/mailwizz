<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SystemInit
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class SystemInit extends CApplicationComponent
{
    /**
     * @var bool
     */
    protected $_hasRanOnBeginRequest = false;

    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        parent::init();
        app()->attachEventHandler('onBeginRequest', [$this, '_runOnBeginRequest']);
    }

    /**
     * @throws CException
     */
    public function _runOnBeginRequest(): void
    {
        if ($this->_hasRanOnBeginRequest) {
            return;
        }

        // since 2.1.10
        if (!is_cli()) {
            /** @var OptionReverseProxy $optionReverseProxy */
            $optionReverseProxy = container()->get(OptionReverseProxy::class);
            $optionReverseProxy->rewriteServerUserHostAddress();
        }

        // check if app is read only and take proper action
        $this->_checkIfAppIsReadOnly();

        $appName = apps()->getCurrentAppName();

        if (!is_cli()) {
            hooks()->addAction($appName . '_controller_before_action', [$this, '_reindexGetArray']);
        }

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        if (!in_array($appName, ['backend', 'console']) && !$common->getIsSiteOnline()) {
            hooks()->addAction($appName . '_controller_before_action', [$this, '_setRedirectToOfflinePage'], -1000);
        }

        if (!is_cli()) {

            // clean the globals.
            ioFilter()->cleanGlobals();

            // nice urls
            if ($common->getUseCleanUrls()) {
                urlManager()->showScriptName = false;
            }

            // set the application display language
            $this->setApplicationLanguage();

            if (!in_array($appName, ['api'])) {
                // check if we need to upgrade
                $this->checkUpgrade();
            }
        }

        /** @var OptionCdn $optionCdn */
        $optionCdn = container()->get(OptionCdn::class);

        // since 1.3.5.4 - CDN Support
        if (
            !is_cli() &&
            !in_array($appName, ['api']) &&
            $optionCdn->getIsEnabled() &&
            ($cdnDomain = $optionCdn->getSubdomain())
        ) {
            if (stripos($cdnDomain, 'http') !== 0) {
                $cdnDomain = 'http://' . $cdnDomain;
            }

            /** @var CWebApplication $app */
            $app = app();

            assetManager()->baseUrl = sprintf('%s/%s', $cdnDomain, ltrim(assetManager()->baseUrl, '/'));
            if ($app->hasComponent('themeManager') && $app->getThemeManager()->setAppTheme() /*&& app()->getTheme()*/) {
                $app->getThemeManager()->setBaseUrl(sprintf('%s/%s', $cdnDomain, ltrim((string)$app->getThemeManager()->getBaseUrl(), '/')));
            }
        }

        // load all extensions.
        extensionsManager()->loadAllExtensions();

        // setup theme or base view system if not cli mode
        if (!is_cli() && !in_array($appName, ['api'])) {

            /** @var CWebApplication $app */
            $app = app();

            // try to set the theme system
            if ($app->hasComponent('themeManager') /*&& app()->getTheme()*/) {

                // set the theme
                $app->getThemeManager()->setAppTheme();
            }
        }

        /** @var bool $isCli */
        $isCli = is_cli();

        /** @var bool $isAjax */
        $isAjax = is_ajax();

        if (empty($isCli) && empty($isAjax) && in_array($appName, ['backend', 'frontend', 'customer'])) {
            hooks()->addAction($appName . '_controller_before_action', [$this, '_checkStoredData'], -1000);
        }

        // and mark the event as completed.
        $this->_hasRanOnBeginRequest = true;
    }

    /**
     * Called in all controllers init() method, will redirect to update page.
     *
     * @return void
     */
    public function _setRedirectToUpdatePage(): void
    {
        /** @var CWebApplication $app */
        $app = app();

        /** @var AppsBehavior $apps */
        $apps = apps();

        /** @var Controller $controller */
        $controller = $app->getController();

        // leave the error page alone
        if (stripos($app->getErrorHandler()->errorAction, $controller->getRoute()) !== false) {
            return;
        }

        if (!$apps->isAppName('backend') || $controller->getId() != 'update') {
            request()->redirect(apps()->getAppUrl('backend', 'update/index', true));
        }
    }

    /**
     * @return void
     */
    public function _setRedirectToOfflinePage(): void
    {
        /** @var Controller $controller */
        $controller = app()->getController();

        /** @var CAction $action */
        $action = $controller->getAction();

        $controllerHandler = 'site';

        $isErrorPage   = $controller->getId() == $controllerHandler && $action->getId() == 'error';
        $isOfflinePage = $controller->getId() == $controllerHandler && $action->getId() == 'offline';
        if (!$isErrorPage && !$isOfflinePage) {
            request()->redirect(apps()->getAppUrl('frontend', $controllerHandler . '/offline', true));
        }
    }

    /**
     * @return void
     */
    public function _reindexGetArray(): void
    {
        if (empty($_GET)) {
            return;
        }

        /** @var CMap $get */
        $get = app_param('GET');
        $get->mergeWith($_GET);

        $_GET = (array)ioFilter()->stripClean($_GET);
    }

    /**
     * @return void
     */
    public function _checkStoredData(): void
    {
        /** @var OptionLicense $license */
        $license = container()->get(OptionLicense::class);
        if ($license->getPurchaseCode()) {
            return;
        }

        notify()->addError($license->getMissingPurchaseCodeMessage());
    }

    /**
     * @throws CException
     */
    protected function setApplicationLanguage(): void
    {
        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        // multilanguage is available since 1.1 and the Language class does not exist prior to that version
        if (!version_compare($common->version, '1.1', '>=')) {
            return;
        }

        $languageCode = null;

        // 1.4.4
        if (($langCode = (string)request()->getQuery('lang')) && strlen($langCode) <= 5) {
            $regionCode = null;
            if (strpos($langCode, '_') !== false) {
                [$langCode, $regionCode] = explode('_', $langCode);
            }
            $attributes = [
                'language_code' => $langCode,
            ];
            if (!empty($regionCode)) {
                $attributes['region_code'] = $regionCode;
            }
            $language = Language::model()->findByAttributes($attributes);
            if (!empty($language)) {
                app()->setLanguage($language->getLanguageAndLocaleCode());
            }
            return;
        }
        //

        if ($language = Language::getDefaultLanguage()) {
            $languageCode = $language->getLanguageAndLocaleCode();
        }

        if (apps()->isAppName('frontend')) {
            if (!empty($languageCode)) {
                app()->setLanguage($languageCode);
            }
            return;
        }

        $loadCustomerLanguage = app()->hasComponent('customer') && customer()->getId() > 0;
        $loadUserLanguage     = !$loadCustomerLanguage && app()->hasComponent('user') && user()->getId() > 0;

        if ($loadCustomerLanguage || $loadUserLanguage) {
            if ($loadCustomerLanguage && ($model = customer()->getModel())) {
                if (!empty($model->language_id)) {
                    $language = Language::model()->findByPk((int)$model->language_id);
                    if (!empty($language)) {
                        $languageCode = $language->getLanguageAndLocaleCode();
                    }
                }
            }

            if ($loadUserLanguage && ($model = user()->getModel())) {
                if (!empty($model->language_id)) {
                    $language = Language::model()->findByPk((int)$model->language_id);
                    if (!empty($language)) {
                        $languageCode = $language->getLanguageAndLocaleCode();
                    }
                }
            }
        }

        if (!empty($languageCode)) {
            app()->setLanguage($languageCode);
        }
    }

    /**
     * Will check and see if the application needs upgrade.
     * If it needs, will put it in maintenance mode untill upgrade is done.
     *
     * @since 1.1
     * @return void
     */
    protected function checkUpgrade(): void
    {
        if (!in_array(apps()->getCurrentAppName(), ['backend', 'customer', 'frontend'])) {
            return;
        }

        /** @var OptionCommon $common */
        $common       = container()->get(OptionCommon::class);
        $fileVersion  = MW_VERSION;
        $dbVersion    = $common->version;

        if (!version_compare($fileVersion, $dbVersion, '>')) {
            return;
        }

        if ($common->getIsSiteOnline()) {
            $common->saveAttributes([
                'site_status' => OptionCommon::STATUS_OFFLINE,
            ]);
        }

        // only if the user is logged in
        if (app()->hasComponent('user') && user()->getId() > 0) {
            $appName = apps()->getCurrentAppName();
            hooks()->addAction($appName . '_controller_init', [$this, '_setRedirectToUpdatePage']);
        }
    }

    /**
     * @return void
     */
    protected function _checkIfAppIsReadOnly(): void
    {
        if (!defined('MW_IS_APP_READ_ONLY') || !MW_IS_APP_READ_ONLY) {
            return;
        }

        $message = 'The application demo runs in READ-ONLY mode!';
        if (is_cli()) {
            exit($message);
        }

        if (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], array_map('trim', explode(',', MW_DEVELOPERS_IPS)))) {
            return;
        }

        $neverAllowed = [
            '/api', '/customer/api-keys/generate', '/backend/settings/license',
            '/backend/misc/application-log', '/customer/guest/confirm-registration',
            '/backend/misc/phpinfo',
        ];

        $uri     = $_SERVER['REQUEST_URI'] ?? null;
        $allowed = ['/backend/guest', '/customer/guest'];
        $referer = '../';
        $allow   = false;

        $uri      = trim(str_replace('/index.php/', '/', $uri), '/');
        $uriParts = array_unique(explode('/', $uri));
        $webApps  = apps()->getWebApps();
        foreach ($uriParts as $index => $part) {
            if (!in_array($part, $webApps)) {
                unset($uriParts[$index]);
            }
            break;
        }
        $uri = '/' . implode('/', $uriParts);

        foreach ($neverAllowed as $uriString) {
            if (strpos($uri, $uriString) === 0) {
                if (!request()->getIsAjaxRequest()) {
                    notify()->addWarning($message);
                    request()->redirect($referer);
                }
                app()->end();
            }
        }

        if (empty($_POST)) {
            return;
        }

        foreach ($allowed as $uriString) {
            if (strpos($uri, $uriString) === 0) {
                $allow = true;
                break;
            }
        }

        if (!$allow) {
            if (!request()->getIsAjaxRequest()) {
                notify()->addWarning($message);
                request()->redirect($referer);
            }
            app()->end();
        }
    }
}
