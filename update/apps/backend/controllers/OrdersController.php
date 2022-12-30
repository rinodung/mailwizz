<?php declare(strict_types=1);

use Dompdf\Dompdf;

if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * OrdersController
 *
 * Handles the actions for price plans orders related tasks
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.3
 */

class OrdersController extends Controller
{
    /**
     * @return void
     */
    public function init()
    {
        $this->onBeforeAction = [$this, '_registerJuiBs'];
        parent::init();
    }

    /**
     * @return array
     */
    public function filters()
    {
        $filters = [
            'postOnly + delete, delete_note',
        ];

        return CMap::mergeArray($filters, parent::filters());
    }

    /**
     * List all available orders
     *
     * @return void
     * @throws CException
     */
    public function actionIndex()
    {
        $order = new PricePlanOrder('search');
        $order->unsetAttributes();
        $order->attributes = (array)ioFilter()->xssClean((array)request()->getOriginalQuery($order->getModelName(), []));

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('orders', 'View orders'),
            'pageHeading'     => t('orders', 'View orders'),
            'pageBreadcrumbs' => [
                t('orders', 'Orders') => createUrl('orders/index'),
                t('app', 'View all'),
            ],
        ]);

        $this->render('list', compact('order'));
    }

    /**
     * Create order
     *
     * @return void
     * @throws CException
     */
    public function actionCreate()
    {
        $order = new PricePlanOrder();

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($order->getModelName(), []))) {
            $order->attributes = $attributes;
            if (!$order->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                $note = new PricePlanOrderNote();
                $note->attributes = (array)request()->getPost($note->getModelName(), []);
                $note->order_id   = (int)$order->order_id;
                $note->user_id    = user()->getId();
                $note->save();

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'order'     => $order,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['orders/index']);
            }
        }

        $note = new PricePlanOrderNote('search');
        $note->attributes = (array)request()->getQuery($note->getModelName(), []);
        $note->order_id   = (int)$order->order_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('orders', 'Create order'),
            'pageHeading'     => t('orders', 'Create order'),
            'pageBreadcrumbs' => [
                t('orders', 'Orders') => createUrl('orders/index'),
                t('app', 'Create'),
            ],
        ]);

        $this->render('form', compact('order', 'note'));
    }

    /**
     * Update existing order
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate($id)
    {
        $order = PricePlanOrder::model()->findByPk((int)$id);

        if (empty($order)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        if (request()->getIsPostRequest() && ($attributes = (array)request()->getPost($order->getModelName(), []))) {
            $order->attributes = $attributes;
            if (!$order->save()) {
                notify()->addError(t('app', 'Your form has a few errors, please fix them and try again!'));
            } else {
                $note = new PricePlanOrderNote();
                $note->attributes = (array)request()->getPost($note->getModelName(), []);
                $note->order_id   = (int)$order->order_id;
                $note->user_id    = user()->getId();
                $note->save();

                notify()->addSuccess(t('app', 'Your form has been successfully saved!'));
            }

            hooks()->doAction('controller_action_save_data', $collection = new CAttributeCollection([
                'controller'=> $this,
                'success'   => notify()->getHasSuccess(),
                'order'     => $order,
            ]));

            if ($collection->itemAt('success')) {
                $this->redirect(['orders/index']);
            }
        }

        $note = new PricePlanOrderNote('search');
        $note->attributes = (array)request()->getQuery($note->getModelName(), []);
        $note->order_id   = (int)$order->order_id;

        $this->setData([
            'pageMetaTitle'   => $this->getData('pageMetaTitle') . ' | ' . t('orders', 'Update order'),
            'pageHeading'     => t('orders', 'Update order'),
            'pageBreadcrumbs' => [
                t('orders', 'Orders') => createUrl('orders/index'),
                t('app', 'Update'),
            ],
        ]);

        $this->render('form', compact('order', 'note'));
    }

    /**
     * View order
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionView($id)
    {
        $order = PricePlanOrder::model()->findByPk((int)$id);

        if (empty($order)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $pricePlan = $order->plan;
        $customer  = $order->customer;

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
                t('orders', 'Orders') => createUrl('orders/index'),
                t('app', 'View'),
            ],
        ]);

        $this->render('order_detail', compact('order', 'pricePlan', 'customer', 'note', 'transaction'));
    }

    /**
     * View order in PDF format
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionPdf($id)
    {
        /** @var PricePlanOrder|null $order */
        $order = PricePlanOrder::model()->findByPk((int)$id);

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
        $dompdf->stream('invoice-' . $order->getNumber() . '.pdf', ['Attachment' => false]);
    }

    /**
     * Email the invoice
     *
     * @param int $id
     *
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionEmail_invoice($id)
    {
        /** @var PricePlanOrder|null $order */
        $order = PricePlanOrder::model()->findByPk((int)$id);

        if (empty($order)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        /** @var Customer $customer */
        $customer = $order->customer;

        /** @var DeliveryServer|null $deliveryServer */
        $deliveryServer = DeliveryServer::pickServer(0, null, ['useFor' => [DeliveryServer::USE_FOR_INVOICES]]);

        if (empty($deliveryServer)) {
            notify()->addWarning(t('orders', 'Please try again later!'));
            $this->redirect(['orders/view', 'id' => $id]);
            return;
        }

        /** @var OptionMonetizationInvoices $invoiceOptions */
        $invoiceOptions = container()->get(OptionMonetizationInvoices::class);
        $ref            = $order->getNumber();

        $storagePath = (string)Yii::getPathOfAlias('root.frontend.assets.files.invoices');
        if ((!file_exists($storagePath) || !is_dir($storagePath)) && !mkdir($storagePath, 0777, true)) {
            notify()->addWarning(t('orders', 'Unable to create the invoices storage directory!'));
            $this->redirect(['orders/view', 'id' => $id]);
        }
        $invoicePath = $storagePath . '/' . preg_replace('/(\-){2,}/', '-', (string)preg_replace('/[^a-z0-9\-]+/i', '-', $ref)) . '.pdf';

        ob_start();
        toggle_ob_implicit_flush(false);
        $this->actionPdf($id);
        $pdf = ob_get_clean();

        if (!file_put_contents($invoicePath, $pdf)) {
            notify()->addWarning(t('orders', 'Unable to create the invoice!'));
            $this->redirect(['orders/view', 'id' => $id]);
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

        $this->redirect(['orders/view', 'id' => $id]);
    }

    /**
     * Delete existing order
     *
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete($id)
    {
        $order = PricePlanOrder::model()->findByPk((int)$id);

        if (empty($order)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $order->delete();

        $redirect = null;
        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $redirect = request()->getPost('returnUrl', ['orders/index']);
        }

        // since 1.3.5.9
        hooks()->doAction('controller_action_delete_data', $collection = new CAttributeCollection([
            'controller' => $this,
            'model'      => $order,
            'redirect'   => $redirect,
        ]));

        if ($collection->itemAt('redirect')) {
            $this->redirect($collection->itemAt('redirect'));
        }
    }

    /**
     * Delete existing order note
     *
     * @param int $id
     *
     * @return void
     * @throws CDbException
     * @throws CException
     * @throws CHttpException
     */
    public function actionDelete_note($id)
    {
        $note = PricePlanOrderNote::model()->findByPk((int)$id);

        if (empty($note)) {
            throw new CHttpException(404, t('app', 'The requested page does not exist.'));
        }

        $note->delete();

        if (!request()->getQuery('ajax')) {
            notify()->addSuccess(t('app', 'The item has been successfully deleted!'));
            $this->redirect(request()->getPost('returnUrl', ['orders/index']));
        }
    }

    /**
     * @param CEvent $event
     *
     * @return void
     */
    public function _registerJuiBs(CEvent $event)
    {
        if (in_array($event->params['action']->id, ['create', 'update'])) {
            $this->addPageStyles([
                ['src' => apps()->getBaseUrl('assets/css/jui-bs/jquery-ui-1.10.3.custom.css'), 'priority' => -1001],
            ]);
        }
    }
}
