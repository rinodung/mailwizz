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

/** @var OptionCustomerLists $model */
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
                    <h3 class="box-title"><?php echo t('settings', 'Customer lists'); ?></h3>
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
                                <?php echo $form->labelEx($model, 'can_import_subscribers'); ?>
                                <?php echo $form->dropDownList($model, 'can_import_subscribers', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_import_subscribers')); ?>
                                <?php echo $form->error($model, 'can_import_subscribers'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_export_subscribers'); ?>
                                <?php echo $form->dropDownList($model, 'can_export_subscribers', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_export_subscribers')); ?>
                                <?php echo $form->error($model, 'can_export_subscribers'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_copy_subscribers'); ?>
                                <?php echo $form->dropDownList($model, 'can_copy_subscribers', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_copy_subscribers')); ?>
                                <?php echo $form->error($model, 'can_copy_subscribers'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_lists'); ?>
                                <?php echo $form->numberField($model, 'max_lists', $model->fieldDecorator->getHtmlOptions('max_lists')); ?>
                                <?php echo $form->error($model, 'max_lists'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_subscribers'); ?>
                                <?php echo $form->numberField($model, 'max_subscribers', $model->fieldDecorator->getHtmlOptions('max_subscribers')); ?>
                                <?php echo $form->error($model, 'max_subscribers'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_subscribers_per_list'); ?>
                                <?php echo $form->numberField($model, 'max_subscribers_per_list', $model->fieldDecorator->getHtmlOptions('max_subscribers_per_list')); ?>
                                <?php echo $form->error($model, 'max_subscribers_per_list'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'copy_subscribers_at_once'); ?>
                                <?php echo $form->numberField($model, 'copy_subscribers_at_once', $model->fieldDecorator->getHtmlOptions('copy_subscribers_at_once')); ?>
                                <?php echo $form->error($model, 'copy_subscribers_at_once'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_delete_own_lists'); ?>
                                <?php echo $form->dropDownList($model, 'can_delete_own_lists', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_delete_own_lists')); ?>
                                <?php echo $form->error($model, 'can_delete_own_lists'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_delete_own_subscribers'); ?>
                                <?php echo $form->dropDownList($model, 'can_delete_own_subscribers', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_delete_own_subscribers')); ?>
                                <?php echo $form->error($model, 'can_delete_own_subscribers'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_segment_lists'); ?>
                                <?php echo $form->dropDownList($model, 'can_segment_lists', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_segment_lists')); ?>
                                <?php echo $form->error($model, 'can_segment_lists'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_segment_conditions'); ?>
                                <?php echo $form->numberField($model, 'max_segment_conditions', $model->fieldDecorator->getHtmlOptions('max_segment_conditions')); ?>
                                <?php echo $form->error($model, 'max_segment_conditions'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_segment_wait_timeout'); ?>
                                <?php echo $form->numberField($model, 'max_segment_wait_timeout', $model->fieldDecorator->getHtmlOptions('max_segment_wait_timeout')); ?>
                                <?php echo $form->error($model, 'max_segment_wait_timeout'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_mark_blacklisted_as_confirmed'); ?>
                                <?php echo $form->dropDownList($model, 'can_mark_blacklisted_as_confirmed', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_mark_blacklisted_as_confirmed')); ?>
                                <?php echo $form->error($model, 'can_mark_blacklisted_as_confirmed'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_use_own_blacklist'); ?>
                                <?php echo $form->dropDownList($model, 'can_use_own_blacklist', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_use_own_blacklist')); ?>
                                <?php echo $form->error($model, 'can_use_own_blacklist'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_edit_own_subscribers'); ?>
                                <?php echo $form->dropDownList($model, 'can_edit_own_subscribers', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_edit_own_subscribers')); ?>
                                <?php echo $form->error($model, 'can_edit_own_subscribers'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'subscriber_profile_update_optin_history'); ?>
                                <?php echo $form->dropDownList($model, 'subscriber_profile_update_optin_history', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('subscriber_profile_update_optin_history')); ?>
                                <?php echo $form->error($model, 'subscriber_profile_update_optin_history'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_create_list_from_filters'); ?>
                                <?php echo $form->dropDownList($model, 'can_create_list_from_filters', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_create_list_from_filters')); ?>
                                <?php echo $form->error($model, 'can_create_list_from_filters'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'show_7days_subscribers_activity_graph'); ?>
                                <?php echo $form->dropDownList($model, 'show_7days_subscribers_activity_graph', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('show_7days_subscribers_activity_graph')); ?>
                                <?php echo $form->error($model, 'show_7days_subscribers_activity_graph'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'force_optin_process'); ?>
                                <?php echo $form->dropDownList($model, 'force_optin_process', $model->getOptInOutOptions(), $model->fieldDecorator->getHtmlOptions('force_optin_process')); ?>
                                <?php echo $form->error($model, 'force_optin_process'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'force_optout_process'); ?>
                                <?php echo $form->dropDownList($model, 'force_optout_process', $model->getOptInOutOptions(), $model->fieldDecorator->getHtmlOptions('force_optout_process')); ?>
                                <?php echo $form->error($model, 'force_optout_process'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'custom_fields_default_visibility'); ?>
                                <?php echo $form->dropDownList($model, 'custom_fields_default_visibility', $model->getCustomFieldsVisibilityOptions(), $model->fieldDecorator->getHtmlOptions('custom_fields_default_visibility')); ?>
                                <?php echo $form->error($model, 'custom_fields_default_visibility'); ?>
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
                    <div class="clearfix"><!-- --></div>
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
