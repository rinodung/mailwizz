<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CookieConsentExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class CookieConsentExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Cookie consent';

    /**
     * @var string
     */
    public $description = 'Inject the cookie consent';

    /**
     * @var string
     */
    public $version = '1.0.0';

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
    public $allowedApps = ['backend', 'customer', 'frontend'];

    /**
     * @var bool
     */
    protected $_canBeDeleted = false;

    /**
     * @var bool
     */
    protected $_canBeDisabled = true;

    /**
     * @var string
     */
    private $_assetsUrl = '';

    /**
     * @inheritDoc
     */
    public function run()
    {
        $this->importClasses('common.models.*');

        // register the common model in container for singleton access
        container()->add(CookieConsentExtCommon::class, CookieConsentExtCommon::class);

        if ($this->isAppName('backend')) {

            // handle all backend related tasks
            $this->backendApp();
        }

        /** @var CookieConsentExtCommon $model */
        $model = container()->get(CookieConsentExtCommon::class);

        if (!$model->getIsEnabled()) {
            return;
        }

        // Register the asset files
        hooks()->addFilter('register_scripts', [$this, '_registerScripts']);

        // Register the html in the footer
        hooks()->addAction('layout_footer_html', [$this, '_injectFooterHtml']);
    }

    /**
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createUrl('settings/index');
    }

    /**
     * @param CList $scripts
     *
     * @return CList
     * @throws CException
     */
    public function _registerScripts(CList $scripts): CList
    {
        $scripts->add(['src' => $this->getAssetsUrl() . '/js/cookie-consent.js']);
        return $scripts;
    }

    /**
     * @return string
     * @throws CException
     */
    public function getAssetsUrl(): string
    {
        if ($this->_assetsUrl !== '') {
            return $this->_assetsUrl;
        }
        return $this->_assetsUrl = assetManager()->publish(dirname(__FILE__) . '/assets', false, -1, MW_DEBUG);
    }

    /**
     * @param Controller $controller
     *
     * @return void
     */
    public function _injectFooterHtml($controller)
    {
        /** @var CookieConsentExtCommon $model */
        $model = container()->get(CookieConsentExtCommon::class);

        echo $model->getCookieConsentHtml();
    }

    /**
     * Handle all backend related tasks
     *
     * @return void
     */
    protected function backendApp()
    {
        $this->addUrlRules([
            ['settings/index', 'pattern' => 'extensions/cookie-consent/settings'],
            ['settings/<action>', 'pattern' => 'extensions/cookie-consent/settings/*'],
        ]);

        $this->addControllerMap([
            'settings' => [
                'class' => 'backend.controllers.CookieConsentExtBackendSettingsController',
            ],
        ]);
    }
}
