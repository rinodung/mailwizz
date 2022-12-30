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
 * @since 1.3.5.2
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var AllCustomersListsSubscribersFilters $filter */
$filter = $controller->getData('filter');

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

    <?php $controller->renderPartial('_filters'); ?>

    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add('<h3 class="box-title">' . IconHelper::make('fa-users') . html_encode((string)$pageHeading) . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                    ->add($controller->widget('common.components.web.widgets.GridViewToggleColumns', ['model' => $filter, 'columns' => ['customer_id', 'list_id', 'subscriber_id', 'subscriber_uid', 'email', 'source', 'ip_address', 'status', 'date_added', 'last_updated']], true))
                    ->add(CHtml::link(IconHelper::make('back') . t('list_subscribers', 'Back to lists'), ['lists/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('list_subscribers', 'Back to lists')]))
                    ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['lists/all_subscribers'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                    ->add(CHtml::link(IconHelper::make('filter') . t('app', 'Filters'), 'javascript:;', ['class' => 'btn btn-primary btn-flat toggle-filters-form', 'title' => t('app', 'Filters')]))
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
                    'id'                => $filter->getModelName() . '-grid',
                    'dataProvider'      => $filter->getActiveDataProvider(),
                    'filter'            => $filter,
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
                        // 'pages'         => $pages,
                    ],
                    'columns' => hooks()->applyFilters('grid_view_columns', [
                        [
                            'name'  => 'customer_id',
                            'value' => 'CHtml::link($data->list->customer->getFullName(), createUrl("customers/update", array("id" => $data->list->customer_id)))',
                            'type'  => 'raw',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'list_id',
                            'value' => '$data->list->name',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'subscriber_id',
                            'value' => '$data->subscriber_id',
                            'filter'=> false,
                        ],
                        [
                            'name'  => 'subscriber_uid',
                            'value' => '$data->subscriber_uid',
                            'filter'=> CHtml::textField('uid', $filter->uid),
                        ],
                        [
                            'name'  => 'email',
                            'value' => '$data->email',
                            'filter'=> CHtml::textField('email', $filter->email),
                        ],
                        [
                            'name'  => 'source',
                            'value' => 't("list_subscribers", ucfirst($data->source))',
                            'filter'=> CHtml::dropDownList('sources[]', !empty($filter->sources) && count($filter->sources) === 1 ? $filter->sources[0] : '', CMap::mergeArray(['' => ''], $filter->getSourcesList())),
                        ],
                        [
                            'name'  => 'ip_address',
                            'value' => '$data->ip_address',
                            'filter'=> CHtml::textField('ip', $filter->ip),
                        ],
                        [
                            'name'  => 'status',
                            'value' => 't("list_subscribers", ucfirst($data->status))',
                            'filter'=> CHtml::dropDownList('statuses[]', !empty($filter->statuses) && count($filter->statuses) === 1 ? $filter->statuses[0] : '', CMap::mergeArray(['' => ''], $filter->getStatusesList())),
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
                            'footer'    => $filter->paginationOptions->getGridFooterPagination(),
                            'buttons'   => [
                                'profile' => [
                                    'label'     => IconHelper::make('fa-user'),
                                    'url'       => 'createUrl("list_subscribers/profile", array("subscriber_uid" => $data->subscriber_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Profile info'), 'class' => 'btn btn-primary btn-flat btn-subscriber-profile-info'],
                                ],
                                'profile_export' => [
                                    'label'     => IconHelper::make('export'),
                                    'url'       => 'createUrl("list_subscribers/profile_export", array("subscriber_uid" => $data->subscriber_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Export profile info'), 'target' => '_blank', 'class' => 'btn btn-primary btn-flat btn-export-subscriber-profile-info'],
                                ],
                                'unsubscribe' => [
                                    'label'     => IconHelper::make('glyphicon-log-out'),
                                    'url'       => 'createUrl("list_subscribers/unsubscribe", array("subscriber_uid" => $data->subscriber_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Unsubscribe'), 'class' => 'btn btn-primary btn-flat unsubscribe', 'data-message' => t('list_subscribers', 'Are you sure you want to unsubscribe this subscriber?')],
                                    'visible'   => '$data->getCanBeUnsubscribed() && $data->status == ListSubscriber::STATUS_CONFIRMED',
                                ],
                                'subscribe' => [
                                    'label'     => IconHelper::make('glyphicon-log-in'),
                                    'url'       => 'createUrl("list_subscribers/subscribe", array("subscriber_uid" => $data->subscriber_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('list_subscribers', 'Subscribe back'), 'class' => 'btn btn-primary btn-flat subscribe', 'data-message' => t('list_subscribers', 'Are you sure you want to subscribe back this unsubscriber?')],
                                    'visible'   => '$data->getCanBeConfirmed() && $data->status == ListSubscriber::STATUS_UNCONFIRMED',
                                ],
                                'confirm' => [
                                    'label'     => IconHelper::make('glyphicon-log-in'),
                                    'url'       => 'createUrl("list_subscribers/subscribe", array("subscriber_uid" => $data->subscriber_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('list_subscribers', 'Confirm subscriber'), 'class' => 'btn btn-primary btn-flat subscribe', 'data-message' => t('list_subscribers', 'Are you sure you want to confirm this subscriber?')],
                                    'visible'   => '$data->getCanBeConfirmed() && $data->status == ListSubscriber::STATUS_UNSUBSCRIBED',
                                ],
                                'delete' => [
                                    'label'     => IconHelper::make('delete'),
                                    'url'       => 'createUrl("list_subscribers/delete", array("subscriber_uid" => $data->subscriber_uid))',
                                    'imageUrl'  => null,
                                    'options'   => ['title' => t('app', 'Delete'), 'class' => 'btn btn-danger btn-flat delete', 'data-message' => t('app', 'Are you sure you want to delete this item? There is no coming back after you do it.')],
                                    'visible'   => '$data->getCanBeDeleted()',
                                ],
                            ],
                            'headerHtmlOptions' => ['style' => 'text-align: right'],
                            'footerHtmlOptions' => ['align' => 'right'],
                            'htmlOptions'       => ['align' => 'right', 'class' => 'options'],
                            'template'          => '{profile} {profile_export} {unsubscribe} {subscribe} {confirm} {delete}',
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
            <?php
            /**
             * Since 1.3.9.8
             * This creates a modal placeholder to push subscriber profile info in.
             */
            $controller->widget('customer.components.web.widgets.SubscriberModalProfileInfoWidget');
            ?>

            <div class="clearfix"><!-- --></div>
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
