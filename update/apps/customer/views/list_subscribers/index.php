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

/** @var Lists $list */
$list = $controller->getData('list');

/** @var ListSubscriber $subscriber */
$subscriber = $controller->getData('subscriber');

/** @var array $rows */
$rows = (array)$controller->getData('rows');

/** @var ListSubscriberBulkFromSource $subBulkFromSource */
$subBulkFromSource = $controller->getData('subBulkFromSource');

/** @var array $displayToggleColumns */
$displayToggleColumns = (array)$controller->getData('displayToggleColumns');

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
                <?php $controller->widget('customer.components.web.widgets.MailListSubNavWidget', [
                    'list' => $list,
                ]); ?>
            </div>
        </div>
        <div class="box-body">
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
                            ->addIf($controller->widget('customer.components.web.widgets.GridViewToggleSubscriberColumns', ['model' => $subscriber, 'list' => $list, 'columns' => $displayToggleColumns], true), count($rows))
                            ->addIf(CHtml::link(IconHelper::make('bulk') . t('app', 'Bulk action from source'), '#bulk-from-source-modal', ['data-toggle' => 'modal', 'class' => 'btn btn-primary btn-flat', 'title' => t('list_subscribers', 'Bulk action from source')]), count($rows))
                            ->add(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['list_subscribers/create', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]))
                            ->add(CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['list_subscribers/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]))
                            ->addIf(CHtml::link(IconHelper::make('filter') . t('app', 'Filters'), 'javascript:;', ['class' => 'btn btn-primary btn-flat toggle-campaigns-filters-form', 'title' => t('app', 'Filters')]), count($rows))
                            ->render();
                        ?>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
                <div class="box-body">

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
                        'enabled'    => !count($rows),
                    ]);
                    ?>

                    <?php if ($collection->itemAt('renderGrid')) { ?>

                        <div id="subscribers-wrapper">
                            <?php $controller->renderPartial('_list'); ?>
                        </div>

                    <?php }

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

                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="bulk-from-source-modal" tabindex="-1" role="dialog" aria-labelledby="bulk-from-source-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('list_subscribers', 'Bulk action from source'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                /** @var CActiveForm $form */
                $form = $controller->beginWidget('CActiveForm', [
                    'action'        => ['list_subscribers/bulk_from_source', 'list_uid' => $list->list_uid],
                    'htmlOptions'   => [
                        'id'        => 'bulk-from-source-form',
                        'enctype'   => 'multipart/form-data',
                    ],
                ]);
                ?>
                <div class="callout callout-info">
                    <?php echo t('list_subscribers', 'Match the subscribers added here against the ones existing in the list and make a bulk action against them!'); ?>
                    <br />
                    <strong><?php echo t('list_subscribers', 'Please note, this is not the list import ability, for list import go to your list overview, followed by Tools box followed by the Import box.'); ?></strong>
                </div>

                <div class="form-group">
                    <?php echo $form->labelEx($subBulkFromSource, 'bulk_from_file'); ?>
                    <?php echo $form->fileField($subBulkFromSource, 'bulk_from_file', $subBulkFromSource->fieldDecorator->getHtmlOptions('bulk_from_file')); ?>
                    <?php echo $form->error($subBulkFromSource, 'bulk_from_file'); ?>
                    <div class="callout callout-info">
                        <?php echo $subBulkFromSource->fieldDecorator->getAttributeHelpText('bulk_from_file'); ?>
                    </div>
                </div>

                <div class="form-group">
                    <?php echo $form->labelEx($subBulkFromSource, 'bulk_from_text'); ?>
                    <?php echo $form->textArea($subBulkFromSource, 'bulk_from_text', $subBulkFromSource->fieldDecorator->getHtmlOptions('bulk_from_text', ['rows' => 5])); ?>
                    <?php echo $form->error($subBulkFromSource, 'bulk_from_text'); ?>
                    <div class="callout callout-info">
                        <?php echo $subBulkFromSource->fieldDecorator->getAttributeHelpText('bulk_from_text'); ?>
                    </div>
                </div>
                <div class="form-group">
                    <?php echo $form->labelEx($subBulkFromSource, 'status'); ?>
                    <?php echo $form->dropDownList($subBulkFromSource, 'status', CMap::mergeArray(['' => t('app', 'Choose')], $subBulkFromSource->getBulkActionsList()), $subBulkFromSource->fieldDecorator->getHtmlOptions('status')); ?>
                    <?php echo $form->error($subBulkFromSource, 'status'); ?>
                    <div class="callout callout-info">
                        <?php echo t('list_subscribers', 'For all the subscribers found in file/text area take this action!'); ?>
                    </div>
                </div>
                <?php $controller->endWidget(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#bulk-from-source-form').submit();"><?php echo t('app', 'Submit'); ?></button>
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
