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

/** @var string $previewUrl */
$previewUrl = (string)$controller->getData('previewUrl');

/** @var CustomerEmailTemplate $template */
$template = $controller->getData('template');

/** @var CampaignTemplate $campaignTemplate */
$campaignTemplate = $controller->getData('campaignTemplate');

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
                    <h3 class="box-title"> <?php echo IconHelper::make('glyphicon-text-width') . $pageHeading; ?> </h3>
                </div>
                <div class="pull-right">
                    <?php if (!$template->getIsNewRecord()) { ?>
                        <a href="javascript:;" onclick="window.open('<?php echo $previewUrl; ?>', '<?php echo t('email_templates', 'Preview'); ?>', 'height=600, width=600'); return false;" class="btn btn-primary btn-flat"><?php echo IconHelper::make('view') . '&nbsp;' . t('email_templates', 'Preview'); ?></a>
                        <a data-toggle="modal" href="#template-test-email" class="btn btn-primary btn-flat"><?php echo IconHelper::make('fa-send') . '&nbsp;' . t('email_templates', 'Send a test email using this template'); ?></a>
                        <?php echo CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['templates/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]); ?>
                    <?php } ?>
                    <?php echo CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['templates/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]); ?>
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
                            <?php echo $form->labelEx($template, 'name'); ?>
                            <?php echo $form->textField($template, 'name', $template->fieldDecorator->getHtmlOptions('name')); ?>
                            <?php echo $form->error($template, 'name'); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($template, 'category_id'); ?>
                            <?php echo $form->dropDownList($template, 'category_id', CMap::mergeArray(['' => ''], CustomerEmailTemplateCategory::getAllAsOptions((int)$template->customer_id)), $template->fieldDecorator->getHtmlOptions('category_id')); ?>
                            <?php echo $form->error($template, 'category_id'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <div class="pull-left">
                                <?php echo $form->labelEx($template, 'content'); ?> [<a data-toggle="modal" href="#available-tags-modal"><?php echo t('lists', 'Available tags'); ?></a>]
                                <?php
                                // since 1.3.5
                                hooks()->doAction('before_wysiwyg_editor_left_side', [
                                    'controller' => $controller,
                                    'template'   => $template,
                                ]); ?>
                            </div>
                            <div class="pull-right">
                                <?php
                                // since 1.3.5
                                hooks()->doAction('before_wysiwyg_editor_right_side', [
                                    'controller' => $controller,
                                    'template'   => $template,
                                ]); ?>
                            </div>
                            <div class="clearfix"><!-- --></div>
                            <?php echo $form->textArea($template, 'content', $template->fieldDecorator->getHtmlOptions('content', ['rows' => 15])); ?>
                            <?php echo $form->error($template, 'content'); ?>
                            <?php
                            // since 1.4.4
                            hooks()->doAction('after_wysiwyg_editor', [
                                'controller' => $controller,
                                'template'   => $template,
                            ]); ?>
                        </div>
                    </div>
                </div>
                <?php
                /**
                 * This hook gives a chance to append content after the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->getData()}
                 *
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
    <div class="modal fade" id="available-tags-modal" tabindex="-1" role="dialog" aria-labelledby="available-tags-modal-label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo t('lists', 'Available tags'); ?></h4>
                </div>
                <div class="modal-body" style="max-height: 300px; overflow-y:scroll;">
                    <table class="table table-hover">
                        <tr>
                            <td><?php echo t('lists', 'Tag'); ?></td>
                            <td><?php echo t('lists', 'Required'); ?></td>
                        </tr>
                        <?php foreach ($campaignTemplate->getAvailableTags() as $tag) { ?>
                            <tr>
                                <td><?php echo html_encode($tag['tag']); ?></td>
                                <td><?php echo $tag['required'] ? strtoupper(t('app', CampaignTemplate::TEXT_YES)) : strtoupper(t('app', CampaignTemplate::TEXT_NO)); ?></td>
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

    <?php if (!$template->getIsNewRecord()) { ?>
    <div class="modal fade" id="template-test-email" tabindex="-1" role="dialog" aria-labelledby="template-test-email-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('email_templates', 'Send a test email'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                     <strong><?php echo t('app', 'Notes'); ?>: </strong><br />
                    <?php
                    $text = '
                    * if multiple recipients, separate the email addresses by a comma.<br />
                    * the email tags will not be parsed while sending test emails.<br />
                    * make sure you save the template changes before you send the test.';
                    echo t('email_templates', StringHelper::normalizeTranslationString($text));
                    ?>
                 </div>
                 <?php echo CHtml::form(['templates/test', 'template_uid' => $template->template_uid], 'post', ['id' => 'template-test-form']); ?>
                    <div class="form-group">
                        <?php echo CHtml::label(t('templates', 'Subject'), 'email'); ?>
                        <?php echo CHtml::textField('subject', null, ['class' => 'form-control', 'placeholder' => t('templates', '*** TEST TEMPLATE *** {name}', ['{name}' => $template->name])]); ?>
                    </div>
                    <div class="clearfix"><!-- --></div> 
                    <div class="form-group">
                         <?php echo CHtml::label(t('templates', 'Recipient(s)'), 'email'); ?>
                         <?php echo CHtml::textField('email', null, ['class' => 'form-control', 'placeholder' => t('templates', 'i.e: a@domain.com, b@domain.com, c@domain.com')]); ?>
                     </div>
                     <div class="clearfix"><!-- --></div>
                     <div class="form-group">
                         <?php echo CHtml::label(t('templates', 'From email (optional)'), 'from_email'); ?>
                         <?php echo CHtml::textField('from_email', null, ['class' => 'form-control', 'placeholder' => t('templates', 'i.e: me@domain.com')]); ?>
                     </div>
                 <?php CHtml::endForm(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#template-test-form').submit();"><?php echo IconHelper::make('fa-send') . '&nbsp;' . t('email_templates', 'Send test'); ?></button>
            </div>
          </div>
        </div>
    </div>
    <?php } ?>
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
