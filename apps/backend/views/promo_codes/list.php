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
 * @since 1.3.4.4
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var PricePlanPromoCode $promoCode */
$promoCode = $controller->getData('promoCode');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('views_before_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) {
    /**
     * @since 1.3.9.2
     */
    $itemsCount = PricePlanPromoCode::model()->count(); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
    		<div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('fa-code') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
    		<div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $promoCode, 'columns' => ['code', 'type', 'discount', 'total_amount', 'total_usage', 'customer_usage', 'status', 'date_start', 'date_end']], true), $itemsCount)
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['promo_codes/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['promo_codes/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
            hooks()->doAction('views_before_grid', $collection = new CAttributeCollection([
                'controller'   => $controller,
                'renderGrid'   => true,
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
                    'id'                => $promoCode->getModelName() . '-grid',
                    'dataProvider'      => $promoCode->search(),
                    'filter'            => $promoCode,
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
                    'beforeAjaxUpdate'  => 'js:function(id, options){
                        window.dpStartSettings = $("#PricePlanPromoCode_date_start").data("datepicker").settings;
                        window.dpEndSettings = $("#PricePlanPromoCode_date_end").data("datepicker").settings;
                    }',
                    'afterAjaxUpdate'   => 'js:function(id, data) {
                        $("#PricePlanPromoCode_date_start").datepicker(window.dpStartSettings);
                        $("#PricePlanPromoCode_date_end").datepicker(window.dpEndSettings);
                        window.dpStartSettings = null;
                        window.dpEndSettings = null;
                    }',
                    'columns' => hooks()->applyFilters('grid_view_columns', [
                        [
                            'name'  => 'code',
                            'value' => '$data->code',
                            'filter' => CHtml::activeTextField($promoCode, 'code'),
                        ],
                        [
                            'name'  => 'type',
                            'value' => '$data->typeName',
                            'filter'=> CHtml::activeDropDownList($promoCode, 'type', array_merge(['' => ''], $promoCode->getTypesList())),
                        ],
                        [
                            'name'  => 'discount',
                            'value' => '$data->formattedDiscount',
                            'filter' => CHtml::activeTextField($promoCode, 'discount'),
                        ],
                        [
                            'name'  => 'total_amount',
                            'value' => '$data->formattedTotalAmount',
                            'filter' => CHtml::activeTextField($promoCode, 'total_amount'),
                        ],
                        [
                            'name'  => 'total_usage',
                            'value' => '$data->total_usage',
                            'filter' => CHtml::activeTextField($promoCode, 'total_usage'),
                        ],
                        [
                            'name'  => 'customer_usage',
                            'value' => '$data->customer_usage',
                            'filter' => CHtml::activeTextField($promoCode, 'customer_usage'),
                        ],
                        [
                            'name'  => 'status',
                            'value' => '$data->getStatusName()',
                            'filter'=> CHtml::activeDropDownList($promoCode, 'status', array_merge(['' => ''], $promoCode->getStatusesList())),
                        ],
                        [
                            'name'  => 'date_start',
                            'value' => '$data->dateStart',
                            'filter'=> '<div class="input-group">
                              <span class="input-group-btn">' . CHtml::activeDropDownList($promoCode, 'pickerDateStartComparisonSign', $promoCode->getComparisonSignsList()) . '</span>
                              ' . $controller->widget('zii.widgets.jui.CJuiDatePicker', [
                                'model'     => $promoCode,
                                'attribute' => 'date_start',
                                'cssFile'   => null,
                                'language'  => $promoCode->getDatePickerLanguage(),
                                'options'   => [
                                    'showAnim'   => 'fold',
                                    'dateFormat' => $promoCode->getDatePickerFormat(),
                                ],
                                'htmlOptions' => ['class' => ''],
                            ], true) . '</div>',
                        ],
                        [
                            'name'  => 'date_end',
                            'value' => '$data->dateEnd',
                            'filter'=> '<div class="input-group">
                              <span class="input-group-btn">' . CHtml::activeDropDownList($promoCode, 'pickerDateEndComparisonSign', $promoCode->getComparisonSignsList()) . '</span>
                              ' . $controller->widget('zii.widgets.jui.CJuiDatePicker', [
                                    'model'     => $promoCode,
                                    'attribute' => 'date_end',
                                    'cssFile'   => null,
                                    'language'  => $promoCode->getDatePickerLanguage(),
                                    'options'   => [
                                        'showAnim'   => 'fold',
                                        'dateFormat' => $promoCode->getDatePickerFormat(),
                                    ],
                                    'htmlOptions' => ['class' => ''],
                                ], true) . '</div>',
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $promoCode->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'update' => [
                                    'label'     => IconHelper::make('update'),
                                    'url'       => 'createUrl("promo_codes/update", array("id" => $data->promo_code_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("promo_codes/update")',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("promo_codes/delete", array("id" => $data->promo_code_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("promo_codes/delete")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{update} {delete}',
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
    hooks()->doAction('views_after_grid', new CAttributeCollection([
                'controller'   => $controller,
                'renderedGrid' => $collection->itemAt('renderGrid'),
            ])); ?>
            </div>
            <div class="clearfix"><!-- --></div>
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
hooks()->doAction('views_after_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
