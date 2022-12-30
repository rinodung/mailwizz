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

/** @var AccountController $controller */
$controller = controller();

/** @var Customer $customer */
$customer = $controller->getData('customer');

/** @var string $qrCodeUri */
$qrCodeUri = (string)$controller->getData('qrCodeUri');

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
    <div class="box box-primary borderless">
        <div class="tabs-container">
            <?php
            echo $controller->renderTabs();
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

            // and render only if allowed
            if ($collection->itemAt('renderForm')) {
                /** @var CActiveForm $form */
                $form = $controller->beginWidget('CActiveForm'); ?>
                <div class="box box-primary borderless">
                    <div class="box-body">
                        <?php
                        /**
                         * This hook gives a chance to prepend content before the active form fields.
                         * Please note that from inside the action callback you can access all the controller view variables
                         * via {@CAttributeCollection $collection->controller->getData()}
                         * @since 1.3.3.1
                         */
                        hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                            'controller'    => $controller,
                            'form'          => $form,
                        ])); ?>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <a href="#page-info" class="btn btn-primary btn-flat btn-xs" data-toggle="modal"><?php echo IconHelper::make('info'); ?></a>
                                    <?php echo $form->labelEx($customer, 'twofa_enabled'); ?>
                                    <?php echo $form->dropDownList($customer, 'twofa_enabled', $customer->getYesNoOptions(), $customer->fieldDecorator->getHtmlOptions('twofa_enabled')); ?>
                                    <?php echo $form->error($customer, 'twofa_enabled'); ?>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <img src="<?php echo html_encode((string)$qrCodeUri); ?>" />
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
                            'controller'    => $controller,
                            'form'          => $form,
                        ])); ?>
                        <div class="clearfix"><!-- --></div>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . t('app', 'Save changes'); ?></button>
                        </div>
                        <div class="clearfix"><!-- --></div>
                    </div>
                </div>
                <!-- modals -->
                <div class="modal modal-info fade" id="page-info" tabindex="-1" role="dialog">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                            </div>
                            <div class="modal-body">
                                <?php echo t('customers', 'Use any authenticator app such as Google Authenticator to scan the QR code below.'); ?><br />
                                <?php echo t('customers', 'You will then use the authenticator app to generate the code to login in the app.'); ?><br />
                            </div>
                        </div>
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
            ]));
            ?>
        </div>
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
