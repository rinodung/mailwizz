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
 */

/** @var ExtensionController $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var CookieConsentExtCommon $model */
$model = $controller->getData('model');

/** @var CookieConsentExt $extension */
$extension = $controller->getExtension();

/** @var CActiveForm $form */
$form = $controller->beginWidget('CActiveForm'); ?>

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('glyphicon-html5') . $pageHeading; ?>
            </h3>
        </div>
        <div class="pull-right"></div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'enabled'); ?>
                    <?php echo $form->dropDownList($model, 'enabled', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled')); ?>
                    <?php echo $form->error($model, 'enabled'); ?>
                </div>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'theme'); ?>
                    <?php echo $form->dropDownList($model, 'theme', $model->getThemeOptions(), $model->fieldDecorator->getHtmlOptions('theme')); ?>
                    <?php echo $form->error($model, 'theme'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'position'); ?>
                    <?php echo $form->dropDownList($model, 'position', $model->getPositionOptions(), $model->fieldDecorator->getHtmlOptions('position')); ?>
                    <?php echo $form->error($model, 'position'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'palette_popup_background'); ?>
                    <div class="input-group">
                        <?php echo $form->textField($model, 'palette_popup_background', $model->fieldDecorator->getHtmlOptions('palette_popup_background')); ?>
                        <span class="input-group-addon" style="<?php echo empty($model->palette_popup_background) ? 'display:none' : ''; ?>">
                            &nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-primary btn-flat btn-select-color" title="<?php echo $extension->t('Select background color'); ?>">
                                <?php echo IconHelper::make('fa-paint-brush'); ?>
                            </a>&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-primary btn-flat btn-reset-color" title="<?php echo $extension->t('Reset background color'); ?>">
                                <?php echo IconHelper::make('fa-history'); ?>
                            </a>&nbsp;
                        </span>
                    </div>
                    <?php echo $form->error($model, 'palette_popup_background'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'palette_button_background'); ?>
                    <div class="input-group">
                        <?php echo $form->textField($model, 'palette_button_background', $model->fieldDecorator->getHtmlOptions('palette_button_background')); ?>
                        <span class="input-group-addon" style="<?php echo empty($model->palette_button_background) ? 'display:none' : ''; ?>">
                            &nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-primary btn-flat btn-select-color" title="<?php echo $extension->t('Select button color'); ?>">
                                <?php echo IconHelper::make('fa-paint-brush'); ?>
                            </a>&nbsp;
                            <a href="javascript:;" class="btn btn-xs btn-primary btn-flat btn-reset-color" title="<?php echo $extension->t('Reset button color'); ?>">
                                <?php echo IconHelper::make('fa-history'); ?>
                            </a>&nbsp;
                        </span>
                    </div>
                    <?php echo $form->error($model, 'palette_button_background'); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'message'); ?>
                    <?php echo $form->textField($model, 'message', $model->fieldDecorator->getHtmlOptions('message')); ?>
                    <?php echo $form->error($model, 'message'); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            <button type="submit" class="btn btn-primary btn-submit"><?php echo IconHelper::make('save') . t('app', 'Save changes'); ?></button>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>
<?php $controller->endWidget();
