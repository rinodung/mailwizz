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

/** @var Campaign_reportsController $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var CampaignDeliveryLogArchive|CampaignDeliveryLog $deliveryLogs */
$deliveryLogs = $controller->getData('deliveryLogs');

/** @var array $bulkActions */
$bulkActions = (array)$controller->getData('bulkActions');

/** @var bool $canExportStats */
$canExportStats = (bool)$controller->getData('canExportStats');

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
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-list-alt') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add(CHtml::link(IconHelper::make('fa-envelope') . t('campaign_reports', 'Campaign overview'), [$controller->campaignOverviewRoute, 'campaign_uid' => $campaign->campaign_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('campaign_reports', 'Back to campaign overview')]))
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $deliveryLogs, 'columns' => ['subscriber.email', 'status', 'message', 'date_added']], true))
                    ->addIf($controller->widget('common.components.web.widgets.GridViewDropDownLinksSelector', [
                        'heading' => t('app', 'Export'),
                        'links'   => [
                            CHtml::link(t('app', 'Export all'), [$controller->campaignReportsExportController . '/delivery', 'campaign_uid' => $campaign->campaign_uid], ['target' => '_blank', 'class' => 'btn btn-default btn-flat', 'title' => t('campaign_reports', 'Export all reports')]),
                            CHtml::link(t('app', 'Success only'), [$controller->campaignReportsExportController . '/delivery', 'campaign_uid' => $campaign->campaign_uid, 'CampaignDeliveryLog[status]' => CampaignDeliveryLog::STATUS_SUCCESS], ['target' => '_blank', 'class' => 'btn btn-default btn-flat', 'title' => t('campaign_reports', 'Export success only')]),
                            CHtml::link(t('app', 'Error only'), [$controller->campaignReportsExportController . '/delivery', 'campaign_uid' => $campaign->campaign_uid, 'CampaignDeliveryLog[status]' => CampaignDeliveryLog::STATUS_ERROR], ['target' => '_blank', 'class' => 'btn btn-default btn-flat', 'title' => t('campaign_reports', 'Export error only')]),
                            CHtml::link(t('app', 'Giveup only'), [$controller->campaignReportsExportController . '/delivery', 'campaign_uid' => $campaign->campaign_uid, 'CampaignDeliveryLog[status]' => CampaignDeliveryLog::STATUS_GIVEUP], ['target' => '_blank', 'class' => 'btn btn-default btn-flat', 'title' => t('campaign_reports', 'Export giveup only')]),
                            CHtml::link(t('app', 'Blacklist only'), [$controller->campaignReportsExportController . '/delivery', 'campaign_uid' => $campaign->campaign_uid, 'CampaignDeliveryLog[status]' => CampaignDeliveryLog::STATUS_BLACKLISTED], ['target' => '_blank', 'class' => 'btn btn-default btn-flat', 'title' => t('campaign_reports', 'Export blacklist only')]),
                        ],
                    ], true), !empty($canExportStats))
                    ->add(CHtml::link(IconHelper::make('info'), '#page-info', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']))
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
                    'ajaxUrl'           => createUrl($controller->getRoute(), ['campaign_uid' => $campaign->campaign_uid]),
                    'id'                => $deliveryLogs->getModelName() . '-grid',
                    'dataProvider'      => $deliveryLogs->customerSearch(),
                    'filter'            => $deliveryLogs,
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
                            'name'  => 'subscriber.email',
                            'value' => '$data->subscriber->getDisplayEmail()',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'status',
                            'value' => '$data->getStatusName()',
                            'filter'=> $deliveryLogs->getStatusesArray(),
                        ],
                        [
                            'name'  => 'message',
                            'value' => '$data->message',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                            'filter'=> false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $deliveryLogs->paginationOptions->getGridFooterPagination(),
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
                            'template'          =>'{webversion}',
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
    <!-- modals -->
    <div class="modal modal-info fade" id="page-info" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                </div>
                <div class="modal-body">
                    <?php
                    $text = 'This report shows all the subscribers that were processed in order to receive your email.<br />
                    It also show if the emails have been sent successfully or not.';
                    echo t('campaign_reports', StringHelper::normalizeTranslationString($text));
                    ?>
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
