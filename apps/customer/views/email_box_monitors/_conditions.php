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
 * @since 1.4.5
 */

/** @var Controller $controller */
$controller = controller();

/** @var EmailBoxMonitor $server */
$server = $controller->getData('server');

/** @var CActiveForm $form */
$form = $controller->getData('form');

?>
<div class="row">
    <div class="conditions-container">
        <div class="col-lg-12">
            <h5>
                <div class="pull-left">
                    <?php echo t('servers', 'Defined conditions:'); ?>
                </div>
                <div class="pull-right">
                    <a href="javascript:;" class="btn btn-primary btn-flat btn-add-condition"><?php echo IconHelper::make('create'); ?></a>
                    <a href="#page-info-conditions" data-toggle="modal" class="btn btn-primary btn-flat"><?php echo IconHelper::make('info'); ?></a>
                </div>
                <div class="clearfix"><!-- --></div>
            </h5>

            <div class="row">
                <div class="col-lg-12">
                    <?php echo $form->error($server, 'conditions'); ?>
                </div>
            </div>

            <hr />
        </div>
        <?php foreach ($server->getConditions() as $index => $cond) {?>
            <div class="item">
                <hr />
                <div class="col-lg-2">
                    <?php echo CHtml::label(t('servers', 'Condition'), 'condition'); ?>
                    <?php echo CHtml::dropDownList($server->getModelName() . '[conditions][' . $index . '][condition]', $cond['condition'], $server->getConditionsList(), $server->fieldDecorator->getHtmlOptions('conditions')); ?>
                </div>
                <div class="col-lg-2">
                    <?php echo CHtml::label(t('servers', 'Value'), 'value'); ?>
                    <?php echo CHtml::textField($server->getModelName() . '[conditions][' . $index . '][value]', $cond['value'], $server->fieldDecorator->getHtmlOptions('conditions', ['placeholder' => t('servers', 'Unsubscribe me')])); ?>
                </div>
                <div class="col-lg-2 select-action-wrapper">
                    <?php echo CHtml::label(t('servers', 'Subscriber action'), 'action'); ?>
                    <?php echo CHtml::dropDownList($server->getModelName() . '[conditions][' . $index . '][action]', $cond['action'], $server->getActionsList(), $server->fieldDecorator->getHtmlOptions('conditions')); ?>
                </div>
                <div class="col-lg-2 select-email-list-wrapper" style="display: <?php echo !empty($cond['list_id']) ? 'block' : 'none'; ?>">
                    <?php echo CHtml::label(t('servers', 'Email list'), 'list_id'); ?>
                    <?php echo CHtml::dropDownList($server->getModelName() . '[conditions][' . $index . '][list_id]', $cond['list_id'], $server->getCustomerEmailListsAsOptions(), $server->fieldDecorator->getHtmlOptions('conditions')); ?>
                </div>
                <div class="col-lg-2 select-campaign-group-wrapper" style="display: <?php echo !empty($cond['campaign_group_id']) ? 'block' : 'none'; ?>">
		            <?php echo CHtml::label(t('servers', 'Campaign group'), 'campaign_group_id'); ?>
		            <?php echo CHtml::dropDownList($server->getModelName() . '[conditions][' . $index . '][campaign_group_id]', $cond['campaign_group_id'], $server->getCustomerCampaignGroupsAsOptions(), $server->fieldDecorator->getHtmlOptions('conditions')); ?>
                </div>
                <div class="col-lg-2">
                    <label><?php echo t('app', 'Action'); ?></label><br />
                    <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-condition"><?php echo IconHelper::make('delete'); ?></a>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
        <?php } ?>
    </div>
</div>

<div id="condition-template" style="display: none;">
    <div class="item">
        <hr />
        <div class="col-lg-2">
            <?php echo CHtml::label(t('servers', 'Condition'), 'condition'); ?>
            <?php echo CHtml::dropDownList($server->getModelName() . '[conditions][{index}][condition]', '', $server->getConditionsList(), $server->fieldDecorator->getHtmlOptions('conditions')); ?>
        </div>
        <div class="col-lg-2">
            <?php echo CHtml::label(t('servers', 'Value'), 'value'); ?>
            <?php echo CHtml::textField($server->getModelName() . '[conditions][{index}][value]', '', $server->fieldDecorator->getHtmlOptions('conditions', ['placeholder' => t('servers', 'Unsubscribe me')])); ?>
        </div>
        <div class="col-lg-2 select-action-wrapper">
            <?php echo CHtml::label(t('servers', 'Subscriber action'), 'action'); ?>
            <?php echo CHtml::dropDownList($server->getModelName() . '[conditions][{index}][action]', '', $server->getActionsList(), $server->fieldDecorator->getHtmlOptions('conditions')); ?>
        </div>
        <div class="col-lg-2 select-email-list-wrapper" style="display: none">
            <?php echo CHtml::label(t('servers', 'Email list'), 'list_id'); ?>
            <?php echo CHtml::dropDownList($server->getModelName() . '[conditions][{index}][list_id]', '', $server->getCustomerEmailListsAsOptions(), $server->fieldDecorator->getHtmlOptions('conditions')); ?>
        </div>
        <div class="col-lg-2 select-campaign-group-wrapper" style="display: none">
		    <?php echo CHtml::label(t('servers', 'Campaign group'), 'list_id'); ?>
		    <?php echo CHtml::dropDownList($server->getModelName() . '[conditions][{index}][campaign_group_id]', '', $server->getCustomerCampaignGroupsAsOptions(), $server->fieldDecorator->getHtmlOptions('conditions')); ?>
        </div>
        <div class="col-lg-2">
            <label><?php echo t('app', 'Action'); ?></label><br />
            <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-condition"><?php echo IconHelper::make('delete'); ?></a>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>

<!-- modals -->
<div class="modal modal-info fade" id="page-info-conditions" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                $text = 'These conditions will be applied to the email body and if matched, the given action will be taken against the email address.<br />Conditions are applied in the order they are added and execution stops at first match. The asterix wildcard (*) matches everything.';
                echo t('servers', StringHelper::normalizeTranslationString($text));
                ?>
            </div>
        </div>
    </div>
</div>
