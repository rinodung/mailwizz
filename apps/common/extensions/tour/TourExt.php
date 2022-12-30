<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * TourExt
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class TourExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Tour';

    /**
     * @var string
     */
    public $description = 'MailWizz EMA Tour';

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
        container()->add(TourExtCommon::class, TourExtCommon::class);

        /** @var TourExtCommon $model */
        $model = container()->get(TourExtCommon::class);

        if ($this->isAppName('backend')) {

            /**
             * Add the url rules.
             */
            $this->addUrlRules([

                // settings
                ['settings/index', 'pattern'    => 'extensions/tour/settings'],
                ['settings/<action>', 'pattern' => 'extensions/tour/settings/*'],

                // slideshow slides
                ['slideshow_slides/index', 'pattern'    => 'extensions/tour/slideshows/<slideshow_id:\d+>/slides'],
                ['slideshow_slides/<action>/*', 'pattern' => 'extensions/tour/slideshows/<slideshow_id:\d+>/slides/<action>/*'],
                ['slideshow_slides/<action>', 'pattern' => 'extensions/tour/slideshows/<slideshow_id:\d+>/slides/<action>'],

                // slideshow
                ['slideshows/index', 'pattern'    => 'extensions/tour/slideshows'],
                ['slideshows/<action>/*', 'pattern' => 'extensions/tour/slideshows/<action>/*'],
                ['slideshows/<action>', 'pattern' => 'extensions/tour/slideshows/<action>'],
            ]);

            /**
             * And now we register the controllers for the above rules.
             */
            $this->addControllerMap([
                'settings' => [
                    'class' => 'backend.controllers.TourExtBackendSettingsController',
                ],
                'slideshows' => [
                    'class' => 'backend.controllers.TourExtBackendSlideshowsController',
                ],
                'slideshow_slides' => [
                    'class' => 'backend.controllers.TourExtBackendSlideshowSlidesController',
                ],
            ]);
        }

        /**
         * Now we can continue only if the extension is enabled from its settings:
         */
        if (!$model->getIsEnabled()) {
            return;
        }

        /**
         * Add the url rules.
         */
        $this->addUrlRules([

            // skip
            ['slideshow_skip/index', 'pattern'    => 'extensions/tour/skip-slideshow'],
        ]);

        $this->addControllerMap([
            'slideshow_skip' => [
                'class' => 'common.controllers.TourExtCommonSlideshowSkipController',
            ],
        ]);

        // insert the hook.
        hooks()->addAction('after_opening_body_tag', [$this, '_injectTourData']);
    }

    /**
     * Add the landing page for this extension (settings/general info/etc)
     *
     * @return string
     */
    public function getPageUrl()
    {
        return $this->createUrl('settings/index');
    }

    /**
     * @inheritDoc
     */
    public function beforeEnable()
    {
        // run the install queries
        $this->runQueriesFromSqlFile(dirname(__FILE__) . '/common/data/install.sql');

        // insert default data
        $this->runQueriesFromSqlFile(dirname(__FILE__) . '/common/data/insert.sql');

        // run parent
        return parent::beforeEnable();
    }

    /**
     * @inheritDoc
     */
    public function beforeDisable()
    {
        // run the uninstall queries
        $this->runQueriesFromSqlFile(dirname(__FILE__) . '/common/data/uninstall.sql');

        // run parent
        return parent::beforeDisable();
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
     * @param Controller $controller
     *
     * @return void
     * @throws CException
     */
    public function _injectTourData($controller)
    {
        if (!in_array($controller->getId(), ['dashboard'])) {
            return;
        }

        $appName = apps()->getCurrentAppName();
        $id      = null;

        if ($appName == TourSlideshow::APPLICATION_BACKEND) {
            $id = user()->getId();
        } elseif ($appName == TourSlideshow::APPLICATION_CUSTOMER) {
            $id = customer()->getId();
        }

        if (empty($id)) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('application', $appName);
        $criteria->compare('status', TourSlideshow::STATUS_ACTIVE);
        $criteria->order = 'slideshow_id DESC';

        /** @var TourSlideshow|null $slideshow */
        $slideshow = TourSlideshow::model()->find($criteria);

        if (empty($slideshow)) {
            return;
        }

        $key = 'views.' . $appName . '.' . $id . '.viewed';
        if ($this->getOption($key, 0) == $slideshow->slideshow_id) {
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('slideshow_id', $slideshow->slideshow_id);
        $criteria->compare('status', TourSlideshowSlide::STATUS_ACTIVE);
        $criteria->order = 'sort_order ASC, slide_id ASC';

        /** @var TourSlideshowSlide[] $slides */
        $slides = TourSlideshowSlide::model()->findAll($criteria);

        if (empty($slides)) {
            return;
        }

        $extension = $this;
        $viewFile  = $this->getPathOfAlias('common.views.slideshow') . '.php';
        $controller->renderFile($viewFile, compact('extension', 'slideshow', 'slides'));
    }

    /**
     * @param string $content
     *
     * @return string
     * @throws CException
     */
    public function replaceContentTags(string $content): string
    {
        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        /** @var OptionCommon $common */
        $common = container()->get(OptionCommon::class);

        static $searchReplace = [];
        if (empty($searchReplace)) {
            $searchReplace = [
                '[FULL_NAME]'    => '',
                '[FIRST_NAME]'   => '',
                '[LAST_NAME]'    => '',
                '[APP_NAME]'     => $common->getSiteName(),
                '[BACKEND_URL]'  => rtrim((string)$optionUrl->getBackendUrl(), '/'),
                '[CUSTOMER_URL]' => rtrim((string)$optionUrl->getCustomerUrl(), '/'),
                '[API_URL]'      => rtrim((string)$optionUrl->getApiUrl(), '/'),
                '[FRONTEND_URL]' => rtrim((string)$optionUrl->getFrontendUrl(), '/'),
                '[SUPPORT_URL]'  => defined('MW_SUPPORT_FORUM_URL') ? MW_SUPPORT_FORUM_URL : '',
                '[ASSETS_URL]'   => $this->getAssetsUrl(),
            ];

            $appName = apps()->getCurrentAppName();
            if ($appName == TourSlideshow::APPLICATION_BACKEND) {

                /** @var User $user */
                $user = user()->getModel();
                $searchReplace['[FULL_NAME]']  = $user->getFullName();
                $searchReplace['[FIRST_NAME]'] = $user->first_name;
                $searchReplace['[LAST_NAME]']  = $user->last_name;
            } elseif ($appName == TourSlideshow::APPLICATION_CUSTOMER) {

                /** @var Customer $customer */
                $customer = customer()->getModel();
                $searchReplace['[FULL_NAME]']  = $customer->getFullName();
                $searchReplace['[FIRST_NAME]'] = $customer->first_name;
                $searchReplace['[LAST_NAME]']  = $customer->last_name;
            }
        }

        return (string)str_replace(
            array_keys($searchReplace),
            array_values($searchReplace),
            StringHelper::decodeSurroundingTags($content)
        );
    }
}
