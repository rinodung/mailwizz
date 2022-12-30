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

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var CampaignAbtest $abTest */
$abTest = $controller->getData('abTest');

/** @var CampaignAbtestSubject $abTestSubject */
$abTestSubject = $controller->getData('abTestSubject');

/** @var CampaignAbtestSubject[] $abTestSubjects */
$abTestSubjects = $controller->getData('abTestSubjects');

/** @var bool $canSelectDeliveryServers */
$canSelectDeliveryServers = (bool)$controller->getData('canSelectDeliveryServers');

/** @var CampaignToDeliveryServer $campaignToDeliveryServers */
$campaignToDeliveryServers = $controller->getData('campaignToDeliveryServers');

/** @var DeliveryServer[] $deliveryServers */
$deliveryServers = $controller->getData('deliveryServers');

/** @var array $campaignDeliveryServersArray */
$campaignDeliveryServersArray = (array)$controller->getData('campaignDeliveryServersArray');

/** @var bool $canAddAttachments */
$canAddAttachments = (bool)$controller->getData('canAddAttachments');

/** @var CampaignAttachment $attachment */
$attachment = $controller->getData('attachment');

/** @var bool $canShowOpenActions */
$canShowOpenActions = (bool)$controller->getData('canShowOpenActions');

/** @var CampaignOpenActionSubscriber $openAction */
$openAction = $controller->getData('openAction');

/** @var CampaignOpenActionSubscriber[] $openActions */
$openActions = (array)$controller->getData('openActions');

/** @var array $openAllowedActions */
$openAllowedActions = (array)$controller->getData('openAllowedActions');

/** @var array $openActionLists */
$openActionLists = (array)$controller->getData('openActionLists');

/** @var bool $canShowSentActions */
$canShowSentActions = (bool)$controller->getData('canShowSentActions');

/** @var CampaignSentActionSubscriber $sentAction */
$sentAction = $controller->getData('sentAction');

/** @var CampaignSentActionSubscriber[] $sentActions */
$sentActions = (array)$controller->getData('sentActions');

/** @var array $sentAllowedActions */
$sentAllowedActions = (array)$controller->getData('sentAllowedActions');

/** @var array $sentActionLists */
$sentActionLists = (array)$controller->getData('sentActionLists');

/** @var bool $webhooksEnabled */
$webhooksEnabled = (bool)$controller->getData('webhooksEnabled');

/** @var CampaignTrackOpenWebhook $opensWebhook */
$opensWebhook = $controller->getData('opensWebhook');

/** @var CampaignTrackOpenWebhook[] $opensWebhooks */
$opensWebhooks = (array)$controller->getData('opensWebhooks');

/** @var CampaignOpenActionListField $openListFieldAction */
$openListFieldAction = $controller->getData('openListFieldAction');

/** @var CampaignOpenActionListField[] $openListFieldActions */
$openListFieldActions = (array)$controller->getData('openListFieldActions');

/** @var array $openListFieldActionOptions */
$openListFieldActionOptions = (array)$controller->getData('openListFieldActionOptions');

/** @var CampaignSentActionListField $sentListFieldAction */
$sentListFieldAction = $controller->getData('sentListFieldAction');

/** @var CampaignSentActionListField[] $sentListFieldActions */
$sentListFieldActions = (array)$controller->getData('sentListFieldActions');

/** @var array $sentListFieldActionOptions */
$sentListFieldActionOptions = (array)$controller->getData('sentListFieldActionOptions');

/** @var bool $canShowOpenListFieldActions */
$canShowOpenListFieldActions = (bool)$controller->getData('canShowOpenListFieldActions');

/** @var bool $canShowSentListFieldActions */
$canShowSentListFieldActions = (bool)$controller->getData('canShowSentListFieldActions');

/** @var bool $canSelectTrackingDomains */
$canSelectTrackingDomains = (bool)$controller->getData('canSelectTrackingDomains');

/** @var CampaignFilterOpenUnopen $openUnopenFilter */
$openUnopenFilter = $controller->getData('openUnopenFilter');

/** @var CustomerSuppressionListToCampaign $suppressionListToCampaign */
$suppressionListToCampaign = $controller->getData('suppressionListToCampaign');

/** @var bool $canSelectSuppressionLists */
$canSelectSuppressionLists = (bool)$controller->getData('canSelectSuppressionLists');

/** @var array $selectedSuppressionLists */
$selectedSuppressionLists = (array)$controller->getData('selectedSuppressionLists');

/** @var CustomerSuppressionList[] $allSuppressionLists */
$allSuppressionLists = (array)$controller->getData('allSuppressionLists');

/** @var CampaignExtraTag $extraTag */
$extraTag = $controller->getData('extraTag');

