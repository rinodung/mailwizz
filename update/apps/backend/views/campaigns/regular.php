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
 * @since @since 1.3.5.5
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
if ($viewCollection->itemAt('renderContent')) {
    /**
     * @since 1.3.9.2
     */
    $itemsCount = (int)Campaign::model()->countByAttributes([
        'status' => array_keys($campaign->getStatusesList()),
    ]); ?>

    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('envelope') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $campaign, 'columns' => ['campaign_id', 'campaign_uid', 'customer_id', 'name', 'group_id', 'send_group_id', 'list_id', 'segment_id', 'search_recurring', 'status', 'send_at', 'started_at', 'search_template_name', 'gridViewDelivered', 'gridViewOpens', 'gridViewClicks', 'gridViewBounces', 'gridViewUnsubs']], true), $itemsCount)
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['campaigns/regular'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
        // since 1.3.5.6
        if (AccessHelper::hasRouteAccess('campaigns/bulk_action')) {
            $controller->widget('common.components.web.widgets.GridViewBulkAction', [
                        'model'      => $campaign,
                        'formAction' => createUrl('campaigns/bulk_action'),
                    ]);
        }
        $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $campaign->getModelName() . '-grid',
                    'dataProvider'      => $campaign->search(),
                    'filter'            => $campaign,
                    'filterPosition'    => 'body',
                    'filterCssClass'    => 'grid-filter-cell',
                    'itemsCssClass'     => 'table table-hover',
                    'selectableRows'    => 0,
                    'enableSorting'     => true,
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
                            'name'                => 'campaign_uid',
                            'selectableRows'      => 100,
                            'checkBoxHtmlOptions' => ['name' => 'bulk_item[]'],
                        ],
                        [
                            'name'  => 'campaign_id',
                            'value' => '$data->campaign_id',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'campaign_uid',
                            'value' => 'HtmlHelper::accessLink($data->campaign_uid, array("campaigns/overview", "campaign_uid" => $data->campaign_uid), array("fallbackText" => true))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'customer_id',
                            'value' => 'HtmlHelper::accessLink($data->customer->getFullName(), array("customers/update", "id" => $data->customer_id), array("fallbackText" => true))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'name',
                            'value' => 'HtmlHelper::accessLink($data->name, array("campaigns/overview", "campaign_uid" => $data->campaign_uid), array("fallbackText" => true))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'group_id',
                            'value' => '!empty($data->group_id) ? $data->group->name : "-"',
                            'filter'=> $campaign->getGroupsDropDownArray(),
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'send_group_id',
                            'value' => '!empty($data->send_group_id) ? $data->sendGroup->name : "-"',
                            'filter'=> $campaign->getSendGroupsDropDownArray(),
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'list_id',
                            'value' => 'StringHelper::truncateLength($data->list->name, 100)',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'segment_id',
                            'value' => '!empty($data->segment_id) ? StringHelper::truncateLength($data->segment->name, 100) : "-"',
                            'type'  => 'raw',
                        ],
                        [
                            'name'        => 'search_recurring',
                            'value'       => 't("app", $data->option->cronjob_enabled ? "Yes" : "No")',
                            'filter'      => $campaign->getYesNoOptions(),
                            'htmlOptions' => ['style' => 'max-width: 150px'],
                            'sortable'    => false,
                        ],
                        [
                            'name'  => 'send_at',
                            'value' => '$data->getSendAt()',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'started_at',
                            'value' => '$data->getStartedAt()',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'status',
                            'value' => '$data->getStatusWithStats()',
                            'filter'=> $campaign->getStatusesList(),
                        ],
                        [
                            'name'      => 'search_template_name',
                            'value'     => '!empty($data->template) ? $data->template->name : ""',
                            'sortable'  => false,
                        ],
                        [
                            'name'      => 'gridViewDelivered',
                            'value'     => '$data->getGridViewDelivered()',
                            'filter'    => false,
                            'sortable'  => false,
                        ],
                        [
                            'name'      => 'gridViewOpens',
                            'value'     => '$data->getGridViewOpens()',
                            'filter'    => false,
                            'sortable'  => false,
                        ],
                        [
                            'name'      => 'gridViewClicks',
                            'value'     => '$data->getGridViewClicks()',
                            'filter'    => false,
                            'sortable'  => false,
                        ],
                        [
                            'name'      => 'gridViewBounces',
                            'value'     => '$data->getGridViewBounces()',
                            'filter'    => false,
                            'sortable'  => false,
                        ],
                        [
                            'name'      => 'gridViewUnsubs',
                            'value'     => '$data->getGridViewUnsubs()',
                            'filter'    => false,
                            'sortable'  => false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $campaign->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'quick-view'=> [
                                    'label'     => IconHelper::make('fa-search-plus'),
                                    'url'       => 'createUrl("campaigns/quick_view", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Quick view'), 'class' => 'btn btn-primary btn-flat btn-campaign-quick-view'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/quick_view") && !$data->isPendingDelete',
                                ],
                                'overview'=> [
                                    'label'     => IconHelper::make('glyphicon-info-sign'),
                                    'url'       => 'createUrl("campaigns/overview", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Overview'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/overview") && (!$data->editable || $data->isPaused) && !$data->isPendingDelete && !$data->getIsDraft()',
                                ],
                                'approve'=> [
                                    'label'     => IconHelper::make('glyphicon-check'),
                                    'url'       => 'createUrl("campaigns/approve", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Approve campaign'), 'class' => 'btn btn-primary btn-flat approve', 'data-message' => t('campaigns', 'Are you sure you want to approve this campaign for sending?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/approve") && $data->canBeApproved',
                                ],
                                'disapprove'=> [
                                    'label'     => IconHelper::make('glyphicon-remove-circle'),
                                    'url'       => 'createUrl("campaigns/disapprove", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Disapprove campaign'), 'class' => 'btn btn-primary btn-flat disapprove', 'data-message' => t('campaigns', 'Are you sure you want to disapprove this campaign for sending?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/disapprove") && $data->canBeApproved',
                                ],
                                'pause'=> [
                                    'label'     => IconHelper::make('glyphicon-pause'),
                                    'url'       => 'createUrl("campaigns/pause_unpause", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Pause sending'), 'class' => 'btn btn-primary btn-flat pause-sending', 'data-message' => t('campaigns', 'Are you sure you want to pause this campaign ?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/pause_unpause") && $data->canBePaused',
                                ],
                                'unpause'=> [
                                    'label'     => IconHelper::make('glyphicon-play-circle'),
                                    'url'       => 'createUrl("campaigns/pause_unpause", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Unpause sending'), 'class' => 'btn btn-primary btn-flat unpause-sending', 'data-message' => t('campaigns', 'Are you sure you want to unpause sending emails for this campaign ?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/pause_unpause") && $data->isPaused',
                                ],
                                'block'=> [
                                    'label'     => IconHelper::make('glyphicon-off'),
                                    'url'       => 'createUrl("campaigns/block_unblock", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Block sending'), 'class' => 'btn btn-primary btn-flat block-sending', 'data-message' => t('campaigns', 'Are you sure you want to block this campaign ?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/block_unblock") && !$data->canBeApproved && $data->canBeBlocked',
                                ],
                                'unblock'=> [
                                    'label'     => IconHelper::make('fa-play'),
                                    'url'       => 'createUrl("campaigns/block_unblock", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Unblock sending'), 'class' => 'btn btn-primary btn-flat unblock-sending', 'data-message' => t('campaigns', 'Are you sure you want to unblock sending emails for this campaign ?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/block_unblock") && $data->isBlocked',
                                ],
                                'reset'=> [
                                    'label'     => IconHelper::make('refresh'),
                                    'url'       => 'createUrl("campaigns/resume_sending", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Resume sending'), 'class' => 'btn btn-primary btn-flat resume-campaign-sending', 'data-message' => t('campaigns', 'Resume sending, use this option if you are 100% sure your campaign is stuck and does not send emails anymore!')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/resume_sending") && $data->canBeResumed',
                                ],
                                'webversion'=> [
                                    'label'     => IconHelper::make('view'),
                                    'url'       => 'options()->get("system.urls.frontend_absolute_url") . "campaigns/" . $data->campaign_uid',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Web version'), 'class' => 'btn btn-primary btn-flat', 'target' => '_blank'],
                                    'visible'   => '$data->canViewWebVersion',
                                ],
                                'marksent'=> [
                                    'label'     => IconHelper::make('glyphicon-ok'),
                                    'url'       => 'createUrl("campaigns/marksent", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Mark as sent'), 'class' => 'btn btn-primary btn-flat mark-campaign-as-sent', 'data-message' => t('campaigns', 'Are you sure you want to mark this campaign as sent ?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/marksent") && $data->canBeMarkedAsSent',
                                ],
                                'resendgiveups'=> [
                                    'label'     => IconHelper::make('glyphicon-envelope'),
                                    'url'       => 'createUrl("campaigns/resend_giveups", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('campaigns', 'Resend giveups'), 'class' => 'btn btn-primary btn-flat resend-campaign-giveups', 'data-message' => t('campaigns', 'This will resend the campaign but only to the subscribers where the system was not able to send first time. Are you sure?')],
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/resend_giveups") && $data->getCanShowResetGiveupsButton()',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("campaigns/delete", array("campaign_uid" => $data->campaign_uid))',
                                    'imageUrl'  => null,
                                    'visible'   => 'AccessHelper::hasRouteAccess("campaigns/delete") && $data->removable',
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{quick-view} {overview} {approve} {disapprove} {pause} {unpause} {reset} {block} {unblock} {webversion} {marksent} {resendgiveups} {delete}',
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
    /**
     * Since 1.9.17
     * This creates a modal placeholder to push campaign info in.
     */
    $controller->widget('common.components.web.widgets.CampaignQuickViewWidget');

    /**
     * Since 1.9.17
     * This creates a modal placeholder to push campaign comparison info in.
     */
    $controller->widget('common.components.web.widgets.CampaignsCompareWidget');

    /**
     * Since 2.0.18
     * This adds the modal for when a campaign that requires approval is disapproved
     */
    $controller->renderPartial('_disapprove-modal'); ?>
    
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
