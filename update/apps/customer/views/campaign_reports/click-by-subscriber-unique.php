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

/** @var CampaignTrackUrl $model */
$model = $controller->getData('model');

/** @var ListSubscriber $subscriber */
$subscriber = $controller->getData('subscriber');

/** @var CActiveDataProvider $dataProvider */
$dataProvider = $controller->getData('dataProvider');

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
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $model, 'columns' => ['url.destination', 'ip_address', 'user_agent', 'date_added']], true))
                    ->add(CHtml::link(t('campaign_reports', 'All clicks'), [$controller->campaignReportsController . '/click', 'campaign_uid' => $campaign->campaign_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('campaign_reports', 'Back to all clicks report')]))
                    ->add(CHtml::link(t('campaign_reports', 'All subscriber clicks'), [$controller->campaignReportsController . '/click_by_subscriber', 'campaign_uid' => $campaign->campaign_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('campaign_reports', 'Back to all subscriber clicks')]))
                    ->addIf(CHtml::link(IconHelper::make('export') . t('campaign_reports', 'Export reports'), [$controller->campaignReportsExportController . '/click_by_subscriber_unique', 'campaign_uid' => $campaign->campaign_uid, 'subscriber_uid' => $subscriber->subscriber_uid], ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('campaign_reports', 'Export reports')]), !empty($canExportStats))
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
                    'ajaxUrl'           => createUrl($controller->getRoute(), ['campaign_uid' => $campaign->campaign_uid, 'subscriber_uid' => $subscriber->subscriber_uid]),
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
                            'name'  => 'url.destination',
                            'value' => '$data->url->getDisplayGridDestination()',
                            'type'  => 'raw',
                            'htmlOptions' => ['style' => 'max-width:250px;word-wrap:break-word;'],
                        ],
                        [
                            'name'  => 'clicked_times',
                            'value' => '$data->counter',
                        ],
                        [
                            'name'  => 'ip_address',
                            'value' => 'CHtml::link($data->getIpWithLocationForGrid(), CommonHelper::getIpAddressInfoUrl((string)$data->ip_address), array("target" => "_blank"))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'user_agent',
                            'value' => 'CHtml::link($data->user_agent, CommonHelper::getUserAgentInfoUrl((string)$data->user_agent), array("target" => "_blank"))',
                            'type'  => 'raw',
                            'htmlOptions' => ['style' => 'max-width:220px;word-wrap:break-word;'],
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
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
                    $text = 'This page shows the urls the subscriber <b>{email}</b> clicked on.<br />
                    If the subscriber clicked same link twice, you will see it only once and you will see the number of clicks it received.<br />
                    If you need to see all the clicks and their click time for this subscriber, please click 
                    <a href="{href}">here</a>.';
                    echo t('campaign_reports', StringHelper::normalizeTranslationString($text), [
                        '{email}'   => $subscriber->getDisplayEmail(),
                        '{href}'    => createUrl('campaign_reports/click_by_subscriber', ['campaign_uid' => $campaign->campaign_uid, 'subscriber_uid' => $subscriber->subscriber_uid]),
                    ]);
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
