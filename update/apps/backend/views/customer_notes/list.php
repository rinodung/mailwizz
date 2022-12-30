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
 * @since 2.1.8
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var CustomerNote $note */
$note = $controller->getData('note');

/** @var Customer $customer */
$customer = $controller->getData('customer');

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
                    ->add('<h3 class="box-title">' . IconHelper::make('fa-pencil') . $pageHeading . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $note, 'columns' => ['user_id', 'title', 'date_added', 'last_updated']], true))
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['customer_notes/create', 'customer_uid' => $customer->customer_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['customer_notes/index', 'customer_uid' => $customer->customer_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
                        'ajaxUrl'           => createUrl($controller->getRoute(), ['customer_uid' => $customer->customer_uid]),
                        'id'                => $note->getModelName() . '-grid',
                        'dataProvider'      => $note->search(),
                        'filter'            => $note,
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
                                'name'  => 'user_id',
                                'value' => 'HtmlHelper::accessLink($data->user->getFullName(), ["users/update", "id" => $data->user_id], ["fallbackText" => true])',
                                'type'  => 'raw',
                            ],
                            [
                                'name'  => 'title',
                                'value' => '$data->getShortTitle(30)',
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
                                'footer'    => $note->paginationOptions->getGridFooterPagination(),
                                'buttons'   => [
                                    'view' => [
                                        'label'    => IconHelper::make('view'),
                                        'url'      => 'createUrl("customer_notes/view", array("customer_uid" => $data->customer->customer_uid, "note_uid" => $data->note_uid))',
                                        'imageUrl' => null,
                                        'options'  => ['title' => t('lists', 'View'), 'class' => 'btn btn-primary btn-flat'],
                                        'visible'  => 'AccessHelper::hasRouteAccess("customer_notes/view")',
                                    ],
                                    'update' => [
                                        'label'     => IconHelper::make('update'),
                                        'url'       => 'createUrl("customer_notes/update", array("customer_uid" => $data->customer->customer_uid, "note_uid" => $data->note_uid))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                        'visible'   => 'AccessHelper::hasRouteAccess("customer_notes/update")',
                                    ],
                                    'delete' => [
                                        'label'     => IconHelper::make('delete'),
                                        'url'       => 'createUrl("customer_notes/delete", array("customer_uid" => $data->customer->customer_uid, "note_uid" => $data->note_uid))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                        'visible'   => 'AccessHelper::hasRouteAccess("customer_notes/delete")',
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
