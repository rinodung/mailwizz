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
 * @since 1.3.3.1
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var PricePlanOrder $order */
$order = $controller->getData('order');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('before_view_file_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) {
    /**
     * @since 1.3.9.2
     */
    $itemsCount = PricePlanOrder::model()->count(); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-credit-card') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $order, 'columns' => ['order_uid', 'customer_id', 'plan_id', 'promo_code_id', 'subtotal', 'tax_id', 'tax_percent', 'tax_value', 'discount', 'total', 'status', 'date_added']], true), $itemsCount)
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['orders/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['orders/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->render(); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
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

    /**
     * This widget renders default getting started page for this particular section.
     * @since 1.3.9.2
     */
    $controller->widget('common.components.web.widgets.StartPagesWidget', [
                'collection' => $collection,
                'enabled'    => !$itemsCount,
            ]);

    // and render if allowed
    if ($collection->itemAt('renderGrid')) {
        $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $order->getModelName() . '-grid',
                    'dataProvider'      => $order->search(),
                    'filter'            => $order,
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
                            'name'  => 'order_uid',
                            'value' => 'HtmlHelper::accessLink($data->uid, array("orders/update", "id" => $data->order_id), array("fallbackText" => true))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'customer_id',
                            'value' => 'HtmlHelper::accessLink($data->customer->getFullName(), array("customers/update", "id" => $data->customer_id), array("fallbackText" => true))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'plan_id',
                            'value' => 'HtmlHelper::accessLink($data->plan->name, array("price_plans/update", "id" => $data->plan_id), array("fallbackText" => true))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'promo_code_id',
                            'value' => '!empty($data->promo_code_id) ? HtmlHelper::accessLink($data->promoCode->code, array("promo_codes/update", "id" => $data->promo_code_id), array("fallbackText" => true)) : "-"',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'subtotal',
                            'value' => '$data->formattedSubtotal',
                        ],
                        [
                            'name'  => 'tax_id',
                            'value' => '!empty($data->tax_id) ? $data->tax->name : "---"',
                        ],
                        [
                            'name'  => 'tax_percent',
                            'value' => '$data->formattedTaxPercent',
                        ],
                        [
                            'name'  => 'tax_value',
                            'value' => '$data->formattedTaxValue',
                        ],
                        [
                            'name'  => 'discount',
                            'value' => '$data->formattedDiscount',
                        ],
                        [
                            'name'  => 'total',
                            'value' => '$data->formattedTotal',
                        ],
                        [
                            'name'  => 'status',
                            'value' => '$data->getStatusName()',
                            'filter'=> $order->getStatusesList(),
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                            'filter'=> false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $order->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'view' => [
                                    'label'     => IconHelper::make('view'),
                                    'url'       => 'createUrl("orders/view", array("id" => $data->order_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'View'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("orders/view")',
                                ],
                                'update' => [
                                    'label'     => IconHelper::make('update'),
                                    'url'       => 'createUrl("orders/update", array("id" => $data->order_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("orders/update")',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("orders/delete", array("id" => $data->order_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("orders/delete")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{view} {update} {delete}',
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
            ])); ?>
            <div class="clearfix"><!-- --></div>
            </div>
        </div>
    </div>
<?php
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
