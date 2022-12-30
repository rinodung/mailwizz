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
 * @since 1.3.5.4
 */
?>
<div class="row">
    <?php if (!empty($bulkActions)) {
    $form = $this->beginWidget('CActiveForm', [
            'action'      => $formAction,
            'id'          => 'bulk-action-form',
            'htmlOptions' => ['style' => 'display:none'],
        ]);
    $this->endWidget(); ?>
        <div class="col-lg-3" id="bulk-actions-wrapper" style="display: none;">
            <div class="row">
                <div class="col-lg-6">
                    <?php echo CHtml::dropDownList('bulk_action', null, CMap::mergeArray(['' => t('app', 'Choose')], $bulkActions), [
                        'class'           => 'form-control',
                        'data-delete-msg' => t('app', 'Are you sure you want to remove the selected items?'),
                    ]); ?>
                </div>
                <div class="col-lg-4">
                    <a href="javascript:;" class="btn btn-flat btn-primary" id="btn-run-bulk-action" style="display:none"><?php echo t('app', 'Run bulk action'); ?></a>
                </div>
            </div>
        </div>
    <?php
} ?>
</div>