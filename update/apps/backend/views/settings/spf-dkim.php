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
 * @since 1.3.6.6
 */

/** @var Controller $controller */
$controller = controller();

/** @var OptionSpfDkim $model */
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
                    <h3 class="box-title"><?php echo t('settings', 'SPF/Dkim'); ?></h3>
                </div>
                <div class="pull-right">
                    <?php echo CHtml::link(IconHelper::make('info'), '#page-info', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
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
                            <?php echo CHtml::link(IconHelper::make('info'), '#page-info-spf', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                            <?php echo $form->labelEx($model, 'spf'); ?>
                            <?php echo $form->textField($model, 'spf', $model->fieldDecorator->getHtmlOptions('spf')); ?>
                            <?php echo $form->error($model, 'spf'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
			                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-dmarc', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
			                <?php echo $form->labelEx($model, 'dmarc'); ?>
			                <?php echo $form->textField($model, 'dmarc', $model->fieldDecorator->getHtmlOptions('dmarc')); ?>
			                <?php echo $form->error($model, 'dmarc'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($model, 'update_sending_domains'); ?>
                            <?php echo $form->dropDownList($model, 'update_sending_domains', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('update_sending_domains')); ?>
                            <?php echo $form->error($model, 'update_sending_domains'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo CHtml::link(IconHelper::make('info'), '#page-info-dkim', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                            <?php echo $form->labelEx($model, 'dkim_private_key'); ?>
                            <?php echo $form->textArea($model, 'dkim_private_key', $model->fieldDecorator->getHtmlOptions('dkim_private_key', ['rows' => 10])); ?>
                            <?php echo $form->error($model, 'dkim_private_key'); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo CHtml::link(IconHelper::make('info'), '#page-info-dkim', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                            <?php echo $form->labelEx($model, 'dkim_public_key'); ?>
                            <?php echo $form->textArea($model, 'dkim_public_key', $model->fieldDecorator->getHtmlOptions('dkim_public_key', ['rows' => 10])); ?>
                            <?php echo $form->error($model, 'dkim_public_key'); ?>
                        </div>
                    </div>
                    <div class="clearfix"><!-- --></div>
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
        <!-- modals -->
        <div class="modal modal-info fade" id="page-info" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo t('settings', 'Please note that the values you set here will be used for all the Sending Domains.'); ?><br />
                        <?php echo t('settings', 'If you don\'t want this, then leave these empty.'); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal modal-info fade" id="page-info-spf" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo t('settings', 'You can use {url} to generate the SPF records.', ['{url}' => CHtml::link('https://www.mailwizz.com/tools/spf-record-generator/', 'https://www.mailwizz.com/tools/spf-record-generator/', ['target' => '_blank'])]); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal modal-info fade" id="page-info-dmarc" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                    </div>
                    <div class="modal-body">
					    <?php echo t('settings', 'You can use {url} to generate the DMARC records.', ['{url}' => CHtml::link('https://www.mailwizz.com/tools/dmarc-record-generator/', 'https://www.mailwizz.com/tools/dmarc-record-generator/', ['target' => '_blank'])]); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal modal-info fade" id="page-info-dkim" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo t('settings', 'You can use {url} to generate the dkim records.', ['{url}' => CHtml::link('http://dkimcore.org/tools/keys.html', 'http://dkimcore.org/tools/keys.html', ['target' => '_blank'])]); ?><br />
                        <?php echo t('settings', 'Please note that you have to paste the full public/private keys, including the key header/footer.'); ?>
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
