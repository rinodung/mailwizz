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

/** @var AccountController $controller */
$controller = controller();

/** @var Customer $customer */
$customer = $controller->getData('customer');

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
    <div class="box box-primary borderless no-top-border">
        <div class="tabs-container">
            <?php
            echo $controller->renderTabs();
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

            // and render only if allowed
            if ($collection->itemAt('renderForm')) {
                /** @var CActiveForm $form */
                $form = $controller->beginWidget('CActiveForm', [
                    'htmlOptions' => ['enctype' => 'multipart/form-data'],
                ]); ?>
                <div class="box box-primary borderless no-top-border">
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
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'first_name'); ?>
                                    <?php echo $form->textField($customer, 'first_name', $customer->fieldDecorator->getHtmlOptions('first_name')); ?>
                                    <?php echo $form->error($customer, 'first_name'); ?>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'last_name'); ?>
                                    <?php echo $form->textField($customer, 'last_name', $customer->fieldDecorator->getHtmlOptions('last_name')); ?>
                                    <?php echo $form->error($customer, 'last_name'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'email'); ?>
                                    <?php echo $form->emailField($customer, 'email', $customer->fieldDecorator->getHtmlOptions('email')); ?>
                                    <?php echo $form->error($customer, 'email'); ?>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'confirm_email'); ?>
                                    <?php echo $form->emailField($customer, 'confirm_email', $customer->fieldDecorator->getHtmlOptions('confirm_email')); ?>
                                    <?php echo $form->error($customer, 'confirm_email'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'fake_password'); ?>
                                    <?php echo $form->passwordField($customer, 'fake_password', $customer->fieldDecorator->getHtmlOptions('password')); ?>
                                    <?php echo $form->error($customer, 'fake_password'); ?>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'confirm_password'); ?>
                                    <?php echo $form->passwordField($customer, 'confirm_password', $customer->fieldDecorator->getHtmlOptions('confirm_password')); ?>
                                    <?php echo $form->error($customer, 'confirm_password'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'timezone'); ?>
                                    <?php echo $form->dropDownList($customer, 'timezone', $customer->getTimeZonesArray(), $customer->fieldDecorator->getHtmlOptions('timezone')); ?>
                                    <?php echo $form->error($customer, 'timezone'); ?>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'language_id'); ?>
                                    <?php echo $form->dropDownList($customer, 'language_id', CMap::mergeArray(['' => t('app', 'Application default')], Language::getLanguagesArray()), $customer->fieldDecorator->getHtmlOptions('language_id')); ?>
                                    <?php echo $form->error($customer, 'language_id'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php
                                    /** @var OptionCustomerRegistration $registration */
                                    $registration = container()->get(OptionCustomerRegistration::class); ?>
                                    <?php echo $form->labelEx($customer, 'birthDate'); ?>
                                    <?php echo $controller->widget('zii.widgets.jui.CJuiDatePicker', [
                                        'model'     => $customer,
                                        'attribute' => 'birthDate',
                                        'cssFile'   => null,
                                        'language'  => $customer->getDatePickerLanguage(),
                                        'options'   => [
                                            'showAnim'    => 'fold',
                                            'dateFormat'  => $customer->getDatePickerFormat(),
                                            'changeYear'  => true,
                                            'changeMonth' => true,
                                            'defaultDate' => sprintf('-%dy', $registration->getMinimumAge()),
                                            'maxDate'     => sprintf('-%dy', $registration->getMinimumAge()),
                                            'yearRange'   => '-100:+0',
                                        ],
                                        'htmlOptions' => $customer->fieldDecorator->getHtmlOptions('birthDate'),
                                    ], true); ?>
                                    <?php echo $form->error($customer, 'birthDate'); ?>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($customer, 'phone'); ?>
                                    <?php echo $form->textField($customer, 'phone', $customer->fieldDecorator->getHtmlOptions('phone')); ?>
                                    <?php echo $form->error($customer, 'phone'); ?>
                                </div>
                            </div>
                        </div>
                        <hr />
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="row">
                                    <div class="col-lg-2">
                                        <img src="<?php echo $customer->getAvatarUrl(90, 90); ?>" class="img-thumbnail"/>
                                    </div>
                                    <div class="col-lg-10">
                                        <div class="form-group">
                                            <?php echo $form->labelEx($customer, 'new_avatar'); ?>
                                            <?php echo $form->fileField($customer, 'new_avatar', $customer->fieldDecorator->getHtmlOptions('new_avatar')); ?>
                                            <?php echo $form->error($customer, 'new_avatar'); ?>
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
            ]));
            ?>
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
