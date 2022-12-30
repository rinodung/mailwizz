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
 * @since 1.7.0
 */

?>

<div class="form-group field-<?php echo html_encode((string)$field->type->identifier); ?> wrap-<?php echo strtolower((string)$field->tag); ?>" style="display: <?php echo !empty($visible) ? 'block' : 'none'; ?>">
    <?php echo CHtml::activeLabelEx($model, 'value', ['for' => $field->tag]); ?>
    <?php echo CHtml::dropDownList($field->tag, $model->value, $range, $model->getHtmlOptions('value')); ?>
    <?php echo CHtml::error($model, 'value'); ?>
    <?php if (!empty($field->description)) { ?>
        <div class="field-description">
            <?php echo html_encode((string)$field->description); ?>
        </div>
    <?php } ?>
</div>
