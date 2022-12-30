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
 * @since 1.3.7.3
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Article $model */
$model = $controller->getData('model');

/** @var OptionUrl $optionUrl */
$optionUrl = $controller->getData('optionUrl');

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
                    ->add('<h3 class="box-title">' . IconHelper::make('envelope') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $model, 'columns' => ['email', 'ip_address', 'user_agent', 'date_added']], true))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['block_email_request/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->add(CHtml::link(IconHelper::make('info'), '#page-info', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']))
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
                'controller' => $controller,
                'renderGrid' => true,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderGrid')) {

                // since 1.7.3
                try {
                    $controller->widget('common.components.web.widgets.GridViewBulkAction', [
                        'model'      => $model,
                        'formAction' => createUrl('block_email_request/bulk_action'),
                    ]);
                } catch (Exception $e) {
                }

                try {
                    $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                        'ajaxUrl'        => createUrl($controller->getRoute()),
                        'id'             => $model->getModelName() . '-grid',
                        'dataProvider'   => $model->search(),
                        'filter'         => $model,
                        'filterPosition' => 'body',
                        'filterCssClass' => 'grid-filter-cell',
                        'itemsCssClass'  => 'table table-hover',
                        'selectableRows' => 0,
                        'enableSorting'  => false,
                        'cssFile'        => false,
                        'pagerCssClass'  => 'pagination pull-right',
                        'pager'          => [
                            'class'       => 'CLinkPager',
                            'cssFile'     => false,
                            'header'      => false,
                            'htmlOptions' => ['class' => 'pagination'],
                        ],
                        'columns'        => hooks()->applyFilters('grid_view_columns', [
                            [
                                'class'               => 'CCheckBoxColumn',
                                'name'                => 'email_id',
                                'selectableRows'      => 100,
                                'checkBoxHtmlOptions' => ['name' => 'bulk_item[]'],
                            ],
                            [
                                'name'  => 'email',
                                'value' => '$data->email',
                            ],
                            [
                                'name'  => 'ip_address',
                                'value' => '$data->ip_address',
                            ],
                            [
                                'name'  => 'user_agent',
                                'value' => '$data->user_agent',
                            ],
                            [
                                'name'   => 'date_added',
                                'value'  => '$data->dateAdded',
                                'filter' => false,
                            ],
                            [
                                'class'             => 'DropDownButtonColumn',
                                'header'            => t('app', 'Options'),
                                'footer'            => $model->paginationOptions->getGridFooterPagination(),
                                'buttons'           => [
                                    'confirm' => [
                                        'label'    => IconHelper::make('fa-check-square-o'),
                                        'url'      => 'createUrl("block_email_request/confirm", array("id" => $data->email_id))',
                                        'imageUrl' => null,
                                        'options'  => [
                                            'title' => t('email_blacklist', 'Confirm'),
                                            'class' => 'btn btn-primary btn-flat confirm',
                                        ],
                                        'visible'  => 'AccessHelper::hasRouteAccess("block_email_request/confirm") && !$data->isConfirmed',
                                    ],
                                    'delete'  => [
                                        'label'    => IconHelper::make('delete'),
                                        'url'      => 'createUrl("block_email_request/delete", array("id" => $data->email_id))',
                                        'imageUrl' => null,
                                        'options'  => [
                                            'title' => t('app', 'Delete'),
                                            'class' => 'btn btn-danger btn-flat delete',
                                        ],
                                        'visible'  => 'AccessHelper::hasRouteAccess("block_email_request/delete")',
                                    ],
                                ],
                                'headerHtmlOptions' => ['style' => 'text-align: right'],
                                'footerHtmlOptions' => ['align' => 'right'],
                                'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                                'template'          => '{confirm} {delete}',
                            ],
                        ], $controller),
                    ], $controller));
                } catch (Exception $e) {
                }
            }
            /**
             * This hook gives a chance to append content after the grid view content.
             * Please note that from inside the action callback you can access all the controller view
             * variables via {@CAttributeCollection $collection->controller->getData()}
             * @since 1.3.3.1
             */
            hooks()->doAction('after_grid_view', new CAttributeCollection([
                'controller'   => $controller,
                'renderedGrid' => $collection->itemAt('renderGrid'),
            ]));
            ?>
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
                    <?php echo t('email_blacklist', 'When people enter their email addresses as a {request} to be added in the global blacklist so that they will never receive emails from this source again, those will appear here and in case they have not confirmed the request, you can confirm it for them.', [
                        '{request}' => CHtml::link(strtolower(t('email_blacklist', 'Request')), $optionUrl->getFrontendUrl('lists/block-address'), ['target' => '_blank']),
                    ]); ?><br />
                    <?php echo t('email_blacklist', 'Once a request is confirmed, the email will be added into the global blacklist and the subscriber status will change to blacklisted!'); ?><br />
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
    'controller'      => $controller,
    'renderedContent' => $viewCollection->itemAt('renderContent'),
]));
