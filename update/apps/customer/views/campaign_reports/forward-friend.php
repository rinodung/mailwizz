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

/** @var Campaign_reportsController $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var CampaignForwardFriend $forward */
$forward = $controller->getData('forward');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('views_before_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) { ?>
    <div class="box box-primary borderless">
        <div class="box-header">
    		<div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('envelope') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
    		<div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $forward, 'columns' => ['subscriber_id', 'to_email', 'to_name', 'from_email', 'from_name', 'subject', 'message', 'ip_address', 'date_added']], true))
                    ->add(CHtml::link(IconHelper::make('envelope') . t('campaign_reports', 'Campaign overview'), [$controller->campaignOverviewRoute, 'campaign_uid' => $campaign->campaign_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('campaign_reports', 'Back to campaign overview')]))
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['campaign_reports/forward_friend', 'campaign_uid' => $campaign->campaign_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->render();
                ?>
    		</div>
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
            hooks()->doAction('views_before_grid', $collection = new CAttributeCollection([
                'controller'   => $controller,
                'renderGrid'   => true,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderGrid')) {
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute(), ['campaign_uid' => $campaign->campaign_uid]),
                    'id'                => $forward->getModelName() . '-grid',
                    'dataProvider'      => $forward->search(),
                    'filter'            => $forward,
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
                            'name'  => 'subscriber_id',
                            'value' => '!empty($data->subscriber_id) ? $data->subscriber->getDisplayEmail() : "-"',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'to_email',
                            'value' => '$data->to_email',
                        ],
                        [
                            'name'  => 'to_name',
                            'value' => '$data->to_name',
                        ],
                        [
                            'name'  => 'from_email',
                            'value' => '$data->from_email',
                        ],
                        [
                            'name'  => 'from_name',
                            'value' => '$data->from_name',
                        ],
                        [
                            'name'  => 'subject',
                            'value' => '$data->subject',
                        ],
                        [
                            'name'  => 'message',
                            'value' => '$data->message',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'ip_address',
                            'value' => '$data->ip_address',
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                            'filter'=> false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $forward->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [],
                            'htmlOptions' => [
                                'style' => 'width:70px;',
                            ],
                            'template' => '',
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
            hooks()->doAction('views_after_grid', new CAttributeCollection([
                'controller'   => $controller,
                'renderedGrid' => $collection->itemAt('renderGrid'),
            ]));
            ?>
            </div>
            <div class="clearfix"><!-- --></div>
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
hooks()->doAction('views_after_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
