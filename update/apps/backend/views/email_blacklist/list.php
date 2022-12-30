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

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var EmailBlacklist $blacklist */
$blacklist = $controller->getData('blacklist');

/** @var EmailBlacklistFilters $filter */
$filter = $controller->getData('filter');

/** @var array $importUrl */
$importUrl = $controller->getData('importUrl');

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
    $itemsCount = EmailBlacklist::model()->count(); ?>

    <?php $controller->renderPartial('_filters'); ?>

    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-ban-circle') . html_encode((string)$pageHeading) . '</h3>')
                    ->render(); ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->addIf($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $filter, 'columns' => ['email', 'reason', 'date_added']], true), $itemsCount)
                    ->addIf(HtmlHelper::accessLink(IconHelper::make('delete') . t('app', 'Remove all'), ['email_blacklist/delete_all'], ['class' => 'btn btn-danger btn-flat delete-all', 'title' => t('app', 'Remove all'), 'data-message' => t('dashboard', 'Are you sure you want to remove all blacklisted emails?')]), $itemsCount)
                    ->addIf(HtmlHelper::accessLink(IconHelper::make('export') . t('app', 'Export'), ['email_blacklist/export'], ['class' => 'btn btn-primary btn-flat', 'target' => '_blank', 'title' => t('app', 'Export')]), $itemsCount)
                    ->addIf(CHtml::link(IconHelper::make('import') . t('app', 'Import'), '#csv-import-modal', ['data-toggle' => 'modal', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Import')]), AccessHelper::hasRouteAccess('email_blacklist/import'))
                    ->add(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['email_blacklist/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['email_blacklist/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->addIf(CHtml::link(IconHelper::make('filter') . t('app', 'Filters'), 'javascript:;', ['class' => 'btn btn-primary btn-flat toggle-filters-form', 'title' => t('app', 'Filters')]), $itemsCount)
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
        if (AccessHelper::hasRouteAccess('email_blacklist/bulk_action')) {
            $controller->widget('common.components.web.widgets.GridViewBulkAction', [
                        'model'      => $filter,
                        'formAction' => createUrl('email_blacklist/bulk_action'),
                    ]);
        }
        $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
                    'id'                => $filter->getModelName() . '-grid',
                    'dataProvider'      => $filter->getActiveDataProvider(),
                    'filter'            => null,
                    'filterPosition'    => 'body',
                    'filterCssClass'    => 'grid-filter-cell',
                    'itemsCssClass'     => 'table table-hover',
                    'selectableRows'    => true,
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
                            'checkBoxHtmlOptions' => ['name' => 'email_id[]'],
                            'visible'             => AccessHelper::hasRouteAccess('email_blacklist/bulk_action'),
                        ],
                        [
                            'name'  => 'email',
                            'value' => '$data->email',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'reason',
                            'value' => '$data->reason',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'date_added',
                            'value' => '$data->dateAdded',
                            'filter'=> false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $filter->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'update' => [
                                    'label'     => IconHelper::make('update'),
                                    'url'       => 'createUrl("email_blacklist/update", array("id" => $data->email_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Update'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("email_blacklist/update")',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("email_blacklist/delete", array("id" => $data->email_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("email_blacklist/delete")',
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
              <h4 class="modal-title"><?php echo t('email_blacklist', 'Import from CSV file'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                    <?php echo t('email_blacklist', 'Please note, the csv file must contain a header with at least the email column.'); ?><br />
                    <?php echo t('email_blacklist', 'If unsure about how to format your file, do an export first and see how the file looks.'); ?>
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
                    <?php echo $form->labelEx($blacklist, 'file'); ?>
                    <?php echo $form->fileField($blacklist, 'file', $filter->fieldDecorator->getHtmlOptions('file')); ?>
                    <?php echo $form->error($blacklist, 'file'); ?>
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
