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
 * @since 1.7.4
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $reasonField */
$reasonField = (string)$controller->getData('reasonField');

/** @var ListSubscriber $subscriber */
$subscriber = $controller->getData('subscriber');

/** @var Customer $customer */
$customer = $controller->getData('customer');

?>

<div class="row">
    <div class="col-lg-6 col-lg-push-3 col-md-6 col-md-push-3 col-sm-12">
        <?php
        /** @var CActiveForm $form */
        $form = $controller->beginWidget('CActiveForm'); ?>
        <div class="box box-primary borderless">
            <div class="box-header">
                <h3 class="box-title"><?php echo t('lists', 'Unsubscribe'); ?></h3>
            </div>
            <div class="box-body">
                <div class="callout callout-info">
                    <?php echo t('lists', 'This action will unsubscribe you from all the email lists belonging to this customer!'); ?><br />
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($subscriber, 'email'); ?>
                            <?php echo $form->textField($subscriber, 'email', $subscriber->fieldDecorator->getHtmlOptions('email')); ?>
                            <?php echo $form->error($subscriber, 'email'); ?>
                        </div>
                    </div>
                </div>
                <?php echo $reasonField; ?>
            </div>
            <div class="box-footer">
                <div class="pull-right">
                    <?php echo CHtml::submitButton(t('lists', 'Unsubscribe'), ['class' => 'btn btn-primary btn-flat']); ?>
                </div>
                <div class="clearfix"> </div>
            </div>
        </div>
        <?php $controller->endWidget(); ?>
    </div>
</div>


