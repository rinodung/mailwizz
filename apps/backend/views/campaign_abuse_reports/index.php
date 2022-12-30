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
 * @since 1.3.5
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var CampaignAbuseReport $reports */
$reports = $controller->getData('reports');

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
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-comment') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $reports, 'columns' => ['customer_info', 'campaign_info', 'list_info', 'subscriber_info', 'reason', 'log', 'date_added']], true))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['campaign_abuse_reports/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
                'controller'  => $controller,
                'renderGrid'  => true,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderGrid')) {
                // since 2.0.33
                if (AccessHelper::hasRouteAccess('campaign_abuse_reports/bulk_action')) {
                    $controller->widget('common.components.web.widgets.GridViewBulkAction', [
                        'model'      => $reports,
                        'formAction' => createUrl('campaign_abuse_reports/bulk_action'),
                    ]);
                }
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $reports->getModelName() . '-grid',
                    'dataProvider'      => $reports->search(),
                    'filter'            => $reports,
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
                            'class'               => 'CCheckBoxColumn',
                            'name'                => 'report_id',
                            'selectableRows'      => 100,
                            'checkBoxHtmlOptions' => ['name' => 'bulk_item[]'],
                            'visible'             => AccessHelper::hasRouteAccess('campaign_abuse_reports/bulk_action'),
                        ],
                        [
                            'name'  => 'customer_info',
                            'value' => '$data->customer_info',
                        ],
                        [
                            'name'  => 'campaign_info',
                            'value' => '$data->campaign_info',
                        ],
                        [
                            'name'  => 'list_info',
                            'value' => '$data->list_info',
                        ],
                        [
                            'name'  => 'subscriber_info',
                            'value' => '$data->subscriber_info',
                        ],
                        [
                            'name'  => 'reason',
                            'value' => '$data->reason',
                        ],
                        [
                            'name'  => 'log',
                            'value' => 'nl2br($data->log)',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                            'filter'=> false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $reports->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("campaign_abuse_reports/delete", array("id" => $data->report_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaign_abuse_reports/delete")',
                                ],
                                'blacklist' => [
                                    'label'     => IconHelper::make('glyphicon-ban-circle'),
                                    'url'       => 'createUrl("campaign_abuse_reports/blacklist", array("id" => $data->report_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Blacklist email'), 'class' => 'btn btn-danger btn-flat blacklist-email', 'data-message' => t('campaigns', 'Are you sure you want to blacklist the email address specified in the abuse report?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaign_abuse_reports/blacklist")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{delete} {blacklist}',
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
