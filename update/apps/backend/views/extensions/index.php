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

/** @var Controller $controller */
$controller = controller();

/** @var ExtensionHandlerForm $model */
$model = $controller->getData('model');

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
                <h3 class="box-title">
                    <?php echo IconHelper::make('glyphicon-plus-sign') . t('extensions', 'Uploaded extensions'); ?>
                </h3>
            </div>
            <div class="pull-right">
                <?php
                if (AccessHelper::hasRouteAccess('extensions/upload')) {
                    echo CHtml::link(IconHelper::make('upload') . t('extensions', 'Upload extension'), '#extension-upload-modal', ['class' => 'btn btn-primary btn-flat', 'data-toggle' => 'modal', 'title' => t('extensions', 'Upload extension')]);
                }
                ?>
                <?php echo HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['extensions/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]); ?>
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
                'ID'            => 1,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderGrid')) {
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $model->getModelName() . '-grid',
                    'dataProvider'      => $model->getDataProvider(),
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
                            'name'  => t('extensions', 'Name'),
                            'value' => '$data["name"]',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => t('extensions', 'Description'),
                            'value' => '$data["description"]',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => t('extensions', 'Version'),
                            'value' => '$data["version"]',
                        ],
                        [
                            'name'  => t('extensions', 'Author'),
                            'value' => '$data["author"]',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => t('extensions', 'Website'),
                            'value' => '$data["website"]',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => t('extensions', 'Enabled'),
                            'value' => '$data["enabled"] ? t("app", "Yes") : t("app", "No")',
                            'type'  => 'raw',
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'afterDelete'=> 'function(){window.location.reload();}',
                            'buttons'    => [
                                'page' => [
                                    'label'     => IconHelper::make('view'),
                                    'url'       => '$data["pageUrl"]',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('extensions', 'Extension detail page'), 'class'=>'btn btn-primary btn-flat'],
                                    'visible'   => '$data["enabled"] && !empty($data["pageUrl"])',
                                ],
                                'enable' => [
                                    'label'     => IconHelper::make('glyphicon-ok'),
                                    'url'       => 'createUrl("extensions/enable", array("id" => $data["id"]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Enable'), 'class'=>'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("extensions/enable") && !$data["enabled"]',
                                ],
                                'disable' => [
                                    'label'     => IconHelper::make('glyphicon-ban-circle'),
                                    'url'       => 'createUrl("extensions/disable", array("id" => $data["id"]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Disable'), 'class'=>'btn btn-danger btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("extensions/disable") && $data["enabled"]',
                                ],
                                'update' => [
                                    'label'     => IconHelper::make('glyphicon-arrow-up'),
                                    'url'       => 'createUrl("extensions/update", array("id" => $data["id"]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class'=>'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("extensions/update") && $data["mustUpdate"]',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("extensions/delete", array("id" => $data["id"]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class'=>'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("extensions/delete")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{page} {enable} {disable} {update} {delete}',
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

    <hr />

    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <h3 class="box-title">
                    <?php echo IconHelper::make('glyphicon-plus-sign') . t('extensions', 'Core extensions'); ?>
                </h3>
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
                'ID'            => 2,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderGrid')) {
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $model->getModelName() . '-core-grid',
                    'dataProvider'      => $model->getDataProvider(true),
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
                            'name'  => t('extensions', 'Name'),
                            'value' => '$data["name"]',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => t('extensions', 'Description'),
                            'value' => '$data["description"]',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => t('extensions', 'Version'),
                            'value' => '$data["version"]',
                        ],
                        [
                            'name'  => t('extensions', 'Author'),
                            'value' => '$data["author"]',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => t('extensions', 'Website'),
                            'value' => '$data["website"]',
                            'type'  => 'raw',
                        ],
                        [
                            'name'  => t('extensions', 'Enabled'),
                            'value' => '$data["enabled"] ? t("app", "Yes") : t("app", "No")',
                            'type'  => 'raw',
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'afterDelete'=> 'function(){window.location.reload();}',
                            'buttons'    => [
                                'page' => [
                                    'label'     => IconHelper::make('view'),
                                    'url'       => '$data["pageUrl"]',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('extensions', 'Extension detail page'), 'class'=>'btn btn-primary btn-flat'],
                                    'visible'   => '$data["enabled"] && !empty($data["pageUrl"])',
                                ],
                                'enable' => [
                                    'label'     => IconHelper::make('glyphicon-ok'),
                                    'url'       => 'createUrl("extensions/enable", array("id" => $data["id"]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Enable'), 'class'=>'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("extensions/enable") && !$data["enabled"]',
                                ],
                                'disable' => [
                                    'label'     => IconHelper::make('glyphicon-ban-circle'),
                                    'url'       => 'createUrl("extensions/disable", array("id" => $data["id"]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Disable'), 'class'=>'btn btn-danger btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("extensions/disable") && $data["enabled"] && $data["canBeDisabled"]',
                                ],
                                'update' => [
                                    'label'     => IconHelper::make('glyphicon-arrow-up'),
                                    'url'       => 'createUrl("extensions/update", array("id" => $data["id"]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class'=>'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("extensions/update") && $data["mustUpdate"]',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("extensions/delete", array("id" => $data["id"]))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class'=>'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("extensions/delete") && $data["enabled"] && $data["canBeDeleted"]',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{page} {enable} {disable} {update} {delete}',
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

    <div class="modal fade" id="extension-upload-modal" tabindex="-1" role="dialog" aria-labelledby="extension-upload-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('extensions', 'Upload extension archive.'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                     <?php echo t('extensions', 'Please note that only zip files are allowed for upload.'); ?>
                 </div>
                <?php
                /** @var CActiveForm $form */
        $form = $controller->beginWidget('CActiveForm', [
                    'action'        => ['extensions/upload'],
                    'id'            => $model->getModelName() . '-upload-form',
                    'htmlOptions'   => ['enctype' => 'multipart/form-data'],
                ]);
                ?>
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'archive'); ?>
                    <?php echo $form->fileField($model, 'archive', $model->fieldDecorator->getHtmlOptions('archive')); ?>
                    <?php echo $form->error($model, 'archive'); ?>
                </div>
                <?php $controller->endWidget(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#<?php echo $model->getModelName(); ?>-upload-form').submit();"><?php echo IconHelper::make('upload') . '&nbsp;' . t('app', 'Upload archive'); ?></button>
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
