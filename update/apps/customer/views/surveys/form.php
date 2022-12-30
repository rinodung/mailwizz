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
 * @since 1.7.8
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Survey $survey */
$survey = $controller->getData('survey');

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
                        ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-survey') . html_encode((string)$pageHeading) . '</h3>')
                        ->render(); ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['surveys/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$survey->getIsNewRecord())
                        ->add(CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['surveys/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
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
                <div class="clearfix"><!-- --></div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($survey, 'name'); ?>
                            <?php echo $form->textField($survey, 'name', $survey->fieldDecorator->getHtmlOptions('name')); ?>
                            <?php echo $form->error($survey, 'name'); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($survey, 'display_name'); ?>
                            <?php echo $form->textField($survey, 'display_name', $survey->fieldDecorator->getHtmlOptions('display_name')); ?>
                            <?php echo $form->error($survey, 'display_name'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($survey, 'description'); ?>
                            <?php echo $form->textarea($survey, 'description', $survey->fieldDecorator->getHtmlOptions('description')); ?>
                            <?php echo $form->error($survey, 'description'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($survey, 'finish_redirect'); ?>
                            <?php echo $form->textField($survey, 'finish_redirect', $survey->fieldDecorator->getHtmlOptions('finish_redirect')); ?>
                            <?php echo $form->error($survey, 'finish_redirect'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($survey, 'status'); ?>
                            <?php echo $form->dropDownList($survey, 'status', $survey->getStatusesList(), $survey->fieldDecorator->getHtmlOptions('status')); ?>
                            <?php echo $form->error($survey, 'status'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($survey, 'start_at'); ?>
                            <?php echo $form->hiddenField($survey, 'start_at', $survey->fieldDecorator->getHtmlOptions('start_at')); ?>
                            <?php echo $form->textField($survey, 'startAt', $survey->fieldDecorator->getHtmlOptions('start_at', [
                                'data-keyup' => t('surveys', 'Please use the date/time picker to set the value, do not enter it manually!'),
                            ])); ?>
                            <?php echo CHtml::textField('fake_start_at', $survey->start_at, [
                                'data-date-format'  => 'yyyy-mm-dd hh:ii:ss',
                                'data-autoclose'    => true,
                                'data-language'     => LanguageHelper::getAppLanguageCode(),
                                'data-syncurl'      => createUrl('campaigns/sync_datetime'),
                                'class'             => 'form-control',
                                'style'             => 'visibility:hidden; height:1px; margin:0; padding:0;',
                            ]); ?>
                            <?php echo $form->error($survey, 'start_at'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($survey, 'end_at'); ?>
                            <?php echo $form->hiddenField($survey, 'end_at', $survey->fieldDecorator->getHtmlOptions('end_at')); ?>
                            <?php echo $form->textField($survey, 'endAt', $survey->fieldDecorator->getHtmlOptions('end_at', [
                                'data-keyup' => t('surveys', 'Please use the date/time picker to set the value, do not enter it manually!'),
                            ])); ?>
                            <?php echo CHtml::textField('fake_end_at', $survey->end_at, [
                                'data-date-format'  => 'yyyy-mm-dd hh:ii:ss',
                                'data-autoclose'    => true,
                                'data-language'     => LanguageHelper::getAppLanguageCode(),
                                'data-syncurl'      => createUrl('campaigns/sync_datetime'),
                                'class'             => 'form-control',
                                'style'             => 'visibility:hidden; height:1px; margin:0; padding:0;',
                            ]); ?>
                            <?php echo $form->error($survey, 'end_at'); ?>
                        </div>
                    </div>
                </div>

                <div class="clearfix"><!-- --></div>

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
