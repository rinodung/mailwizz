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

/** @var PricePlan $pricePlan */
$pricePlan = $controller->getData('pricePlan');

/** @var PricePlanCustomerGroupDisplay $pricePlanDisplay */
$pricePlanDisplay = $controller->getData('pricePlanDisplay');

/** @var array $pricePlanDisplaySelected */
$pricePlanDisplaySelected = (array)$controller->getData('pricePlanDisplaySelected');
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
                        ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-credit-card') . html_encode((string)$pageHeading) . '</h3>')
                        ->render(); ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['price_plans/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$pricePlan->getIsNewRecord())
                        ->add(HtmlHelper::accessLink(IconHelper::make('cancel') . t('app', 'Cancel'), ['price_plans/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
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
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($pricePlan, 'name'); ?>
                            <?php echo $form->textField($pricePlan, 'name', $pricePlan->fieldDecorator->getHtmlOptions('name')); ?>
                            <?php echo $form->error($pricePlan, 'name'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($pricePlan, 'group_id'); ?>
                            <?php echo $form->dropDownList($pricePlan, 'group_id', CustomerGroup::getGroupsArray(), $pricePlan->fieldDecorator->getHtmlOptions('group_id')); ?>
                            <?php echo $form->error($pricePlan, 'group_id'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($pricePlan, 'price'); ?>
                            <?php echo $form->textField($pricePlan, 'price', $pricePlan->fieldDecorator->getHtmlOptions('price')); ?>
                            <?php echo $form->error($pricePlan, 'price'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($pricePlan, 'description'); ?>
                            <?php echo $form->textArea($pricePlan, 'description', $pricePlan->fieldDecorator->getHtmlOptions('description')); ?>
                            <?php echo $form->error($pricePlan, 'description'); ?>
                        </div> 
                    </div>
                </div>     
                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($pricePlan, 'status'); ?>
                            <?php echo $form->dropDownList($pricePlan, 'status', $pricePlan->getStatusesList(), $pricePlan->fieldDecorator->getHtmlOptions('status')); ?>
                            <?php echo $form->error($pricePlan, 'status'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($pricePlan, 'visible'); ?>
                            <?php echo $form->dropDownList($pricePlan, 'visible', $pricePlan->getYesNoOptions(), $pricePlan->fieldDecorator->getHtmlOptions('visible')); ?>
                            <?php echo $form->error($pricePlan, 'visible'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($pricePlan, 'sort_order'); ?>
                            <?php echo $form->dropDownList($pricePlan, 'sort_order', $pricePlan->getSortOrderList(), $pricePlan->fieldDecorator->getHtmlOptions('sort_order')); ?>
                            <?php echo $form->error($pricePlan, 'sort_order'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($pricePlan, 'recommended'); ?>
                            <?php echo $form->dropDownList($pricePlan, 'recommended', $pricePlan->getYesNoOptions(), $pricePlan->fieldDecorator->getHtmlOptions('recommended')); ?>
                            <?php echo $form->error($pricePlan, 'recommended'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group">
		                    <?php echo $form->labelEx($pricePlanDisplay, 'group_id'); ?>
		                    <?php echo $form->dropDownList($pricePlanDisplay, 'group_id', CMap::mergeArray(['0' => ''], $pricePlanDisplay->getCustomerGroupsList()), $pricePlanDisplay->fieldDecorator->getHtmlOptions('group_id', [
                                'multiple' => true,
                                'options'  => $pricePlanDisplaySelected,
                            ])); ?>
		                    <?php echo $form->error($pricePlanDisplay, 'group_id'); ?>
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
