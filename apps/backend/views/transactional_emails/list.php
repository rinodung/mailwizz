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
 * @since 1.3.4.6
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var TransactionalEmail $email */
$email = $controller->getData('email');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('views_before_content', $viewCollection = new CAttributeCollection([
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
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $email, 'columns' => ['to_email', 'to_name', 'reply_to_email', 'reply_to_name', 'from_email', 'from_name', 'subject', 'status', 'send_at']], true))
                    ->add(HtmlHelper::accessLink(IconHelper::make('refresh') . t('app', 'Refresh'), ['transactional_emails/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->render();
                ?>
    		</div>
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
            hooks()->doAction('views_before_grid', $collection = new CAttributeCollection([
                'controller'   => $controller,
                'renderGrid'   => true,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderGrid')) {
                $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                    'ajaxUrl'           => createUrl($controller->getRoute()),
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
                            'name'  => 'to_email',
                            'value' => '$data->to_email',
                        ],
                        [
                            'name'  => 'to_name',
                            'value' => '$data->to_name',
                        ],

                        [
                            'name'  => 'reply_to_email',
                            'value' => '$data->reply_to_email',
                        ],
                        [
                            'name'  => 'reply_to_name',
                            'value' => '$data->reply_to_name',
                        ],
                        [
                            'name'  => 'from_email',
                            'value' => '$data->from_email',
                        ],
                        [
                            'name'  => 'from_name',
                            'value' => '$data->from_name',
                        ],
                        [
                            'name'  => 'subject',
                            'value' => '$data->subject',
                        ],
                        [
                            'name'  => 'status',
                            'value' => '$data->getStatusName()',
                            'filter'=> $email->getStatusesList(),
                        ],
                        [
                            'name'  => 'send_at',
                            'value' => '$data->sendAt',
                            'filter'=> false,
                        ],
                        [
                            'class'     => 'DropDownButtonColumn',
                            'header'    => t('app', 'Options'),
                            'footer'    => $email->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'resend' => [
                                    'label'     => IconHelper::make('glyphicon-play-circle'),
                                    'url'       => 'createUrl("transactional_emails/resend", array("id" => $data->email_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Resend'), 'class' => 'btn btn-primary btn-flat'],
                                    'visible'   => '$data->status == TransactionalEmail::STATUS_SENT && AccessHelper::hasRouteAccess("transactional_emails/resend")',
                                ],
                                'preview' => [
                                    'label'     => IconHelper::make('view'),
                                    'url'       => 'createUrl("transactional_emails/preview", array("id" => $data->email_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Preview'), 'class' => 'btn btn-primary btn-flat preview-transactional-email', 'target' => '_blank'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("transactional_emails/preview")',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("transactional_emails/delete", array("id" => $data->email_id))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete'],
                                    'visible'   => 'AccessHelper::hasRouteAccess("transactional_emails/delete")',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{resend} {preview} {delete}',
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
            hooks()->doAction('views_after_grid', new CAttributeCollection([
                'controller'   => $controller,
                'renderedGrid' => $collection->itemAt('renderGrid'),
            ]));
            ?>
            </div>
            <div class="clearfix"><!-- --></div>
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
hooks()->doAction('views_after_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
