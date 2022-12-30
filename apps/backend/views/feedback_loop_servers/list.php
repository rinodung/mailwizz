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
 * @since 1.3.3.1
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var FeedbackLoopServer $server */
$server = $controller->getData('server');

/** @var FeedbackLoopServerCsvImport $csvImport */
$csvImport = $controller->getData('csvImport');

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
    $itemsCount = (int)FeedbackLoopServer::model()->countByAttributes([
        'status' => array_keys($server->getStatusesList()),
    ]); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-transfer') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $server, 'columns' => ['customer_id', 'name', 'hostname', 'username', 'service', 'port', 'protocol', 'status']], true), $itemsCount)
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['feedback_loop_servers/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->addIf(HtmlHelper::accessLink(IconHelper::make('import') . t('app', 'Import'), '#csv-import-modal', ['data-toggle' => 'modal', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Import')]), AccessHelper::hasRouteAccess('feedback_loop_servers/import'))
                    ->addIf(CHtml::link(IconHelper::make('export') . t('app', 'Export'), ['feedback_loop_servers/export'], ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Export')]), $itemsCount)
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['feedback_loop_servers/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
        // since 1.3.5.4
        if (AccessHelper::hasRouteAccess('feedback_loop_servers/bulk_action')) {
            $controller->widget('common.components.web.widgets.GridViewBulkAction', [
                        'model'      => $server,
                        'formAction' => createUrl('feedback_loop_servers/bulk_action'),
                    ]);
        }
        $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $server->getModelName() . '-grid',
                    'dataProvider'      => $server->search(),
                    'filter'            => $server,
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
                            'name'                => 'server_id',
                            'selectableRows'      => 100,
                            'checkBoxHtmlOptions' => ['name' => 'bulk_item[]'],
                            'visible'             => AccessHelper::hasRouteAccess('feedback_loop_servers/bulk_action'),
                        ],
                        [
                            'name'  => 'customer_id',
                            'value' => '!empty($data->customer) ? $data->customer->getFullName() : t("app", "System")',
                            'filter'=> CHtml::activeTextField($server, 'customer_id'),
                        ],
                        [
                            'name'  => 'name',
                            'value' => 'empty($data->name) ? null : HtmlHelper::accessLink($data->name, array("feedback_loop_servers/update", "id" => $data->server_id), array("fallbackText" => true))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'hostname',
                            'value' => 'HtmlHelper::accessLink($data->hostname, array("feedback_loop_servers/update", "id" => $data->server_id), array("fallbackText" => true))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'username',
                            'value' => '$data->username',
                        ],
                        [
                            'name'  => 'service',
                            'value' => '$data->serviceName',
                            'filter'=> $server->getServicesArray(),
                        ],

                        [
                            'name'  => 'port',
                            'value' => '$data->port',
                        ],
                        [
                            'name'  => 'protocol',
                            'value' => '$data->protocolName',
                            'filter'=> $server->getProtocolsArray(),
                        ],
                        [
                            'name'  => 'status',
                            'value' => 'ucfirst(t("app", $data->status))',
                            'filter'=> $server->getStatusesList(),
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $server->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'update' => [
                                    'label'     => IconHelper::make('update'),
                                    'url'       => 'createUrl("feedback_loop_servers/update", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("feedback_loop_servers/update")',
                                ],
                                'copy'=> [
                                    'label'     => IconHelper::make('copy'),
                                    'url'       => 'createUrl("feedback_loop_servers/copy", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Copy'), 'class' => 'btn btn-primary btn-flat copy-server'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("feedback_loop_servers/copy")',
                                ],
                                'enable'=> [
                                    'label'     => IconHelper::make('glyphicon-open'),
                                    'url'       => 'createUrl("feedback_loop_servers/enable", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Enable'), 'class' => 'btn btn-primary btn-flat enable-server'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("feedback_loop_servers/enable") && $data->getIsDisabled()',
                                ],
                                'disable'=> [
                                    'label'     => IconHelper::make('glyphicon-save'),
                                    'url'       => 'createUrl("feedback_loop_servers/disable", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Disable'), 'class' => 'btn btn-primary btn-flat disable-server'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("feedback_loop_servers/disable") && $data->getIsActive()',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("feedback_loop_servers/delete", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("feedback_loop_servers/delete")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{update} {copy} {enable} {disable} {delete}',
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
                    <?php
                    $text = 'Please note, when adding a feedback loop server make sure the email address is used only for reading feedback email but nothing more.<br />
                    This is important since the script that checks the bounced emails needs to read all the emails from the account you specify and beside it can be time and memory consuming, it will also delete all the emails from the email account.';
    echo t('servers', StringHelper::normalizeTranslationString($text)); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="csv-import-modal" tabindex="-1" role="dialog" aria-labelledby="csv-import-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo t('servers', 'Import from CSV file'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="callout callout-info">
                        <?php echo t('servers', 'Please note, the csv file must contain a header with proper columns.'); ?><br />
                        <?php echo t('servers', 'If unsure about how to format your file, do an export first and see how the file looks.'); ?>
                    </div>
                    <?php
                    /** @var CActiveForm $form */
                    $form = $controller->beginWidget('CActiveForm', [
                        'action'        => ['feedback_loop_servers/import'],
                        'htmlOptions'   => [
                            'id'        => 'import-csv-form',
                            'enctype'   => 'multipart/form-data',
                        ],
                    ]); ?>
                    <div class="form-group">
                        <?php echo $form->labelEx($csvImport, 'file'); ?>
                        <?php echo $form->fileField($csvImport, 'file', $csvImport->fieldDecorator->getHtmlOptions('file')); ?>
                        <?php echo $form->error($csvImport, 'file'); ?>
                    </div>
                    <?php $controller->endWidget(); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
                    <button type="button" class="btn btn-primary btn-flat" onclick="$('#import-csv-form').submit();"><?php echo IconHelper::make('import') . '&nbsp;' . t('app', 'Import file'); ?></button>
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
