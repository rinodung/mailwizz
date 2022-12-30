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

/** @var Survey $survey */
$survey = $controller->getData('survey');

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
    $itemsCount = (int)Survey::model()->countByAttributes([
        'customer_id' => (int)customer()->getId(),
        'status'      => array_keys($survey->getStatusesList()),
    ]); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-list') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $survey, 'columns' => ['survey_uid', 'name', 'display_name', 'responders_count', 'status', 'date_added', 'last_updated']], true), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['surveys/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['surveys/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
                    'id'                => $survey->getModelName() . '-grid',
                    'dataProvider'      => $survey->search(),
                    'filter'            => $survey,
                    'filterPosition'    => 'body',
                    'filterCssClass'    => 'grid-filter-cell',
                    'itemsCssClass'     => 'table table-hover',
                    'selectableRows'    => 0,
                    'enableSorting'     => true,
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
                            'name'  => 'survey_uid',
                            'value' => 'CHtml::link($data->survey_uid,createUrl("surveys/overview", array("survey_uid" => $data->survey_uid)))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'name',
                            'value' => 'CHtml::link($data->name,createUrl("surveys/overview", array("survey_uid" => $data->survey_uid)))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'display_name',
                            'value' => 'CHtml::link($data->display_name,createUrl("surveys/overview", array("survey_uid" => $data->survey_uid)))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'     => 'responders_count',
                            'value'    => 'CHtml::link(formatter()->formatNumber($data->respondersCount), createUrl("survey_responders/index", array("survey_uid" => $data->survey_uid)))',
                            'filter'   => false,
                            'sortable' => false,
                            'type'     => 'raw',
                        ],
                        [
                            'name'   => 'status',
                            'value'  => '$data->getStatusName()',
                            'filter' => $survey->getStatusesList(),
                        ],
                        [
                            'name'      => 'date_added',
                            'value'     => '$data->dateAdded',
                            'filter'    => false,
                        ],
                        [
                            'name'      => 'last_updated',
                            'value'     => '$data->lastUpdated',
                            'filter'    => false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $survey->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'overview' => [
                                    'label'     => IconHelper::make('info'),
                                    'url'       => 'createUrl("surveys/overview", array("survey_uid" => $data->survey_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('surveys', 'Overview'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => '!$data->getIsPendingDelete()',
                                ],
                                'view' => [
                                    'label'     => IconHelper::make('view'),
                                    'url'       => '$data->getViewUrl()',
                                    'imageUrl'  => null,
                                    'options'   => ['target' => '_blank', 'title' => t('surveys', 'View'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => '!$data->getIsPendingDelete()',
                                ],
                                'copy'=> [
                                    'label'     => IconHelper::make('copy'),
                                    'url'       => 'createUrl("surveys/copy", array("survey_uid" => $data->survey_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Copy'), 'class' => 'btn btn-primary btn-flat copy-list'],
                                    'visible'   => '!$data->getIsPendingDelete()',
                                ],
                                'update' => [
                                    'label'     => IconHelper::make('update'),
                                    'url'       => 'createUrl("surveys/update", array("survey_uid" => $data->survey_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => '$data->getEditable()',
                                ],
                                'confirm_delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("surveys/delete", array("survey_uid" => $data->survey_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat'],
                                    'visible'   => '$data->getIsRemovable()',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          =>'{overview} {view} {copy} {update} {confirm_delete}',
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
