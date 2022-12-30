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

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var DeliveryServerWarmupPlan $plan */
$plan = $controller->getData('plan');

/** @var bool $isPlanActive */
$isPlanActive = $plan->getIsActive();

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
if ($viewCollection->itemAt('renderContent')) {
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
                        ->add('<h3 class="box-title">' . IconHelper::make('fa-area-chart') . $pageHeading . '</h3>')
                        ->addIf(CHtml::tag('span', ['class' => 'badge bg-green'], t('warmup_plans', 'Active')), $isPlanActive)
                        ->render(); ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(HtmlHelper::accessLink(IconHelper::make('fa-check-square') . t('app', 'Activate plan'), ['delivery_server_warmup_plans/activate', 'id' => $plan->plan_id], ['class' => 'btn btn-success btn-flat btn-activate-warmup-plan', 'title' => t('app', 'Activate plan'), 'data-confirm' => t('warmup_plans', 'Are you sure you want to run this action? After you activate the plan, you will be able to edit only its name and description')]), !$plan->getIsNewRecord() && !$isPlanActive)
                        ->addIf(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['delivery_server_warmup_plans/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$plan->getIsNewRecord())
                        ->add(HtmlHelper::accessLink(IconHelper::make('cancel') . t('app', 'Cancel'), ['delivery_server_warmup_plans/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
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
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($plan, 'name'); ?>
                            <?php echo $form->textField($plan, 'name', $plan->fieldDecorator->getHtmlOptions('name')); ?>
                            <?php echo $form->error($plan, 'name'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($plan, 'sendings_count'); ?>
                            <?php echo $form->textField($plan, 'sendings_count', $plan->fieldDecorator->getHtmlOptions('sendings_count', [
                                'readonly' => $isPlanActive,
                                'disabled' => $isPlanActive,
                            ])); ?>
                            <?php echo $form->error($plan, 'sendings_count'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($plan, 'sending_limit'); ?>
                            <?php echo $form->textField($plan, 'sending_limit', $plan->fieldDecorator->getHtmlOptions('sending_limit', [
                                'readonly' => $isPlanActive,
                                'disabled' => $isPlanActive,
                            ])); ?>
                            <?php echo $form->error($plan, 'sending_limit'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($plan, 'sending_limit_type'); ?>
                            <?php echo $form->dropDownList($plan, 'sending_limit_type', $plan->getSendingLimitTypeOptions(), $plan->fieldDecorator->getHtmlOptions('sending_limit_type', [
                                'prompt'   => t('app', 'Please select an option.'),
                                'readonly' => $isPlanActive,
                                'disabled' => $isPlanActive,
                            ])); ?>
                            <?php echo $form->error($plan, 'sending_limit_type'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($plan, 'sending_quota_type'); ?>
                            <?php echo $form->dropDownList($plan, 'sending_quota_type', $plan->getSendingQuotaTypeOptions(), $plan->fieldDecorator->getHtmlOptions('sending_quota_type', [
                                'prompt'   => t('app', 'Please select an option.'),
                                'readonly' => $isPlanActive,
                                'disabled' => $isPlanActive,
                            ])); ?>
                            <?php echo $form->error($plan, 'sending_quota_type'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($plan, 'sending_strategy'); ?>
                            <?php echo $form->dropDownList($plan, 'sending_strategy', $plan->getSendingStrategyOptions(), $plan->fieldDecorator->getHtmlOptions('sending_strategy', [
                                'prompt'   => t('app', 'Please select an option.'),
                                'readonly' => $isPlanActive,
                                'disabled' => $isPlanActive,
                            ])); ?>
                            <?php echo $form->error($plan, 'sending_strategy'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($plan, 'sending_increment_ratio'); ?>
                            <?php echo $form->dropDownList($plan, 'sending_increment_ratio', $plan->getSendingIncrementRatioArray(), $plan->fieldDecorator->getHtmlOptions('sending_increment_ratio', [
                                'readonly' => $isPlanActive,
                                'disabled' => $isPlanActive,
                            ])); ?>
                            <?php echo $form->error($plan, 'sending_increment_ratio'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($plan, 'description'); ?>
                            <?php echo $form->textArea($plan, 'description', $plan->fieldDecorator->getHtmlOptions('description', ['rows' => 10])); ?>
                            <?php echo $form->error($plan, 'description'); ?>
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

    if (!$plan->getIsNewRecord() && !$plan->hasErrors()) {
        $controller->renderPartial('_schedules', compact('plan'));
    }
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
