<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 *
 * Campaign activity map
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3
 */

class CampaignActivityMapExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Campaign activity map';

    /**
     * @var string
     */
    public $description = 'Pinpoint the activity of subscribers on a map.';

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
     * @inheritDoc
     */
    public function run()
    {
        $this->importClasses('common.models.*');

        // register the common model in container for singleton access
        container()->add(CampaignActivityMapExtCommon::class, CampaignActivityMapExtCommon::class);

        if ($this->isAppName('backend')) {
            $this->addUrlRules([
                ['settings/index', 'pattern' => 'extensions/campaign-activity-map/settings'],
            ]);

            $this->addControllerMap([
                'settings' => [
                    'class' => 'backend.controllers.CampaignActivityMapExtBackendSettingsController',
                ],
            ]);
        } elseif ($this->isAppName('customer') || $this->isAppName('frontend')) {

            /** @var CampaignActivityMapExtCommon $model */
            $model = container()->get(CampaignActivityMapExtCommon::class);

            if ($model->getShowOpensMap() || $model->getShowClicksMap() || $model->getShowUnsubscribesMap()) {

                /** @var string $appName */
                $appName = apps()->getCurrentAppName();

                // register the ajax actions that will return the json payload to populate the map
                hooks()->addFilter($appName . '_controller_campaigns_actions', [$this, '_registerAction']);

                // register the extension assets for when the called controller is the campaign one
                hooks()->addAction($appName . '_controller_campaigns_before_action', [$this, '_registerAssets']);

                // register the view display action of the map
                hooks()->addAction('customer_campaigns_overview_after_tracking_stats', [$this, '_showMapView']);
            }
        }
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
     * @param CMap $actions
     *
     * @return CMap
     * @throws CException
     */
    public function _registerAction(CMap $actions)
    {
        $actions->add('opens_activity_map', [
            'class' => $this->getPathAlias('customer.actions.CampaignActivityMapExtCustomerOpensAction'),
        ]);
        $actions->add('clicks_activity_map', [
            'class' => $this->getPathAlias('customer.actions.CampaignActivityMapExtCustomerClicksAction'),
        ]);
        $actions->add('unsubscribes_activity_map', [
            'class' => $this->getPathAlias('customer.actions.CampaignActivityMapExtCustomerUnsubscribesAction'),
        ]);
        return $actions;
    }

    /**
     * @param CAction $action
     *
     * @return void
     * @throws CException
     */
    public function _registerAssets(CAction $action)
    {
        if ($action->getId() != 'overview') {
            return;
        }

        /** @var CampaignActivityMapExtCommon $model */
        $model = container()->get(CampaignActivityMapExtCommon::class);

        /** @var string $assetsUrl */
        $assetsUrl = $this->getAssetsUrl();

        /** @var string $mapsApiUrl */
        $mapsApiUrl = '//maps.googleapis.com/maps/api/js?v=3&sensor=false';

        if ($model->getTranslateMap()) {
            $mapsApiUrl .= '&language=' . app()->getLocale()->getLanguageID(app()->getLanguage());
        }

        $key = $model->getGoogleMapsApiKey();
        if (!empty($key)) {
            $mapsApiUrl .= '&key=' . $key;
        }

        $action->getController()->getData('pageScripts')->mergeWith([
            ['src' => $mapsApiUrl],
            // array('src' => '//rawgit.com/googlemaps/js-marker-clusterer/gh-pages/src/markerclusterer.js'),
            ['src' => $assetsUrl . '/markerclusterer.js'],
            ['src' => $assetsUrl . '/gmaps.min.js'],
            ['src' => $assetsUrl . '/maps.js'],
        ]);

        $action->getController()->getData('pageStyles')->add(['src' => $assetsUrl . '/gmaps.css']);
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     * @throws CException
     */
    public function _showMapView(CAttributeCollection $collection)
    {
        /** @var Controller $controller */
        $controller = $collection->itemAt('controller');

        /** @var Campaign $campaign */
        $campaign = $controller->getData('campaign');

        /** @var CampaignActivityMapExt $context */
        $context = $this;

        /** @var CampaignActivityMapExtCommon $model */
        $model = container()->get(CampaignActivityMapExtCommon::class);

        $controller->renderFile(dirname(__FILE__) . '/customer/views/map.php', compact('campaign', 'context', 'model'));
    }

    /**
     * @return string
     * @throws CException
     */
    public function getAssetsUrl(): string
    {
        static $assetsUrl;

        if ($assetsUrl !== null) {
            return $assetsUrl;
        }

        return $assetsUrl = assetManager()->publish(dirname(__FILE__) . '/customer/assets', false, -1, MW_DEBUG);
    }
}
