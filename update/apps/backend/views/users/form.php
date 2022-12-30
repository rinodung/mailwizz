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

/** @var User $user */
$user = $controller->getData('user');

/** @var OptionTwoFactorAuth $twoFaSettings */
$twoFaSettings = $controller->getData('twoFaSettings');

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
        <?php
        if (!$user->getIsNewRecord() && $twoFaSettings->getIsEnabled()) { ?>
            <ul class="nav nav-tabs" style="border-bottom: 0px;">
                <li class="active"><a href="<?php echo createUrl('users/update', ['id' => $user->user_id]); ?>"><?php echo html_encode(t('app', 'Profile')); ?></a></li>
                <li class="inactive"><a href="<?php echo createUrl('users/2fa', ['id' => $user->user_id]); ?>"><?php echo html_encode(t('app', '2FA')); ?></a></li>
            </ul>
        <?php }
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
                    <div class="pull-left">
                        <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                            ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-user') . html_encode((string)$pageHeading) . '</h3>')
                            ->render(); ?>
                    </div>
                    <div class="pull-right">
                        <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                            ->addIf(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['users/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$user->getIsNewRecord())
                            ->add(HtmlHelper::accessLink(IconHelper::make('cancel') . t('app', 'Cancel'), ['users/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
                            ->render(); ?>
                    </div>
                    <div class="clearfix"><!-- --></div>
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
                        <div class="col-lg-6">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'first_name'); ?>
                                <?php echo $form->textField($user, 'first_name', $user->fieldDecorator->getHtmlOptions('first_name')); ?>
                                <?php echo $form->error($user, 'first_name'); ?>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'last_name'); ?>
                                <?php echo $form->textField($user, 'last_name', $user->fieldDecorator->getHtmlOptions('last_name')); ?>
                                <?php echo $form->error($user, 'last_name'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'email'); ?>
                                <?php echo $form->emailField($user, 'email', $user->fieldDecorator->getHtmlOptions('email')); ?>
                                <?php echo $form->error($user, 'email'); ?>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'confirm_email'); ?>
                                <?php echo $form->emailField($user, 'confirm_email', $user->fieldDecorator->getHtmlOptions('confirm_email')); ?>
                                <?php echo $form->error($user, 'confirm_email'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'fake_password'); ?>
                                <?php echo $form->passwordField($user, 'fake_password', $user->fieldDecorator->getHtmlOptions('password')); ?>
                                <?php echo $form->error($user, 'fake_password'); ?>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'confirm_password'); ?>
                                <?php echo $form->passwordField($user, 'confirm_password', $user->fieldDecorator->getHtmlOptions('confirm_password')); ?>
                                <?php echo $form->error($user, 'confirm_password'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'timezone'); ?>
                                <?php echo $form->dropDownList($user, 'timezone', $user->getTimeZonesArray(), $user->fieldDecorator->getHtmlOptions('timezone')); ?>
                                <?php echo $form->error($user, 'timezone'); ?>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'language_id'); ?>
                                <?php echo $form->dropDownList($user, 'language_id', CMap::mergeArray(['' => t('app', 'Application default')], Language::getLanguagesArray()), $user->fieldDecorator->getHtmlOptions('language_id')); ?>
                                <?php echo $form->error($user, 'language_id'); ?>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <?php echo $form->labelEx($user, 'status'); ?>
                                <?php echo $form->dropDownList($user, 'status', $user->getStatusesArray(), $user->fieldDecorator->getHtmlOptions('status')); ?>
                                <?php echo $form->error($user, 'status'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php if ($user->getIsRemovable() && ($options = UserGroup::getAllAsOptions())) { ?>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($user, 'group_id'); ?>
                                    <?php echo $form->dropDownList($user, 'group_id', CMap::mergeArray(['' => ''], $options), $user->fieldDecorator->getHtmlOptions('group_id')); ?>
                                    <?php echo $form->error($user, 'group_id'); ?>
                                </div>
                            </div>
                        <?php } ?>
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
