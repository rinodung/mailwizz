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

/** @var ListSubscriber $subscriber */
$subscriber = $controller->getData('subscriber');
?>

<div class="row">
    <div class="col-lg-12">
        <div class="form-group">
            <?php echo CHtml::activeLabelEx($subscriber, 'email'); ?>
            <?php echo CHtml::activeTextField($subscriber, 'email', $subscriber->fieldDecorator->getHtmlOptions('email')); ?>
            <?php echo CHtml::error($subscriber, 'email'); ?>
        </div>
    </div>
</div>
