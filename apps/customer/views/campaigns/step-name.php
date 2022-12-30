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

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var array $listsArray */
$listsArray = (array)$controller->getData('listsArray');

/** @var array $segmentsArray */
$segmentsArray = (array)$controller->getData('segmentsArray');

/** @var array $groupsArray */
$groupsArray = (array)$controller->getData('groupsArray');

/** @var bool $canSegmentLists */
$canSegmentLists = (bool)$controller->getData('canSegmentLists');

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
if ($viewCollection->itemAt('renderContent')) {
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
                        ->add('<h3 class="box-title">' . IconHelper::make('envelope') . html_encode((string)$pageHeading) . '</h3>')
                        ->render(); ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(CHtml::link(IconHelper::make('create') . t('app', 'Create new'), ['campaigns/create'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Create new')]), !$campaign->getIsNewRecord())
                        ->add(CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['campaigns/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]))
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
                            <?php echo $form->labelEx($campaign, 'name'); ?>
                            <?php echo $form->textField($campaign, 'name', $campaign->fieldDecorator->getHtmlOptions('name')); ?>
                            <?php echo $form->error($campaign, 'name'); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'type'); ?>
                            <?php echo $form->dropDownList($campaign, 'type', $campaign->getTypesList(), $campaign->fieldDecorator->getHtmlOptions('type', [
                                'disabled'  => $campaign->getIsPaused(),
                                'class'     => 'form-control select2',
                                'style'     => 'width: 100%',
                            ])); ?>
                            <?php echo $form->error($campaign, 'type'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'list_id'); ?>
                            <?php echo $form->dropDownList($campaign, 'list_id', $listsArray, $campaign->fieldDecorator->getHtmlOptions('list_id', [
                                'disabled'  => $campaign->getIsPaused(),
                                'class'     => 'form-control select2',
                                'style'     => 'width: 100%',
                            ])); ?>
                            <?php echo $form->error($campaign, 'list_id'); ?>
                        </div>
                    </div>
                    <?php if (!empty($canSegmentLists)) { ?>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'segment_id'); ?>
                            <?php echo $form->dropDownList($campaign, 'segment_id', $segmentsArray, $campaign->fieldDecorator->getHtmlOptions('segment_id', [
                                'disabled'  => $campaign->getIsPaused() || (empty($campaign->segment_id) && empty($campaign->list_id)),
                                'data-url'  => createUrl('campaigns/list_segments'),
                                'class'     => 'form-control select2',
                                'style'     => 'width: 100%',
                            ])); ?>
                            <?php echo $form->error($campaign, 'segment_id'); ?>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
			                <?php echo $form->labelEx($campaign, 'group_id'); ?>
			                <?php echo $form->dropDownList($campaign, 'group_id', $groupsArray, $campaign->fieldDecorator->getHtmlOptions('group_id', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
			                <?php echo $form->error($campaign, 'group_id'); ?>
                        </div>
                    </div>
                    <div class="col-lg-6">
	                    <?php echo $form->labelEx($campaign, 'send_group_id'); ?>
	                    <?php echo $form->hiddenField($campaign, 'send_group_id', $campaign->fieldDecorator->getHtmlOptions('send_group_id')); ?>
                        <div class="input-group">
	                        <?php
                            $controller->widget('zii.widgets.jui.CJuiAutoComplete', [
                                'name'          => 'send_group_id_autocomplete',
                                'id'            => 'send_group_id_autocomplete',
                                'value'         => !empty($campaign->sendGroup) ? $campaign->sendGroup->name : null,
                                'source'        => createUrl('campaign_send_groups/autocomplete'),
                                'cssFile'       => false,
                                'options'       => [
                                    'minLength' => '2',
                                    'select'    => 'js:function(event, ui) {
                                        $("#' . CHtml::activeId($campaign, 'send_group_id') . '").val(ui.item.group_id);
                                        $(".btn-save-send-group-autocomplete").attr("disabled", true);
                                    }',
                                    'search'    => 'js:function(event, ui) {
                                        $("#' . CHtml::activeId($campaign, 'send_group_id') . '").val("");
                                    }',
                                    'change'    => 'js:function(event, ui) {
                                        if (!ui.item) {
                                            $("#' . CHtml::activeId($campaign, 'send_group_id') . '").val("");
                                        }
                                    }',
                                ],
                                'htmlOptions'   => $campaign->fieldDecorator->getHtmlOptions('send_group_id'),
                            ]); ?>
                            <span class="input-group-btn">
                                <button class="btn btn-primary btn-flat btn-save-send-group-autocomplete" disabled type="button" data-action="<?php echo createUrl('campaign_send_groups/quick_create'); ?>"><?php echo IconHelper::make('save'); ?></button>
                            </span>
                        </div>
                        <div class="clearfix"><!-- --></div>
	                    <?php echo $form->error($campaign, 'send_group_id'); ?>
                    </div>
                </div>
                <?php
                /**
                 * This hook gives a chance to append content after the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables
                 * via {@CAttributeCollection $collection->controller->getData()}
                 *
                 * @since 1.3.3.1
                 */
                hooks()->doAction('after_active_form_fields', new CAttributeCollection([
                    'controller'    => $controller,
                    'campaign'      => $campaign,
                    'form'          => $form,
                ])); ?>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-footer">
                <div class="wizard">
                    <?php if ($campaign->getIsNewRecord()) { ?>
                    <ul class="steps">
                        <li class="active"><?php echo t('campaigns', 'Details'); ?><span class="chevron"></span></li>
                        <li><?php echo t('campaigns', 'Setup'); ?><span class="chevron"></span></li>
                        <li><?php echo t('campaigns', 'Template'); ?><span class="chevron"></span></li>
                        <li><?php echo t('campaigns', 'Confirmation'); ?><span class="chevron"></span></li>
                    </ul>
                    <?php } else { ?>
                    <ul class="steps">
                        <li class="active"><a href="<?php echo createAbsoluteUrl('campaigns/update', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Details'); ?></a><span class="chevron"></span></li>
                        <li><a href="<?php echo createAbsoluteUrl('campaigns/setup', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Setup'); ?></a><span class="chevron"></span></li>
                        <li><a href="<?php echo createAbsoluteUrl('campaigns/template', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Template'); ?></a><span class="chevron"></span></li>
                        <li><a href="<?php echo createAbsoluteUrl('campaigns/confirm', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Confirmation'); ?></a><span class="chevron"></span></li>
                        <li><a href="javascript:;"><?php echo t('app', 'Done'); ?></a><span class="chevron"></span></li>
                    </ul>
                    <?php } ?>
                    <div class="actions">
                        <button type="submit" id="is_next" name="is_next" value="1" class="btn btn-primary btn-flat btn-go-next"><?php echo IconHelper::make('next') . '&nbsp;' . t('campaigns', 'Save and next'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $controller->endWidget();
    }
    /**
     * This hook gives a chance to append content after the active form.
     * Please note that from inside the action callback you can access all the controller view variables
     * via {@CAttributeCollection $collection->controller->getData()}
     * @since 1.3.3.1
     */
    hooks()->doAction('after_active_form', new CAttributeCollection([
        'controller'      => $controller,
        'renderedForm'    => $collection->itemAt('renderForm'),
    ]));
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
?>
