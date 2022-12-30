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

/** @var DeliveryServer $server */
$server = $controller->getData('server');

/** @var DeliveryServerCsvImport $csvImport */
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
    $itemsCount = (int)DeliveryServer::model()->countByAttributes([
        'customer_id' => (int)customer()->getId(),
        'status'      => array_keys($server->getStatusesList()),
    ]); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-send') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php $box = BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $server, 'columns' => ['server_id', 'name', 'hostname', 'username', 'from_email', 'type', 'status']], true), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('create') . t('app', 'Create new server'), '#select-server-type-modal', ['data-toggle' => 'modal', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new server')]))
                    ->add(CHtml::link(IconHelper::make('import') . t('app', 'Import'), '#csv-import-modal', ['data-toggle' => 'modal', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Import')]))
                    ->addIf(CHtml::link(IconHelper::make('export') . t('app', 'Export'), ['delivery_servers/export'], ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Export')]), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['delivery_servers/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->render();
    unset($box); ?>
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
        $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $server->getModelName() . '-grid',
                    'dataProvider'      => $server->search(),
                    'filter'            => $server,
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
                            'name'  => 'server_id',
                            'value' => '$data->server_id',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'name',
                            'value' => 'empty($data->name) ? null : CHtml::link($data->name, createUrl("delivery_servers/update", array("type" => $data->type, "id" => $data->server_id)))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'hostname',
                            'value' => 'CHtml::link($data->hostname, createUrl("delivery_servers/update", array("type" => $data->type, "id" => $data->server_id)))',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => 'username',
                            'value' => '$data->username',
                        ],
                        [
                            'name'  => 'from_email',
                            'value' => '$data->from_email',
                        ],
                        [
                            'name'  => 'type',
                            'value' => 'DeliveryServer::getNameByType($data->type)',
                            'filter'=> $server->getCustomerTypesList(),
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
                                    'url'       => 'createUrl("delivery_servers/update", array("type" => $data->type, "id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => '$data->getCanBeUpdated()',
                                ],
                                'copy'=> [
                                    'label'     => IconHelper::make('copy'),
                                    'url'       => 'createUrl("delivery_servers/copy", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Copy'), 'class' => 'btn btn-primary btn-flat copy-server'],
                                ],
                                'enable'=> [
                                    'label'     => IconHelper::make('glyphicon-open'),
                                    'url'       => 'createUrl("delivery_servers/enable", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Enable'), 'class' => 'btn btn-primary btn-flat enable-server'],
                                    'visible'   => '$data->getIsDisabled()',
                                ],
                                'disable'=> [
                                    'label'     => IconHelper::make('save'),
                                    'url'       => 'createUrl("delivery_servers/disable", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Disable'), 'class' => 'btn btn-danger btn-flat disable-server'],
                                    'visible'   => '$data->getIsActive()',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("delivery_servers/delete", array("id" => $data->server_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => '$data->getCanBeDeleted()',
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
                'controller'    => $controller,
                'renderedGrid'  => $collection->itemAt('renderGrid'),
            ])); ?>
            <div class="clearfix"><!-- --></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="select-server-type-modal" tabindex="-1" role="dialog" aria-labelledby="select-server-type-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo t('servers', 'Select a delivery server type'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="search-box">
                        <input name="search" type="text" class="form-control" placeholder="<?php echo t('servers', 'Search or scroll for more...'); ?>" />
                    </div>
                    <div class="clearfix"><!-- --></div>
                    <ul class="select-delivery-servers-list">
                        <div class="mouse-scroll-indicator top-right no-border">
                            <span class="sm"></span>
                        </div>
                        <?php foreach (DeliveryServer::getCustomerTypesMapping(customer()->getModel()) as $type => $className) {
                /** @var DeliveryServer $instance */
                $instance = new $className(); ?>
                            <li>
                                <a href="<?php echo createUrl('delivery_servers/create', ['type' => $instance->type]); ?>">
                                    <?php echo html_encode((string)$instance->getTypeName()); ?>
                                    <?php if ($instance->getIsRecommended()) { ?><span title="<?php echo t('servers', 'Recommended'); ?>"><?php echo IconHelper::make('fa-thumbs-up'); ?></span><?php } ?>
                                </a>

                                <?php if ($providerUrl = $instance->getProviderUrl()) { ?>
                                    <a href="<?php echo html_encode((string)$providerUrl); ?>" target="_blank" title="<?php echo t('servers', 'Sign up'); ?>">
                                        <?php echo IconHelper::make('fa-sign-in'); ?>
                                    </a>
                                <?php } ?>
                            </li>
                        <?php
            } ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
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
                        'action'        => ['delivery_servers/import'],
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
