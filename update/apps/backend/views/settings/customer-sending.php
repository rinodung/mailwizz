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
 * @since 1.3.4.4
 */

/** @var Controller $controller */
$controller = controller();

/** @var OptionCustomerSending $model */
$model = $controller->getData('model');

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
    <div class="box box-default borderless">
        <div class="box-header">
            <?php $controller->renderPartial('_customers_tabs'); ?>
        </div>
        <?php
        /**
         * This hook gives a chance to prepend content before the active form or to replace the default active form entirely.
         * Please note that from inside the action callback you can access all the controller view variables
         * via {@CAttributeCollection $collection->controller->getData()}
         * In case the form is replaced, make sure to set {@CAttributeCollection $collection->add('renderForm', false)}
         * in order to stop rendering the default content.
         * @since 1.3.4.3
         */
        hooks()->doAction('before_active_form', $collection = new CAttributeCollection([
            'controller'    => $controller,
            'renderForm'    => true,
        ]));

        // and render if allowed
        if ($collection->itemAt('renderForm')) {
            /** @var CActiveForm $form */
            $form = $controller->beginWidget('CActiveForm'); ?>
            <div class="box box-primary borderless">
                <div class="box-header">
                    <div class="pull-left">
                        <h3 class="box-title"><?php echo t('settings', 'Customer sending'); ?></h3>
                    </div>
                    <div class="pull-right">
                        <?php echo CHtml::link(IconHelper::make('info'), '#page-info', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
                <div class="box-body">
                    <?php
                    /**
                     * This hook gives a chance to prepend content before the active form fields.
                     * Please note that from inside the action callback you can access all the controller view variables
                     * via {@CAttributeCollection $collection->controller->getData()}
                     * @since 1.3.4.3
                     */
                    hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                        'controller'    => $controller,
                        'form'          => $form,
                    ])); ?>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'quota'); ?>
                                <?php echo $form->numberField($model, 'quota', $model->fieldDecorator->getHtmlOptions('quota')); ?>
                                <?php echo $form->error($model, 'quota'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'quota_time_value'); ?>
                                <?php echo $form->numberField($model, 'quota_time_value', $model->fieldDecorator->getHtmlOptions('quota_time_value')); ?>
                                <?php echo $form->error($model, 'quota_time_value'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'quota_time_unit'); ?>
                                <?php echo $form->dropDownList($model, 'quota_time_unit', $model->getTimeUnits(), $model->fieldDecorator->getHtmlOptions('quota_time_unit')); ?>
                                <?php echo $form->error($model, 'quota_time_unit'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'quota_wait_expire'); ?>
                                <?php echo $form->dropDownList($model, 'quota_wait_expire', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('quota_wait_expire')); ?>
                                <?php echo $form->error($model, 'quota_wait_expire'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <?php echo $form->labelEx($model, 'action_quota_reached'); ?>
                                        <?php echo $form->dropDownList($model, 'action_quota_reached', $model->getActionsQuotaReached(), $model->fieldDecorator->getHtmlOptions('action_quota_reached')); ?>
                                        <?php echo $form->error($model, 'action_quota_reached'); ?>
                                    </div>
                                </div>
                                <div class="col-lg-6 move-to-group-id" style="display: <?php echo $model->getActionQuotaWhenReachedIsMoveToGroup() ? 'block' : 'none'; ?>;">
                                    <div class="form-group">
                                        <?php echo $form->labelEx($model, 'move_to_group_id'); ?>
                                        <?php echo $form->dropDownList($model, 'move_to_group_id', CMap::mergeArray(['' => t('app', 'Choose')], $model->getGroupsList()), $model->fieldDecorator->getHtmlOptions('move_to_group_id')); ?>
                                        <?php echo $form->error($model, 'move_to_group_id'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'hourly_quota'); ?>
                                <?php echo $form->numberField($model, 'hourly_quota', $model->fieldDecorator->getHtmlOptions('hourly_quota')); ?>
                                <?php echo $form->error($model, 'hourly_quota'); ?>
                            </div>
                        </div>
                    </div>
                    <hr />
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'quota_notify_enabled'); ?>
                                <?php echo $form->dropDownList($model, 'quota_notify_enabled', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('quota_notify_enabled')); ?>
                                <?php echo $form->error($model, 'quota_notify_enabled'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'quota_notify_percent'); ?>
                                <?php echo $form->numberField($model, 'quota_notify_percent', $model->fieldDecorator->getHtmlOptions('quota_notify_percent')); ?>
                                <?php echo $form->error($model, 'quota_notify_percent'); ?>
                            </div>
                        </div>
                        <div class="clearfix"><!-- --></div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-quota-email-template', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                <?php echo $form->labelEx($model, 'quota_notify_email_content'); ?>
                                <?php echo $form->textArea($model, 'quota_notify_email_content', $model->fieldDecorator->getHtmlOptions('quota_notify_email_content')); ?>
                                <?php echo $form->error($model, 'quota_notify_email_content'); ?>
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
                                    <?php echo t('settings', 'A sending quota of 1000 with a time value of 1 and a time unit of Day means the customer is able to send 1000 emails during 1 day.'); ?>
                                    <br />
                                    <?php echo t('settings', 'If waiting is enabled and the customer sends all emails in an hour, he will wait 23 more hours until the specified action is taken.'); ?>
                                    <br />
                                    <?php echo t('settings', 'However, if the waiting is disabled, the action will be taken immediatly.'); ?>
                                    <br />
                                    <?php echo t('settings', 'You can find a more detailed explanation for these settings {here}.', [
                                        '{here}' => CHtml::link(t('settings', 'here'), hooks()->applyFilters('customer_sending_explanation_url', 'https://www.mailwizz.com/kb/understanding-sending-quota-limits-work/'), ['target' => '_blank']),
                                    ]); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal modal-info fade" id="page-info-quota-email-template" tabindex="-1" role="dialog">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                </div>
                                <div class="modal-body">
                                    <?php echo t('settings', 'Following placeholders are available:'); ?>
                                    <div style="width:100%; max-height: 100px; overflow:scroll">
                                        <a href="javascript:;" class="btn btn-primary btn-xs btn-flat">[FIRST_NAME]</a>
                                        <a href="javascript:;" class="btn btn-primary btn-xs btn-flat">[LAST_NAME]</a>
                                        <a href="javascript:;" class="btn btn-primary btn-xs btn-flat">[FULL_NAME]</a>
                                        <a href="javascript:;" class="btn btn-primary btn-xs btn-flat">[QUOTA_TOTAL]</a>
                                        <a href="javascript:;" class="btn btn-primary btn-xs btn-flat">[QUOTA_USAGE]</a>
                                        <a href="javascript:;" class="btn btn-primary btn-xs btn-flat">[QUOTA_USAGE_PERCENT]</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    /**
                     * This hook gives a chance to append content after the active form fields.
                     * Please note that from inside the action callback you can access all the controller view variables
                     * via {@CAttributeCollection $collection->controller->getData()}
                     * @since 1.3.4.3
                     */
                    hooks()->doAction('after_active_form_fields', new CAttributeCollection([
                        'controller'    => $controller,
                        'form'          => $form,
                    ])); ?>
                </div>
                <div class="box-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . t('app', 'Save changes'); ?></button>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
            </div>
            <?php
            $controller->endWidget();
        }
        /**
         * This hook gives a chance to append content after the active form.
         * Please note that from inside the action callback you can access all the controller view variables
         * via {@CAttributeCollection $collection->controller->getData()}
         * @since 1.3.4.3
         */
        hooks()->doAction('after_active_form', new CAttributeCollection([
            'controller'      => $controller,
            'renderedForm'    => $collection->itemAt('renderForm'),
        ])); ?>
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
