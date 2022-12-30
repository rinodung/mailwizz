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
 * @since 1.3
 */

/** @var Controller $controller */
$controller = controller();

/** @var ThemeHandlerForm $model */
$model = $controller->getData('model');

/** @var array $apps */
$apps = (array)$controller->getData('apps');

/** @var string $app */
$app = (string)$controller->getData('app');

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
				    <?php echo IconHelper::make('glyphicon-plus-sign') . t('themes', 'Available themes'); ?>
                </h3>
            </div>
            <div class="pull-right">
			    <?php
                if (AccessHelper::hasRouteAccess('theme/upload')) {
                    echo CHtml::link(IconHelper::make('upload') . t('themes', 'Upload theme'), '#theme-upload-modal', ['class' => 'btn btn-primary btn-flat', 'data-toggle' => 'modal', 'title' => t('themes', 'Upload theme')]);
                }
                ?>
			    <?php echo HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['theme/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <ul class="nav nav-tabs" style="border-bottom: 0px;">
		        <?php foreach ($apps as $appName) {?>
                    <li class="<?php echo (string)$app == (string)$appName ? 'active' : 'inactive'; ?>"><a href="<?php echo createUrl('theme/index', ['app' => html_encode((string)$appName)]); ?>"><?php echo t('app', ucfirst(html_encode((string)$appName))); ?></a></li>
		        <?php } ?>
            </ul>
            <div class="box box-primary borderless">
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
                                'ajaxUrl'           => createUrl($controller->getRoute()),
                                'id'                => $model->getModelName() . '-grid',
                                'dataProvider'      => $model->getDataProvider($app),
                                'filter'            => null,
                                'filterPosition'    => 'body',
                                'filterCssClass'    => 'grid-filter-cell',
                                'itemsCssClass'     => 'table table-hover',
                                'selectableRows'    => 0,
                                'enableSorting'     => false,
                                'cssFile'           => false,
                                'pager'             => false,
                                'columns' => hooks()->applyFilters('grid_view_columns', [
                                    [
                                        'name'  => t('themes', 'Name'),
                                        'value' => '$data["name"]',
                                        'type'  => 'raw',
                                    ],
                                    [
                                        'name'  => t('themes', 'Description'),
                                        'value' => '$data["description"]',
                                        'type'  => 'raw',
                                    ],
                                    [
                                        'name'  => t('themes', 'Version'),
                                        'value' => '$data["version"]',
                                    ],
                                    [
                                        'name'  => t('themes', 'Author'),
                                        'value' => '$data["author"]',
                                        'type'  => 'raw',
                                    ],
                                    [
                                        'name'  => t('themes', 'Website'),
                                        'value' => '$data["website"]',
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
                                                'options'   => ['title' => t('themes', 'Theme detail page'), 'class'=>'btn btn-primary btn-flat'],
                                                'visible'   => '$data["enabled"] && !empty($data["pageUrl"])',
                                            ],
                                            'enable' => [
                                                'label'     => IconHelper::make('glyphicon-ok'),
                                                'url'       => '$data["enableUrl"]',
                                                'imageUrl'  => null,
                                                'options'   => ['title' => t('app', 'Enable'), 'class'=>'btn btn-primary btn-flat'],
                                                'visible'   => 'AccessHelper::hasRouteAccess("theme/enable") && !$data["enabled"]',
                                            ],
                                            'disable' => [
                                                'label'     => IconHelper::make('glyphicon-ban-circle'),
                                                'url'       => '$data["disableUrl"]',
                                                'imageUrl'  => null,
                                                'options'   => ['title' => t('app', 'Disable'), 'class'=>'btn btn-danger btn-flat'],
                                                'visible'   => 'AccessHelper::hasRouteAccess("theme/disable") && $data["enabled"]',
                                            ],
                                            'delete' => [
                                                'label'     => IconHelper::make('glyphicon-remove'),
                                                'url'       => '$data["deleteUrl"]',
                                                'imageUrl'  => null,
                                                'options'   => ['title' => t('app', 'Delete'), 'class'=>'btn btn-danger btn-flat delete'],
                                                'visible'   => 'AccessHelper::hasRouteAccess("theme/delete")',
                                            ],
                                        ],
                                        'headerHtmlOptions' => ['style' => 'text-align: right'],
                                        'footerHtmlOptions' => ['align' => 'right'],
                                        'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                                        'template'          => '{page} {enable} {disable} {delete}',
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
        </div>
    </div>    
    
    <div class="modal fade" id="theme-upload-modal" tabindex="-1" role="dialog" aria-labelledby="theme-upload-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('themes', 'Upload theme archive.'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                     <?php echo t('themes', 'Please note that only zip files are allowed for upload.'); ?>
                 </div>
                <?php
                /** @var CActiveForm $form */
        $form = $controller->beginWidget('CActiveForm', [
                    'action'        => ['theme/upload', 'app' => $app],
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
