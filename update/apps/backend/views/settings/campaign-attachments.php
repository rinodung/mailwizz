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
 * @since 1.3.2
 */

/** @var Controller $controller */
$controller = controller();

/** @var OptionCampaignAttachment $model */
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
    <div class="box box-primary borderless">
        <?php $controller->renderPartial('_campaigns_tabs');
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
                    <h3 class="box-title"><?php echo IconHelper::make('fa-cog') . t('settings', 'Campaign attachments'); ?></h3>
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
                                <?php echo $form->labelEx($model, 'enabled'); ?>
                                <?php echo $form->dropDownList($model, 'enabled', $model->getEnabledOptions(), $model->fieldDecorator->getHtmlOptions('enabled')); ?>
                                <?php echo $form->error($model, 'enabled'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'allowed_file_size'); ?>
                                <?php echo $form->dropDownList($model, 'allowed_file_size', $model->getFileSizeOptions(), $model->fieldDecorator->getHtmlOptions('allowed_file_size')); ?>
                                <?php echo $form->error($model, 'allowed_file_size'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <?php echo $form->labelEx($model, 'allowed_files_count'); ?>
                                <?php echo $form->numberField($model, 'allowed_files_count', $model->fieldDecorator->getHtmlOptions('allowed_files_count')); ?>
                                <?php echo $form->error($model, 'allowed_files_count'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <hr />
                            <div class="pull-left">
                                <h5><?php echo t('settings', 'Allowed extensions'); ?>:</h5>
                            </div>
                            <div class="pull-right">
                                <a href="javascript:;" class="btn btn-primary btn-flat add-campaign-allowed-extension"><?php echo IconHelper::make('create'); ?></a>
                            </div>
                            <div class="clearfix"><!-- --></div>
                            <?php echo $form->error($model, 'allowed_extensions'); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div id="campaign-allowed-ext-list">
                                    <?php foreach ($model->allowed_extensions as $ext) { ?>
                                        <div class="col-lg-3 item">
                                            <div class="input-group">
                                                <?php echo CHtml::textField($model->getModelName() . '[allowed_extensions][]', $ext, $model->fieldDecorator->getHtmlOptions('allowed_extensions', ['class' => 'form-control'])); ?>
                                                <span class="input-group-btn">
                                            <a href="javascript:;" class="btn btn-danger btn-flat remove-campaign-allowed-ext"><?php echo IconHelper::make('delete'); ?></a>
                                        </span>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <hr />
                            <div class="pull-left">
                                <h5><?php echo t('settings', 'Allowed mime types'); ?>:</h5>
                            </div>
                            <div class="pull-right">
                                <a href="javascript:;" class="btn btn-primary btn-flat add-campaign-allowed-mime"><?php echo IconHelper::make('create'); ?></a>
                            </div>
                            <div class="clearfix"><!-- --></div>
                            <?php echo $form->error($model, 'allowed_mime_types'); ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div id="campaign-allowed-mime-list">
                                    <?php foreach ($model->allowed_mime_types as $mime) { ?>
                                        <div class="col-lg-3 item">
                                            <div class="input-group">
                                                <?php echo CHtml::textField($model->getModelName() . '[allowed_mime_types][]', $mime, $model->fieldDecorator->getHtmlOptions('allowed_mime_types', ['class' => 'form-control'])); ?>
                                                <span class="input-group-btn">
                                                <a href="javascript:;" class="btn btn-danger btn-flat remove-campaign-allowed-mime"><?php echo IconHelper::make('delete'); ?></a>
                                            </span>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
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
            </div>
            <div class="box box-primary borderless">
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
        <div style="display: none;" id="campaign-allowed-ext-item">
            <div class="col-lg-3 item">
                <div class="input-group">
                    <?php echo CHtml::textField($model->getModelName() . '[allowed_extensions][]', '', $model->fieldDecorator->getHtmlOptions('allowed_extensions', ['class' => 'form-control'])); ?>
                    <span class="input-group-btn">
                    <a href="javascript:;" class="btn btn-danger btn-flat remove-campaign-allowed-ext"><?php echo IconHelper::make('delete'); ?></a>
                </span>
                </div>
            </div>
        </div>
        <div style="display: none;" id="campaign-allowed-mime-item">
            <div class="col-lg-3 item">
                <div class="input-group">
                    <?php echo CHtml::textField($model->getModelName() . '[allowed_mime_types][]', '', $model->fieldDecorator->getHtmlOptions('allowed_mime_types', ['class' => 'form-control'])); ?>
                    <span class="input-group-btn">
                    <a href="javascript:;" class="btn btn-danger btn-flat remove-campaign-allowed-mime"><?php echo IconHelper::make('delete'); ?></a>
                </span>
                </div>
            </div>
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
