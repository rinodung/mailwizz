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

/** @var OptionCustomerRegistration $model */
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
                    <h3 class="box-title"><?php echo t('settings', 'Customer registration'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'enabled'); ?>
                                <?php echo $form->dropDownList($model, 'enabled', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled')); ?>
                                <?php echo $form->error($model, 'enabled'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'default_group'); ?>
                                <?php echo $form->dropDownList($model, 'default_group', CMap::mergeArray(['' => t('app', 'Choose')], $model->getGroupsList()), $model->fieldDecorator->getHtmlOptions('default_group')); ?>
                                <?php echo $form->error($model, 'default_group'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'unconfirm_days_removal'); ?>
                                <?php echo $form->numberField($model, 'unconfirm_days_removal', $model->fieldDecorator->getHtmlOptions('unconfirm_days_removal')); ?>
                                <?php echo $form->error($model, 'unconfirm_days_removal'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'require_approval'); ?>
                                <?php echo $form->dropDownList($model, 'require_approval', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('require_approval')); ?>
                                <?php echo $form->error($model, 'require_approval'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'require_email_confirmation'); ?>
                                <?php echo $form->dropDownList($model, 'require_email_confirmation', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('require_email_confirmation')); ?>
                                <?php echo $form->error($model, 'require_email_confirmation'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'company_required'); ?>
                                <?php echo $form->dropDownList($model, 'company_required', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('company_required')); ?>
                                <?php echo $form->error($model, 'company_required'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'tc_url'); ?>
                                <?php echo $form->textField($model, 'tc_url', $model->fieldDecorator->getHtmlOptions('tc_url')); ?>
                                <?php echo $form->error($model, 'tc_url'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'send_email_method'); ?>
                                <?php echo $form->dropDownList($model, 'send_email_method', $model->getSendEmailMethods(), $model->fieldDecorator->getHtmlOptions('send_email_method')); ?>
                                <?php echo $form->error($model, 'send_email_method'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'new_customer_registration_notification_to'); ?>
                                <?php echo $form->textField($model, 'new_customer_registration_notification_to', $model->fieldDecorator->getHtmlOptions('new_customer_registration_notification_to')); ?>
                                <?php echo $form->error($model, 'new_customer_registration_notification_to'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'default_country'); ?>
                                <?php echo $form->dropDownList($model, 'default_country', CMap::mergeArray(['' => ''], Country::getAsDropdownOptions()), $model->fieldDecorator->getHtmlOptions('default_country')); ?>
                                <?php echo $form->error($model, 'default_country'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'default_timezone'); ?>
                                <?php echo $form->dropDownList($model, 'default_timezone', CMap::mergeArray(['' => ''], DateTimeHelper::getTimeZones()), $model->fieldDecorator->getHtmlOptions('default_timezone')); ?>
                                <?php echo $form->error($model, 'default_timezone'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'minimum_age'); ?>
                                <?php echo $form->textField($model, 'minimum_age', $model->fieldDecorator->getHtmlOptions('minimum_age')); ?>
                                <?php echo $form->error($model, 'minimum_age'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'forbidden_domains'); ?>
                                <?php echo $form->textArea($model, 'forbidden_domains', $model->fieldDecorator->getHtmlOptions('forbidden_domains')); ?>
                                <?php echo $form->error($model, 'forbidden_domains'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr />
            <div class="box box-primary borderless">
                <div class="box-header">
                    <h3 class="box-title"><?php echo t('settings', 'Send customer to email list'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'api_enabled'); ?>
                                <?php echo $form->dropDownList($model, 'api_enabled', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('api_enabled')); ?>
                                <?php echo $form->error($model, 'api_enabled'); ?>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'api_url'); ?>
                                <?php echo $form->textField($model, 'api_url', $model->fieldDecorator->getHtmlOptions('api_url')); ?>
                                <?php echo $form->error($model, 'api_url'); ?>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'api_key'); ?>
                                <?php echo $form->textField($model, 'api_key', $model->fieldDecorator->getHtmlOptions('api_key')); ?>
                                <?php echo $form->error($model, 'api_key'); ?>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'api_list_uid'); ?>
                                <?php echo $form->textField($model, 'api_list_uid', $model->fieldDecorator->getHtmlOptions('api_list_uid')); ?>
                                <?php echo $form->error($model, 'api_list_uid'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'api_consent_text'); ?>
                                <?php echo $form->textField($model, 'api_consent_text', $model->fieldDecorator->getHtmlOptions('api_consent_text')); ?>
                                <?php echo $form->error($model, 'api_consent_text'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr />
            <div class="box box-primary borderless">
                <div class="box-header">
                    <h3 class="box-title"><?php echo t('settings', 'Facebook integration'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'facebook_enabled'); ?>
                                <?php echo $form->dropDownList($model, 'facebook_enabled', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('facebook_enabled')); ?>
                                <?php echo $form->error($model, 'facebook_enabled'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'facebook_app_id'); ?>
                                <?php echo $form->textField($model, 'facebook_app_id', $model->fieldDecorator->getHtmlOptions('facebook_app_id')); ?>
                                <?php echo $form->error($model, 'facebook_app_id'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'facebook_app_secret'); ?>
                                <?php echo $form->textField($model, 'facebook_app_secret', $model->fieldDecorator->getHtmlOptions('facebook_app_secret')); ?>
                                <?php echo $form->error($model, 'facebook_app_secret'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr />
            <div class="box box-primary borderless">
                <div class="box-header">
                    <h3 class="box-title"><?php echo t('settings', 'Twitter integration'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'twitter_enabled'); ?>
                                <?php echo $form->dropDownList($model, 'twitter_enabled', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('twitter_enabled')); ?>
                                <?php echo $form->error($model, 'twitter_enabled'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'twitter_app_consumer_key'); ?>
                                <?php echo $form->textField($model, 'twitter_app_consumer_key', $model->fieldDecorator->getHtmlOptions('twitter_app_consumer_key')); ?>
                                <?php echo $form->error($model, 'twitter_app_consumer_key'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'twitter_app_consumer_secret'); ?>
                                <?php echo $form->textField($model, 'twitter_app_consumer_secret', $model->fieldDecorator->getHtmlOptions('twitter_app_consumer_secret')); ?>
                                <?php echo $form->error($model, 'twitter_app_consumer_secret'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr />
            <div class="box box-primary borderless">
                <div class="box-header">
                    <h3 class="box-title"><?php echo t('settings', 'Welcome email'); ?></h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'welcome_email'); ?>
                                <?php echo $form->dropDownList($model, 'welcome_email', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('welcome_email')); ?>
                                <?php echo $form->error($model, 'welcome_email'); ?>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="form">
                                <?php echo $form->labelEx($model, 'welcome_email_subject'); ?>
                                <?php echo $form->textField($model, 'welcome_email_subject', $model->fieldDecorator->getHtmlOptions('welcome_email_subject')); ?>
                                <?php echo $form->error($model, 'welcome_email_subject'); ?>
                            </div>
                        </div>
                        <div class="clearfix"><!-- --></div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-welcome-email-content', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                <?php echo $form->labelEx($model, 'welcome_email_content'); ?>
                                <?php echo $form->textArea($model, 'welcome_email_content', $model->fieldDecorator->getHtmlOptions('welcome_email_content', ['rows' => 20])); ?>
                                <?php echo $form->error($model, 'welcome_email_content'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
            </div>
            <div class="box box-primary borderless">
                <div class="box-footer">
                    <div class="pull-right">
                        <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . t('app', 'Save changes'); ?></button>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
            </div>
            <!-- modals -->
            <div class="modal modal-info fade" id="page-info-welcome-email-content" tabindex="-1" role="dialog">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                        </div>
                        <div class="modal-body">
                            <?php echo $model->fieldDecorator->getAttributeHelpText('welcome_email_content'); ?>
                        </div>
                    </div>
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