/** @var CampaignExtraTag[] $extraTags */
$extraTags = (array)$controller->getData('extraTags');

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
        $form = $controller->beginWidget('CActiveForm', [
            'htmlOptions' => [
                'enctype' => 'multipart/form-data',
            ],
        ]); ?>
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
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'from_name'); ?> [<a data-toggle="modal" href="#available-tags-modal"><?php echo t('campaigns', 'Available tags'); ?></a>]
                            <?php echo $form->textField($campaign, 'from_name', $campaign->fieldDecorator->getHtmlOptions('from_name')); ?>
                            <?php echo $form->error($campaign, 'from_name'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'from_email'); ?>
                            <?php echo $form->textField($campaign, 'from_email', $campaign->fieldDecorator->getHtmlOptions('from_email')); ?>
                            <?php echo $form->error($campaign, 'from_email'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'reply_to'); ?>
                            <?php echo $form->emailField($campaign, 'reply_to', $campaign->fieldDecorator->getHtmlOptions('reply_to')); ?>
                            <?php echo $form->error($campaign, 'reply_to'); ?>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'to_name'); ?> [<a data-toggle="modal" href="#available-tags-modal"><?php echo t('campaigns', 'Available tags'); ?></a>]
                            <?php echo $form->textField($campaign, 'to_name', $campaign->fieldDecorator->getHtmlOptions('to_name')); ?>
                            <?php echo $form->error($campaign, 'to_name'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($campaign, 'subject'); ?> [<a data-toggle="modal" href="#available-tags-modal"><?php echo t('campaigns', 'Available tags'); ?></a>] [<a href="javascript:;" id="toggle-emoji-list"><?php echo t('campaigns', 'Toggle emoji list'); ?></a>]
                            <?php echo $form->textField($campaign, 'subject', $campaign->fieldDecorator->getHtmlOptions('subject')); ?>
                            <?php echo $form->error($campaign, 'subject'); ?>
                        </div>
                        <div id="emoji-list">
                            <div id="emoji-list-wrapper">
                                <?php foreach (EmojiHelper::getList() as $emoji => $description) { ?>
                                <span title="<?php echo ucwords(strtolower((string)$description)); ?>"><?php echo $emoji; ?></span>
                                <?php } ?>
                            </div>
                            <div class="callout callout-info" style="margin-top:5px; margin-bottom: 0px;">
                                <?php echo t('campaigns', 'You can click on any emoji to enter it in the subject or scroll for more.'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
			            <?php
                        // since 1.9.17
                        hooks()->doAction('campaign_form_setup_step_after_campaign_subject', [
                            'controller' => $controller,
                            'campaign'   => $campaign,
                            'form'       => $form,
                        ]); ?>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-primary borderless" style="margin-bottom:0px;">
                            <div class="box-header">
                                <div class="pull-left">
                                    <h3 class="box-title">
                                        <?php echo IconHelper::make('glyphicon-cog') . t('campaigns', 'Campaign options'); ?>
                                    </h3>
                                </div>
                                <div class="pull-right"></div>
                                <div class="clearfix"><!-- --></div>
                            </div>
                            <div class="box-body">
                                <?php
                                // since 1.3.9.0
                                hooks()->doAction('campaign_form_setup_step_before_campaign_options', [
                                    'controller' => $controller,
                                    'campaign'   => $campaign,
                                    'form'       => $form,
                                ]); ?>
                                <div class="row">
                                    <div class="col-lg-2">
                                        <div class="form-group">
                                            <?php echo $form->labelEx($campaign->option, 'open_tracking'); ?>
                                            <?php echo $form->dropDownList($campaign->option, 'open_tracking', $campaign->option->getYesNoOptions(), $campaign->option->fieldDecorator->getHtmlOptions('open_tracking')); ?>
                                            <?php echo $form->error($campaign->option, 'open_tracking'); ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-2">
                                        <div class="form-group">
                                            <?php echo $form->labelEx($campaign->option, 'url_tracking'); ?>
                                            <?php echo $form->dropDownList($campaign->option, 'url_tracking', $campaign->option->getYesNoOptions(), $campaign->option->fieldDecorator->getHtmlOptions('url_tracking')); ?>
                                            <?php echo $form->error($campaign->option, 'url_tracking'); ?>
                                        </div>
                                    </div>
                                    <?php if ($campaign->customer->getGroupOption('campaigns.can_embed_images', 'no') == Campaign::TEXT_YES) { ?>
                                    <div class="col-lg-2">
                                        <div class="form-group">
                                            <?php echo $form->labelEx($campaign->option, 'embed_images'); ?>
                                            <?php echo $form->dropDownList($campaign->option, 'embed_images', $campaign->option->getYesNoOptions(), $campaign->option->fieldDecorator->getHtmlOptions('embed_images')); ?>
                                            <?php echo $form->error($campaign->option, 'embed_images'); ?>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="col-lg-2">
                                        <div class="form-group">
                                            <?php echo $form->labelEx($campaign->option, 'plain_text_email'); ?>
                                            <?php echo $form->dropDownList($campaign->option, 'plain_text_email', $campaign->option->getYesNoOptions(), $campaign->option->fieldDecorator->getHtmlOptions('plain_text_email')); ?>
                                            <?php echo $form->error($campaign->option, 'plain_text_email'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <?php if (!empty($canSelectTrackingDomains)) { ?>
                                        <div class="col-lg-2">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($campaign->option, 'tracking_domain_id'); ?>
                                                <?php echo $form->dropDownList($campaign->option, 'tracking_domain_id', $campaign->option->getTrackingDomainsArray(), $campaign->option->fieldDecorator->getHtmlOptions('tracking_domain_id')); ?>
                                                <?php echo $form->error($campaign->option, 'tracking_domain_id'); ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                    <?php if (!$campaign->getIsAutoresponder()) {?>
                                        <div class="col-lg-2">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($campaign->option, 'max_send_count'); ?>
                                                <?php echo $form->numberField($campaign->option, 'max_send_count', $campaign->option->fieldDecorator->getHtmlOptions('max_send_count')); ?>
                                                <?php echo $form->error($campaign->option, 'max_send_count'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($campaign->option, 'max_send_count_random'); ?>
                                                <?php echo $form->dropDownList($campaign->option, 'max_send_count_random', $campaign->option->getYesNoOptions(), $campaign->option->fieldDecorator->getHtmlOptions('max_send_count_random')); ?>
                                                <?php echo $form->error($campaign->option, 'max_send_count_random'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($campaign->option, 'email_stats'); ?>
                                                <?php echo $form->textField($campaign->option, 'email_stats', $campaign->option->fieldDecorator->getHtmlOptions('email_stats')); ?>
                                                <?php echo $form->error($campaign->option, 'email_stats'); ?>
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <div class="form-group">
                                                <?php echo $form->labelEx($campaign->option, 'email_stats_delay_days'); ?>
                                                <?php echo $form->numberField($campaign->option, 'email_stats_delay_days', $campaign->option->fieldDecorator->getHtmlOptions('email_stats_delay_days')); ?>
                                                <?php echo $form->error($campaign->option, 'email_stats_delay_days'); ?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <?php echo $form->labelEx($campaign->option, 'preheader'); ?>
                                            <?php echo $form->textField($campaign->option, 'preheader', $campaign->option->fieldDecorator->getHtmlOptions('preheader')); ?>
                                            <?php echo $form->error($campaign->option, 'preheader'); ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
			                                <?php echo $form->labelEx($campaign->option, 'forward_friend_subject'); ?>
			                                <?php echo $form->textField($campaign->option, 'forward_friend_subject', $campaign->option->fieldDecorator->getHtmlOptions('forward_friend_subject')); ?>
			                                <?php echo $form->error($campaign->option, 'forward_friend_subject'); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                // since 1.3.9.0
                                hooks()->doAction('campaign_form_setup_step_after_campaign_options', [
                                    'controller' => $controller,
                                    'campaign'   => $campaign,
                                    'form'       => $form,
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($campaign->getCanDoAbTest()) { ?>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div class="box box-primary borderless" style="margin-bottom:0px;">
                            <div class="box-header">
                                <div class="pull-left">
                                    <h3 class="box-title">
							            <?php echo IconHelper::make('fa-code-fork') . t('campaigns', 'A/B Test'); ?>
                                    </h3>
                                </div>
                                <div class="pull-right">
	                                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-abtest', ['class' => 'btn btn-primary btn-flat no-spin', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                </div>
                                <div class="clearfix"><!-- --></div>
                            </div>
                            <div class="box-body">
					            <?php
                                // since 2.0.29
                                hooks()->doAction('campaign_form_setup_step_before_campaign_abtest_options', [
                                    'controller' => $controller,
                                    'campaign'   => $campaign,
                                    'form'       => $form,
                                    'abTest'     => $abTest,
                                ]); ?>
                                <div class="row">
                                    <div class="col-lg-3">
                                        <div class="form-group">
	                                        <?php echo $form->labelEx($abTest, 'enabled'); ?>
	                                        <?php echo $form->dropDownList($abTest, 'enabled', $abTest->getYesNoOptions(), $abTest->fieldDecorator->getHtmlOptions('enabled')); ?>
	                                        <?php echo $form->error($abTest, 'enabled'); ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group">
			                                <?php echo $form->labelEx($abTest, 'winner_criteria_opens_count'); ?>
			                                <?php echo $form->textField($abTest, 'winner_criteria_opens_count', $abTest->fieldDecorator->getHtmlOptions('winner_criteria_opens_count')); ?>
			                                <?php echo $form->error($abTest, 'winner_criteria_opens_count'); ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group">
			                                <?php echo $form->labelEx($abTest, 'winner_criteria_operator'); ?>
			                                <?php echo $form->dropDownList($abTest, 'winner_criteria_operator', $abTest->getOperatorsList(), $abTest->fieldDecorator->getHtmlOptions('winner_criteria_operator')); ?>
			                                <?php echo $form->error($abTest, 'winner_criteria_operator'); ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group">
			                                <?php echo $form->labelEx($abTest, 'winner_criteria_days_count'); ?>
			                                <?php echo $form->textField($abTest, 'winner_criteria_days_count', $abTest->fieldDecorator->getHtmlOptions('winner_criteria_days_count')); ?>
			                                <?php echo $form->error($abTest, 'winner_criteria_days_count'); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row campaign-abtest-testers-wrapper">
                                    <div class="col-lg-12">
                                        <ul class="nav nav-tabs">
                                            <li class="active">
                                                <a href="#tab-abtest-for-subject-lines" data-toggle="tab">
						                            <?php echo t('campaigns', 'Test subject lines'); ?>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content">
                                            <div class="tab-pane active" id="tab-abtest-for-subject-lines">
                                                <div class="box box-primary borderless">
                                                    <div class="box-header">
                                                        <div class="pull-left"></div>
                                                        <div class="pull-right">
                                                            <a href="javascript:" class="btn btn-sm btn-flat btn-primary btn-abtest-subject-line-item-add"><?php echo IconHelper::make('fa-plus-square') . ' ' . t('campaigns', 'Add subject line'); ?></a>
                                                        </div>
                                                        <div class="clearfix"><!-- --></div>
                                                    </div>
                                                    <div class="box-body abtest-subject-line-items">
                                                        <?php foreach ($abTestSubjects as $index => $subject) { ?>
                                                            <div class="row abtest-subject-line-item">
	                                                            <?php echo $form->hiddenField($subject, 'subject_id', $subject->fieldDecorator->getHtmlOptions('subject_id', [
                                                                    'name' => sprintf('%s[%d][subject_id]', $subject->getModelName(), $index),
                                                                ])); ?>
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
				                                                        <?php echo $form->labelEx($subject, 'subject'); ?>
				                                                        <?php echo $form->textField($subject, 'subject', $subject->fieldDecorator->getHtmlOptions('subject', [
                                                                            'name' => sprintf('%s[%d][subject]', $subject->getModelName(), $index),
                                                                        ])); ?>
				                                                        <?php echo $form->error($subject, 'subject'); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-2">
                                                                    <div class="form-group">
				                                                        <?php echo $form->labelEx($subject, 'opens_count'); ?>
				                                                        <?php echo $form->textField($subject, 'opens_count', $subject->fieldDecorator->getHtmlOptions('opens_count', [
                                                                            'readonly'  => true,
                                                                            'name'      => sprintf('%s[%d][opens_count]', $subject->getModelName(), $index),
                                                                        ])); ?>
				                                                        <?php echo $form->error($subject, 'opens_count'); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-2">
                                                                    <div class="form-group">
				                                                        <?php echo $form->labelEx($subject, 'usage_count'); ?>
				                                                        <?php echo $form->textField($subject, 'usage_count', $subject->fieldDecorator->getHtmlOptions('usage_count', [
                                                                            'readonly'  => true,
                                                                            'name'      => sprintf('%s[%d][usage_count]', $subject->getModelName(), $index),
                                                                        ])); ?>
				                                                        <?php echo $form->error($subject, 'usage_count'); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-2">
                                                                    <div class="form-group">
				                                                        <?php echo CHtml::label(t('app', 'Action'), ''); ?>
                                                                        <div class="clearfix"><!-- --></div>
                                                                        <a href="javascript:" class="btn btn-flat btn-danger btn-abtest-subject-line-item-remove"><?php echo IconHelper::make('fa-trash') . ' ' . t('campaigns', 'Remove subject line'); ?></a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

					            <?php
                                // since 2.0.29
                                hooks()->doAction('campaign_form_setup_step_after_campaign_abtest_options', [
                                    'controller' => $controller,
                                    'campaign'   => $campaign,
                                    'form'       => $form,
                                    'abTest'     => $abTest,
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>

                <div class="row">
                    <hr />
                    <div class="col-lg-12">
                        <div class="pull-right">
                            <a href="javascript:;" class="btn btn-flat btn-primary btn-show-more-options"><?php echo IconHelper::make('fa-plus-square') . ' ' . t('campaigns', 'Show more options'); ?></a>
                            <a href="javascript:;" class="btn btn-flat btn-primary btn-show-less-options" style="display: none"><?php echo IconHelper::make('fa-minus-square') . ' ' . t('campaigns', 'Show less options'); ?></a>
                        </div>
                    </div>

                    <div class="col-lg-12 more-options-wrapper" style="display: none">

                        <?php if (count($campaign->getRelatedCampaignsAsOptions())) { ?>
                            <hr />
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="box box-primary borderless">
                                        <div class="box-header">
                                            <div class="pull-left">
                                                <h3 class="box-title">
                                                    <?php echo IconHelper::make('glyphicon-backward') . t('campaigns', 'Previous opened/unopened campaigns'); ?>
                                                </h3>
                                            </div>
                                            <div class="pull-right"></div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <div class="callout callout-info">
                                                        <?php echo t('campaigns', 'Send this campaign only to subscribers that have opened or have not opened previous campaigns:'); ?>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <div class="form-group">
                                                                <?php echo CHtml::label($openUnopenFilter->getAttributeLabel('action'), 'action'); ?>
                                                                <?php echo $form->labelEx($openUnopenFilter, 'action', ['style' => 'display:none']); ?>
                                                                <?php echo $form->dropDownList($openUnopenFilter, 'action', CMap::mergeArray(['' => t('app', 'Choose')], $openUnopenFilter->getActionsList()), $openUnopenFilter->fieldDecorator->getHtmlOptions('action', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                                <?php echo $form->error($openUnopenFilter, 'action'); ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-4">
                                                            <div class="form-group">
                                                                <?php echo CHtml::label($openUnopenFilter->getAttributeLabel('previous_campaign_id'), 'previous_campaign_id'); ?>
                                                                <?php echo $form->labelEx($openUnopenFilter, 'previous_campaign_id', ['style' => 'display:none']); ?>
                                                                <?php echo $form->dropDownList($openUnopenFilter, 'previous_campaign_id', CMap::mergeArray(['' => t('app', 'Choose')], $campaign->getRelatedCampaignsAsOptions()), $openUnopenFilter->fieldDecorator->getHtmlOptions('previous_campaign_id', ['multiple' => true, 'class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                                <?php echo $form->error($openUnopenFilter, 'previous_campaign_id'); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="clearfix"><!-- --></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if (!empty($canShowOpenListFieldActions)) { ?>
                            <hr />
                            <div class="box box-primary borderless panel-campaign-open-list-fields-actions">
                                <div class="box-header">
                                    <div class="pull-left">
                                        <h3 class="box-title">
                                            <?php echo IconHelper::make('glyphicon-tasks') . t('campaigns', 'Change subscriber custom field value upon campaign open'); ?>
                                        </h3>
                                    </div>
                                    <div class="pull-right">
                                        <a href="javascript:;" class="btn btn-primary btn-flat btn-campaign-open-list-fields-actions-add"><?php echo IconHelper::make('create'); ?></a>
                                        <?php echo CHtml::link(IconHelper::make('info'), '#page-info-campaign-open-list-fields-actions', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                    </div>
                                    <div class="clearfix"><!-- --></div>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="campaign-open-list-fields-actions-list">
                                            <?php if (!empty($openListFieldActions)) {
                                    foreach ($openListFieldActions as $index => $openListFieldAct) { ?>
                                                <div class="col-lg-6 campaign-open-list-fields-actions-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <div class="form-group">
                                                                <?php echo $form->labelEx($openListFieldAct, 'field_id'); ?>
                                                                <?php echo CHtml::dropDownList($openListFieldAct->getModelName() . '[' . $index . '][field_id]', $openListFieldAct->field_id, $openListFieldActionOptions, $openListFieldAct->fieldDecorator->getHtmlOptions('field_id', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                                <?php echo $form->error($openListFieldAct, 'field_id'); ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <?php echo $form->labelEx($openListFieldAct, 'field_value'); ?>
                                                                <?php echo CHtml::textField($openListFieldAct->getModelName() . '[' . $index . '][field_value]', $openListFieldAct->field_value, $openListFieldAct->fieldDecorator->getHtmlOptions('field_value')); ?>
                                                                <?php echo $form->error($openListFieldAct, 'field_value'); ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-1">
                                                            <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-open-list-fields-actions-remove" data-action-id="<?php echo $openListFieldAct->action_id; ?>" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php }
                                } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- modals -->
                            <div class="modal modal-info fade" id="page-info-campaign-open-list-fields-actions" tabindex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo t('campaigns', 'This is useful if you later need to segment your list and find out who opened this campaign or who did not and based on that to take another action, like sending the campaign again to subscribers that did not open it previously.'); ?><br />
                                            <?php echo t('campaigns', 'In most of the cases, you will want to keep these fields as hidden fields.'); ?><br />
                                            <br />
                                            <?php echo t('campaigns', 'Following tags are available to be used as dynamic values:'); ?><br />
                                            <div style="width: 100%; height: 200px; overflow-y: scroll">
                                                <table class="table table-bordered table-condensed">
                                                    <thead>
                                                    <tr>
                                                        <th><?php echo t('campaigns', 'Tag'); ?></th>
                                                        <th><?php echo t('campaigns', 'Description'); ?></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php foreach (CampaignHelper::getParsedFieldValueByListFieldValueTagInfo() as $tag => $tagInfo) { ?>
                                                        <tr>
                                                            <td><?php echo $tag; ?></td>
                                                            <td><?php echo $tagInfo; ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if (!empty($canShowSentListFieldActions)) { ?>
                            <hr />
                            <div class="box box-primary borderless panel-campaign-sent-list-fields-actions">
                                <div class="box-header">
                                    <div class="pull-left">
                                        <h3 class="box-title">
                                            <?php echo IconHelper::make('glyphicon-tasks') . t('campaigns', 'Change subscriber custom field value upon campaign sent'); ?>
                                        </h3>
                                    </div>
                                    <div class="pull-right">
                                        <a href="javascript:;" class="btn btn-primary btn-flat btn-campaign-sent-list-fields-actions-add"><?php echo IconHelper::make('create'); ?></a>
                                        <?php echo CHtml::link(IconHelper::make('info'), '#page-info-campaign-sent-list-fields-actions', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                    </div>
                                    <div class="clearfix"><!-- --></div>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="campaign-sent-list-fields-actions-list">
                                            <?php if (!empty($sentListFieldActions)) {
                                    foreach ($sentListFieldActions as $index => $sentListFieldAct) { ?>
                                                <div class="col-lg-6 campaign-sent-list-fields-actions-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                                    <div class="row">
                                                        <div class="col-lg-4">
                                                            <div class="form-group">
                                                                <?php echo $form->labelEx($sentListFieldAct, 'field_id'); ?>
                                                                <?php echo CHtml::dropDownList($sentListFieldAct->getModelName() . '[' . $index . '][field_id]', $sentListFieldAct->field_id, $sentListFieldActionOptions, $sentListFieldAct->fieldDecorator->getHtmlOptions('field_id', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                                <?php echo $form->error($sentListFieldAct, 'field_id'); ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-6">
                                                            <div class="form-group">
                                                                <?php echo $form->labelEx($sentListFieldAct, 'field_value'); ?>
                                                                <?php echo CHtml::textField($sentListFieldAct->getModelName() . '[' . $index . '][field_value]', $sentListFieldAct->field_value, $sentListFieldAct->fieldDecorator->getHtmlOptions('field_value')); ?>
                                                                <?php echo $form->error($sentListFieldAct, 'field_value'); ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-lg-1">
                                                            <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-sent-list-fields-actions-remove" data-action-id="<?php echo $sentListFieldAct->action_id; ?>" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php }
                                } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- modals -->
                            <div class="modal modal-info fade" id="page-info-campaign-sent-list-fields-actions" tabindex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo t('campaigns', 'Following tags are available to be used as dynamic values:'); ?><br />
                                            <div style="width: 100%; height: 200px; overflow-y: scroll">
                                                <table class="table table-bordered table-condensed">
                                                    <thead>
                                                    <tr>
                                                        <th><?php echo t('campaigns', 'Tag'); ?></th>
                                                        <th><?php echo t('campaigns', 'Description'); ?></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php foreach (CampaignHelper::getParsedFieldValueByListFieldValueTagInfo() as $tag => $tagInfo) { ?>
                                                        <tr>
                                                            <td><?php echo $tag; ?></td>
                                                            <td><?php echo $tagInfo; ?></td>
                                                        </tr>
                                                    <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if (!empty($canShowOpenActions)) { ?>
                            <hr />
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="box box-primary borderless panel-campaign-open-actions">
                                        <div class="box-header">
                                            <div class="pull-left">
                                                <h3 class="box-title">
                                                    <?php echo IconHelper::make('glyphicon-new-window') . t('campaigns', 'Actions against subscribers upon campaign open'); ?>
                                                </h3>
                                            </div>
                                            <div class="pull-right">
                                                <a href="javascript:;" class="btn btn-primary btn-flat btn-campaign-open-actions-add"><?php echo IconHelper::make('create'); ?></a>
                                                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-campaign-open-actions-list', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                            </div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="campaign-open-actions-list">
                                                    <?php if (!empty($openActions)) {
                                    foreach ($openActions as $index => $openAct) { ?>
                                                        <div class="col-lg-6 campaign-open-actions-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                                            <div class="row">
                                                                <div class="col-lg-4">
                                                                    <div class="form-group">
                                                                        <?php echo $form->labelEx($openAct, 'action'); ?>
                                                                        <?php echo CHtml::dropDownList($openAct->getModelName() . '[' . $index . '][action]', $openAct->action, $openAllowedActions, $openAct->fieldDecorator->getHtmlOptions('action', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                                        <?php echo $form->error($openAct, 'action'); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <?php echo $form->labelEx($openAct, 'list_id'); ?>
                                                                        <?php echo CHtml::dropDownList($openAct->getModelName() . '[' . $index . '][list_id]', $openAct->list_id, $openActionLists, $openAct->fieldDecorator->getHtmlOptions('list_id', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                                        <?php echo $form->error($openAct, 'list_id'); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-1">
                                                                    <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-open-actions-remove" data-action-id="<?php echo $openAct->action_id; ?>" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php }
                                } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- modals -->
                            <div class="modal modal-info fade" id="page-info-campaign-open-actions-list" tabindex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo t('campaigns', 'When a subscriber opens your campaign, do following actions against the subscriber itself:'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if (!empty($canShowSentActions)) { ?>
                            <hr />
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="box box-primary borderless panel-campaign-sent-actions">
                                        <div class="box-header">
                                            <div class="pull-left">
                                                <h3 class="box-title">
                                                    <?php echo IconHelper::make('glyphicon-new-window') . t('campaigns', 'Actions against subscribers upon campaign sent'); ?>
                                                </h3>
                                            </div>
                                            <div class="pull-right">
                                                <a href="javascript:;" class="btn btn-primary btn-flat btn-campaign-sent-actions-add"><?php echo IconHelper::make('create'); ?></a>
                                                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-campaign-sent-actions-list', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                            </div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="campaign-sent-actions-list">
                                                    <?php if (!empty($sentActions)) {
                                    foreach ($sentActions as $index => $sentAct) { ?>
                                                        <div class="col-lg-6 campaign-sent-actions-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                                            <div class="row">
                                                                <div class="col-lg-4">
                                                                    <div class="form-group">
                                                                        <?php echo $form->labelEx($sentAct, 'action'); ?>
                                                                        <?php echo CHtml::dropDownList($sentAct->getModelName() . '[' . $index . '][action]', $sentAct->action, $sentAllowedActions, $sentAct->fieldDecorator->getHtmlOptions('action', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                                        <?php echo $form->error($sentAct, 'action'); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-6">
                                                                    <div class="form-group">
                                                                        <?php echo $form->labelEx($sentAct, 'list_id'); ?>
                                                                        <?php echo CHtml::dropDownList($sentAct->getModelName() . '[' . $index . '][list_id]', $sentAct->list_id, $sentActionLists, $sentAct->fieldDecorator->getHtmlOptions('list_id', ['class' => 'form-control select2', 'style' => 'width: 100%'])); ?>
                                                                        <?php echo $form->error($sentAct, 'list_id'); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-1">
                                                                    <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-sent-actions-remove" data-action-id="<?php echo $sentAct->action_id; ?>" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php }
                                } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- modals -->
                            <div class="modal modal-info fade" id="page-info-campaign-sent-actions-list" tabindex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo t('campaigns', 'When a campaign is sent to your subscriber, do following actions against the subscriber itself:'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

	                    <?php if (!empty($webhooksEnabled)) { ?>
                            <hr />
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="box box-primary borderless panel-campaign-track-open-webhook">
                                        <div class="box-header">
                                            <div class="pull-left">
                                                <h3 class="box-title">
								                    <?php echo IconHelper::make('glyphicon-new-window') . t('campaigns', 'Subscribers webhooks upon campaign open'); ?>
                                                </h3>
                                            </div>
                                            <div class="pull-right">
                                                <a href="javascript:;" class="btn btn-primary btn-flat btn-campaign-track-open-webhook-add"><?php echo IconHelper::make('create'); ?></a>
							                    <?php echo CHtml::link(IconHelper::make('info'), '#page-info-campaign-track-open-webhook', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                            </div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                        <div class="box-body">
                                            <div class="row">
                                                <div class="campaign-track-open-webhook-list">
								                    <?php if (!empty($opensWebhooks)) {
                                    foreach ($opensWebhooks as $index => $opensWebhookModel) { ?>
                                                        <div class="col-lg-6 campaign-track-open-webhook-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                                            <div class="row">
                                                                <div class="col-lg-11">
                                                                    <div class="form-group">
													                    <?php echo $form->labelEx($opensWebhookModel, 'webhook_url'); ?>
													                    <?php echo CHtml::textField($opensWebhookModel->getModelName() . '[' . $index . '][webhook_url]', $opensWebhookModel->webhook_url, $opensWebhookModel->fieldDecorator->getHtmlOptions('webhook_url')); ?>
													                    <?php echo $form->error($opensWebhookModel, 'webhook_url'); ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-lg-1">
                                                                    <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-track-open-webhook-remove" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                                </div>
                                                            </div>
                                                        </div>
								                    <?php }
                                } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- modals -->
                            <div class="modal modal-info fade" id="page-info-campaign-track-open-webhook" tabindex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                        </div>
                                        <div class="modal-body">
						                    <?php echo t('campaigns', 'When a campaign is opened by a subscriber, send a webhook request containing event data to the given urls'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
	                    <?php } ?>


                        <hr />
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="box box-primary borderless panel-campaign-extra-tags">
                                    <div class="box-header">
                                        <div class="pull-left">
                                            <h3 class="box-title">
                                                <?php echo IconHelper::make('glyphicon-tag') . t('campaigns', 'Extra tags'); ?>
                                            </h3>
                                        </div>
                                        <div class="pull-right">
                                            <a href="javascript:;" class="btn btn-primary btn-flat btn-campaign-extra-tags-add"><?php echo IconHelper::make('create'); ?></a>
                                            <?php echo CHtml::link(IconHelper::make('info'), '#page-info-campaign-extra-tags-list', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                        </div>
                                        <div class="clearfix"><!-- --></div>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="campaign-extra-tags-list">
                                                <?php if (!empty($extraTags)) {
                                    foreach ($extraTags as $index => $_extraTag) { ?>
                                                    <div class="col-lg-6 campaign-extra-tags-row" data-start-index="<?php echo (int)$index; ?>" style="margin-bottom: 10px;">
                                                        <div class="row">
                                                            <div class="col-lg-5">
                                                                <div class="form-group">
                                                                    <?php echo $form->labelEx($_extraTag, 'tag'); ?>
                                                                    <?php echo CHtml::textField($_extraTag->getModelName() . '[' . $index . '][tag]', $_extraTag->tag, $_extraTag->fieldDecorator->getHtmlOptions('tag')); ?>
                                                                    <?php echo $form->error($_extraTag, 'tag'); ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-5">
                                                                <div class="form-group">
                                                                    <?php echo $form->labelEx($_extraTag, 'content'); ?>
                                                                    <?php echo CHtml::textField($_extraTag->getModelName() . '[' . $index . '][content]', $_extraTag->content, $_extraTag->fieldDecorator->getHtmlOptions('content')); ?>
                                                                    <?php echo $form->error($_extraTag, 'content'); ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-2">
                                                                <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-extra-tags-remove" data-tag-id="<?php echo $_extraTag->tag_id; ?>" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php }
                                } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- modals -->
                        <div class="modal modal-info fade" id="page-info-campaign-extra-tags-list" tabindex="-1" role="dialog">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                    </div>
                                    <div class="modal-body">
                                        <?php echo t('campaigns', 'This allows you to define extra campaign tags which you might use in the campaign.'); ?><br />
                                        <?php echo t('campaigns', 'Please note that your tags defined here will be prefixed with the prefix: {prefix}', [
                                                '{prefix}' => CampaignExtraTag::getTagPrefix(),
                                        ]); ?><br />
                                        <?php echo t('campaigns', 'So if you define a tag named COMPANY_NAME, you can then use it like: "{example}" in your campaign.', [
                                                '{example}' => CampaignExtraTag::getTagPrefix() . 'COMPANY_NAME',
                                        ]); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($canSelectDeliveryServers && !empty($deliveryServers)) { ?>
                            <hr />
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="box box-primary borderless">
                                        <div class="box-header">
                                            <div class="pull-left">
                                                <h3 class="box-title">
                                                    <?php echo IconHelper::make('glyphicon-send') . t('campaigns', 'Campaign delivery servers'); ?>
                                                </h3>
                                            </div>
                                            <div class="pull-right">
                                                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-delivery-servers-pool', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                            </div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                        <div class="box-body panel-delivery-servers-pool">
                                            <div class="row">
                                                <?php foreach ($deliveryServers as $server) { ?>
                                                    <div class="col-lg-4">
                                                        <div class="item">
                                                            <?php echo CHtml::checkBox($campaignToDeliveryServers->getModelName() . '[]', in_array($server->server_id, $campaignDeliveryServersArray), ['value' => $server->server_id]); ?>
                                                            <?php echo $server->getDisplayName(); ?>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- modals -->
                            <div class="modal modal-info fade" id="page-info-delivery-servers-pool" tabindex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo t('campaigns', 'Select which delivery servers are used for this campaign, if no option is selected, all the available servers will be used.'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if ($canSelectSuppressionLists && !empty($allSuppressionLists)) { ?>
                            <hr />
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="box box-primary borderless">
                                        <div class="box-header">
                                            <div class="pull-left">
                                                <h3 class="box-title">
                                                    <?php echo IconHelper::make('glyphicon-ban-circle') . t('campaigns', 'Campaign suppression lists'); ?>
                                                </h3>
                                            </div>
                                            <div class="pull-right"></div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                        <div class="box-body">
                                            <div class="row">
                                                <?php foreach ($allSuppressionLists as $suppressionList) { ?>
                                                    <div class="col-lg-4">
                                                        <div class="item">
                                                            <?php echo CHtml::checkBox($suppressionListToCampaign->getModelName() . '[]', in_array($suppressionList->list_id, $selectedSuppressionLists), ['value' => $suppressionList->list_id]); ?>
                                                            <?php echo $suppressionList->name; ?>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- modals -->
                            <div class="modal modal-info fade" id="page-info-delivery-servers-pool" tabindex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo t('campaigns', 'Select which delivery servers are used for this campaign, if no option is selected, all the available servers will be used.'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <?php if ($canAddAttachments) { ?>
                            <hr />
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="box box-primary borderless panel-campaign-attachments">
                                        <div class="box-header">
                                            <div class="pull-left">
                                                <h3 class="box-title">
                                                    <?php echo IconHelper::make('glyphicon-upload') . t('campaigns', 'Campaign attachments'); ?>
                                                </h3>
                                            </div>
                                            <div class="pull-right">
                                                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-campaign-attachments', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                                            </div>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                        <div class="box-body">
                                            <?php
                                            $controller->widget('CMultiFileUpload', [
                                                'model'        => $attachment,
                                                'attribute'    => 'file',
                                                'max'          => $attachment->getAllowedFilesCount(),
                                                'options'      => [
                                                    'STRING' => [
                                                        'file'      => new CJavaScriptExpression('window.MultiFileCustomString.file'),
                                                        'selected'  => new CJavaScriptExpression('window.MultiFileCustomString.selected'),
                                                    ],
                                                ],
                                            ]);
                                            ?>
                                            <?php if (!empty($campaign->attachments)) { ?>
                                                <h5><?php echo t('campaigns', 'Uploaded files for this campaign:'); ?></h5>
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <?php foreach ($campaign->attachments as $file) { ?>
                                                            <div class="col-lg-4">
                                                                <div class="item">
                                                                    <a href="<?php echo createUrl('campaigns/remove_attachment', ['campaign_uid' => $campaign->campaign_uid, 'attachment_id' => $file->attachment_id]); ?>" class="btn btn-xs btn-danger btn-remove-attachment" data-message="<?php echo t('campaigns', 'Are you sure you want to remove this attachment?'); ?>">x</a>
                                                                    <?php echo $file->name; ?>
                                                                </div>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <div class="clearfix"><!-- --></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- modals -->
                            <div class="modal modal-info fade" id="page-info-campaign-attachments" tabindex="-1" role="dialog">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                            <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                                        </div>
                                        <div class="modal-body">
                                            <?php echo t('campaigns', 'You are allowed to upload up to {maxCount} attachments. Each attachment size must be lower than {maxSize}.', [
                                                '{maxCount}' => $attachment->getAllowedFilesCount(),
                                                '{maxSize}'  => ($attachment->getAllowedFileSize() / 1024 / 1024) . ' mb',
                                            ]); ?>
                                            <?php if (count($allowedExtensions = $attachment->getAllowedExtensions()) > 0) { ?>
                                                <br />
                                                <?php echo t('campaigns', 'Following file types are allowed for upload: {types}', [
                                                    '{types}' => implode(', ', $allowedExtensions),
                                                ]); ?>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        } ?>

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
                    'form'          => $form,
                ])); ?>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-footer">
                <div class="wizard">
                    <ul class="steps">
                        <li class="complete"><a href="<?php echo createAbsoluteUrl('campaigns/update', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Details'); ?></a><span class="chevron"></span></li>
                        <li class="active"><a href="<?php echo createAbsoluteUrl('campaigns/setup', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Setup'); ?></a><span class="chevron"></span></li>
                        <li><a href="<?php echo createAbsoluteUrl('campaigns/template', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Template'); ?></a><span class="chevron"></span></li>
                        <li><a href="<?php echo createAbsoluteUrl('campaigns/confirm', ['campaign_uid' => $campaign->campaign_uid]); ?>"><?php echo t('campaigns', 'Confirmation'); ?></a><span class="chevron"></span></li>
                        <li><a href="javascript:;"><?php echo t('app', 'Done'); ?></a><span class="chevron"></span></li>
                    </ul>
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
     * This hook gives a chance to append content after the active form fields.
     * Please note that from inside the action callback you can access all the controller view variables
     * via {@CAttributeCollection $collection->controller->getData()}
     * @since 1.3.3.1
     */
    hooks()->doAction('after_active_form', new CAttributeCollection([
        'controller'      => $controller,
        'renderedForm'    => $collection->itemAt('renderForm'),
    ])); ?>
    <div class="modal fade" id="available-tags-modal" tabindex="-1" role="dialog" aria-labelledby="available-tags-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('lists', 'Available tags'); ?></h4>
            </div>
            <div class="modal-body" style="max-height: 300px; overflow-y:scroll;">
                <table class="table table-hover">
                    <tr>
                        <td><?php echo t('lists', 'Tag'); ?></td>
                        <td><?php echo t('lists', 'Required'); ?></td>
                    </tr>
                    <?php foreach ($campaign->getSubjectToNameAvailableTags() as $tag) { ?>
                    <tr>
                        <td><?php echo html_encode($tag['tag']); ?></td>
                        <td><?php echo $tag['required'] ? strtoupper(t('app', Campaign::TEXT_YES)) : strtoupper(t('app', Campaign::TEXT_NO)); ?></td>
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

	<?php if ($campaign->getCanDoAbTest()) { ?>
    <div id="abtest-subject-line-item-template" style="display: none">
        <div class="row abtest-subject-line-item">
            <div class="col-lg-6">
                <div class="form-group">
				    <?php echo $form->labelEx($abTestSubject, '[{counter}]subject'); ?>
				    <?php echo $form->textField($abTestSubject, '[{counter}]subject', $abTestSubject->fieldDecorator->getHtmlOptions('subject')); ?>
				    <?php echo $form->error($abTestSubject, '[{counter}]subject'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
				    <?php echo $form->labelEx($abTestSubject, '[{counter}]opens_count'); ?>
				    <?php echo $form->textField($abTestSubject, '[{counter}]opens_count', $abTestSubject->fieldDecorator->getHtmlOptions('opens_count', ['readonly' => true])); ?>
				    <?php echo $form->error($abTestSubject, '[{counter}]opens_count'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
				    <?php echo $form->labelEx($abTestSubject, '[{counter}]usage_count'); ?>
				    <?php echo $form->textField($abTestSubject, '[{counter}]usage_count', $abTestSubject->fieldDecorator->getHtmlOptions('usage_count', ['readonly' => true])); ?>
				    <?php echo $form->error($abTestSubject, '[{counter}]usage_count'); ?>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group">
				    <?php echo CHtml::label(t('app', 'Action'), ''); ?>
                    <div class="clearfix"><!-- --></div>
                    <a href="javascript:" class="btn btn-flat btn-danger btn-abtest-subject-line-item-remove"><?php echo IconHelper::make('fa-trash') . ' ' . t('campaigns', 'Remove subject line'); ?></a>
                </div>
            </div>
        </div>
    </div>
    <div class="modal modal-info fade" id="page-info-abtest" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                </div>
                <div class="modal-body">
                    <?php echo t('campaigns', 'If enabled, the A/B Test will rotate the A/B Test items in a round-robin fashion until a winner is decided based on the A/B Test conditions.'); ?><br />
                </div>
            </div>
        </div>
    </div>
    <?php } ?>

    <div id="campaign-open-actions-template" style="display: none;">
        <div class="col-lg-6 campaign-open-actions-row" data-start-index="{index}" style="margin-bottom: 10px;">
            <div class="row">
                <div class="col-lg-4">
                    <div class="form-group">
                        <?php echo $form->labelEx($openAction, 'action'); ?>
                        <?php echo CHtml::dropDownList($openAction->getModelName() . '[{index}][action]', null, $openAllowedActions, $openAction->fieldDecorator->getHtmlOptions('action', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($openAction, 'action'); ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <?php echo $form->labelEx($openAction, 'list_id'); ?>
                        <?php echo CHtml::dropDownList($openAction->getModelName() . '[{index}][list_id]', null, $openActionLists, $openAction->fieldDecorator->getHtmlOptions('list_id', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($openAction, 'list_id'); ?>
                    </div>
                </div>
                <div class="col-lg-1">
                    <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-open-actions-remove" data-action-id="0" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <div id="campaign-sent-actions-template" style="display: none;">
        <div class="col-lg-6 campaign-sent-actions-row" data-start-index="{index}" style="margin-bottom: 10px;">
            <div class="row">
                <div class="col-lg-4">
                    <div class="form-group">
                        <?php echo $form->labelEx($sentAction, 'action'); ?>
                        <?php echo CHtml::dropDownList($sentAction->getModelName() . '[{index}][action]', null, $sentAllowedActions, $sentAction->fieldDecorator->getHtmlOptions('action', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($sentAction, 'action'); ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <?php echo $form->labelEx($sentAction, 'list_id'); ?>
                        <?php echo CHtml::dropDownList($sentAction->getModelName() . '[{index}][list_id]', null, $sentActionLists, $sentAction->fieldDecorator->getHtmlOptions('list_id', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($sentAction, 'list_id'); ?>
                    </div>
                </div>
                <div class="col-lg-1">
                    <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-sent-actions-remove" data-action-id="0" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($webhooksEnabled)) { ?>
        <div id="campaign-track-open-webhook-template" style="display: none;">
            <div class="col-lg-6 campaign-track-open-webhook-row" data-start-index="{index}" style="margin-bottom: 10px;">
                <div class="row">
                    <div class="col-lg-11">
                        <div class="form-group">
                            <?php echo $form->labelEx($opensWebhook, 'webhook_url'); ?>
                            <?php echo CHtml::textField($opensWebhook->getModelName() . '[{index}][webhook_url]', null, $opensWebhook->fieldDecorator->getHtmlOptions('webhook_url')); ?>
                            <?php echo $form->error($opensWebhook, 'webhook_url'); ?>
                        </div>
                    </div>
                    <div class="col-lg-1">
                        <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-track-open-webhook-remove" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <div id="campaign-extra-tags-template" style="display: none;">
        <div class="col-lg-6 campaign-extra-tags-row" data-start-index="{index}" style="margin-bottom: 10px;">
            <div class="row">
                <div class="col-lg-5">
                    <div class="form-group">
                        <?php echo $form->labelEx($extraTag, 'tag'); ?>
                        <?php echo CHtml::textField($extraTag->getModelName() . '[{index}][tag]', null, $extraTag->fieldDecorator->getHtmlOptions('tag')); ?>
                        <?php echo $form->error($extraTag, 'tag'); ?>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="form-group">
                        <?php echo $form->labelEx($extraTag, 'content'); ?>
                        <?php echo CHtml::textField($extraTag->getModelName() . '[{index}][content]', null, $extraTag->fieldDecorator->getHtmlOptions('content')); ?>
                        <?php echo $form->error($extraTag, 'content'); ?>
                    </div>
                </div>
                <div class="col-lg-2">
                    <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-extra-tags-remove" data-tag-id="0" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <div id="campaign-open-list-fields-actions-template" style="display: none;">
        <div class="col-lg-6 campaign-open-list-fields-actions-row" data-start-index="{index}" style="margin-bottom: 10px;">
            <div class="row">
                <div class="col-lg-4">
                    <div class="form-group">
                        <?php echo $form->labelEx($openListFieldAction, 'field_id'); ?>
                        <?php echo CHtml::dropDownList($openListFieldAction->getModelName() . '[{index}][field_id]', null, $openListFieldActionOptions, $openListFieldAction->fieldDecorator->getHtmlOptions('field_id', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($openListFieldAction, 'field_id'); ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <?php echo $form->labelEx($openListFieldAction, 'field_value'); ?>
                        <?php echo CHtml::textField($openListFieldAction->getModelName() . '[{index}][field_value]', null, $openListFieldAction->fieldDecorator->getHtmlOptions('field_value')); ?>
                        <?php echo $form->error($openListFieldAction, 'field_value'); ?>
                    </div>
                </div>
                <div class="col-lg-1">
                    <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-open-list-fields-actions-remove" data-action-id="0" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
                </div>
            </div>
        </div>
    </div>

    <div id="campaign-sent-list-fields-actions-template" style="display: none;">
        <div class="col-lg-6 campaign-sent-list-fields-actions-row" data-start-index="{index}" style="margin-bottom: 10px;">
            <div class="row">
                <div class="col-lg-4">
                    <div class="form-group">
                        <?php echo $form->labelEx($sentListFieldAction, 'field_id'); ?>
                        <?php echo CHtml::dropDownList($sentListFieldAction->getModelName() . '[{index}][field_id]', null, $sentListFieldActionOptions, $sentListFieldAction->fieldDecorator->getHtmlOptions('field_id', ['class' => 'form-control select2-no-init', 'style' => 'width: 100%'])); ?>
                        <?php echo $form->error($sentListFieldAction, 'field_id'); ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="form-group">
                        <?php echo $form->labelEx($sentListFieldAction, 'field_value'); ?>
                        <?php echo CHtml::textField($sentListFieldAction->getModelName() . '[{index}][field_value]', null, $sentListFieldAction->fieldDecorator->getHtmlOptions('field_value')); ?>
                        <?php echo $form->error($sentListFieldAction, 'field_value'); ?>
                    </div>
                </div>
                <div class="col-lg-1">
                    <a style="margin-top: 27px;" href="javascript:;" class="btn btn-flat btn-danger btn-campaign-sent-list-fields-actions-remove" data-action-id="0" data-message="<?php echo t('app', 'Are you sure you want to remove this item?'); ?>"><?php echo IconHelper::make('delete'); ?></a>
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
