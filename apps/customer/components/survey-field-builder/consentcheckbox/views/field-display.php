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
 * @since 1.7.8
 */

?>

<div class="form-group field-<?php echo $field->type->identifier; ?> wrap-<?php echo strtolower((string)$field->getTag()); ?>" style="display: <?php echo !empty($visible) ? 'block' : 'none'; ?>">
    <div>
        <?php echo CHtml::checkBox($field->getTag(), !empty($model->value), $model->getHtmlOptions('value', [
            'value'        => !empty($model->value) ? $model->value : $field->consent_text,
            'uncheckValue' => '',
        ])); ?>
        <?php echo CHtml::activeLabelEx($model, 'value', ['for' => $field->getTag()]); ?>
    </div>
    <?php echo CHtml::error($model, 'value'); ?>
    <?php if (!empty($model->value) || !empty($field->consent_text)) { ?>
        <div class="field-description field-consent-text">
            <?php echo !empty($model->value) ? $model->value : $field->consent_text; ?>
        </div>
    <?php } ?>
</div>