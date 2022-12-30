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

/** @var Lists $list */
$list = $controller->getData('list');

/** @var ListSegment $segment */
$segment = $controller->getData('segment');

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
    $itemsCount = (int)ListSegment::model()->countByAttributes([
        'list_id' => (int)$list->list_id,
        'status'  => array_keys($segment->getStatusesList()),
    ]); ?>

    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php $controller->widget('customer.components.web.widgets.MailListSubNavWidget', [
                    'list' => $list,
                ]); ?>
            </div>
        </div>
        <div class="box-body">
            <div class="box box-primary borderless">
                <div class="box-header">
                    <div class="pull-left">
                        <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                            ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-cog') . html_encode((string)$pageHeading) . '</h3>')
                            ->render(); ?>
                    </div>
                    <div class="pull-right">
                        <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                            ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $segment, 'columns' => ['name', 'date_added', 'last_updated']], true), $itemsCount)
                            ->add(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['list_segments/create', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                            ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['list_segments/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
                                'ajaxUrl'           => createUrl($controller->getRoute(), ['list_uid' => $list->list_uid]),
                                'id'                => $segment->getModelName() . '-grid',
                                'dataProvider'      => $segment->search(),
                                'filter'            => null,
                                'filterPosition'    => 'body',
                                'filterCssClass'    => 'grid-filter-cell',
                                'itemsCssClass'     => 'table table-hover',
                                'selectableRows'    => 0,
                                'enableSorting'     => false,
                                'cssFile'           => false,
                                'pager'             => [
                                    'class'         => 'CLinkPager',
                                    'cssFile'       => false,
                                    'header'        => false,
                                    'htmlOptions'   => ['class' => 'pagination'],
                                ],
                                'columns' => hooks()->applyFilters('grid_view_columns', [
                                    [
                                        'name'  => 'name',
                                        'value' => 'CHtml::link($data->name,createUrl("list_segments/update", array("list_uid" => $data->list->list_uid, "segment_uid" => $data->segment_uid)))',
                                        'type'  => 'raw',
                                    ],
                                    [
                                        'name'  => 'date_added',
                                        'value' => '$data->dateAdded',
                                    ],
                                    [
                                        'name'  => 'last_updated',
                                        'value' => '$data->lastUpdated',
                                    ],
                                    [
                                        'class'     => 'DropDownButtonColumn',
                                        'header'    => t('app', 'Options'),
                                        'footer'    => $segment->paginationOptions->getGridFooterPagination(),
                                        'buttons'   => [
                                            'copy'=> [
                                                'label'     => IconHelper::make('copy'),
                                                'url'       => 'createUrl("list_segments/copy", array("list_uid" => $data->list->list_uid, "segment_uid" => $data->segment_uid))',
                                                'imageUrl'  => null,
                                                'options'   => ['title' => t('app', 'Copy'), 'class' => 'btn btn-primary btn-flat copy-segment'],
                                            ],
                                            'update' => [
                                                'label'     => IconHelper::make('update'),
                                                'url'       => 'createUrl("list_segments/update", array("list_uid" => $data->list->list_uid, "segment_uid" => $data->segment_uid))',
                                                'imageUrl'  => null,
                                                'options'   => ['title' => t('app', 'Update'), 'class'=>'btn btn-primary btn-flat'],
                                            ],
                                            'confirm_delete' => [
                                                'label'     => IconHelper::make('delete'),
                                                'url'       => 'createUrl("list_segments/delete", array("list_uid" => $data->list->list_uid, "segment_uid" => $data->segment_uid))',
                                                'imageUrl'  => null,
                                                'options'   => ['title' => t('app', 'Delete'), 'class'=>'btn btn-danger btn-flat'],
                                            ],
                                        ],
                                        'headerHtmlOptions' => ['style' => 'text-align: right'],
                                        'footerHtmlOptions' => ['align' => 'right'],
                                        'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                                        'template'          =>'{copy} {update} {confirm_delete}',
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
