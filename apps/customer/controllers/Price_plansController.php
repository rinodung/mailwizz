<?php declare(strict_types=1);

use Dompdf\Dompdf;

if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Price_plansController
 *
 * Handles the actions for price plans related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class Price_plansController extends Controller
{
    /**
     * @return void
     * @throws CException
     */
    public function init()
    {
        /** @var OptionMonetizationMonetization $optionMonetizationMonetization */
        $optionMonetizationMonetization = container()->get(OptionMonetizationMonetization::class);

        if (!$optionMonetizationMonetization->getIsEnabled()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        // subaccounts do not have access here
        if (is_subaccount()) {
            $this->redirect(['dashboard/index']);
            return;
        }

        $this->addPageScript(['src' => AssetsUrl::js('price-plans.js')]);
        parent::init();
    }

    /**
     * List all available price plans
     *
     * @return void
     */
    public function actionIndex()
    {
        $session = session();
        $session->remove('payment_gateway');
        $session->remove('plan_uid');
        $session->remove('currency_code');
        $session->remove('promo_code');

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $paymentMethods = ['' => t('app', 'Choose')];
        $paymentMethods = (array)hooks()->applyFilters('customer_price_plans_payment_methods_dropdown', $paymentMethods);

        $criteria = new CDbCriteria();
        $criteria->compare('status', PricePlan::STATUS_ACTIVE);
        $criteria->compare('visible', PricePlan::TEXT_YES);
        $criteria->order = 'sort_order ASC, plan_id DESC';
        $pricePlans = PricePlan::model()->findAll($criteria);

        // 1.6.2 - filter out plans not meant for the group this customer is in
        foreach ($pricePlans as $index => $plan) {
            $relations = PricePlanCustomerGroupDisplay::model()->findAllByAttributes([
                'plan_id' => $plan->plan_id,
            ]);
            if (empty($relations)) {
                continue;
            }
            $unset = true;
            foreach ($relations as $relation) {
                if ($relation->group_id == $customer->group_id) {
                    $unset = false;
                    break;
                }
            }
            if ($unset) {
                unset($pricePlans[$index]);
            }
        }
        $pricePlans = array_values($pricePlans);
        //

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('price_plans', 'View price plans'),
            'pageHeading'     => t('price_plans', 'View price plans'),
            'pageBreadcrumbs' => [
                t('price_plans', 'Price plans') => createUrl('price_plans/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('pricePlans', 'customer', 'paymentMethods'));
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionOrders()
    {
        $order = new PricePlanOrder('customer-search');

        $order->unsetAttributes();
        $order->attributes = (array)request()->getQuery($order->getModelName(), []);
        $order->customer_id = (int)customer()->getId();

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('orders', 'View your orders'),
            'pageHeading'     => t('orders', 'View your orders'),
            'pageBreadcrumbs' => [
                t('price_plans', 'Price plans') => createUrl('price_plans/index'),
                t('orders', 'Orders') => createUrl('price_plans/orders'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('orders', compact('order'));
    }

    /**
     * @param string $order_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionOrder_detail($order_uid)
    {
        $order = PricePlanOrder::model()->findByAttributes([
            'order_uid'   => (string)$order_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($order)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $note = new PricePlanOrderNote('search');
        $note->unsetAttributes();
        $note->attributes = (array)request()->getQuery($note->getModelName(), []);
        $note->order_id   = (int)$order->order_id;

        $transaction = new PricePlanOrderTransaction('search');
        $transaction->unsetAttributes();
        $transaction->attributes = (array)request()->getQuery($transaction->getModelName(), []);
        $transaction->order_id   = (int)$order->order_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('orders', 'View your order'),
            'pageHeading'     => t('orders', 'View your order'),
            'pageBreadcrumbs' => [
                t('price_plans', 'Price plans') => createUrl('price_plans/index'),
                t('orders', 'Orders') => createUrl('price_plans/orders'),
                t('app', 'View'),
            ],
        ]);

        $this->render('order_detail', compact('order', 'note', 'transaction'));
    }

    /**
     * @param string $order_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionOrder_pdf($order_uid)
    {
        /** @var PricePlanOrder|null $order */
        $order = PricePlanOrder::model()->findByAttributes([
            'order_uid'   => (string)$order_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($order)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var PricePlan $pricePlan */
        $pricePlan = $order->plan;

        /** @var OptionMonetizationInvoices $invoiceOptions */
        $invoiceOptions = container()->get(OptionMonetizationInvoices::class);

        $html = $this->renderPartial('common.views.orders.invoice', [
            'order'          => $order,
            'invoiceOptions' => $invoiceOptions,
            'pricePlan'      => $pricePlan,
        ], true);

        // 1.8.4
        $html = (string)hooks()->applyFilters('price_plan_order_generate_pdf_invoice', $html, $order);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream('invoice-' . $order->order_uid . '.pdf', ['Attachment' => false]);
    }

    /**
     * @param string $order_uid
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionEmail_invoice($order_uid)
    {
        /** @var PricePlanOrder|null $order */
        $order = PricePlanOrder::model()->findByAttributes([
            'order_uid'   => (string)$order_uid,
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($order)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var Customer $customer */
        $customer = $order->customer;

        /** @var DeliveryServer|null $deliveryServer */
        $deliveryServer = DeliveryServer::pickServer(0, null, ['useFor' => [DeliveryServer::USE_FOR_INVOICES]]);

        if (empty($deliveryServer)) {
            notify()->addWarning(t('orders', 'Please try again later!'));
            $this->redirect(['price_plans/order_detail', 'order_uid' => $order_uid]);
            return;
        }

        /** @var OptionMonetizationInvoices $invoiceOptions */
        $invoiceOptions = container()->get(OptionMonetizationInvoices::class);
        $ref            = $order->getNumber();

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.invoices');
        if ((!file_exists($storagePath) || !is_dir($storagePath)) && !mkdir($storagePath, 0777, true)) {
            notify()->addWarning(t('orders', 'Unable to create the invoices storage directory!'));
            $this->redirect(['price_plans/order_detail', 'order_uid' => $order_uid]);
            return;
        }
        $invoicePath = $storagePath . '/' . preg_replace('/(\-){2,}/', '-', (string)preg_replace('/[^a-z0-9\-]+/i', '-', $ref)) . '.pdf';

        ob_start();
        toggle_ob_implicit_flush(false);
        $this->actionOrder_pdf($order_uid);
        $pdf = ob_get_clean();

        if (!file_put_contents($invoicePath, $pdf)) {
            notify()->addWarning(t('orders', 'Unable to create the invoice!'));
            $this->redirect(['price_plans/order_detail', 'order_uid' => $order_uid]);
            return;
        }

        if (!($emailSubject = $invoiceOptions->email_subject)) {
            $emailSubject = t('orders', 'Your requested invoice - {ref}', [
                '{ref}' => $ref,
            ]);
        }

        /** @var OptionCommon */
        $common = container()->get(OptionCommon::class);

        $params = CommonEmailTemplate::getAsParamsArrayBySlug(
            'order-invoice',
            [
                'to'          => [$customer->email => $customer->getFullName()],
                'subject'     => $emailSubject,
                'from_name'   => $common->getSiteName(),
                'attachments' => [$invoicePath],
            ],
            [
                '[CUSTOMER_NAME]' => $customer->getFullName(),
                '[REF]'           => $ref,
            ]
        );

        if ($emailBody = $invoiceOptions->email_content) {
            $params['body'] = nl2br($emailBody);
        }

        if ($deliveryServer->sendEmail($params)) {
            notify()->addSuccess(t('orders', 'The invoice has been successfully emailed!'));
        } else {
            notify()->addError(t('orders', 'Unable to email the invoice!'));
        }

        unlink($invoicePath);

        $this->redirect(['price_plans/order_detail', 'order_uid' => $order_uid]);
    }

    /**
     * @return void
     * @throws CException
     */
    public function actionPayment()
    {
        $session = session();

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $planUid        = request()->getPost('plan_uid', $session->itemAt('plan_uid'));
        $paymentGateway = request()->getPost('payment_gateway', $session->itemAt('payment_gateway'));

        if (empty($planUid) || empty($paymentGateway)) {
            $this->redirect(['price_plans/index']);
            return;
        }

        $extensionsManager = extensionsManager();
        $extensionInstance = $extensionsManager->getExtensionInstance('payment-gateway-' . $paymentGateway);

        if (empty($extensionInstance)) {
            notify()->addError(t('price_plans', 'Unable to load the payment gateway!'));
            $this->redirect(['price_plans/index']);
            return;
        }

        if (!method_exists($extensionInstance, 'getPaymentHandler')) {
            notify()->addError(t('price_plans', 'Invalid payment gateway setup!'));
            $this->redirect(['price_plans/index']);
            return;
        }

        $paymentHandler = $extensionInstance->getPaymentHandler();
        if (!is_object($paymentHandler) || !($paymentHandler instanceof PaymentHandlerAbstract)) {
            notify()->addError(t('price_plans', 'Invalid payment gateway setup!'));
            $this->redirect(['price_plans/index']);
            return;
        }
        $paymentHandler->controller = $this;
        $paymentHandler->extension  = $extensionInstance;

        $pricePlan = PricePlan::model()->findByAttributes([
            'plan_uid' => $planUid,
            'status'   => PricePlan::STATUS_ACTIVE,
        ]);

        if (empty($pricePlan)) {
            notify()->addError(t('price_plans', 'The specified price plan is invalid!'));
            $this->redirect(['price_plans/index']);
            return;
        }

        // since 1.3.6.2
        $in = $customer->isOverPricePlanLimits($pricePlan);
        if ($in->itemat('overLimit') === true) {
            $reason = t('price_plans', 'Selected price plan allows {n} {w} but you already have {m}, therefore you cannot apply for the plan!', [
                '{n}' => $in->itemAt('limit'),
                '{w}' => t('price_plans', (string)$in->itemAt('object')),
                '{m}' => $in->itemAt('count'),
            ]);
            notify()->addError($reason);
            $this->redirect(['price_plans/index']);
            return;
        }

        /** @var Currency|null $currency */
        $currency = Currency::model()->findDefault();

        if (empty($currency)) {
            notify()->addError(t('price_plans', 'Unable to set a correct currency!'));
            $this->redirect(['price_plans/index']);
            return;
        }

        $session->add('payment_gateway', $paymentGateway);
        $session->add('plan_uid', $pricePlan->plan_uid);
        $session->add('currency_code', $currency->code);

        /** @var string $promoCode */
        $promoCode = (string)$session->itemAt('promo_code');

        /** @var PricePlanPromoCode|null $promoCodeModel */
        $promoCodeModel = null;
        if (!empty($promoCode)) {

            /** @var PricePlanPromoCode|null $promoCodeModel */
            $promoCodeModel = PricePlanPromoCode::model()->findByAttributes(['code' => $promoCode]);
        }

        $note = new PricePlanOrderNote();

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $this->setData([
            'extension'         => $extensionInstance,
            'customer'          => $customer,
            'paymentGateway'    => $paymentGateway,
            'paymentHandler'    => $paymentHandler,
            'promoCode'         => $promoCode,
            'note'              => $note,
        ]);

        $order = new PricePlanOrder();
        $order->customer_id     = (int)$customer->customer_id;
        $order->plan_id         = (int)$pricePlan->plan_id;
        $order->promo_code_id   = !empty($promoCodeModel) ? $promoCodeModel->promo_code_id : null;
        $order->currency_id     = (int)$currency->currency_id;

        $order->addRelatedRecord('customer', $customer, false);
        $order->addRelatedRecord('plan', $pricePlan, false);
        $order->addRelatedRecord('currency', $currency, false);
        if ($order->promo_code_id && !empty($promoCodeModel)) {
            $order->addRelatedRecord('promoCode', $promoCodeModel, false);
        }

        $this->setData('order', $order->calculate());

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('price_plans', 'Price plans payment'),
            'pageHeading'     => t('price_plans', 'Price plans payment'),
            'pageBreadcrumbs' => [
                t('price_plans', 'Price plans') => createUrl('price_plans/index'),
                t('app', 'Payment'),
            ],
        ]);

        $this->render('payment');
    }

    /**
     * @return void
     * @throws CException
     * @throws ReflectionException
     */
    public function actionOrder()
    {
        $session = session();

        if (!request()->getIsPostRequest()) {
            $this->redirect(['price_plans/payment']);
            return;
        }

        if (!$session->contains('payment_gateway') || !$session->contains('plan_uid') || !$session->contains('currency_code')) {
            $message = t('price_plans', 'Unable to load payment data!');
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['price_plans/payment']);
            return;
        }

        $paymentGateway = $session->itemAt('payment_gateway');
        $planUid        = $session->itemAt('plan_uid');
        $currencyCode   = $session->itemAt('currency_code');
        $promoCode      = $session->itemAt('promo_code');

        $extensionsManager = extensionsManager();
        $extensionInstance = $extensionsManager->getExtensionInstance('payment-gateway-' . $paymentGateway);

        if (empty($extensionInstance)) {
            $message = t('price_plans', 'Unable to load the payment gateway!');
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['price_plans/payment']);
            return;
        }

        if (!method_exists($extensionInstance, 'getPaymentHandler')) {
            $message = t('price_plans', 'Invalid payment gateway setup!');
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['price_plans/payment']);
            return;
        }

        $reflection = new ReflectionMethod($extensionInstance, 'getPaymentHandler');
        if (!$reflection->isPublic()) {
            $message = t('price_plans', 'Invalid payment gateway setup!');
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['price_plans/payment']);
            return;
        }

        $paymentHandler = $extensionInstance->getPaymentHandler();
        if (!is_object($paymentHandler) || !($paymentHandler instanceof PaymentHandlerAbstract)) {
            $message = t('price_plans', 'Invalid payment gateway setup!');
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['price_plans/payment']);
            return;
        }
        $paymentHandler->controller = $this;
        $paymentHandler->extension  = $extensionInstance;

        /** @var PricePlan|null $pricePlan */
        $pricePlan = PricePlan::model()->findByUid((string)$planUid);

        if (empty($pricePlan)) {
            $message = t('price_plans', 'The specified price plan is invalid!');
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['price_plans/payment']);
            return;
        }

        /** @var Currency|null $currency */
        $currency = Currency::model()->findByCode((string)$currencyCode);

        if (empty($currency)) {
            $message = t('price_plans', 'Invalid currency specified!');
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['price_plans/payment']);
            return;
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        $order       = new PricePlanOrder();
        $transaction = new PricePlanOrderTransaction();

        $note             = new PricePlanOrderNote();
        $note->attributes = (array)request()->getPost($note->getModelName(), []);

        $order->customer_id = (int)$customer->customer_id;
        $order->plan_id     = (int)$pricePlan->plan_id;
        $order->currency_id = (int)$currency->currency_id;

        $order->addRelatedRecord('customer', $customer, false);
        $order->addRelatedRecord('plan', $pricePlan, false);
        $order->addRelatedRecord('currency', $currency, false);

        if (!empty($promoCode)) {
            $promoCodeModel = PricePlanPromoCode::model()->findByAttributes(['code' => $promoCode]);
            if (!empty($promoCodeModel)) {
                $order->promo_code_id = (int)$promoCodeModel->promo_code_id;
                $order->addRelatedRecord('promoCode', $promoCodeModel, false);
            }
        }

        $this->setData([
            'extension'         => $extensionInstance,
            'customer'          => $customer,
            'paymentGateway'    => $paymentGateway,
            'paymentHandler'    => $paymentHandler,
            'promoCode'         => $promoCode,
            'pricePlan'         => $pricePlan,
            'currency'          => $currency,
            'order'             => $order,
            'transaction'       => $transaction,
            'note'              => $note,
        ]);

        if (!$order->calculate()->save(false)) {
            $message = t('price_plans', 'Cannot save your order!');
            if (request()->getIsAjaxRequest()) {
                $this->renderJson([
                    'result'  => 'error',
                    'message' => $message,
                ]);
                return;
            }
            notify()->addError($message);
            $this->redirect(['price_plans/payment']);
            return;
        }

        $transaction->order_id             = (int)$order->order_id;
        $transaction->payment_gateway_name = $paymentGateway;
        $transaction->save(false);

        $note->order_id = (int)$order->order_id;
        if (!empty($note->note)) {
            $note->customer_id = (int)$order->customer_id;
            $note->save();
        }

        $order->onAfterSave = [PricePlanOrder::class, 'sendNewOrderNotificationsEvent'];

        hooks()->doAction('customer_price_plans_before_payment_handler_process_order', $this);

        $paymentHandler->processOrder();

        hooks()->doAction('customer_price_plans_after_payment_handler_process_order', $this);

        if (request()->getIsAjaxRequest()) {
            $this->renderJson([]);
            return;
        }
        $this->redirect(['price_plans/index']);
    }

    /**
     * Promo code
     *
     * @return void
     * @throws CException
     */
    public function actionPromo()
    {
        $session = session();
        $session->remove('promo_code');

        if (!request()->getIsPostRequest()) {
            $this->redirect(['price_plans/payment']);
            return;
        }

        if (!$session->contains('plan_uid')) {
            $this->redirect(['price_plans/payment']);
            return;
        }

        $promoCode = request()->getPost('promo_code', '');
        if (empty($promoCode)) {
            $this->redirect(['price_plans/payment']);
            return;
        }

        $criteria = new CDbCriteria();
        $criteria->compare('code', $promoCode);
        $criteria->compare('status', PricePlanPromoCode::STATUS_ACTIVE);
        $criteria->addCondition('date_start <= NOW() AND date_end >= NOW()');

        /** @var PricePlanPromoCode|null $promoCodeModel */
        $promoCodeModel = PricePlanPromoCode::model()->find($criteria);

        if (empty($promoCodeModel)) {
            notify()->addError(t('price_plans', 'The provided promotional code does not exists anymore!'));
            $this->redirect(['price_plans/payment']);
            return;
        }

        /** @var string $planUid */
        $planUid = $session->itemAt('plan_uid');

        /** @var PricePlan|null $pricePlan */
        $pricePlan = PricePlan::model()->findByUid($planUid);

        if (empty($pricePlan)) {
            $this->redirect(['price_plans/payment']);
            return;
        }

        if ($promoCodeModel->total_amount > 0 && $pricePlan->price < $promoCodeModel->total_amount) {
            notify()->addError(t('price_plans', 'This promo code requires that select a price plan that costs at least {amount}!', [
                '{amount}' => $promoCodeModel->getFormattedTotalAmount(),
            ]));
            $this->redirect(['price_plans/payment']);
            return;
        }

        /** @var Customer $customer */
        $customer = customer()->getModel();

        if ($promoCodeModel->customer_usage > 0) {
            $usedByThisCustomer = PricePlanOrder::model()->countByAttributes([
                'promo_code_id' => $promoCodeModel->promo_code_id,
                'customer_id'   => $customer->customer_id,
            ]);
            if ($usedByThisCustomer >= $promoCodeModel->customer_usage) {
                notify()->addError(t('price_plans', 'You have reached the maximum usage times for this promo code!'));
                $this->redirect(['price_plans/payment']);
                return;
            }
        }

        if ($promoCodeModel->total_usage > 0) {
            $usedTimes = PricePlanOrder::model()->countByAttributes([
                'promo_code_id' => $promoCodeModel->promo_code_id,
            ]);
            if ($usedTimes >= $promoCodeModel->total_usage) {
                notify()->addError(t('price_plans', 'This promo code has reached the maximum usage times!'));
                $this->redirect(['price_plans/payment']);
                return;
            }
        }
        $session->add('promo_code', $promoCodeModel->code);

        notify()->addSuccess(t('price_plans', 'The promo code has been successfully applied!'));
        $this->redirect(['price_plans/payment']);
    }

    /**
     * Export
     *
     * @return void
     */
    public function actionOrders_export()
    {
        $models = PricePlanOrder::model()->findAllByAttributes([
            'customer_id' => (int)customer()->getId(),
        ]);

        if (empty($models)) {
            notify()->addError(t('app', 'There is no item available for export!'));
            $this->redirect(['index']);
            return;
        }

        // Set the download headers
        HeaderHelper::setDownloadHeaders('price-plans-orders.csv');

        $attributes = AttributeHelper::removeSpecialAttributes($models[0]->attributes);

        /** @var callable $callback */
        $callback   = [$models[0], 'getAttributeLabel'];
        $attributes = array_map($callback, array_keys($attributes));

        $attributes = CMap::mergeArray($attributes, [
            $models[0]->getAttributeLabel('plan_id'),
            $models[0]->getAttributeLabel('currency_id'),
        ]);

        try {
            $csvWriter = League\Csv\Writer::createFromPath('php://output', 'w');
            $csvWriter->insertOne($attributes);

            foreach ($models as $model) {
                $attributes = AttributeHelper::removeSpecialAttributes($model->attributes);
                $attributes = CMap::mergeArray($attributes, [
                    'plan'      => $model->plan_id ? $model->plan->name : '',
                    'currency'  => $model->currency_id ? $model->currency->name : '',
                ]);
                $csvWriter->insertOne(array_values($attributes));
            }
        } catch (Exception $e) {
        }

        app()->end();
    }
}
