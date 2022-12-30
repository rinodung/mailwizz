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
 * @since 1.3.6.9
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var EmailBlacklistMonitor $monitor */
$monitor = $controller->getData('monitor');

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
    $itemsCount = EmailBlacklistMonitor::model()->count(); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-ban-circle') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $monitor, 'columns' => ['name', 'email_condition', 'email', 'reason_condition', 'reason', 'condition_operator', 'notifications_to', 'status', 'date_added']], true), $itemsCount)
                    ->addIf(HtmlHelper::accessLink(IconHelper::make('delete') . t('app', 'Remove all'), ['email_blacklist_monitors/delete_all'], ['class' => 'btn btn-danger btn-flat delete-all', 'title' => t('app', 'Remove all'), 'data-message' => t('dashboard', 'Are you sure you want to remove all blacklist monitors?')]), $itemsCount)
                    ->addIf(HtmlHelper::accessLink(IconHelper::make('export') . t('app', 'Export'), ['email_blacklist_monitors/export'], ['class' => 'btn btn-primary btn-flat', 'target' => '_blank', 'title' => t('app', 'Export')]), $itemsCount)
                    ->addIf(CHtml::link(IconHelper::make('import') . t('app', 'Import'), '#csv-import-modal', ['data-toggle' => 'modal', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Import')]), AccessHelper::hasRouteAccess('email_blacklist_monitors/import'))
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['email_blacklist_monitors/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['email_blacklist_monitors/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->add(CHtml::link(IconHelper::make('info'), '#page-info', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']))
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
                'controller'  => $controller,
                'renderGrid'  => true,
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
                    'id'                => $monitor->getModelName() . '-grid',
                    'dataProvider'      => $monitor->search(),
                    'filter'            => $monitor,
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
                            'value' => '$data->name',
                        ],
                        [
                            'name'  => 'email_condition',
                            'value' => 't("email_blacklist", ucfirst($data->email_condition))',
                            'filter'=> $monitor->getConditionsList(),
                        ],
                        [
                            'name'  => 'email',
                            'value' => '$data->email',
                        ],
                        [
                            'name'  => 'reason_condition',
                            'value' => 't("email_blacklist", ucfirst($data->reason_condition))',
                            'filter'=> $monitor->getConditionsList(),
                        ],
                        [
                            'name'  => 'reason',
                            'value' => '$data->reason',
                        ],
                        [
                            'name'  => 'condition_operator',
                            'value' => 't("email_blacklist", ucfirst($data->condition_operator))',
                            'filter'=> $monitor->getConditionOperatorsList(),
                        ],
                        [
                            'name'  => 'notifications_to',
                            'value' => '$data->notifications_to',
                            'filter'=> true,
                        ],
                        [
                            'name'  => 'status',
                            'value' => '$data->getStatusName()',
                            'filter'=> $monitor->getStatusesList(),
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                            'filter'=> false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $monitor->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'update' => [
                                    'label'     => IconHelper::make('update'),
                                    'url'       => 'createUrl("email_blacklist_monitors/update", array("id" => $data->monitor_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("email_blacklist_monitors/update")',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("email_blacklist_monitors/delete", array("id" => $data->monitor_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("email_blacklist_monitors/delete")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{update} {delete}',
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
            ])); ?>
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
                    <?php echo t('email_blacklist', 'Blacklist monitors will monitor the email blacklist and when emails matching the conditions will be added in the blacklist, they will be removed automatically and subscribers matching the emails will be marked back as confirmed.'); ?><br />
                    <?php echo t('email_blacklist', 'Please note that in order for the monitoring to work, you need to add the following cron job, which runs once per hour:'); ?><br />
                    <b>0 * * * * <?php echo CommonHelper::findPhpCliPath(); ?> -q <?php echo MW_PATH; ?>/apps/console/console.php email-blacklist-monitor >/dev/null 2>&1 </b>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="csv-import-modal" tabindex="-1" role="dialog" aria-labelledby="csv-import-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo t('email_blacklist', 'Import from CSV file'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="callout callout-info">
                        <?php echo t('email_blacklist', 'If unsure about how to format your file, do an export first and see how the file looks.'); ?>
                    </div>
                    <?php
                    /** @var CActiveForm $form */
        $form = $controller->beginWidget('CActiveForm', [
                        'action'        => ['email_blacklist_monitors/import'],
                        'htmlOptions'   => [
                            'id'        => 'import-csv-form',
                            'enctype'   => 'multipart/form-data',
                        ],
                    ]); ?>
                    <div class="form-group">
                        <?php echo $form->labelEx($monitor, 'file'); ?>
                        <?php echo $form->fileField($monitor, 'file', $monitor->fieldDecorator->getHtmlOptions('file')); ?>
                        <?php echo $form->error($monitor, 'file'); ?>
                    </div>
                    <?php $controller->endWidget(); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
                    <button type="button" class="btn btn-primary btn-flat" onclick="$('#import-csv-form').submit();"><?php echo t('app', 'Import file'); ?></button>
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
