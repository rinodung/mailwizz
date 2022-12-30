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
 * @since 2.1.10
 */

/** @var Controller $controller */
$controller = controller();

/** @var DeliveryServer $server */
$server = $controller->getData('server');

/** @var CActiveForm $form */
$form = $controller->getData('form');
?>

<hr />

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
		        <?php echo IconHelper::make('fa-bars') . t('servers', 'Warmup plan'); ?>
                <span class="badge badge-ds-warmup-plan-status" data-completed-class="bg-green" data-completed-text="<?php echo t('app', 'Completed'); ?>" data-not-completed-class="bg-blue" data-not-completed-text="<?php echo t('app', 'Not completed yet'); ?>" style="display:none"></span>
            </h3>
        </div>
        <div class="pull-right"></div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
	        <?php if (!empty($server->warmup_plan_id) && !empty($server->warmupPlan) && $server->warmupPlan->getIsDeliveryServerProcessing((int)$server->server_id)) { ?>
                <div class="col-lg-12">
                    <div class="callout callout-info">
				        <?php echo t('warmup_plans', 'Switching between plans when they are in processing mode is not advised. You should let the warmup plan finish all the generated schedules.'); ?><br />
				        <?php echo t('warmup_plans', 'If you do switch, the current plan will freeze and will resume if you ever use it again with this delivery server.'); ?>
                    </div>
                </div>
	        <?php } ?>
            <div class="col-lg-3">
                <div class="form-group">
                    <?php echo $form->labelEx($server, 'warmup_plan_id'); ?>
                    <?php echo $form->hiddenField($server, 'warmup_plan_id', $server->fieldDecorator->getHtmlOptions('warmup_plan_id', ['class' => 'delivery-server-warmup-plan-id'])); ?>
                    <?php
                    $controller->widget('zii.widgets.jui.CJuiAutoComplete', [
                        'name'          => 'warmup-plan',
                        'value'         => !empty($server->warmup_plan_id) ? $server->warmupPlan->name : null,
                        'source'        => createUrl('delivery_server_warmup_plans/autocomplete'),
                        'cssFile'       => false,
                        'options'       => [
                            'minLength' => '2',
                            'select'    => 'js:function(event, ui) {
                        $("#' . CHtml::activeId($server, 'warmup_plan_id') . '").val(ui.item.warmup_plan_id);
                        $("#warmup-plan").trigger("autocomplete:select", [ui]);
                    }',
                            'search'    => 'js:function(event, ui) {
                        $("#' . CHtml::activeId($server, 'warmup_plan_id') . '").val("");
                    }',
                            'change'    => 'js:function(event, ui) {
                        if (!ui.item) {
                            $("#' . CHtml::activeId($server, 'warmup_plan_id') . '").val("");
                        }
                        $("#warmup-plan").trigger("autocomplete:change", [ui]);
                    }',
                        ],
                        'htmlOptions'   => $server->fieldDecorator->getHtmlOptions('warmup_plan_id'),
                    ]);
                    ?>
                    <?php echo $form->error($server, 'warmup_plan_id'); ?>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="warmup-plan-schedules-wrapper" data-url="<?php echo $server->getIsNewRecord() ? '' : createUrl('delivery_servers/warmup_plan_schedules', ['id' => $server->server_id]); ?>"></div>
            </div>
        </div>
    </div>
</div>
