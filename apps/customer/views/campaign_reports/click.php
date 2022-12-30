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

/** @var CampaignUrl $model */
$model = $controller->getData('model');

/** @var CActiveDataProvider $dataProvider */
$dataProvider = $controller->getData('dataProvider');

/** @var bool $canExportStats */
$canExportStats = (bool)$controller->getData('canExportStats');

/** @var bool $canDeleteStats */
$canDeleteStats = (bool)$controller->getData('canDeleteStats');

/** @var string|null $show */
$show = (string)$controller->getData('show');

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
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $model, 'columns' => ['destination', 'clicked_times', 'date_added']], true))
                    ->add(CHtml::link(t('campaign_reports', 'All clicks'), [$controller->campaignReportsController . '/click', 'campaign_uid' => $campaign->campaign_uid], ['class' => 'btn ' . (empty($show) ? 'btn-default' : 'btn-primary') . ' btn-flat']))
                    ->add(CHtml::link(t('campaign_reports', 'Top clicks'), [$controller->campaignReportsController . '/click', 'campaign_uid' => $campaign->campaign_uid, 'show' => 'top'], ['class' => 'btn ' . ($show == 'top' ? 'btn-default' : 'btn-primary') . ' btn-flat']))
                    ->add(CHtml::link(t('campaign_reports', 'Latest clicks'), [$controller->campaignReportsController . '/click', 'campaign_uid' => $campaign->campaign_uid, 'show' => 'latest'], ['class' => 'btn ' . ($show == 'latest' ? 'btn-default' : 'btn-primary') . ' btn-flat']))
                    ->addIf(CHtml::link(IconHelper::make('export') . t('campaign_reports', 'Export reports'), [$controller->campaignReportsExportController . '/click', 'campaign_uid' => $campaign->campaign_uid], ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('campaign_reports', 'Export reports')]), !empty($canExportStats))
                    ->addIf(CHtml::link(IconHelper::make('delete') . t('campaign_reports', 'Delete reports'), [$controller->campaignReportsController . '/delete_clicks', 'campaign_uid' => $campaign->campaign_uid], ['class' => 'btn btn-danger btn-flat btn-delete-reports', 'title' => t('campaign_reports', 'Delete reports'), 'data-confirm' => t('campaign_reports', 'Are you sure you want to remove these reports? There is no coming back after this!')]), !empty($canDeleteStats))
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

            if ($collection->itemAt('renderGrid')) {
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute(), ['campaign_uid' => $campaign->campaign_uid]),
                    'id'                => $model->getModelName() . '-grid',
                    'dataProvider'      => $dataProvider,
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
                            'name'  => 'destination',
                            'value' => '$data->getDisplayGridDestination()',
                            'type'  => 'raw',
                            'htmlOptions' => ['style' => 'max-width:420px;word-wrap:break-word;'],
                        ],
                        [
                            'name'  => 'clicked_times',
                            'value' => '$data->counter',
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $model->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'urlclick'      => [
                                    'label'     => IconHelper::make('info'),
                                    'url'       => 'createUrl(app()->controller->campaignReportsController . "/click_url", array("campaign_uid" => $data->campaign->campaign_uid, "url_id" => $data->url_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaign_reports', 'View all clicks for this url'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => '!app()->controller->getData("clickStartDate")',
                                ],
                                'urlclickstartdate' => [
                                    'label'     => IconHelper::make('info'),
                                    'url'       => 'createUrl(app()->controller->campaignReportsController . "/click_url", array("campaign_uid" => $data->campaign->campaign_uid, "url_id" => $data->url_id, "CampaignTrackUrl" => ["date_added" => sprintf(">=%s", html_encode(app()->controller->getData("clickStartDate")))]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaign_reports', 'View all clicks for this url'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'app()->controller->getData("clickStartDate")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{urlclick}{urlclickstartdate}',
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
                    <?php echo t('campaign_reports', 'This report shows all the urls from the email and the number of clicks each url received.'); ?>
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
