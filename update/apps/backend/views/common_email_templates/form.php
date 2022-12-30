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
 * @since 1.5.5
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var CommonEmailTemplate $model */
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
                        ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-text-width') . html_encode((string)$pageHeading) . '</h3>')
                        ->render(); ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(HtmlHelper::accessLink(IconHelper::make('create') . t('app', 'Create new'), ['common_email_templates/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$model->getIsNewRecord())
                        ->add(HtmlHelper::accessLink(IconHelper::make('cancel') . t('app', 'Cancel'), ['common_email_templates/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
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
                            <?php echo $form->labelEx($model, 'name'); ?>
                            <?php echo $form->textField($model, 'name', $model->fieldDecorator->getHtmlOptions('name')); ?>
                            <?php echo $form->error($model, 'name'); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
			                <?php echo $form->labelEx($model, 'subject'); ?> [<a data-toggle="modal" href="#available-tags-modal"><?php echo t('common_email_templates', 'Available tags'); ?></a>]
			                <?php echo $form->textField($model, 'subject', $model->fieldDecorator->getHtmlOptions('subject')); ?>
			                <?php echo $form->error($model, 'subject'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'content'); ?> [<a data-toggle="modal" href="#available-tags-modal"><?php echo t('common_email_templates', 'Available tags'); ?></a>]
                            <?php echo $form->textArea($model, 'content', $model->fieldDecorator->getHtmlOptions('content', ['rows' => 15])); ?>
                            <?php echo $form->error($model, 'content'); ?>
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

        
        <div class="modal fade" id="available-tags-modal" tabindex="-1" role="dialog" aria-labelledby="available-tags-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo t('common_email_templates', 'Available tags'); ?></h4>
                    </div>
                    <div class="modal-body" style="max-height: 300px; overflow-y:scroll;">
                        <table class="table table-hover">
                            <tr>
                                <td><?php echo t('common_email_templates', 'Tag'); ?></td>
                                <td><?php echo t('common_email_templates', 'Description'); ?></td>
                            </tr>
                            <?php foreach ($model->getAllTags() as $tag) { ?>
                                <tr>
                                    <td><?php echo html_encode((string)$tag->tag); ?></td>
                                    <td><?php echo html_encode((string)$tag->description); ?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
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
