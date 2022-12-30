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

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var OptionCronDelivery $cronDeliveryModel */
$cronDeliveryModel = $controller->getData('cronDeliveryModel');

?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><?php echo IconHelper::make('fa-cog') . t('settings', 'Delivery settings'); ?></h3>
        </div>
        <div class="pull-right">
            
        </div>
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
            'controller'        => $controller,
            'form'              => $form,
        ]));
        ?>
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronDeliveryModel, 'auto_adjust_campaigns_at_once'); ?>
                    <?php echo $form->dropDownList($cronDeliveryModel, 'auto_adjust_campaigns_at_once', $cronDeliveryModel->getYesNoOptions(), $cronDeliveryModel->fieldDecorator->getHtmlOptions('auto_adjust_campaigns_at_once')); ?>
                    <?php echo $form->error($cronDeliveryModel, 'auto_adjust_campaigns_at_once'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronDeliveryModel, 'campaigns_at_once'); ?>
                    <?php echo $form->numberField($cronDeliveryModel, 'campaigns_at_once', $cronDeliveryModel->fieldDecorator->getHtmlOptions('campaigns_at_once')); ?>
                    <?php echo $form->error($cronDeliveryModel, 'campaigns_at_once'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronDeliveryModel, 'subscribers_at_once'); ?>
                    <?php echo $form->numberField($cronDeliveryModel, 'subscribers_at_once', $cronDeliveryModel->fieldDecorator->getHtmlOptions('subscribers_at_once')); ?>
                    <?php echo $form->error($cronDeliveryModel, 'subscribers_at_once'); ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronDeliveryModel, 'change_server_at'); ?>
                    <?php echo $form->numberField($cronDeliveryModel, 'change_server_at', $cronDeliveryModel->fieldDecorator->getHtmlOptions('change_server_at')); ?>
                    <?php echo $form->error($cronDeliveryModel, 'change_server_at'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronDeliveryModel, 'max_bounce_rate'); ?>
                    <?php echo $form->numberField($cronDeliveryModel, 'max_bounce_rate', $cronDeliveryModel->fieldDecorator->getHtmlOptions('max_bounce_rate', [
                        'step' => '0.01',
                        'min'  => '-1',
                        'max'  => '100',
                    ])); ?>
                    <?php echo $form->error($cronDeliveryModel, 'max_bounce_rate'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
			        <?php echo $form->labelEx($cronDeliveryModel, 'max_complaint_rate'); ?>
			        <?php echo $form->numberField($cronDeliveryModel, 'max_complaint_rate', $cronDeliveryModel->fieldDecorator->getHtmlOptions('max_complaint_rate', [
                        'step' => '0.01',
                        'min'  => '-1',
                        'max'  => '100',
                    ])); ?>
			        <?php echo $form->error($cronDeliveryModel, 'max_complaint_rate'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronDeliveryModel, 'retry_failed_sending'); ?>
                    <?php echo $form->dropDownList($cronDeliveryModel, 'retry_failed_sending', $cronDeliveryModel->getYesNoOptions(), $cronDeliveryModel->fieldDecorator->getHtmlOptions('retry_failed_sending')); ?>
                    <?php echo $form->error($cronDeliveryModel, 'retry_failed_sending'); ?>
                </div>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo CHtml::link(IconHelper::make('info'), '#page-info-pcntl', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                    <?php echo $form->labelEx($cronDeliveryModel, 'use_pcntl'); ?>
                    <?php echo $form->dropDownList($cronDeliveryModel, 'use_pcntl', $cronDeliveryModel->getYesNoOptions(), $cronDeliveryModel->fieldDecorator->getHtmlOptions('use_pcntl')); ?>
                    <?php echo $form->error($cronDeliveryModel, 'use_pcntl'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronDeliveryModel, 'campaigns_in_parallel'); ?>
                    <?php echo $form->numberField($cronDeliveryModel, 'campaigns_in_parallel', $cronDeliveryModel->fieldDecorator->getHtmlOptions('campaigns_in_parallel')); ?>
                    <?php echo $form->error($cronDeliveryModel, 'campaigns_in_parallel'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronDeliveryModel, 'subscriber_batches_in_parallel'); ?>
                    <?php echo $form->numberField($cronDeliveryModel, 'subscriber_batches_in_parallel', $cronDeliveryModel->fieldDecorator->getHtmlOptions('subscriber_batches_in_parallel')); ?>
                    <?php echo $form->error($cronDeliveryModel, 'subscriber_batches_in_parallel'); ?>
                </div>
            </div>
        </div>
        <!-- modals -->
        <div class="modal modal-info fade" id="page-info-pcntl" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo t('settings', 'You can use below settings to increase the delivery speed. Please be aware that wrong changes might have undesired results.'); ?>
                        <br />
                        <strong><?php echo t('settings', 'Also note that below will apply only if you have installed and enabled PHP\'s PCNTL extension on your server. If you are not sure if your server has the extension, ask your hosting.'); ?></strong>
                    </div>
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
            'controller'        => $controller,
            'form'              => $form,
        ]));
        ?>
        <div class="clearfix"><!-- --></div>
    </div>
</div>
<hr />
