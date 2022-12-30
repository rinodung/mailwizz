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
 * @since 1.2
 */

/** @var Controller $controller */
$controller = controller();

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var OptionCronProcessSubscribers $cronSubscribersModel */
$cronSubscribersModel = $controller->getData('cronSubscribersModel');

?>
<div class="box box-primary borderless">
    <div class="box-header">
        <h3 class="box-title"><?php echo IconHelper::make('fa-cog') . t('settings', 'Subscribers settings'); ?></h3>
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
            'controller'            => $controller,
            'form'                  => $form,
        ]));
        ?>
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronSubscribersModel, 'unsubscribe_days'); ?>
                    <?php echo $form->numberField($cronSubscribersModel, 'unsubscribe_days', $cronSubscribersModel->fieldDecorator->getHtmlOptions('unsubscribe_days')); ?>
                    <?php echo $form->error($cronSubscribersModel, 'unsubscribe_days'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronSubscribersModel, 'unconfirm_days'); ?>
                    <?php echo $form->numberField($cronSubscribersModel, 'unconfirm_days', $cronSubscribersModel->fieldDecorator->getHtmlOptions('unconfirm_days')); ?>
                    <?php echo $form->error($cronSubscribersModel, 'unconfirm_days'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronSubscribersModel, 'blacklisted_days'); ?>
                    <?php echo $form->numberField($cronSubscribersModel, 'blacklisted_days', $cronSubscribersModel->fieldDecorator->getHtmlOptions('blacklisted_days')); ?>
                    <?php echo $form->error($cronSubscribersModel, 'blacklisted_days'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronSubscribersModel, 'sync_custom_fields_values'); ?>
                    <?php echo $form->dropDownList($cronSubscribersModel, 'sync_custom_fields_values', $cronSubscribersModel->getYesNoOptions(), $cronSubscribersModel->fieldDecorator->getHtmlOptions('sync_custom_fields_values')); ?>
                    <?php echo $form->error($cronSubscribersModel, 'sync_custom_fields_values'); ?>
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
            'controller'            => $controller,
            'form'                  => $form,
        ]));
        ?>
        <div class="clearfix"><!-- --></div>
    </div>
</div>
<hr />