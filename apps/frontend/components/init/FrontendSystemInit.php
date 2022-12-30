<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FrontendSystemInit
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class FrontendSystemInit extends CApplicationComponent
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
     * Register the assets
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
            ['src' => 'https://fonts.googleapis.com/css?family=Open+Sans:300,400,700', 'priority' => -1000],
            ['src' => 'https://fonts.googleapis.com/css?family=Lato:100,100i,300,300i,400,400i,700,700i,900,900i', 'priority' => -1000],
            ['src' => 'https://fonts.googleapis.com/css?family=Roboto:300,400,700,900', 'priority' => -1000],
            ['src' => 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css', 'priority' => -1000],
            ['src' => 'https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css', 'priority' => -1000],
            ['src' => apps()->getBaseUrl('assets/css/adminlte.css'), 'priority' => -1000],
            ['src' => AssetsUrl::css('style.css'), 'priority' => -1000],
        ]);

        // since 1.3.5.4 - skin
        $skinName = null;

        /** @var OptionCustomization $optionCustomization */
        $optionCustomization = container()->get(OptionCustomization::class);

        if ($_skinName = $optionCustomization->getFrontendSkin()) {
            if (is_file((string)Yii::getPathOfAlias('root.frontend.assets.css') . '/' . $_skinName . '.css')) {
                $styles->add(['src' => apps()->getBaseUrl('frontend/assets/css/' . $_skinName . '.css'), 'priority' => -1000]);
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
        app()->getController()->getData('bodyClasses')->add('sidebar-hidden');

        // 2.1.8
        app()->getController()->getData('bodyClasses')->add(sprintf('app-%s', apps()->getCurrentAppName()));

        // since 1.3.4.8
        if (is_file(AssetsPath::css('style-custom.css'))) {
            $version = filemtime(AssetsPath::css('style-custom.css'));
            $styles->mergeWith([
                ['src' => AssetsUrl::css('style-custom.css') . '?v=' . $version, 'priority' => -1000],
            ]);
        }

        return $styles;
    }
}
