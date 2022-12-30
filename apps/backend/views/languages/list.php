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
 * @since 1.1
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Language $language */
$language = $controller->getData('language');

/** @var LanguageUploadForm $languageUpload */
$languageUpload = $controller->getData('languageUpload');

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
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-flag') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $language, 'columns' => ['name', 'language_code', 'region_code', 'is_default', 'date_added', 'last_updated']], true))
                    ->addIf(CHtml::link(IconHelper::make('upload') . t('languages', 'Upload language pack'), '#language-upload-modal', ['class' => 'btn btn-primary btn-flat', 'data-toggle' => 'modal', 'title' => t('languages', 'Upload language pack')]), AccessHelper::hasRouteAccess('languages/upload'))
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['languages/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['languages/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
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
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $language->getModelName() . '-grid',
                    'dataProvider'      => $language->search(),
                    'filter'            => $language,
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
                            'name'  => 'language_code',
                            'value' => '$data->language_code',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'region_code',
                            'value' => '$data->region_code',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'is_default',
                            'value' => 't("app", ucfirst($data->is_default))',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'last_updated',
                            'value' => '$data->lastUpdated',
                            'filter'=> false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $language->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'translations' => [
                                    'label'     => IconHelper::make('view'),
                                    'url'       => 'createUrl("translations/index", array("language_id" => $data->language_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'View translations'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("translations/index")',
                                ],
                                'update' => [
                                    'label'     => IconHelper::make('update'),
                                    'url'       => 'createUrl("languages/update", array("id" => $data->language_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("languages/update")',
                                ],
                                'export' => [
                                    'label'     => IconHelper::make('export'),
                                    'url'       => 'createUrl("languages/export", array("id" => $data->language_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Export'), 'target' => '_blank', 'class' => 'btn btn-primary btn-flat export'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("languages/export")',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("languages/delete", array("id" => $data->language_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("languages/delete") && $data->is_default === Language::TEXT_NO',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{translations} {update} {export} {delete}',
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
    <?php if (AccessHelper::hasRouteAccess('languages/upload')) { ?>
        <div class="modal fade" id="language-upload-modal" tabindex="-1" role="dialog" aria-labelledby="language-upload-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo t('languages', 'Upload language pack'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="callout callout-info">
                            <?php echo t('languages', 'Please note that only zip files are allowed for upload.'); ?><br />
                            <strong><?php echo t('app', 'Warning'); ?></strong>: <?php echo t('languages', 'Language packs contain executable PHP files, please check the packs before upload.'); ?>
                        </div>
                        <?php
                        /** @var CActiveForm $form */
                        $form = $controller->beginWidget('CActiveForm', [
                            'action'        => ['languages/upload'],
                            'id'            => $languageUpload->getModelName() . '-upload-form',
                            'htmlOptions'   => ['enctype' => 'multipart/form-data'],
                        ]);
                        ?>
                        <div class="form-group">
                            <?php echo $form->labelEx($languageUpload, 'archive'); ?>
                            <?php echo $form->fileField($languageUpload, 'archive', $languageUpload->fieldDecorator->getHtmlOptions('archive')); ?>
                            <?php echo $form->error($languageUpload, 'archive'); ?>
                        </div>
                        <?php $controller->endWidget(); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
                        <button type="button" class="btn btn-primary btn-flat" onclick="$('#<?php echo html_encode((string)$languageUpload->getModelName()); ?>-upload-form').submit();"><?php echo IconHelper::make('upload') . '&nbsp;' . t('app', 'Upload archive'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
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
