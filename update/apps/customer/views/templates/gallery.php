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
 * @since 1.3.4.7
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var CustomerEmailTemplate $template */
$template = $controller->getData('template');

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
    $itemsCount = CustomerEmailTemplate::model()->count('customer_id IS NULL'); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-text-width') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $template, 'columns' => ['screenshot', 'name', 'category_id', 'date_added', 'last_updated']], true), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['templates/gallery'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
                        'id'                => $template->getModelName() . '-grid',
                        'dataProvider'      => $template->search(),
                        'filter'            => $template,
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
                                'name'  => 'screenshot',
                                'type'  => 'raw',
                                'value' => 'CHtml::image($data->getScreenshotSrc(), "", array("class" => "img-round", "width" => 120))',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'name',
                                'value' => '$data->name',
                            ],
                            [
                                'name'  => 'category_id',
                                'value' => '!empty($data->category_id) ? $data->category->name : null',
                                'filter'=> CustomerEmailTemplateCategory::getAllAsOptions(),
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
                                'footer'    => $template->paginationOptions->getGridFooterPagination(),
                                'buttons'   => [
                                    'import' => [
                                        'label'     => IconHelper::make('import'),
                                        'url'       => 'createUrl("templates/gallery_import", array("template_uid" => $data->template_uid))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Import'), 'class' => 'btn btn-primary btn-flat'],
                                    ],
                                ],
                                'headerHtmlOptions' => ['style' => 'text-align: right'],
                                'footerHtmlOptions' => ['align' => 'right'],
                                'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                                'template'          => '{import}',
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
