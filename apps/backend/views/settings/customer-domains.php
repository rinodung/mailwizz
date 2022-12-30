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

/** @var OptionCustomerSendingDomains $sending */
$sending = $controller->getData('sending');

/** @var OptionCustomerTrackingDomains $tracking */
$tracking = $controller->getData('tracking');

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
    <div class="box box-default borderless">
        <div class="box-header">
            <?php $controller->renderPartial('_customers_tabs'); ?>
        </div>
        <?php
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
            $form = $controller->beginWidget('CActiveForm');
            /**
             * This hook gives a chance to prepend content before the active form fields.
             * Please note that from inside the action callback you can access all the controller view variables
             * via {@CAttributeCollection $collection->controller->getData()}
             * @since 1.3.3.1
             */
            hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                'controller'    => $controller,
                'form'          => $form,
            ]));

            // tracking domains fields
            $controller->renderPartial('customer-tracking-domains', ['model' => $tracking, 'form' => $form]); ?>
            <hr />
            <?php
            // sending domains fields
            $controller->renderPartial('customer-sending-domains', ['model' => $sending, 'form' => $form]);

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
