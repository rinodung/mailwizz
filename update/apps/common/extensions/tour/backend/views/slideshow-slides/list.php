<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/** @var ExtensionController $controller */
$controller = controller();

/** @var TourSlideshowSlide $slide */
$slide = $controller->getData('slide');

/** @var TourSlideshow $slideshow */
$slideshow = $controller->getData('slideshow');

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->data}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->renderContent} to false
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('before_view_file_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) {
    $controller->renderPartial($controller->getExtension()->getPathAlias('backend.views._tabs')); ?>

    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <h3 class="box-title">
                    <?php echo IconHelper::make('glyphicon-book') . $pageHeading; ?>
                </h3>
            </div>
            <div class="pull-right">
                <?php echo CHtml::link(IconHelper::make('create') . t('app', 'Create new'), $controller->getExtension()->createUrl('slideshow_slides/create', ['slideshow_id' => $slideshow->slideshow_id]), ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]); ?>
                <?php echo CHtml::link(t('app', 'Refresh'), $controller->getExtension()->createUrl('slideshow_slides/index', ['slideshow_id' => $slideshow->slideshow_id]), ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <?php
                /**
                 * This hook gives a chance to prepend content or to replace the default grid view content with a custom content.
                 * Please note that from inside the action callback you can access all the controller view
                 * variables via {@CAttributeCollection $collection->controller->data}
                 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->renderGrid} to false
                 * in order to stop rendering the default content.
                 * @since 1.3.3.1
                 */
                hooks()->doAction('before_grid_view', $collection = new CAttributeCollection([
                    'controller'   => $controller,
                    'renderGrid'   => true,
                ]));

    // and render if allowed
    if ($collection->itemAt('renderGrid')) {
        $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                        'ajaxUrl'           => createUrl($controller->getRoute(), ['slideshow_id' => $slideshow->slideshow_id]),
                        'id'                => $slide->getModelName() . '-grid',
                        'dataProvider'      => $slide->search(),
                        'filter'            => $slide,
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
                                'name'  => 'image',
                                'value' => 'CHtml::image($data->getImageUrl(120, 60), $data->title, array("class" => "img-thumbnail"))',
                                'type'  => 'raw',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'title',
                                'value' => '$data->title',
                            ],
                            [
                                'name'  => 'content',
                                'value' => 'StringHelper::truncateLength($data->content, 100)',
                            ],
                            [
                                'name'  => 'sort_order',
                                'value' => '$data->sort_order',
                                'filter'=> false,
                            ],
                            [
                                'name'   => 'status',
                                'value'  => 'ucfirst(t("app", $data->status))',
                                'filter' => $slide->getStatusesList(),
                            ],
                            [
                                'name'   => 'date_added',
                                'value'  => '$data->dateAdded',
                                'filter' => false,
                            ],
                            [
                                'name'   => 'last_updated',
                                'value'  => '$data->lastUpdated',
                                'filter' => false,
                            ],
                            [
                                'class'  => 'DropDownButtonColumn',
                                'header' => t('app', 'Options'),
                                'footer' => $slide->paginationOptions->getGridFooterPagination(),
                                'buttons'=> [
                                    'update' => [
                                        'label'     => IconHelper::make('update'),
                                        'url'       => '$data->getExtension()->createUrl("slideshow_slides/update", ["slideshow_id" => $data->slideshow_id, "id" => $data->slide_id])',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    ],
                                    'delete' => [
                                        'label'     => IconHelper::make('delete'),
                                        'url'       => '$data->getExtension()->createUrl("slideshow_slides/delete", ["slideshow_id" => $data->slideshow_id, "id" => $data->slide_id])',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
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
     * variables via {@CAttributeCollection $collection->controller->data}
     * @since 1.3.3.1
     */
    hooks()->doAction('after_grid_view', new CAttributeCollection([
                    'controller'   => $controller,
                    'renderedGrid' => $collection->itemAt('renderGrid'),
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
 * variables via {@CAttributeCollection $collection->controller->data}
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
