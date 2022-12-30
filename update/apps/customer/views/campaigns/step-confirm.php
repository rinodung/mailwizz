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

/** @var string $jqCronLanguage */
$jqCronLanguage = (string)$controller->getData('jqCronLanguage');

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
    if ($campaign->hasErrors()) { ?>
    <div class="alert alert-block alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        <?php echo CHtml::errorSummary($campaign); ?>
    </div>
    <?php
    }

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
                    <h3 class="box-title">
                        <?php echo IconHelper::make('envelope') . $pageHeading; ?>
                    </h3>
                </div>
                <div class="pull-right">
                    <?php echo CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['campaigns/index'], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]); ?>
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
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'send_at'); ?>
                            <?php echo $form->hiddenField($campaign, 'send_at', $campaign->fieldDecorator->getHtmlOptions('send_at')); ?>
                            <?php echo $form->textField($campaign, 'sendAt', $campaign->fieldDecorator->getHtmlOptions('send_at', [
                                    'data-keyup' => t('campaigns', 'Please use the date/time picker to set the value, do not enter it manually!'),
                            ])); ?>
                            <?php echo CHtml::textField('fake_send_at', $campaign->dateTimeFormatter->formatDateTime($campaign->send_at), [
                                'data-date-format'  => 'yyyy-mm-dd hh:ii:ss',
                                'data-autoclose'    => true,
                                'data-language'     => LanguageHelper::getAppLanguageCode(),
                                'data-syncurl'      => createUrl('campaigns/sync_datetime'),
                                'class'             => 'form-control',
                                'style'             => 'visibility:hidden; height:1px; margin:0; padding:0;',
                            ]); ?>
                            <?php echo $form->error($campaign, 'send_at'); ?>
                        </div>
                    </div>

                    <?php if ($campaign->customer->getGroupOption('campaigns.can_use_recurring_campaigns', 'yes') == Campaign::TEXT_YES && $campaign->getIsRegular()) { ?>
                    <div class="col-lg-6 jqcron-holder">
                        <?php echo $form->checkbox($campaign->option, 'cronjob_enabled', $campaign->option->fieldDecorator->getHtmlOptions('cronjob_enabled', ['uncheckValue' => 0, 'class' => 'btn btn-primary btn-flat', 'style' => 'padding-top:3px'])); ?>&nbsp;<?php echo $form->labelEx($campaign->option, 'cronjob'); ?>
                        <div class="col-lg-12 jqcron-wrapper">
                            <?php echo $form->hiddenField($campaign->option, 'cronjob', $campaign->option->fieldDecorator->getHtmlOptions('cronjob', ['data-lang' => $jqCronLanguage])); ?>
                        </div>
                        <?php echo $form->error($campaign->option, 'cronjob'); ?>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign->option, 'cronjob_max_runs'); ?>
                            <?php echo $form->numberField($campaign->option, 'cronjob_max_runs', $campaign->option->fieldDecorator->getHtmlOptions('cronjob_max_runs', ['min' => -1, 'max' => 10000000, 'step' => 1])); ?>
                            <?php echo $form->error($campaign->option, 'cronjob_max_runs'); ?>
                        </div>
                    </div>
                    <?php } ?>

                </div>

	            <?php if ($campaign->customer->getGroupOption('campaigns.can_use_timewarp', 'no') == Campaign::TEXT_YES && $campaign->getIsRegular()) { ?>
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
			                    <?php echo $form->labelEx($campaign->option, 'timewarp_enabled'); ?>
			                    <?php echo $form->dropDownList($campaign->option, 'timewarp_enabled', $campaign->option->getYesNoOptions(), $campaign->option->fieldDecorator->getHtmlOptions('timewarp_enabled', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
			                    <?php echo $form->error($campaign->option, 'timewarp_enabled'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
			                    <?php echo $form->labelEx($campaign->option, 'timewarp_hour'); ?>
			                    <?php echo $form->dropDownList($campaign->option, 'timewarp_hour', $campaign->option->getTimewarpHours(), $campaign->option->fieldDecorator->getHtmlOptions('timewarp_hour', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
			                    <?php echo $form->error($campaign->option, 'timewarp_hour'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
			                    <?php echo $form->labelEx($campaign->option, 'timewarp_minute'); ?>
			                    <?php echo $form->dropDownList($campaign->option, 'timewarp_minute', $campaign->option->getTimewarpMinutes(), $campaign->option->fieldDecorator->getHtmlOptions('timewarp_minute', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
			                    <?php echo $form->error($campaign->option, 'timewarp_minute'); ?>
                            </div>
                        </div>
                    </div>
	            <?php } ?>

                <div class="row">
	                <?php if ($campaign->getIsAutoresponder()) { ?>
                        <div class="col-lg-4">
                            <div class="form-group">
				                <?php echo $form->labelEx($campaign->option, 'autoresponder_event'); ?>
				                <?php echo $form->dropDownList($campaign->option, 'autoresponder_event', $campaign->option->getAutoresponderEvents(), $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_event')); ?>
				                <?php echo $form->error($campaign->option, 'autoresponder_event'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
				                <?php echo $form->labelEx($campaign->option, 'autoresponder_time_value'); ?>
				                <?php echo $form->numberField($campaign->option, 'autoresponder_time_value', $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_time_value')); ?>
				                <?php echo $form->error($campaign->option, 'autoresponder_time_value'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
				                <?php echo $form->labelEx($campaign->option, 'autoresponder_time_unit'); ?>
				                <?php echo $form->dropDownList($campaign->option, 'autoresponder_time_unit', $campaign->option->getAutoresponderTimeUnits(), $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_time_unit')); ?>
				                <?php echo $form->error($campaign->option, 'autoresponder_time_unit'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
				                <?php echo $form->labelEx($campaign->option, 'autoresponder_time_min_hour'); ?>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="row">
                                            <div class="col-lg-6">
								                <?php echo $form->dropDownList($campaign->option, 'autoresponder_time_min_hour', CMap::mergeArray(['' => t('app', 'Hour')], $campaign->option->getAutoresponderTimeMinHoursList()), $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_time_min_hour')); ?>
                                            </div>
                                            <div class="col-lg-6">
								                <?php echo $form->dropDownList($campaign->option, 'autoresponder_time_min_minute', CMap::mergeArray(['' => t('app', 'Minute')], $campaign->option->getAutoresponderTimeMinMinutesList()), $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_time_min_minute')); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
				                <?php echo $form->error($campaign->option, 'autoresponder_time_min_hour'); ?>
				                <?php echo $form->error($campaign->option, 'autoresponder_time_min_minute'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
				                <?php echo $form->labelEx($campaign->option, 'autoresponder_include_imported'); ?>
				                <?php echo $form->dropDownList($campaign->option, 'autoresponder_include_imported', $campaign->option->getYesNoOptions(), $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_include_imported')); ?>
				                <?php echo $form->error($campaign->option, 'autoresponder_include_imported'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
				                <?php echo $form->labelEx($campaign->option, 'autoresponder_include_current'); ?>
				                <?php echo $form->dropDownList($campaign->option, 'autoresponder_include_current', $campaign->option->getYesNoOptions(), $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_include_current')); ?>
				                <?php echo $form->error($campaign->option, 'autoresponder_include_current'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4 autoresponder-open-campaign-id-wrapper" style="display: <?php echo !empty($campaign->option->autoresponder_open_campaign_id) || $campaign->option->autoresponder_event == CampaignOption::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_OPEN ? 'block' : 'none'; ?>;">
                            <div class="form-group">
				                <?php echo $form->labelEx($campaign->option, 'autoresponder_open_campaign_id'); ?>
				                <?php echo $form->dropDownList($campaign->option, 'autoresponder_open_campaign_id', CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getRelatedCampaignsAsOptions()), $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_open_campaign_id')); ?>
				                <?php echo $form->error($campaign->option, 'autoresponder_open_campaign_id'); ?>
                            </div>
                        </div>
                        <div class="col-lg-4 autoresponder-sent-campaign-id-wrapper" style="display: <?php echo !empty($campaign->option->autoresponder_sent_campaign_id) || $campaign->option->autoresponder_event == CampaignOption::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_SENT ? 'block' : 'none'; ?>;">
                            <div class="form-group">
				                <?php echo $form->labelEx($campaign->option, 'autoresponder_sent_campaign_id'); ?>
				                <?php echo $form->dropDownList($campaign->option, 'autoresponder_sent_campaign_id', CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getRelatedCampaignsAsOptions()), $campaign->option->fieldDecorator->getHtmlOptions('autoresponder_sent_campaign_id')); ?>
				                <?php echo $form->error($campaign->option, 'autoresponder_sent_campaign_id'); ?>
                            </div>
                        </div>
	                <?php } ?>
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
                <hr />
                <div class="table-responsive">
                    <?php
                    $controller->widget('zii.widgets.CDetailView', [
                        'data'          => $campaign,
                        'cssFile'       => false,
                        'htmlOptions'   => ['class' => 'table table-striped table-bordered table-hover table-condensed'],
                        'attributes'    => [
                            'name',
                            [
                                'label' => t('campaigns', 'List/Segment'),
                                'value' => $campaign->getListSegmentName(),
                            ],
                            [
                                'label'   => t('campaigns', 'Suppression list(s)'),
                                'value'   => $campaign->getSuppressionListsName(),
                                'visible' => $campaign->getSuppressionListsName(),
                            ],
                            'from_name', 'reply_to', 'to_name', 'subject',
                            [
                                'label'   => t('campaigns', 'Delivery servers'),
                                'value'   => $campaign->getDeliveryServersNames(),
                                'visible' => $campaign->getDeliveryServersNames(),
                            ],
                            [
                                'label' => $campaign->getAttributeLabel('date_added'),
                                'value' => $campaign->dateAdded,
                            ],
                            [
                                'label' => $campaign->getAttributeLabel('last_updated'),
                                'value' => $campaign->dateTimeFormatter->getLastUpdated(),
                            ],
                            [
                                'label' => t('campaigns', 'Estimated recipients count'),
                                'value' => CHtml::tag('div', [
                                    'id'       => 'campaign-estimate-recipients-count',
                                    'data-url' => createUrl('campaigns/estimate_recipients_count', ['campaign_uid' => $campaign->campaign_uid]),
                                    'data-fail'=> t('app', 'Unable to complete the request'),
                                ], IconHelper::make('fa-spinner fa-spin') . ' ' . t('app', 'Please wait...')),
                                'type'  => 'raw',
                            ],
                        ],
                    ]); ?>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-footer">
                <div class="pull-left">
                    <div class="wizard">
                        <ul class="steps">
                            <li class="complete"><a href="<?php echo createAbsoluteUrl('campaigns/update', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Details'); ?></a><span class="chevron"></span></li>
                            <li class="complete"><a href="<?php echo createAbsoluteUrl('campaigns/setup', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Setup'); ?></a><span class="chevron"></span></li>
                            <li class="complete"><a href="<?php echo createAbsoluteUrl('campaigns/template', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Template'); ?></a><span class="chevron"></span></li>
                            <li class="active"><a href="<?php echo createAbsoluteUrl('campaigns/confirm', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Confirmation'); ?></a><span class="chevron"></span></li>
                            <li><a href="javascript:;"><?php echo t('app', 'Done'); ?></a><span class="chevron"></span></li>
                        </ul>
                    </div>
                </div>
                <div class="pull-right">
                    <div class="actions">
                        <div class="btn-group dropup buttons-save-changes-and-action">
                            <button type="button" class="btn btn-primary btn-flat dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				                <?php echo IconHelper::make('save') . t('app', 'Save changes and...'); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <button type="submit" id="is_next" name="is_next" value="1" class="btn btn-primary btn-flat btn-go-next">
						                <?php echo $campaign->getIsAutoresponder() ? t('campaigns', 'Save and activate') : t('campaigns', 'Send campaign'); ?>
                                    </button>
                                </li>
                                <li><button type="submit" class="btn btn-primary btn-flat" name="stay-on-page" value="1"><?php echo t('campaigns', 'Stay on page'); ?></button></li>
                            </ul>
                        </div>
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
