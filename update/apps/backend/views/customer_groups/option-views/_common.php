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
 * @since 1.3.4.6
 */

/** @var Controller $controller */
$controller = controller();

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var CustomerGroupOptionCommon $model */
$model = $controller->getData('model');

 ?>
<div class="box box-primary borderless">
    <div class="box-body">
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'show_articles_menu'); ?>
                    <?php echo $form->dropDownList($model, 'show_articles_menu', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('show_articles_menu')); ?>
                    <?php echo $form->error($model, 'show_articles_menu'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'mask_email_addresses'); ?>
                    <?php echo $form->dropDownList($model, 'mask_email_addresses', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('mask_email_addresses')); ?>
                    <?php echo $form->error($model, 'mask_email_addresses'); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'notification_message'); ?>
                    <?php echo $form->textArea($model, 'notification_message', $model->fieldDecorator->getHtmlOptions('notification_message')); ?>
                    <?php echo $form->error($model, 'notification_message'); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"><!-- --></div>
</div>