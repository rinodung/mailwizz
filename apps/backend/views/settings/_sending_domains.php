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
 * @since 2.0.30
 */

/** @var Controller $controller */
$controller = controller();

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var OptionCronProcessSendingDomains $cronSendingDomainsModel */
$cronSendingDomainsModel = $controller->getData('cronSendingDomainsModel');

?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><?php echo IconHelper::make('fa-cog') . t('settings', 'Settings for sending domains'); ?></h3>
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
                    <?php echo $form->labelEx($cronSendingDomainsModel, 'hourly_checks'); ?>
                    <?php echo $form->dropDownList($cronSendingDomainsModel, 'hourly_checks', $cronSendingDomainsModel->getYesNoOptions(), $cronSendingDomainsModel->fieldDecorator->getHtmlOptions('hourly_checks')); ?>
                    <?php echo $form->error($cronSendingDomainsModel, 'hourly_checks'); ?>
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
