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
 * @since 1.3.4
 */

/** @var Controller $controller */
$controller = controller();

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var CustomerGroupOptionLists $model */
$model = $controller->getData('model');

 ?>
<div class="box box-primary borderless">
    <div class="box-body">
        <div class="clearfix"><!-- --></div>
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
    </div>
    <div class="clearfix"><!-- --></div>
</div>