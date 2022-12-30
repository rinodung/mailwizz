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

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Lists $list */
$list = $controller->getData('list');

/** @var ListSegment $segment */
$segment = $controller->getData('segment');

/** @var ListSegmentCondition $condition */
$condition = $controller->getData('condition');

/** @var ListSegmentCondition[] $conditions */
$conditions = (array)$controller->getData('conditions');

/** @var array $conditionValueTags */
$conditionValueTags = (array)$controller->getData('conditionValueTags');

/** @var ListSegmentCampaignCondition $campaignCondition */
$campaignCondition = $controller->getData('campaignCondition');

/** @var ListSegmentCampaignCondition[] $campaignConditions */
$campaignConditions = (array)$controller->getData('campaignConditions');

/** @var bool $canExport */
$canExport = (bool)$controller->getData('canExport');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('before_view_file_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) { ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <?php $controller->widget('customer.components.web.widgets.MailListSubNavWidget', [
                    'list' => $list,
                ]); ?>
            </div>
        </div>
        <div class="box-body">
            <?php
            /**
             * This hook gives a chance to prepend content before the active form or to replace the default active form entirely.
             * Please note that from inside the action callback you can access all the controller view variables
             * via {@CAttributeCollection $collection->controller->getData()}
             * In case the form is replaced, make sure to set {@CAttributeCollection $collection->add('renderForm', false)}
             * in order to stop rendering the default content.
             * @since 1.3.3.1
             */
            hooks()->doAction('before_active_form', $collection = new CAttributeCollection([
                'controller'    => $controller,
                'renderForm'    => true,
            ]));

            // and render if allowed
            if ($collection->itemAt('renderForm')) {
                /** @var CActiveForm $form */
                $form = $controller->beginWidget('CActiveForm'); ?>
                <div class="box box-primary borderless">
                    <div class="box-header">
                        <div class="pull-left">
                            <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                                ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-cog') . html_encode((string)$pageHeading) . '</h3>')
                                ->render(); ?>
                        </div>
                        <div class="pull-right">
                            <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                                ->addIf(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['list_segments/create', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$segment->getIsNewRecord())
                                ->add(CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['list_segments/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
                                ->addIf(CHtml::link(IconHelper::make('export') . t('list_export', 'Export segment'), ['list_segments_export/index', 'list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid], ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('list_export', 'Export segment')]), !$segment->getIsNewRecord() && !empty($canExport))
                                ->render(); ?>
                        </div>
                        <div class="clearfix"><!-- --></div>
                    </div>
                    <div class="box-body">
                        <?php
                        /**
                         * This hook gives a chance to prepend content before the active form fields.
                         * Please note that from inside the action callback you can access all the controller view variables
                         * via {@CAttributeCollection $collection->controller->getData()}
                         * @since 1.3.3.1
                         */
                        hooks()->doAction('before_active_form_fields', new CAttributeCollection([
                            'controller'    => $controller,
                            'form'          => $form,
                        ])); ?>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($segment, 'name'); ?>
                                    <?php echo $form->textField($segment, 'name', $segment->fieldDecorator->getHtmlOptions('name')); ?>
                                    <?php echo $form->error($segment, 'name'); ?>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <?php echo $form->labelEx($segment, 'operator_match'); ?>
                                    <?php echo $form->dropDownList($segment, 'operator_match', $segment->getOperatorMatchArray(), $segment->fieldDecorator->getHtmlOptions('operator_match')); ?>
                                    <?php echo $form->error($segment, 'operator_match'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="conditions-container">
                                <div class="col-lg-12">
                                    <h5>
                                        <div class="pull-left">
                                            <?php echo t('list_segments', 'Conditions that apply to your list custom fields:'); ?>
                                        </div>
                                        <div class="pull-right">
                                            <a href="javascript:;" class="btn btn-primary btn-flat btn-add-condition"><?php echo IconHelper::make('create'); ?></a>
                                            <a href="#conditions-value-tags" data-toggle="modal" class="btn btn-primary btn-flat"><?php echo IconHelper::make('info'); ?></a>
                                        </div>
                                        <div class="clearfix"><!-- --></div>
                                    </h5>
                                    <hr />
                                </div>
                                <?php if (!empty($conditions)) {
                            foreach ($conditions as $index => $cond) {?>
                                        <div class="item">
                                            <hr />
                                            <div class="col-lg-3">
                                                <?php echo CHtml::activeLabelEx($cond, 'field_id'); ?>
                                                <?php echo CHtml::dropDownList($cond->getModelName() . '[' . $index . '][field_id]', $cond->field_id, $segment->getFieldsDropDownArray(), $cond->fieldDecorator->getHtmlOptions('field_id')); ?>
                                                <?php echo CHtml::error($cond, 'field_id'); ?>
                                            </div>
                                            <div class="col-lg-3">
                                                <?php echo CHtml::activeLabelEx($cond, 'operator_id'); ?>
                                                <?php echo CHtml::dropDownList($cond->getModelName() . '[' . $index . '][operator_id]', $cond->operator_id, $cond->getOperatorsDropDownArray(), $cond->fieldDecorator->getHtmlOptions('operator_id')); ?>
                                                <?php echo CHtml::error($cond, 'operator_id'); ?>
                                            </div>
                                            <div class="col-lg-3">
                                                <?php echo CHtml::activeLabelEx($cond, 'value'); ?>
                                                <?php echo CHtml::textField($cond->getModelName() . '[' . $index . '][value]', $cond->value, $cond->fieldDecorator->getHtmlOptions('value')); ?>
                                                <?php echo CHtml::error($cond, 'value'); ?>
                                            </div>
                                            <div class="col-lg-3">
                                                <label><?php echo t('app', 'Action'); ?></label><br />
                                                <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-condition"><?php echo IconHelper::make('delete'); ?></a>
                                            </div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                    <?php }
                        } ?>
                            </div>
                        </div>
                        <hr />
                        <div class="row">
                            <div class="campaigns-conditions-container">
                                <div class="col-lg-12">
                                    <h5>
                                        <div class="pull-left">
                                            <?php echo t('list_segments', 'Conditions that apply to the campaigns sent to the list this segment belongs to:'); ?>
                                        </div>
                                        <div class="pull-right">
                                            <a href="javascript:;" class="btn btn-primary btn-flat btn-add-campaign-condition"><?php echo IconHelper::make('create'); ?></a>
                                        </div>
                                        <div class="clearfix"><!-- --></div>
                                    </h5>
                                </div>
                                <?php if (!empty($campaignConditions)) {
                            foreach ($campaignConditions as $index => $cond) {?>
                                        <div class="item">
                                            <div class="col-lg-3">
                                                <?php echo CHtml::activeLabelEx($cond, 'action'); ?>
                                                <?php echo CHtml::dropDownList($cond->getModelName() . '[' . $index . '][action]', $cond->action, $cond->getActionsList(), $cond->fieldDecorator->getHtmlOptions('action')); ?>
                                                <?php echo CHtml::error($cond, 'action'); ?>
                                            </div>
                                            <div class="col-lg-3">
                                                <?php echo CHtml::activeLabelEx($cond, 'campaign_id'); ?>
                                                <?php echo CHtml::dropDownList($cond->getModelName() . '[' . $index . '][campaign_id]', $cond->campaign_id, $cond->getCampaignsList((int)$list->list_id), $cond->fieldDecorator->getHtmlOptions('campaign_id')); ?>
                                                <?php echo CHtml::error($cond, 'campaign_id'); ?>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="row">
                                                    <div class="col-lg-4">
                                                        <?php echo CHtml::activeLabelEx($cond, 'time_comparison_operator'); ?>
                                                        <?php echo CHtml::dropDownList($cond->getModelName() . '[' . $index . '][time_comparison_operator]', $cond->time_comparison_operator, $cond->getTimeComparisonOperatorsList(), $cond->fieldDecorator->getHtmlOptions('time_comparison_operator')); ?>
                                                        <?php echo CHtml::error($cond, 'time_comparison_operator'); ?>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <?php echo CHtml::activeLabelEx($cond, 'time_value'); ?>
                                                        <?php echo CHtml::textField($cond->getModelName() . '[' . $index . '][time_value]', $cond->time_value, $cond->fieldDecorator->getHtmlOptions('time_value')); ?>
                                                        <?php echo CHtml::error($cond, 'time_value'); ?>
                                                    </div>
                                                    <div class="col-lg-4">
                                                        <?php echo CHtml::activeLabelEx($cond, 'time_unit'); ?>
                                                        <?php echo CHtml::dropDownList($cond->getModelName() . '[' . $index . '][time_unit]', $cond->time_unit, $cond->getTimeUnitsList(), $cond->fieldDecorator->getHtmlOptions('time_unit')); ?>
                                                        <?php echo CHtml::error($cond, 'time_unit'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-1">
                                                <label><?php echo t('app', 'Action'); ?></label><br />
                                                <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-campaign-condition"><?php echo IconHelper::make('delete'); ?></a>
                                            </div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                    <?php }
                        } ?>
                            </div>
                        </div>
                        <hr />
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="subscribers-wrapper" style="display: none;">
                                    <h5><?php echo t('list_segments', 'Subscribers matching your segment:'); ?></h5>
                                    <hr />
                                    <div id="subscribers-wrapper"></div>
                                </div>
                            </div>
                        </div>
                        <?php
                        /**
                         * This hook gives a chance to append content after the active form fields.
                         * Please note that from inside the action callback you can access all the controller view variables
                         * via {@CAttributeCollection $collection->controller->getData()}
                         * @since 1.3.3.1
                         */
                        hooks()->doAction('after_active_form_fields', new CAttributeCollection([
                            'controller'    => $controller,
                            'form'          => $form,
                        ])); ?>
                        <div class="clearfix"><!-- --></div>
                    </div>
                    <div class="box-footer">
                        <div class="pull-right">
                            <?php if (!$segment->getIsNewRecord() && (!empty($conditions) || !empty($campaignConditions))) { ?>
                                <a href="<?php echo createUrl('list_segments/subscribers', ['list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]); ?>" class="btn btn-primary btn-flat btn-show-segment-subscribers"><?php echo IconHelper::make('view') . t('app', 'Show matching subscribers'); ?></a>
                            <?php } ?>
                            <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . t('app', 'Save changes'); ?></button>
                        </div>
                        <div class="clearfix"><!-- --></div>
                    </div>
                </div>
                <?php
                $controller->endWidget();
            }
            /**
             * This hook gives a chance to append content after the active form fields.
             * Please note that from inside the action callback you can access all the controller view variables
             * via {@CAttributeCollection $collection->controller->getData()}
             * @since 1.3.3.1
             */
            hooks()->doAction('after_active_form', new CAttributeCollection([
                'controller'      => $controller,
                'renderedForm'    => $collection->itemAt('renderForm'),
            ]));
            ?>
            <div id="condition-template" style="display: none;">
                <div class="item">
                    <hr />
                    <div class="col-lg-3">
                        <?php echo CHtml::activeLabelEx($condition, 'field_id'); ?>
                        <?php echo CHtml::dropDownList($condition->getModelName() . '[{index}][field_id]', $condition->field_id, $segment->getFieldsDropDownArray(), $condition->fieldDecorator->getHtmlOptions('field_id')); ?>
                        <?php echo CHtml::error($condition, 'field_id'); ?>
                    </div>
                    <div class="col-lg-3">
                        <?php echo CHtml::activeLabelEx($condition, 'operator_id'); ?>
                        <?php echo CHtml::dropDownList($condition->getModelName() . '[{index}][operator_id]', $condition->operator_id, $condition->getOperatorsDropDownArray(), $condition->fieldDecorator->getHtmlOptions('operator_id')); ?>
                        <?php echo CHtml::error($condition, 'operator_id'); ?>
                    </div>
                    <div class="col-lg-3">
                        <?php echo CHtml::activeLabelEx($condition, 'value'); ?>
                        <?php echo CHtml::textField($condition->getModelName() . '[{index}][value]', $condition->value, $condition->fieldDecorator->getHtmlOptions('value')); ?>
                        <?php echo CHtml::error($condition, 'value'); ?>
                    </div>
                    <div class="col-lg-3">
                        <label><?php echo t('app', 'Action'); ?></label><br />
                        <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-condition"><?php echo IconHelper::make('delete'); ?></a>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
            </div>

            <div id="campaign-condition-template" style="display: none;">
                <div class="item">
                    <div class="col-lg-3">
                        <?php echo CHtml::activeLabelEx($campaignCondition, 'action'); ?>
                        <?php echo CHtml::dropDownList($campaignCondition->getModelName() . '[{index}][action]', $campaignCondition->action, $campaignCondition->getActionsList(), $campaignCondition->fieldDecorator->getHtmlOptions('action')); ?>
                        <?php echo CHtml::error($campaignCondition, 'action'); ?>
                    </div>
                    <div class="col-lg-3">
                        <?php echo CHtml::activeLabelEx($campaignCondition, 'campaign_id'); ?>
                        <?php echo CHtml::dropDownList($campaignCondition->getModelName() . '[{index}][campaign_id]', $campaignCondition->campaign_id, $campaignCondition->getCampaignsList((int)$list->list_id), $campaignCondition->fieldDecorator->getHtmlOptions('campaign_id')); ?>
                        <?php echo CHtml::error($campaignCondition, 'campaign_id'); ?>
                    </div>
                    <div class="col-lg-5">
                        <div class="row">
                            <div class="col-lg-4">
                                <?php echo CHtml::activeLabelEx($campaignCondition, 'time_comparison_operator'); ?>
                                <?php echo CHtml::dropDownList($campaignCondition->getModelName() . '[{index}][time_comparison_operator]', $campaignCondition->time_comparison_operator, $campaignCondition->getTimeComparisonOperatorsList(), $campaignCondition->fieldDecorator->getHtmlOptions('time_comparison_operator')); ?>
                                <?php echo CHtml::error($campaignCondition, 'time_comparison_operator'); ?>
                            </div>
                            <div class="col-lg-4">
                                <?php echo CHtml::activeLabelEx($campaignCondition, 'time_value'); ?>
                                <?php echo CHtml::textField($campaignCondition->getModelName() . '[{index}][time_value]', $campaignCondition->time_value, $campaignCondition->fieldDecorator->getHtmlOptions('time_value')); ?>
                                <?php echo CHtml::error($campaignCondition, 'time_value'); ?>
                            </div>
                            <div class="col-lg-4">
                                <?php echo CHtml::activeLabelEx($campaignCondition, 'time_unit'); ?>
                                <?php echo CHtml::dropDownList($campaignCondition->getModelName() . '[{index}][time_unit]', $campaignCondition->time_unit, $campaignCondition->getTimeUnitsList(), $campaignCondition->fieldDecorator->getHtmlOptions('time_unit')); ?>
                                <?php echo CHtml::error($campaignCondition, 'time_unit'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <label><?php echo t('app', 'Action'); ?></label><br />
                        <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-campaign-condition"><?php echo IconHelper::make('delete'); ?></a>
                    </div>
                    <div class="clearfix"><!-- --></div>
                </div>
            </div>

            <div class="modal fade" id="conditions-value-tags" tabindex="-1" role="dialog" aria-labelledby="conditions-value-tags-label" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title"><?php echo t('list_segments', 'Available value tags'); ?></h4>
                        </div>
                        <div class="modal-body">
                            <div class="callout callout-info">
                                <?php echo t('list_segments', 'Following tags can be used as dynamic values. They will be replaced as shown below.'); ?>
                            </div>
                            <table class="table table-bordered table-condensed">
                                <tr>
                                    <td><?php echo t('list_segments', 'Tag'); ?></td>
                                    <td><?php echo t('list_segments', 'Description'); ?></td>
                                </tr>
                                <?php foreach ($conditionValueTags as $tagInfo) { ?>
                                    <tr>
                                        <td><?php echo html_encode($tagInfo['tag']); ?></td>
                                        <td><?php echo html_encode($tagInfo['description']); ?></td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
