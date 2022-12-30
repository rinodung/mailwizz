<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * This file is part of the MailWizz EMA application.
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

/** @var Controller $controller */
$controller = controller();

/** @var PricePlanOrder $order */
$order = $controller->getData('order');

/** @var PricePlanOrderNote $note */
$note = $controller->getData('note');

/** @var PricePlanOrderTransaction $transaction */
$transaction = $controller->getData('transaction');

?>

<div class="price-plan-payment">
    <div class="row">
        <div class="col-xs-12">
            <h2 class="page-header">
                <i class="fa fa-credit-card"></i> <?php echo $order->plan->name; ?>
                <small class="pull-right">
                    <?php echo $order->getAttributeLabel('order_uid'); ?> <b><?php echo $order->getUid(); ?></b>,
                    <?php echo $order->getAttributeLabel('date_added'); ?>: <?php echo $order->dateTimeFormatter->getDateAdded(); ?>
                </small>
            </h2>                            
        </div>
    </div>

    <div class="row invoice-info">
        <?php if ((string)hooks()->applyFilters('price_plan_order_payment_from_to_layout', 'from-to') == 'from-to') { ?>
            <div class="col-sm-4 invoice-col">
                <?php echo t('orders', (string)hooks()->applyFilters('price_plan_order_payment_from_text', 'Payment from')); ?>
                <address>
                    <?php echo $order->getHtmlPaymentFrom(); ?>
                </address>
            </div>
            <div class="col-sm-4 invoice-col">
                <?php echo t('orders', (string)hooks()->applyFilters('price_plan_order_payment_to_text', 'Payment to')); ?>
                <address>
                    <?php echo $order->getHtmlPaymentTo(); ?>
                </address>
            </div>
        <?php } else { ?>
            <div class="col-sm-4 invoice-col">
                <?php echo t('orders', (string)hooks()->applyFilters('price_plan_order_payment_to_text', 'Payment to')); ?>
                <address>
                    <?php echo $order->getHtmlPaymentTo(); ?>
                </address>
            </div>
            <div class="col-sm-4 invoice-col">
                <?php echo t('orders', (string)hooks()->applyFilters('price_plan_order_payment_from_text', 'Payment from')); ?>
                <address>
                    <?php echo $order->getHtmlPaymentFrom(); ?>
                </address>
            </div>
        <?php } ?>
        <div class="col-sm-4 invoice-col"></div>
    </div>

    <hr />
    
    <div class="row">
        <div class="col-xs-12 table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo t('orders', 'This order applies for the "{planName}" pricing plan.', ['{planName}' => $order->plan->name]); ?></th>
                    </tr>                                    
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $order->plan->description; ?></td>
                    </tr>
                </tbody>
            </table>                            
        </div>
    </div>
    
    <hr />
    
    <div class="row no-print">
        <div class="col-xs-12">
            <p class="lead" style="margin-bottom: 0px;"><?php echo t('orders', 'Notes'); ?>:</p>
        </div>
        <div class="form-group col-lg-12"> 
            <div class="table-responsive">
            <?php
            $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                'ajaxUrl'           => createUrl($controller->getRoute(), ['id' => (int)$order->order_id]),
                'id'                => $note->getModelName() . '-grid',
                'dataProvider'      => $note->search(),
                'filter'            => null,
                'filterPosition'    => 'body',
                'filterCssClass'    => 'grid-filter-cell',
                'itemsCssClass'     => 'table table-hover',
                'selectableRows'    => 0,
                'enableSorting'     => false,
                'cssFile'           => false,
                'pagerCssClass'     => 'pagination pull-right',
                'pager'             => [
                    'class'         => 'CLinkPager',
                    'cssFile'       => false,
                    'header'        => false,
                    'htmlOptions'   => ['class' => 'pagination'],
                ],
                'columns' => hooks()->applyFilters('grid_view_columns', [
                    [
                        'name'  => 'author',
                        'value' => '$data->getAuthor()',
                    ],
                    [
                        'name'  => 'note',
                        'value' => '$data->note',
                    ],
                    [
                        'name'  => 'date_added',
                        'value' => '$data->dateAdded',
                    ],
                ], $controller),
            ], $controller));
            ?>    
            </div>
        </div>
    </div>
    
    <hr />
    
    <div class="row">
        <div class="col-xs-6 no-print">
            <p class="lead" style="margin-bottom: 0px;"><?php echo t('orders', 'Transaction info'); ?>:</p>
            <div class="table-responsive">
            <?php
            /**
             * This hook gives a chance to prepend content or to replace the default grid view content with a custom content.
             * Please note that from inside the action callback you can access all the controller view
             * variables via {@CAttributeCollection $collection->controller->getData()}
             * In case the content is replaced, make sure to set {@CAttributeCollection $collection->itemAt('renderGrid')} to false
             * in order to stop rendering the default content.
             * @since 1.3.3.1
             */
            hooks()->doAction('before_grid_view', $collection = new CAttributeCollection([
                'controller'    => $controller,
                'renderGrid'    => true,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderGrid')) {
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $transaction->getModelName() . '-grid',
                    'dataProvider'      => $transaction->search(),
                    'filter'            => null,
                    'filterPosition'    => 'body',
                    'filterCssClass'    => 'grid-filter-cell',
                    'itemsCssClass'     => 'table table-hover',
                    'selectableRows'    => 0,
                    'enableSorting'     => false,
                    'cssFile'           => false,
                    'pagerCssClass'     => 'pagination pull-right',
                    'pager'             => [
                        'class'         => 'CLinkPager',
                        'cssFile'       => false,
                        'header'        => false,
                        'htmlOptions'   => ['class' => 'pagination'],
                    ],
                    'columns' => hooks()->applyFilters('grid_view_columns', [
                        [
                            'name'  => 'payment_gateway_name',
                            'value' => '$data->payment_gateway_name',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'payment_gateway_transaction_id',
                            'value' => 'wordwrap($data->payment_gateway_transaction_id, 30, "<br />", true)',
                            'type'  => 'raw',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'status',
                            'value' => '$data->getStatusName()',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                            'filter'=> false,
                        ],
                    ], $controller),
                ], $controller));
            }
            /**
             * This hook gives a chance to append content after the grid view content.
             * Please note that from inside the action callback you can access all the controller view
             * variables via {@CAttributeCollection $collection->controller->getData()}
             * @since 1.3.3.1
             */
            hooks()->doAction('after_grid_view', new CAttributeCollection([
                'controller'    => $controller,
                'renderedGrid'  => $collection->itemAt('renderGrid'),
            ]));
            ?>    
            </div>
        </div>
        <div class="col-xs-6">
            <p class="lead"><?php echo t('orders', 'Amount'); ?>:</p>
            <div class="table-responsive">
                <table class="table">
                    <tr>
                        <th style="width:50%"><?php echo t('orders', 'Subtotal'); ?>:</th>
                        <td><?php echo $order->getFormattedSubtotal(); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('orders', 'Tax'); ?>:</th>
                        <td><?php echo $order->getFormattedTaxValue(); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('orders', 'Discount'); ?>:</th>
                        <td><?php echo $order->getFormattedDiscount(); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('orders', 'Total'); ?>:</th>
                        <td><?php echo $order->getFormattedTotal(); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo t('orders', 'Status'); ?>:</th>
                        <td><?php echo $order->getStatusName(); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <hr />
    
    <div class="row no-print">
        <div class="col-xs-12">
            <div class="pull-right">
                <button class="btn btn-success btn-flat" onclick="window.print();"><i class="fa fa-print"></i> <?php echo t('app', 'Print'); ?></button>
                <a href="<?php echo createUrl('price_plans/email_invoice', ['order_uid' => $order->order_uid]); ?>" class="btn btn-success btn-flat"><i class="fa fa-envelope"></i> <?php echo t('orders', 'Email invoice'); ?></a>
                <a target="_blank" href="<?php echo createUrl('price_plans/order_pdf', ['order_uid' => $order->order_uid]); ?>" class="btn btn-success btn-flat"><i class="fa fa-clipboard"></i> <?php echo t('orders', 'View invoice'); ?></a>
                <a href="<?php echo createUrl('price_plans/orders'); ?>" class="btn btn-primary btn-flat"><?php echo IconHelper::make('back') . '&nbsp;' . t('orders', 'Back to orders'); ?></a>    
            </div>
        </div>
    </div>
</div>
