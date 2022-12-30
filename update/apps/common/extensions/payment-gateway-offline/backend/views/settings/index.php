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

/** @var PaymentGatewayOfflineExtCommon $model */
$model = $controller->getData('model');

/** @var CActiveForm $form */
$form = $controller->beginWidget('CActiveForm');
?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('glyphicon-transfer') . $pageHeading; ?>
            </h3>
        </div>
        <div class="pull-right"></div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'description'); ?>
                    <?php echo $form->textArea($model, 'description', $model->fieldDecorator->getHtmlOptions('description', ['rows' => 5])); ?>
                    <?php echo $form->error($model, 'description'); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'status'); ?>
                    <?php echo $form->dropDownList($model, 'status', $model->getStatusesDropDown(), $model->fieldDecorator->getHtmlOptions('status')); ?>
                    <?php echo $form->error($model, 'status'); ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'sort_order'); ?>
                    <?php echo $form->dropDownList($model, 'sort_order', $model->getSortOrderDropDown(), $model->fieldDecorator->getHtmlOptions('sort_order', ['data-placement' => 'left'])); ?>
                    <?php echo $form->error($model, 'sort_order'); ?>
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
