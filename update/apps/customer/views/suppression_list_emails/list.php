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
 * @since 1.4.4
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var CustomerSuppressionList $list */
$list = $controller->getData('list');

/** @var CustomerSuppressionListEmail $email */
$email = $controller->getData('email');

/** @var array $importUrl */
$importUrl = (array)$controller->getData('importUrl');

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
    $itemsCount = CustomerSuppressionListEmail::model()->countByAttributes([
        'list_id' => $list->list_id,
    ]); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-ban-circle') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $email, 'columns' => ['email', 'date_added', 'last_updated']], true), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['suppression_list_emails/create', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->addIf(CHtml::link(IconHelper::make('export') . t('app', 'Export'), ['suppression_list_emails/export', 'list_uid' => $list->list_uid], ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Export')]), $itemsCount)
                    ->add(CHtml::link(IconHelper::make('glyphicon-import') . t('app', 'Import'), '#csv-import-modal', ['data-toggle' => 'modal', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Import')]))
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['suppression_list_emails/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->add(CHtml::link(IconHelper::make('back') . t('app', 'Back'), ['suppression_lists/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Back')]))
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
        $controller->widget('common.components.web.widgets.GridViewBulkAction', [
                        'model'      => $email,
                        'formAction' => createUrl('suppression_list_emails/bulk_action', ['list_uid' => $list->list_uid]),
                    ]);
        $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                        'ajaxUrl'           => createUrl($controller->getRoute(), ['list_uid' => $list->list_uid]),
                        'id'                => $email->getModelName() . '-grid',
                        'dataProvider'      => $email->search(),
                        'filter'            => $email,
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
                                'name'                => 'email_id',
                                'selectableRows'      => 100,
                                'checkBoxHtmlOptions' => ['name' => 'bulk_item[]'],
                            ],
                            [
                                'name'  => 'email',
                                'value' => '$data->getDisplayEmail()',
                            ],
                            [
                                'class'     => 'DropDownButtonColumn',
                                'header'    => t('app', 'Options'),
                                'footer'    => $email->paginationOptions->getGridFooterPagination(),
                                'buttons'   => [
                                    'update' => [
                                        'label'     => IconHelper::make('update'),
                                        'url'       => 'createUrl("suppression_list_emails/update", array("list_uid" => $data->list->list_uid, "email_id" => $data->email_id))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    ],
                                    'delete' => [
                                        'label'     => IconHelper::make('delete'),
                                        'url'       => 'createUrl("suppression_list_emails/delete", array("list_uid" => $data->list->list_uid, "email_id" => $data->email_id))',
                                        'imageUrl'  => null,
                                        'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
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

    <div class="modal fade" id="csv-import-modal" tabindex="-1" role="dialog" aria-labelledby="csv-import-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo t('suppression_lists', 'Import from CSV file'); ?></h4>
                </div>
                <div class="modal-body">
                    <div class="callout callout-info">
                        <?php echo t('suppression_lists', 'Please note, the csv file must contain a header with the email column.'); ?><br />
                        <?php echo t('suppression_lists', 'If unsure about how to format your file, do an export first and see how the file looks.'); ?>
                    </div>
                    <?php
                    /** @var CActiveForm $form */
                    $form = $controller->beginWidget('CActiveForm', [
                        'action'        => $importUrl,
                        'htmlOptions'   => [
                            'id'        => 'import-csv-form',
                            'enctype'   => 'multipart/form-data',
                        ],
                    ]); ?>
                    <div class="form-group">
                        <?php echo $form->labelEx($email, 'file'); ?>
                        <?php echo $form->fileField($email, 'file', $email->fieldDecorator->getHtmlOptions('file')); ?>
                        <?php echo $form->error($email, 'file'); ?>
                    </div>
                    <?php $controller->endWidget(); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
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
