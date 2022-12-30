<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PaymentGatewayPaypalExtPaymentHandler
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class PaymentGatewayPaypalExtPaymentHandler extends PaymentHandlerAbstract
{
    /**
     * @return void
     * @throws CException
     */
    public function renderPaymentView()
    {
        /** @var PricePlanOrder $order */
        $order = $this->controller->getData('order');

        /** @var PaymentGatewayPaypalExtCommon $model */
        $model = container()->get(PaymentGatewayPaypalExtCommon::class);

        /** @var CustomerCompany $company */
        $company = !empty($order->customer->company) ? $order->customer->company : null;

        /** @var ExtensionInit $extension */
        $extension = $this->extension;

        $cancelUrl = createAbsoluteUrl('price_plans/index');
        $returnUrl = createAbsoluteUrl('price_plans/index');
        $notifyUrl = $extension->createAbsoluteUrl('ipn/index');

        $assetsUrl = assetManager()->publish($extension->getPathOfAlias('customer.assets'), false, -1, MW_DEBUG);
        clientScript()->registerScriptFile($assetsUrl . '/js/payment-form.js');

        $customVars = StringHelper::randomSha1();
        $view       = $extension->getPathAlias('customer.views.payment-form');

        $this->controller->renderPartial($view, compact('model', 'order', 'company', 'extension', 'cancelUrl', 'returnUrl', 'notifyUrl', 'customVars'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function processOrder()
    {
        if (strlen((string)request()->getPost('custom', '')) != 40) {
            return;
        }

        /** @var PricePlanOrderTransaction $transaction */
        $transaction = $this->controller->getData('transaction');

        /** @var PricePlanOrder $order */
        $order = $this->controller->getData('order');

        $order->status = PricePlanOrder::STATUS_PENDING;
        $order->save(false);

        $transaction->payment_gateway_name           = 'Paypal - www.paypal.com';
        $transaction->payment_gateway_transaction_id = (string)request()->getPost('custom', '');
        $transaction->status                         = PricePlanOrderTransaction::STATUS_PENDING_RETRY;
        $transaction->save(false);

        $message = $this->extension->t('Your order is in "{status}" status, it usually takes a few minutes to be processed and if everything is fine, your pricing plan will become active!', [
            '{status}' => t('orders', $order->status),
        ]);

        if (request()->getIsAjaxRequest()) {
            $this->controller->renderJson([
                'result'  => 'success',
                'message' => $message,
            ]);
        }

        notify()->addInfo($message);
        $this->controller->redirect(['price_plans/index']);
    }
}
