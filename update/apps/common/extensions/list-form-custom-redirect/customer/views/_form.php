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

/** @var ListFormCustomRedirect $model */
$model = $controller->getData('model');

/** @var CActiveForm $form */
$form = $controller->getData('form');

?>
<hr />
<div class="row">
    <div class="col-lg-9">
        <div class="form-group">
            <label><?php echo t('lists', 'Instead of the above message, redirect the subscriber to the following url:'); ?></label>
            <?php echo $form->textField($model, 'url', $model->fieldDecorator->getHtmlOptions('url')); ?>
            <?php echo $form->error($model, 'url'); ?>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="form-group">
            <label><?php echo t('lists', 'After this number of seconds:'); ?></label>
            <?php echo $form->numberField($model, 'timeout', $model->fieldDecorator->getHtmlOptions('timeout')); ?>
            <?php echo $form->error($model, 'timeout'); ?>
        </div>
    </div>
</div>
