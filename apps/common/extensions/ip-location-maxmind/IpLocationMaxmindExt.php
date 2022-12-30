<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Ip location - MaxMind DB
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class IpLocationMaxmindExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Ip location - MaxMind.com';

    /**
     * @var string
     */
    public $description = 'Retrieve ip location data using GeoLite2 database created by MaxMind.com';

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
    public $allowedApps = ['frontend', 'backend', 'customer'];

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
        container()->add(IpLocationMaxmindExtCommon::class, IpLocationMaxmindExtCommon::class);

        /** @var IpLocationMaxmindExtCommon $model */
        $model = container()->get(IpLocationMaxmindExtCommon::class);

        // register the extension page route and controller only if backend
        if ($this->isAppName('backend')) {
            $this->addUrlRules([
                ['settings/index', 'pattern' => 'ip-location-services/maxmind/settings'],
                ['settings/<action>', 'pattern' => 'ip-location-services/maxmind/settings/*'],
            ]);

            $this->addControllerMap([
                'settings' => [
                    'class' => 'backend.controllers.IpLocationMaxmindExtBackendSettingsController',
                ],
            ]);

            // register the service in the list of available services.
            hooks()->addFilter('backend_ip_location_services_display_list', [$this, '_registerServiceForDisplay']);
        } elseif ($this->isAppName('frontend')) {

            // register the hooks
            if ($model->getIsEnabled()) {

                // track email opens if allowed
                if ($model->getIsEnabledOnEmailOpen()) {
                    hooks()->addAction('frontend_campaigns_after_track_opening', [$this, '_registerServiceForSavingLocation'], (int)$this->getOption('sort_order', 0));
                }

                // track url clicks if allowed
                if ($model->getIsEnabledOnTrackUrl()) {
                    hooks()->addAction('frontend_campaigns_after_track_url', [$this, '_registerServiceForSavingLocation'], (int)$this->getOption('sort_order', 0));
                }

                // track unsubscribes if allowed
                if ($model->getIsEnabledOnUnsubscribe()) {
                    hooks()->addAction('frontend_lists_after_track_campaign_unsubscribe', [$this, '_registerServiceForSavingLocation'], (int)$this->getOption('sort_order', 0));
                }
            }
        } elseif ($this->isAppName('customer')) {

            // register the hooks
            if ($model->getIsEnabled()) {

                // track customer login
                if ($model->getIsEnabledOnCustomerLogin()) {
                    hooks()->addAction('customer_login_log_add_new_before_save', [$this, '_trackCustomerLoginLog']);
                }
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
     * @param array $registeredServices
     *
     * @return array
     */
    public function _registerServiceForDisplay(array $registeredServices = [])
    {
        if (isset($registeredServices['maxmind'])) {
            return $registeredServices;
        }

        $registeredServices['maxmind'] = [
            'id'            => 'maxmind',
            'name'          => $this->t('MaxMind.com'),
            'description'   => $this->t('Offers IP location based on GeoLite2 database created by MaxMind'),
            'status'        => $this->getOption('status', 'disabled'),
            'sort_order'    => (int)$this->getOption('sort_order', 0),
            'page_url'      => $this->getPageUrl(),
        ];

        return $registeredServices;
    }

    /**
     * @param Controller $controller
     * @param mixed $trackModel
     *
     * @return void
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function _registerServiceForSavingLocation(Controller $controller, $trackModel)
    {
        // if the ip data has been saved already, don't bother.
        // @phpstan-ignore-next-line
        if ($controller->getData('ipLocationSaved') || !empty($trackModel->location_id) || empty($trackModel->id)) {
            return;
        }

        /** @var ActiveRecord|null $model */
        $model = null;

        /** @var string $ipAddress */
        $ipAddress = '';

        /** @var int $trackModelId */
        $trackModelId = 0;

        if ($trackModel instanceof CampaignTrackOpen) {

            /** @var CampaignTrackOpen $model */
            $model = CampaignTrackOpen::model();

            /** @var CampaignTrackOpen $track */
            $track = $trackModel;

            /** @var string $ipAddress */
            $ipAddress = $track->ip_address;

            /** @var int $trackModelId */
            $trackModelId = (int)$track->id;
        } elseif ($trackModel instanceof CampaignTrackUrl) {

            /** @var CampaignTrackUrl $model */
            $model = CampaignTrackUrl::model();

            /** @var CampaignTrackUrl $track */
            $track = $trackModel;

            /** @var string $ipAddress */
            $ipAddress = $track->ip_address;

            /** @var int $trackModelId */
            $trackModelId = (int)$track->id;
        } elseif ($trackModel instanceof CampaignTrackUnsubscribe) {

            /** @var CampaignTrackUnsubscribe $model */
            $model = CampaignTrackUnsubscribe::model();

            /** @var CampaignTrackUnsubscribe $track */
            $track = $trackModel;

            /** @var string $ipAddress */
            $ipAddress = $track->ip_address;

            /** @var int $trackModelId */
            $trackModelId = (int)$track->id;
        }

        if (empty($model)) {
            return;
        }

        /** @var IpLocation|null $location */
        $location = IpLocation::findByIp($ipAddress);
        if (empty($location) || empty($location->location_id)) {
            return;
        }

        $model->updateByPk($trackModelId, [
            'location_id' => $location->location_id,
        ]);

        $controller->setData('ipLocationSaved', true);
    }

    /**
     * @param CAttributeCollection $collection
     *
     * @return void
     * @throws MaxMind\Db\Reader\InvalidDatabaseException
     */
    public function _trackCustomerLoginLog(CAttributeCollection $collection)
    {
        /** @var CustomerLoginLog $model */
        $model = $collection->itemAt('model');

        if (empty($model->ip_address) ||
            !FilterVarHelper::ip($model->ip_address) ||
            !empty($model->location_id)) {
            return;
        }

        $location = IpLocation::findByIp($model->ip_address);
        if (!empty($location) && !empty($location->location_id)) {
            $model->location_id = (int)$location->location_id;
        }
    }
}
