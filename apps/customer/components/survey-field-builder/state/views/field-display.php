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

<div class="form-group field-<?php echo $field->type->identifier; ?> state-field wrap-<?php echo strtolower((string)$field->getTag()); ?>" style="display: <?php echo !empty($visible) ? 'block' : 'none'; ?>">
    <?php echo CHtml::activeLabelEx($model, 'value', ['for' => $field->getTag()]); ?>
    <?php echo CHtml::dropDownList($field->getTag(), $model->value, $statesList, $model->getHtmlOptions('value', [
        'data-selected' => $model->value,
        'data-url'      => createUrl('surveys/fields_country_by_zone'),
    ])); ?>
    <?php echo CHtml::error($model, 'value'); ?>
    <?php if (!empty($field->description)) { ?>
        <div class="field-description">
            <?php echo $field->description; ?>
        </div>
    <?php } ?>
</div>