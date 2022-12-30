<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Payment gateway - Offline
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class PaymentGatewayOfflineExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Payment gateway - Offline';

    /**
     * @var string
     */
    public $description = 'Retrieve payments offline';

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
    public $allowedApps = ['customer', 'backend'];

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
        container()->add(PaymentGatewayOfflineExtCommon::class, PaymentGatewayOfflineExtCommon::class);

        /** @var PaymentGatewayOfflineExtCommon $model */
        $model = container()->get(PaymentGatewayOfflineExtCommon::class);

        if ($this->isAppName('backend')) {

            // handle all backend related tasks
            $this->backendApp();
        } elseif ($this->isAppName('customer') && $model->getIsEnabled()) {

            // handle all customer related tasks
            $this->customerApp();
        }
    }

    /**
     * @inheritDoc
     */
    public function getPageUrl()
    {
        return $this->createUrl('settings/index');
    }

    /**
     * @param array $registeredGateways
     *
     * @return array
     */
    public function _registerGatewayForBackendDisplay(array $registeredGateways = [])
    {
        if (isset($registeredGateways['offline'])) {
            return $registeredGateways;
        }

        /** @var PaymentGatewayOfflineExtCommon $model */
        $model = container()->get(PaymentGatewayOfflineExtCommon::class);

        $registeredGateways['offline'] = [
            'id'            => 'offline',
            'name'          => $this->t('Offline'),
            'description'   => $this->t('Retrieve payments offline'),
            'status'        => $this->getOption('status', 'disabled'),
            'sort_order'    => $model->getSortOrder(),
            'page_url'      => $this->getPageUrl(),
        ];

        return $registeredGateways;
    }

    /**
     * This is called by the customer app to process the payment
     * must be implemented by all payment gateways
     *
     * @return mixed
     * @throws CException
     */
    public function getPaymentHandler()
    {
        return Yii::createComponent([
            'class' => $this->getPathAlias('customer.components.utils.PaymentGatewayOfflineExtPaymentHandler'),
        ]);
    }

    /**
     * @param array $paymentMethods
     *
     * @return array
     */
    public function _registerGatewayInCustomerDropDown(array $paymentMethods)
    {
        if (isset($paymentMethods['offline'])) {
            return $paymentMethods;
        }
        $paymentMethods['offline'] = $this->t('Offline payment');

        return $paymentMethods;
    }

    /**
     * Handle all backend related tasks
     *
     * @return void
     */
    protected function backendApp()
    {
        $this->addUrlRules([
            ['settings/index', 'pattern' => 'payment-gateways/offline/settings'],
            ['settings/<action>', 'pattern' => 'payment-gateways/offline/settings/*'],
        ]);

        $this->addControllerMap([
            'settings' => [
                'class' => 'backend.controllers.PaymentGatewayOfflineExtSettingsController',
            ],
        ]);

        // register the gateway in the list of available gateways.
        hooks()->addFilter('backend_payment_gateways_display_list', [$this, '_registerGatewayForBackendDisplay']);
    }

    /**
     * @return void
     */
    protected function customerApp()
    {
        // import the utils
        $this->importClasses('customer.components.utils.*');

        // hook into drop down list and add the offline option
        hooks()->addFilter('customer_price_plans_payment_methods_dropdown', [$this, '_registerGatewayInCustomerDropDown']);
    }
}
