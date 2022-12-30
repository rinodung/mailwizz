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
 * @since 1.0
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Customer $customer */
$customer = $controller->getData('customer');

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
    $itemsCount = Customer::model()->count(); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('fa-users') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $customer, 'columns' => ['customer_id', 'parent_id', 'customer_uid', 'first_name', 'last_name', 'email', 'company_name', 'group_id', 'sending_quota_usage', 'status', 'date_added']], true), $itemsCount)
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['customers/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('glyphicon-folder-close') . t('customers', 'Manage groups'), ['customer_groups/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('customers', 'Manage groups')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['customers/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
                'controller'  => $controller,
                'renderGrid'  => true,
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
                    'ajaxUrl'         => createUrl($controller->getRoute()),
                    'id'              => $customer->getModelName() . '-grid',
                    'dataProvider'    => $customer->search(),
                    'filter'          => $customer,
                    'filterPosition'  => 'body',
                    'filterCssClass'  => 'grid-filter-cell',
                    'itemsCssClass'   => 'table table-hover',
                    'selectableRows'  => 0,
                    'enableSorting'   => true,
                    'cssFile'         => false,
                    'pagerCssClass'   => 'pagination pull-right',
                    'pager'           => [
                        'class'       => 'CLinkPager',
                        'cssFile'     => false,
                        'header'      => false,
                        'htmlOptions' => ['class' => 'pagination'],
                    ],
                    'columns' => hooks()->applyFilters('grid_view_columns', [
                        [
                            'name'  => 'customer_id',
                            'value' => '$data->customer_id',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'parent_id',
                            'value' => '!empty($data->parent_id) ? $data->parent->getFullName() : "-"',
                        ],
                        [
                            'name'  => 'customer_uid',
                            'value' => '$data->customer_uid',
                        ],
                        [
                            'name'  => 'first_name',
                            'value' => '$data->first_name',
                        ],
                        [
                            'name'  => 'last_name',
                            'value' => '$data->last_name',
                        ],
                        [
                            'name'  => 'email',
                            'value' => '$data->email',
                        ],
                        [
                            'name'     => 'company_name',
                            'value'    => '!empty($data->company) ? $data->company->name : "-"',
                            'sortable' => false,
                        ],
                        [
                            'name'     => 'group_id',
                            'value'    => '!empty($data->group_id) ? HtmlHelper::accessLink($data->group->name, array("customer_groups/update", "id" => $data->group_id), array("fallbackText" => true)) : "-"',
                            'type'     => 'raw',
                            'filter'   => CustomerGroup::getGroupsArray(),
                            'sortable' => false,
                        ],
                        [
                            'name'     => 'sending_quota_usage',
                            'value'    => '$data->getSendingQuotaUsageDisplay()',
                            'type'     => 'raw',
                            'filter'   => false,
                            'sortable' => false,
                        ],
                        [
                            'name'     => 'status',
                            'value'    => '$data->getStatusName()',
                            'filter'   => $customer->getStatusesArray(),
                            'sortable' => false,
                        ],
                        [
                            'name'     => 'date_added',
                            'value'    => '$data->dateAdded',
                            'filter'   => false,
                            'sortable' => false,
                        ],
                        [
                            'class'   => 'DropDownButtonColumn',
                            'header'  => t('app', 'Options'),
                            'footer'  => $customer->paginationOptions->getGridFooterPagination(),
                            'buttons' => [
                                'impersonate' => [
                                    'label'     => IconHelper::make('glyphicon-random'),
                                    'url'       => 'createUrl("customers/impersonate", array("id" => $data->customer_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Login as this customer'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("customers/impersonate")',
                                ],
                                'reset_quota' => [
                                    'label'     => IconHelper::make('refresh'),
                                    'url'       => 'createUrl("customers/reset_sending_quota", array("id" => $data->customer_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Reset sending quota'), 'class' => 'btn btn-primary btn-flat reset-sending-quota', 'data-message' => t('customers', 'Are you sure you want to reset the sending quota for this customer?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("customers/reset_sending_quota")',
                                ],
                                'update' => [
                                    'label'     => IconHelper::make('update'),
                                    'url'       => 'createUrl("customers/update", array("id" => $data->customer_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("customers/update")',
                                ],
                                'notes' => [
                                    'label'     => IconHelper::make('fa-pencil'),
                                    'url'       => 'createUrl("customer_notes/index", array("customer_uid" => $data->customer_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'View notes'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("customers/update")',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("customers/delete", array("id" => $data->customer_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("customers/delete") && $data->getIsRemovable()',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{impersonate} {reset_quota} {update} {notes} {delete}',
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
                'controller'  => $controller,
                'renderedGrid'=> $collection->itemAt('renderGrid'),
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
