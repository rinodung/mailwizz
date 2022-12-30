<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

/** @var ExtensionController $controller */
$controller = controller();

/** @var CkeditorExtCommon $model */
$model = $controller->getData('model');

/** @var CActiveForm $form */
$form = $controller->beginWidget('CActiveForm');
?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('glyphicon-plus-sign') . $controller->t('CKeditor options'); ?>
            </h3>
        </div>
        <div class="pull-right"></div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-3">
                <div class="form-group">
				    <?php echo $form->labelEx($model, 'enable_editor'); ?>
				    <?php echo $form->dropDownList($model, 'enable_editor', $model->getOptionsDropDown(), $model->fieldDecorator->getHtmlOptions('enable_editor')); ?>
				    <?php echo $form->error($model, 'enable_editor'); ?>
                </div>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'enable_filemanager_user'); ?>
                    <?php echo $form->dropDownList($model, 'enable_filemanager_user', $model->getOptionsDropDown(), $model->fieldDecorator->getHtmlOptions('enable_filemanager_user')); ?>
                    <?php echo $form->error($model, 'enable_filemanager_user'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'enable_filemanager_customer'); ?>
                    <?php echo $form->dropDownList($model, 'enable_filemanager_customer', $model->getOptionsDropDown(), $model->fieldDecorator->getHtmlOptions('enable_filemanager_customer')); ?>
                    <?php echo $form->error($model, 'enable_filemanager_customer'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'default_toolbar'); ?>
                    <?php echo $form->dropDownList($model, 'default_toolbar', $model->getToolbarsDropDown(), $model->fieldDecorator->getHtmlOptions('default_toolbar')); ?>
                    <?php echo $form->error($model, 'default_toolbar'); ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'filemanager_theme'); ?>
                    <?php echo $form->dropDownList($model, 'filemanager_theme', $model->getFilemanagerThemesDropDown(), $model->fieldDecorator->getHtmlOptions('filemanager_theme')); ?>
                    <?php echo $form->error($model, 'filemanager_theme'); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . t('app', 'Save changes'); ?></button>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>
<?php $controller->endWidget(); ?>
