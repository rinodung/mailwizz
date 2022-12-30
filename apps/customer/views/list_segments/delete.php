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

/** @var Lists $list */
$list = $controller->getData('list');

/** @var ListSegment $segment */
$segment = $controller->getData('segment');

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var int $campaignsCount */
$campaignsCount = (int)$controller->getData('campaignsCount');

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
    <?php echo CHtml::form(); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <h3 class="box-title">
                    <?php echo IconHelper::make('glyphicon-remove-circle') . $pageHeading; ?>
                </h3>
            </div>
            <div class="pull-right">
                <?php echo CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['list_segments/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <hr />

            <div class="alert alert-danger alert-dismissable">
                <i class="fa fa-ban"></i>
                <strong>
                    <?php echo t('lists', 'This action will remove {campaigns} campaigns.', [
                        '{campaigns}'   => $campaignsCount,
                    ]); ?>
                    <br />
                    <?php echo t('lists', 'Are you still sure you want to remove this list segment? There is no coming back after you do it!'); ?>
                </strong>
            </div>

            <hr />
            <h5><?php echo t('lists', 'Following campaigns will be removed'); ?></h5>
            <div class="table-responsive">
            <?php
            $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                'ajaxUrl'           => createUrl($controller->getRoute()),
                'id'                => $campaign->getModelName() . '-grid',
                'dataProvider'      => $campaign->search(),
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
                    ],
                    [
                        'name'  => 'type',
                        'value' => 'ucfirst(strtolower((string)$data->getTypeNameDetails()))',
                        'type'  => 'raw',
                        'filter'=> $campaign->getTypesList(),
                        'htmlOptions' => ['style' => 'max-width: 150px'],
                    ],
                    [
                        'name'  => 'status',
                        'value' => '$data->getStatusWithStats()',
                        'filter'=> $campaign->getStatusesList(),
                    ],
                    [
                        'name'  => 'date_added',
                        'value' => '$data->dateAdded',
                        'filter'=> false,
                    ],
                    [
                        'name'  => 'send_at',
                        'value' => '$data->getSendAt()',
                        'filter'=> false,
                    ],
                    [
                        'class'     => 'DropDownButtonColumn',
                        'header'    => t('app', 'Options'),
                        'footer'    => $campaign->paginationOptions->getGridFooterPagination(),
                        'buttons'   => [
                            'overview'=> [
                                'label'     => IconHelper::make('info'),
                                'url'       => 'createUrl("campaigns/overview", array("campaign_uid" => $data->campaign_uid))',
                                'imageUrl'  => null,
                                'options'   => ['title' => t('campaigns', 'Overview'), 'class' => 'btn btn-primary btn-flat'],
                                'visible'   => '!$data->getEditable() || $data->getIsPaused()',
                            ],
                            'update'=> [
                                'label'     => IconHelper::make('update'),
                                'url'       => 'createUrl("campaigns/update", array("campaign_uid" => $data->campaign_uid))',
                                'imageUrl'  => null,
                                'visible'   => '$data->getEditable()',
                                'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                            ],
                        ],
                        'headerHtmlOptions' => ['style' => 'text-align: right'],
                        'footerHtmlOptions' => ['align' => 'right'],
                        'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                        'template'          => '{overview} {update}',
                    ],
                ], $controller),
            ], $controller));
            ?>
            <div class="clearfix"><!-- --></div>
            </div>
        </div>
        <div class="box-footer">
            <div class="pull-right">
                <button type="submit" class="btn btn-danger btn-flat"><?php echo IconHelper::make('delete') . t('app', 'I understand, delete it!'); ?></button>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
    </div>
    <?php echo CHtml::endForm(); ?>
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
