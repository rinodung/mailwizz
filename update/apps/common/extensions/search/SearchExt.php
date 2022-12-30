<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * SearchExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Search';

    /**
     * @var string
     */
    public $description = 'Add search abilities in backend and customer area';

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
    public $allowedApps = ['backend', 'customer'];

    /**
     * @var int
     */
    public $priority = 999;

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
        // since 2.1.10
        if (apps()->isAppName('backend') && user()->getIsGuest()) {
            return;
        }

        // since 2.1.10
        if (apps()->isAppName('customer') && customer()->getIsGuest()) {
            return;
        }

        /**
         * Import the models
         */
        $this->importClasses('common.models.*');
        $this->importClasses('common.behaviors.SearchExtBaseBehavior', true);

        /**
         * Register the asset files
         */
        hooks()->addFilter('register_scripts', [$this, '_registerScripts']);
        hooks()->addFilter('register_styles', [$this, '_registerStyles']);

        /**
         * Register the modal content
         */
        hooks()->addAction('after_opening_body_tag', [$this, '_registerModalView']);

        /**
         * Register the search button
         */
        hooks()->addAction('layout_top_navbar_menu_items_start', [$this, '_registerSearchButton']);

        /**
         * Add the url rules.
         */
        $this->addUrlRules([
            ['search/index',      'pattern' => 'search'],
            ['search/<action>/*', 'pattern' => 'search/<action>/*'],
            ['search/<action>',   'pattern' => 'search/<action>'],

        ]);

        /**
         * And now we register the controllers for the above rules.
         */
        $this->addControllerMap([
            'search' => [
                'class' => 'common.controllers.SearchExtCommonSearchController',
            ],
        ]);
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
        return $this->_assetsUrl = assetManager()->publish(dirname(__FILE__) . '/common/assets', false, -1, MW_DEBUG);
    }

    /**
     * @param CList $scripts
     *
     * @return CList
     * @throws CException
     */
    public function _registerScripts(CList $scripts): CList
    {
        $scripts->add(['src' => $this->getAssetsUrl() . '/js/search.js']);
        return $scripts;
    }

    /**
     * @param CList $styles
     *
     * @return CList
     * @throws CException
     */
    public function _registerStyles(CList $styles): CList
    {
        $styles->add(['src' => $this->getAssetsUrl() . '/css/search.css']);
        return $styles;
    }

    /**
     * @param Controller $controller
     *
     * @return void
     * @throws CException
     */
    public function _registerModalView($controller)
    {
        $extension = $this;
        $controller->renderFile($this->getPathOfAlias('common.views.search-modal') . '.php', compact('extension'));
    }

    /**
     * @param Controller $controller
     *
     * @return void
     * @throws CException
     */
    public function _registerSearchButton($controller)
    {
        $controller->renderFile($this->getPathOfAlias('common.views.search-button') . '.php');
    }
}
