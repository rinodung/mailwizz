<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * PaymentGatewayOfflineExtPaymentHandler
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class PaymentGatewayOfflineExtPaymentHandler extends PaymentHandlerAbstract
{
    /**
     * @return void
     * @throws CException
     */
    public function renderPaymentView()
    {
        /** @var PaymentGatewayOfflineExtCommon $model */
        $model = container()->get(PaymentGatewayOfflineExtCommon::class);

        /** @var ExtensionInit $extension */
        $extension = $this->extension;

        /** @var string $view */
        $view = $extension->getPathAlias('customer.views.payment-form');

        $this->controller->renderPartial($view, compact('model', 'extension'));
    }

    /**
     * Validate the data and process the order
     *
     * @return void
     */
    public function processOrder()
    {
        /** @var PricePlanOrderTransaction $transaction */
        $transaction = $this->controller->getData('transaction');

        /** @var PricePlanOrder $order */
        $order = $this->controller->getData('order');

        $order->status = PricePlanOrder::STATUS_DUE;
        $order->save(false);

        $transaction->payment_gateway_name           = $this->extension->t('Offline payment');
        $transaction->payment_gateway_transaction_id = StringHelper::randomSha1();
        $transaction->status                         = PricePlanOrderTransaction::STATUS_SUCCESS;
        $transaction->save(false);

        $message = $this->extension->t('Your order is in "{status}" status, once it gets approved, your pricing plan will become active!', [
            '{status}' => t('orders', $order->status),
        ]);
        notify()->addInfo($message);

        // the order is not complete, so return false
        $this->controller->redirect(['price_plans/index']);
    }
}
