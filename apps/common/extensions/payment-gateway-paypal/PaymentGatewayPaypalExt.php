<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Payment gateway - Paypal
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class PaymentGatewayPaypalExt extends ExtensionInit
{
    /**
     * @var string
     */
    public $name = 'Payment gateway - Paypal';

    /**
     * @var string
     */
    public $description = 'Retrieve payments using paypal';

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
        container()->add(PaymentGatewayPaypalExtCommon::class, PaymentGatewayPaypalExtCommon::class);

        /** @var PaymentGatewayPaypalExtCommon $model */
        $model = container()->get(PaymentGatewayPaypalExtCommon::class);

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
        if (isset($registeredGateways['paypal'])) {
            return $registeredGateways;
        }

        /** @var PaymentGatewayPaypalExtCommon $model */
        $model = container()->get(PaymentGatewayPaypalExtCommon::class);

        $registeredGateways['paypal'] = [
            'id'            => 'paypal',
            'name'          => $this->t('Paypal'),
            'description'   => $this->t('Retrieve payments using paypal'),
            'status'        => $this->getOption('status', 'disabled'),
            'sort_order'    => $model->getSortOrder(),
            'page_url'      => $this->getPageUrl(),
        ];

        return $registeredGateways;
    }

    /**
     * This replacement is needed to avoid csrf token validation and other errors
     *
     * @return void
     */
    public function validateCsrfToken()
    {
        request()->enableCsrfValidation = false;
    }

    /**
     * @return void
     * @throws CException
     */
    public function registerCustomerAssets()
    {
        $assetsUrl = assetManager()->publish(dirname(__FILE__) . '/customer/assets', false, -1, MW_DEBUG);
        clientScript()->registerScriptFile($assetsUrl . '/js/payment-form.js');
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
            'class' => $this->getPathAlias('customer.components.utils.PaymentGatewayPaypalExtPaymentHandler'),
        ]);
    }

    /**
     * @param array $paymentMethods
     *
     * @return array
     */
    public function _registerGatewayInCustomerDropDown(array $paymentMethods)
    {
        if (isset($paymentMethods['paypal'])) {
            return $paymentMethods;
        }
        $paymentMethods['paypal'] = $this->t('Paypal');

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
            ['settings/index', 'pattern' => 'payment-gateways/paypal/settings'],
            ['settings/<action>', 'pattern' => 'payment-gateways/paypal/settings/*'],
        ]);

        $this->addControllerMap([
            'settings' => [
                'class' => 'backend.controllers.PaymentGatewayPaypalExtBackendSettingsController',
            ],
        ]);

        // register the gateway in the list of available gateways.
        hooks()->addFilter('backend_payment_gateways_display_list', [$this, '_registerGatewayForBackendDisplay']);
    }

    /**
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    protected function customerApp()
    {
        // import the utils
        $this->importClasses('customer.components.utils.*');

        $this->addUrlRules([
            ['ipn/index', 'pattern' => 'payment-gateways/paypal/ipn'],
        ]);

        $this->addControllerMap([
            'ipn' => [
                'class' => 'customer.controllers.PaymentGatewayPaypalExtCustomerIpnController',
            ],
        ]);

        // set the controller unprotected so paypal can post freely
        $unprotected = (array)app_param('unprotectedControllers', []);
        array_push($unprotected, $this->getRoute('ipn'));
        app_param_set('unprotectedControllers', $unprotected);

        // remove the csrf token validation
        if (request()->getIsPostRequest() && request()->enableCsrfValidation) {
            $url    = urlManager()->parseUrl(request());
            $routes = ['price_plans', $this->getRoute('ipn/index')];

            foreach ($routes as $route) {
                if (strpos($url, $route) === 0) {
                    app()->detachEventHandler('onBeginRequest', [request(), 'validateCsrfToken']);
                    app()->attachEventHandler('onBeginRequest', [$this, 'validateCsrfToken']);
                    break;
                }
            }
        }

        // hook into drop down list and add the paypal option
        hooks()->addFilter('customer_price_plans_payment_methods_dropdown', [$this, '_registerGatewayInCustomerDropDown']);
    }
}
