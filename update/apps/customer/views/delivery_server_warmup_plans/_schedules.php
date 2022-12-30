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
 * @since 2.1.10
 */

/** @var Controller $controller */
$controller = controller();

/** @var DeliveryServerWarmupPlan $plan */
$plan = $controller->getData('plan');

/** @var DeliveryServerWarmupPlanSchedule $scheduleSearchModel */
$scheduleSearchModel = $plan->getScheduleSearchModel();

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.4.3
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
                    ->add('<h3 class="box-title">' . IconHelper::make('fa-area-chart') . t('warmup_plans', 'Generated warmup plan') . '</h3>')
                    ->render();
                ?>
            </div>
            <div class="pull-right">
                <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                    ->add(CHtml::link(IconHelper::make('info'), '#page-info', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']))
                    ->render();
                ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-lg-6 warmup-plans-scrollable-container">
                    <div class="table-responsive">
                        <?php
                        /**
                         * This hook gives a chance to prepend content or to replace the default grid view content with a custom content.
                         * Please note that from inside the action callback you can access all the controller view
                         * variables via {@CAttributeCollection $collection->controller->getData()}
                         * In case the content is replaced, make sure to set {@CAttributeCollection $collection->itemAt('renderGrid')} to false
                         * in order to stop rendering the default content.
                         * @since 1.3.4.3
                         */
                        hooks()->doAction('before_grid_view', $collection = new CAttributeCollection([
                            'controller'    => $controller,
                            'renderGrid'    => true,
                        ]));

                        // and render if allowed
                        if ($collection->itemAt('renderGrid')) {
                            $controller->widget('zii.widgets.grid.CGridView', hooks()->applyFilters('grid_view_properties', [
                                'ajaxUrl'           => createUrl($controller->getRoute()),
                                'id'                => $scheduleSearchModel->getModelName(),
                                'dataProvider'      => $scheduleSearchModel->search(),
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
                                        'name'  => t('warmup_plans', 'Schedule'),
                                        'value' => '$row+1',
                                    ],
                                    [
                                        'name'  => t('warmup_plans', 'Increment'),
                                        'value' => '$data->increment',
                                    ],
                                    [
                                        'name'  => t('warmup_plans', 'Quota'),
                                        'value' => '$data->quota',
                                        'type'  => 'raw',
                                    ],
                                ], $controller),
                            ], $controller));
                        }
                        /**
                         * This hook gives a chance to append content after the grid view content.
                         * Please note that from inside the action callback you can access all the controller view
                         * variables via {@CAttributeCollection $collection->controller->getData()}
                         * @since 1.3.4.3
                         */
                        hooks()->doAction('after_grid_view', new CAttributeCollection([
                            'controller'    => $controller,
                            'renderedGrid'  => $collection->itemAt('renderGrid'),
                        ]));
                        ?>
                        <div class="clearfix"><!-- --></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <?php
                    $controller->widget('common.components.web.widgets.DeliveryServerWarmupPlanGraphWidget', [
                        'plan' => $plan,
                    ]); ?>
                </div>
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
                    <?php echo t('warmup_plans', 'Please note that the last schedule from the series might be the subject to roundings.'); ?>
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
 * @since 1.3.4.3
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
