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
 * @since 1.5.3
 */

/** @var Controller $controller */
$controller = controller();

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var CustomerGroupOptionApi $model */
$model = $controller->getData('model');

 ?>
<div class="box box-primary borderless">
    <div class="box-body">
        <div class="row">
            <div class="col-lg-4">
                <div class="form-group">
                    <?php echo $form->labelEx($model, 'enabled'); ?>
                    <?php echo $form->dropDownList($model, 'enabled', $model->getYesNoOptions(), $model->fieldDecorator->getHtmlOptions('enabled')); ?>
                    <?php echo $form->error($model, 'enabled'); ?>
                </div>
            </div>
        </div>
    </div>
</div>