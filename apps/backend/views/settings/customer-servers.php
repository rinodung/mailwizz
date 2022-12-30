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

/** @var OptionCustomerServers $model */
$model = $controller->getData('model');

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
         * @since 1.3.3.1
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
                    <h3 class="box-title"><?php echo t('settings', 'Customer servers'); ?></h3>
                </div>
                <div class="box-body">
                    <?php
                    /**
                     * This hook gives a chance to prepend content before the active form fields.
                     * Please note that from inside the action callback you can access all the controller view variables
                     * via {@CAttributeCollection $collection->controller->getData()}
                     * @since 1.3.3.1
                     */
                    hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                        'controller'    => $controller,
                        'form'          => $form,
                    ])); ?>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_delivery_servers'); ?>
                                <?php echo $form->numberField($model, 'max_delivery_servers', $model->fieldDecorator->getHtmlOptions('max_delivery_servers')); ?>
                                <?php echo $form->error($model, 'max_delivery_servers'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_bounce_servers'); ?>
                                <?php echo $form->numberField($model, 'max_bounce_servers', $model->fieldDecorator->getHtmlOptions('max_bounce_servers')); ?>
                                <?php echo $form->error($model, 'max_bounce_servers'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_fbl_servers'); ?>
                                <?php echo $form->numberField($model, 'max_fbl_servers', $model->fieldDecorator->getHtmlOptions('max_fbl_servers')); ?>
                                <?php echo $form->error($model, 'max_fbl_servers'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'max_email_box_monitors'); ?>
                                <?php echo $form->numberField($model, 'max_email_box_monitors', $model->fieldDecorator->getHtmlOptions('max_email_box_monitors')); ?>
                                <?php echo $form->error($model, 'max_email_box_monitors'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'must_add_bounce_server'); ?>
                                <?php echo $form->dropDownList($model, 'must_add_bounce_server', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('must_add_bounce_server')); ?>
                                <?php echo $form->error($model, 'must_add_bounce_server'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_select_delivery_servers_for_campaign'); ?>
                                <?php echo $form->dropDownList($model, 'can_select_delivery_servers_for_campaign', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_select_delivery_servers_for_campaign')); ?>
                                <?php echo $form->error($model, 'can_select_delivery_servers_for_campaign'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'can_send_from_system_servers'); ?>
                                <?php echo $form->dropDownList($model, 'can_send_from_system_servers', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_send_from_system_servers')); ?>
                                <?php echo $form->error($model, 'can_send_from_system_servers'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <hr />
                            <div class="pull-left">
                                <h5><?php echo t('settings', 'Custom headers'); ?>:</h5>
                            </div>
                            <?php echo $form->textArea($model, 'custom_headers', $model->fieldDecorator->getHtmlOptions('custom_headers', ['rows' => 5])); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <hr />
                            <div class="pull-left">
                                <h5><?php echo t('settings', 'Allowed server types'); ?>:</h5>
                            </div>
                            <div class="clearfix"><!-- --></div>
                            <?php echo $form->error($model, 'allowed_server_types'); ?>
                            <div class="clearfix"><!-- --></div>

                            <div class="row">
                                <?php foreach ($model->getServerTypesList() as $type => $name) { ?>
                                    <div class="col-lg-4">
                                        <div class="row">
                                            <div class="col-lg-8">
                                                <?php echo CHtml::label(t('settings', 'Server type'), '_dummy_'); ?>
                                                <?php echo CHtml::textField('_dummy_', $name, $model->fieldDecorator->getHtmlOptions('allowed_server_types', ['readonly' => true])); ?>
                                            </div>
                                            <div class="col-lg-4">
                                                <?php echo CHtml::label(t('settings', 'Allowed'), '_dummy_'); ?>
                                                <?php echo CHtml::dropDownList($model->getModelName() . '[allowed_server_types][' . $type . ']', in_array($type, $model->allowed_server_types) ? 'yes' : 'no', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('allowed_server_types')); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    /**
                     * This hook gives a chance to append content after the active form fields.
                     * Please note that from inside the action callback you can access all the controller view variables
                     * via {@CAttributeCollection $collection->controller->getData()}
                     * @since 1.3.3.1
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
         * @since 1.3.3.1
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
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
