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
 * @since 2.1.10
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var DeliveryServerWarmupPlan $plan */
$plan = $controller->getData('plan');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.4.3
 */
hooks()->doAction('before_view_file_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) { ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('fa-area-chart') . $pageHeading . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $plan, 'columns' => ['name', 'status', 'sending_limit', 'sendings_count', 'sending_quota_type', 'sending_strategy', 'sending_limit_type', 'date_added', 'last_updated']], true))
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['delivery_server_warmup_plans/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['delivery_server_warmup_plans/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->render();
                ?>
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
                 * @since 1.3.4.3
                 */
                hooks()->doAction('before_grid_view', $collection = new CAttributeCollection([
                    'controller'    => $controller,
                    'renderGrid'    => true,
                ]));

                // and render if allowed
                if ($collection->itemAt('renderGrid')) {
                    $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                        'ajaxUrl'           => createUrl($controller->getRoute()),
                        'id'                => $plan->getModelName() . '-grid',
                        'dataProvider'      => $plan->search(),
                        'filter'            => $plan,
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
                                'name'  => 'name',
                                'value' => '$data->name',
                            ],
                            [
                                'name'  => 'sendings_count',
                                'value' => '$data->getFormattedSendingsCount()',
                            ],
                            [
                                'name'  => 'sending_limit',
                                'value' => '$data->getFormattedSendingLimit()',
                            ],
                            [
                                'name'  => 'sending_limit_type',
                                'value' => 'ucfirst(t("warmup_plans", $data->sending_limit_type))',
                                'filter' => $plan->getSendingLimitTypeOptions(),
                            ],
                            [
                                'name'  => 'sending_quota_type',
                                'value' => 'ucfirst(t("warmup_plans", $data->sending_quota_type))',
                                'filter' => $plan->getSendingQuotaTypeOptions(),
                            ],
                            [
                                'name'  => 'sending_strategy',
                                'value' => 'ucfirst(t("warmup_plans", $data->sending_strategy))',
                                'filter' => $plan->getSendingStrategyOptions(),
                            ],
                            [
                                'name'  => 'status',
                                'value'    => '$data->getStatusName()',
                                'filter'   => $plan->getStatusesOptions(),
                            ],
                            [
                                'name'  => 'date_added',
                                'value' => '$data->dateAdded',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'last_updated',
                                'value' => '$data->lastUpdated',
                                'filter'=> false,
                            ],
                            [
                                'class'     => 'DropDownButtonColumn',
                                'header'    => t('app', 'Options'),
                                'footer'    => $plan->paginationOptions->getGridFooterPagination(),
                                'buttons'   => [
                                    'activate' => [
                                        'label'     => IconHelper::make('fa-check-square'),
                                        'url'       => 'createUrl("delivery_server_warmup_plans/activate", array("id" => $data->plan_id))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Activate plan'), 'class' => 'btn btn-success btn-flat btn-activate-warmup-plan', 'data-confirm' => t('warmup_plans', 'Are you sure you want to run this action? After you activate the plan, you will be able to edit only its name and description')],
                                        'visible'   => 'AccessHelper::hasRouteAccess("delivery_server_warmup_plans/activate") && !$data->getIsActive()',
                                    ],
                                    'update' => [
                                        'label'     => IconHelper::make('update'),
                                        'url'       => 'createUrl("delivery_server_warmup_plans/update", array("id" => $data->plan_id))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                        'visible'   => 'AccessHelper::hasRouteAccess("delivery_server_warmup_plans/update")',
                                    ],
                                    'delete' => [
                                        'label'     => IconHelper::make('delete'),
                                        'url'       => 'createUrl("delivery_server_warmup_plans/delete", array("id" => $data->plan_id))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                        'visible'   => 'AccessHelper::hasRouteAccess("delivery_server_warmup_plans/delete")',
                                    ],
                                ],
                                'headerHtmlOptions' => ['style' => 'text-align: right'],
                                'footerHtmlOptions' => ['align' => 'right'],
                                'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                                'template'          => '{activate} {update} {delete}',
                            ],
                        ], $controller),
                    ], $controller));
                }
                /**
                 * This hook gives a chance to append content after the grid view content.
                 * Please note that from inside the action callback you can access all the controller view
                 * variables via {@CAttributeCollection $collection->controller->getData()}
                 * @since 1.3.4.3
                 */
                hooks()->doAction('after_grid_view', new CAttributeCollection([
                    'controller'    => $controller,
                    'renderedGrid'  => $collection->itemAt('renderGrid'),
                ]));
                ?>
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
 * @since 1.3.4.3
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
