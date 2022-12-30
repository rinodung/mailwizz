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
 * @since 1.4.4
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var CustomerSuppressionList $list */
$list = $controller->getData('list');

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
    $itemsCount = CustomerSuppressionList::model()->countByAttributes([
        'customer_id' => (int)customer()->getId(),
    ]); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-ban-circle') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $list, 'columns' => ['list_uid', 'name', 'date_added', 'last_updated']], true), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['suppression_lists/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->addIf(CHtml::link(IconHelper::make('export') . t('app', 'Export'), ['suppression_lists/export'], ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Export')]), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['suppression_lists/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
                        'ajaxUrl'           => createUrl($controller->getRoute()),
                        'id'                => $list->getModelName() . '-grid',
                        'dataProvider'      => $list->search(),
                        'filter'            => $list,
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
                                'name'  => 'list_uid',
                                'value' => 'CHtml::link($data->list_uid, createUrl("suppression_list_emails/index", array("list_uid" => $data->list_uid)))',
                                'type'  => 'raw',
                            ],
                            [
                                'name'  => 'name',
                                'value' => 'CHtml::link($data->name, createUrl("suppression_list_emails/index", array("list_uid" => $data->list_uid)))',
                                'type'  => 'raw',
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
                                'footer'    => $list->paginationOptions->getGridFooterPagination(),
                                'buttons'   => [
                                    'view' => [
                                        'label'     => IconHelper::make('view'),
                                        'url'       => 'createUrl("suppression_list_emails/index", array("list_uid" => $data->list_uid))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'View emails'), 'class' => 'btn btn-primary btn-flat'],
                                    ],
                                    'update' => [
                                        'label'     => IconHelper::make('update'),
                                        'url'       => 'createUrl("suppression_lists/update", array("list_uid" => $data->list_uid))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    ],
                                    'delete' => [
                                        'label'     => IconHelper::make('delete'),
                                        'url'       => 'createUrl("suppression_lists/delete", array("list_uid" => $data->list_uid))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
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
