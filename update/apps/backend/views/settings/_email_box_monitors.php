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
 * @since 1.4.5
 */

/** @var Controller $controller */
$controller = controller();

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var OptionCronProcessEmailBoxMonitors $cronEmailBoxModel */
$cronEmailBoxModel = $controller->getData('cronEmailBoxModel');

?>
<div class="box box-primary borderless">
    <div class="box-header">
        <h3 class="box-title"><?php echo IconHelper::make('fa-cog') . t('settings', 'Settings for processing email box monitors'); ?></h3>
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
            'controller' => $controller,
            'form'       => $form,
        ]));
        ?>
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronEmailBoxModel, 'servers_at_once'); ?>
                    <?php echo $form->numberField($cronEmailBoxModel, 'servers_at_once', $cronEmailBoxModel->fieldDecorator->getHtmlOptions('servers_at_once')); ?>
                    <?php echo $form->error($cronEmailBoxModel, 'servers_at_once'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronEmailBoxModel, 'emails_at_once'); ?>
                    <?php echo $form->numberField($cronEmailBoxModel, 'emails_at_once', $cronEmailBoxModel->fieldDecorator->getHtmlOptions('emails_at_once')); ?>
                    <?php echo $form->error($cronEmailBoxModel, 'emails_at_once'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronEmailBoxModel, 'pause'); ?>
                    <?php echo $form->numberField($cronEmailBoxModel, 'pause', $cronEmailBoxModel->fieldDecorator->getHtmlOptions('pause')); ?>
                    <?php echo $form->error($cronEmailBoxModel, 'pause'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronEmailBoxModel, 'days_back'); ?>
                    <?php echo $form->numberField($cronEmailBoxModel, 'days_back', $cronEmailBoxModel->fieldDecorator->getHtmlOptions('days_back')); ?>
                    <?php echo $form->error($cronEmailBoxModel, 'days_back'); ?>
                </div>
            </div>
        </div>
        <hr />
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo CHtml::link(IconHelper::make('info'), '#page-info-pcntl-monitor', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                    <?php echo $form->labelEx($cronEmailBoxModel, 'use_pcntl'); ?>
                    <?php echo $form->dropDownList($cronEmailBoxModel, 'use_pcntl', $cronEmailBoxModel->getYesNoOptions(), $cronEmailBoxModel->fieldDecorator->getHtmlOptions('use_pcntl')); ?>
                    <?php echo $form->error($cronEmailBoxModel, 'use_pcntl'); ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($cronEmailBoxModel, 'pcntl_processes'); ?>
                    <?php echo $form->numberField($cronEmailBoxModel, 'pcntl_processes', $cronEmailBoxModel->fieldDecorator->getHtmlOptions('pcntl_processes')); ?>
                    <?php echo $form->error($cronEmailBoxModel, 'pcntl_processes'); ?>
                </div>
            </div>
        </div>
        <!-- modals -->
        <div class="modal modal-info fade" id="page-info-pcntl-monitor" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo t('settings', 'You can use below settings to increase processing speed. Please be aware that wrong changes might have undesired results.'); ?>
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