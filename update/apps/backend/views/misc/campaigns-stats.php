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
 * @since 1.3.8.7
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

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
if ($viewCollection->itemAt('renderContent')) { ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-file') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $campaign, 'columns' => ['customer_id', 'name', 'bounceRate', 'hardBounceRate', 'softBounceRate', 'internalBounceRate', 'unsubscribeRate', 'complaintsRate', 'status', 'send_at']], true))
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
                 * @since 1.3.3.1
                 */
                hooks()->doAction('before_grid_view', $collection = new CAttributeCollection([
                    'controller'    => $controller,
                    'renderGrid'    => true,
                ]));

                // and render if allowed
                if ($collection->itemAt('renderGrid')) {
                    $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                        'ajaxUrl'           => createUrl($controller->getRoute()),
                        'id'                => $campaign->getModelName() . '-grid',
                        'dataProvider'      => $campaign->search(),
                        'filter'            => $campaign,
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
                                'name'  => 'customer_id',
                                'value' => 'HtmlHelper::accessLink($data->customer->getFullName(), array("customers/update", "id" => $data->customer_id))',
                                'type'  => 'raw',
                            ],
                            [
                                'name'  => 'name',
                                'value' => 'HtmlHelper::accessLink($data->name, array("campaigns/overview", "campaign_uid" => $data->campaign_uid))',
                                'type'  => 'raw',
                            ],
                            [
                                'name'  => 'bounceRate',
                                'value' => '$data->stats->getBouncesRate(true) . "%"',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'hardBounceRate',
                                'value' => '$data->stats->getHardBouncesRate(true) . "%"',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'softBounceRate',
                                'value' => '$data->stats->getSoftBouncesRate(true) . "%"',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'internalBounceRate',
                                'value' => '$data->stats->getInternalBouncesRate(true) . "%"',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'unsubscribeRate',
                                'value' => '$data->stats->getUnsubscribesRate(true) . "%"',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'complaintsRate',
                                'value' => '$data->stats->getComplaintsRate(true) . "%"',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'status',
                                'value' => '$data->getStatusWithStats()',
                                'filter'=> false,
                            ],
                            [
                                'name'  => 'send_at',
                                'value' => '$data->sendAt',
                                'filter'=> false,
                            ],
                            [
                                'class'     => 'DropDownButtonColumn',
                                'header'    => t('app', 'Options'),
                                'footer'    => $campaign->paginationOptions->getGridFooterPagination(),
                                'buttons'   => [
                                    'overview'=> [
                                        'label'     => IconHelper::make('glyphicon-info-sign'),
                                        'url'       => 'createUrl("campaigns/overview", array("campaign_uid" => $data->campaign_uid))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('campaigns', 'Overview'), 'class' => 'btn btn-primary btn-flat'],
                                        'visible'   => 'AccessHelper::hasRouteAccess("campaigns/overview") && (!$data->editable || $data->isPaused) && !$data->isPendingDelete && !$data->getIsDraft()',
                                    ],
                                ],
                                'headerHtmlOptions' => ['style' => 'text-align: right'],
                                'footerHtmlOptions' => ['align' => 'right'],
                                'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                                'template'          => '{overview}',
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
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
