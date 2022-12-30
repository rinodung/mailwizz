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
 * @since 1.3.4.6
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var CampaignDeliveryLogArchive|CampaignDeliveryLog $log */
$log = $controller->getData('log');

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
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $log, 'columns' => ['customer_id', 'campaign_id', 'list_id', 'segment_id', 'subscriber_id', 'message', 'status', 'server_id', 'date_added']], true))
                    ->addIf(HtmlHelper::accessLink(t('misc', 'View archived logs'), ['misc/campaigns_delivery_logs', 'archive' => 1], ['class' => 'btn btn-primary btn-flat', 'title' => t('misc', 'View archived logs')]), empty($archive))
                    ->addIf(HtmlHelper::accessLink(t('misc', 'View current logs'), ['misc/campaigns_delivery_logs'], ['class' => 'btn btn-primary btn-flat', 'title' => t('misc', 'View current logs')]), !empty($archive))
                    ->add(HtmlHelper::accessLink(t('misc', 'Delete delivery temporary errors'), ['misc/delete_delivery_temporary_errors'], ['class' => 'btn btn-danger btn-flat btn-delete-delivery-temporary-errors', 'title' => t('app', 'Delete delivery temporary errors'), 'data-confirm' => t('misc', 'Are you sure you want to delete the delivery temporary errors? Please note that this will affect running campaigns, continue only if you really know what you are doing!')]))
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
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $log->getModelName() . '-grid',
                    'dataProvider'      => $log->search(),
                    'filter'            => $log,
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
                            'value' => 'empty($data->campaign) ? "-" : HtmlHelper::accessLink($data->campaign->customer->getFullName(), array("customers/update", "id" => $data->campaign->customer->customer_id))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'campaign_id',
                            'value' => 'empty($data->campaign) ? "-" : HtmlHelper::accessLink($data->campaign->name, array("campaigns/overview", "campaign_uid" => $data->campaign->campaign_uid))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'list_id',
                            'value' => 'empty($data->campaign) ? "-" : HtmlHelper::accessLink($data->campaign->list->name, array("lists/overview", "list_uid" => $data->campaign->list->list_uid))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'segment_id',
                            'value' => '!empty($data->campaign) && !empty($data->campaign->segment_id) ? $data->campaign->segment->name : "-"',
                        ],
                        [
                            'name'  => 'subscriber_id',
                            'value' => 'empty($data->subscriber) ? "-" : $data->subscriber->getDisplayEmail()',
                        ],
                        [
                            'name'  => 'message',
                            'value' => '$data->message',
                        ],
                        [
                            'name'  => 'status',
                            'value' => '$data->getStatusName()',
                            'filter'=> $log->getStatusesArray(),
                        ],
                        [
                            'name'  => 'server_id',
                            'value' => 'empty($data->server) ? "-" : HtmlHelper::accessLink((!empty($data->server->name) ? $data->server->name : $data->server->hostname), array("delivery_servers/update", "type" => $data->server->type, "id" => $data->server_id))',
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
                            'footer'    => $log->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'webversion' => [
                                    'label'     => IconHelper::make('view'),
                                    'url'       => 'options()->get("system.urls.frontend_absolute_url") . "campaigns/" . $data->campaign->campaign_uid . "/web-version/" . $data->subscriber->subscriber_uid',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaign_reports', 'View what was sent'), 'class' => 'btn btn-primary btn-flat', 'target' => '_blank'],
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{webversion}',
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
