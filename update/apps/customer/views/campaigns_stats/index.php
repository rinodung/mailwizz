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

/** @var CampaignStatsFilter $filter */
$filter = $controller->getData('filter');

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
    $itemsCount = (int)Campaign::model()->countByAttributes([
        'customer_id' => (int)customer()->getId(),
        'type'        => Campaign::TYPE_REGULAR,
        'status'      => Campaign::STATUS_SENT,
    ]); ?>

    <?php $controller->renderPartial('_filters'); ?>

    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('envelope') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $filter, 'columns' => ['name', 'subject', 'listName', 'subscribersCount', 'deliverySuccess', 'uniqueOpens', 'allOpens', 'uniqueClicks', 'allClicks', 'unsubscribes', 'bounces', 'softBounces', 'hardBounces', 'internalBounces', 'complaints', 'sendAt']], true), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['campaigns_stats/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->addIf(CHtml::link(IconHelper::make('filter') . t('app', 'Filters'), 'javascript:;', ['class' => 'btn btn-primary btn-flat toggle-filters-form', 'title' => t('app', 'Filters')]), $itemsCount)
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
                    'id'                => $filter->getModelName() . '-grid',
                    'dataProvider'      => $filter->search(),
                    'filter'            => null,
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
                            'value' => 'CHtml::link($data->name, createUrl("campaigns/overview", array("campaign_uid" => $data->campaign_uid)))',
                            'type'  => 'raw',
                            'filter'=> false,
                        ],
                        [
                            'name'   => 'subject',
                            'value'  => '$data->subject',
                            'filter' => false,
                        ],
                        [
                            'name'  => 'listName',
                            'value' => 'empty($data->list) ? "" : CHtml::link($data->listName, createUrl("lists/overview", array("list_uid" => $data->list->list_uid)))',
                            'type'  => 'raw',
                            'filter'=> false,
                        ],
                        [
                            'name'   => 'subscribersCount',
                            'value'  => '$data->subscribersCount',
                            'filter' => false,
                        ],
                        [
                            'name'   => 'deliverySuccess',
                            'value'  => '$data->deliverySuccess',
                            'filter' => false,
                        ],
                        [
                            'name'   => 'uniqueOpens',
                            'value'  => '$data->uniqueOpens',
                            'filter' => false,
                        ],
                        [
                            'name'   => 'allOpens',
                            'value'  => '$data->allOpens',
                            'filter' => false,
                        ],
                        [
                            'name'  => 'uniqueClicks',
                            'value' => '$data->uniqueClicks',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'allClicks',
                            'value' => '$data->allClicks',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'unsubscribes',
                            'value' => '$data->unsubscribes',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'bounces',
                            'value' => '$data->bounces',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'softBounces',
                            'value' => '$data->softBounces',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'hardBounces',
                            'value' => '$data->hardBounces',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'internalBounces',
                            'value' => '$data->internalBounces',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'complaints',
                            'value' => '$data->complaints',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'sendAt',
                            'value' => '$data->sendAt',
                            'filter'=> false,
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
