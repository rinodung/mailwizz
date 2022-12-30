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
 * @since 1.3.4.7
 */

/** @var Controller $controller */
$controller = controller();

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var OptionCustomerSendingDomains $model */
$model = $controller->getData('model');

?>

<div class="box box-primary borderless">
    <div class="box-header">
        <h3 class="box-title"><?php echo t('settings', 'Sending domains'); ?></h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'can_manage_sending_domains'); ?>
                    <?php echo $form->dropDownList($model, 'can_manage_sending_domains', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('can_manage_sending_domains')); ?>
                    <?php echo $form->error($model, 'can_manage_sending_domains'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'max_sending_domains'); ?>
                    <?php echo $form->numberField($model, 'max_sending_domains', $model->fieldDecorator->getHtmlOptions('max_sending_domains')); ?>
                    <?php echo $form->error($model, 'max_sending_domains'); ?>
                </div>
            </div>
        </div>
    </div>
</div>