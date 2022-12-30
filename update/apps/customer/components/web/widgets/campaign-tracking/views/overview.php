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

?>

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('fa-envelope') . t('campaigns', 'Campaign overview'); ?>
            </h3>
        </div>
        <div class="pull-right">
            <?php echo CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]); ?>
            <?php if (!empty($shareReports)) { ?>
                <?php echo CHtml::link(IconHelper::make('fa-share-square-o'), '#page-share-stats', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Share campaign stats'), 'data-toggle' => 'modal']); ?>
            <?php } ?>
            <?php echo CHtml::link(IconHelper::make('info'), '#page-info', ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard" style="padding-bottom: 0px">
                    <div class="progress-box" style="padding-bottom: 0px">
                        <div class="info">
                            <span class="name"><?php echo t('campaign_reports', 'Recipients'); ?></span><span class="number"><?php echo $recipientsLink; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaigns', 'Name'); ?></span><span class="cl-span"><?php echo $campaign->name; ?></span></li>
                        <li><span class="cl-span"><?php echo t('campaigns', 'List/Segment'); ?></span><span class="cl-span"><?php echo $campaign->getListSegmentName(); ?></span></li>
                        <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('subject'); ?></span><span class="cl-span"><?php echo $campaign->subject; ?></span></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <ul class="custom-list">
                        <?php if ($campaign->isRegular) { ?>
                            <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('lastOpen'); ?></span><span class="cl-span"><?php echo $campaign->lastOpen; ?></span></li>
                            <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('started_at'); ?></span><span class="cl-span"><?php echo $campaign->startedAt ? $campaign->startedAt : $campaign->sendAt; ?></span></li>
                            <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('finished_at'); ?></span><span class="cl-span"><?php echo $campaign->finishedAt; ?></span></li>
                        <?php } ?>
                        <?php if ($campaign->isAutoresponder) { ?>
                            <li><span class="cl-span"><?php echo t('campaigns', 'Autoresponder event'); ?></span><span class="cl-span"><?php echo t('campaigns', $campaign->option->autoresponder_event); ?></span></li>
                            <li><span class="cl-span"><?php echo t('campaigns', 'Autoresponder time unit'); ?></span><span class="cl-span"><?php echo ucfirst(t('app', $campaign->option->autoresponder_time_unit)); ?></span></li>
                            <li><span class="cl-span"><?php echo t('campaigns', 'Autoresponder time value'); ?></span><span class="cl-span"><?php echo $campaign->option->autoresponder_time_value; ?></span></li>
                            <?php if ($arTimeMinHourMinute = $campaign->option->getAutoresponderTimeMinHourMinute()) { ?>
                                <li><span class="cl-span"><?php echo t('campaigns', 'Send only at/after this time'); ?></span><span class="cl-span"><?php echo $arTimeMinHourMinute; ?> (UTC 00:00)</span></li>
                            <?php } ?>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><i class="fa fa-bars" aria-hidden="true"></i><?php echo t('campaign_reports', 'Details'); ?></h3>
        </div>
        <div class="pull-right"></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaigns', 'Type'); ?></span><span class="cl-span"><?php echo ucfirst(t('campaigns', $campaign->type)); ?></span></li>
                        <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('from_name'); ?></span><span class="cl-span"><?php echo $campaign->from_name; ?></span></li>
                        <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('from_email'); ?></span><span class="cl-span"><?php echo $campaign->from_email; ?></span></li>
                        <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('reply_to'); ?></span><span class="cl-span"><?php echo $campaign->reply_to; ?></span></li>
                        <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('to_name'); ?></span><span class="cl-span"><?php echo $campaign->to_name; ?></span></li>
                        <li><span class="cl-span"><?php echo t('campaigns', 'Web version'); ?></span><span class="cl-span"><?php echo CHtml::link(t('app', 'View'), $webVersionUrl, ['target' => '_blank']); ?></span></li>

                        <?php if (!empty($campaign->template->name)) { ?>
                            <li><span class="cl-span"><?php echo $campaign->template->getAttributeLabel('name'); ?></span><span class="cl-span"><?php echo $campaign->template->name; ?></span></li>
                        <?php } ?>

                        <?php if (!empty($campaign->send_group_id)) { ?>
                        <li>
                            <span class="cl-span"><?php echo t('campaigns', 'Send group campaigns'); ?></span>
                            <span class="cl-span">
                                <?php echo collect($campaign->sendGroup->campaigns)->filter(function (Campaign $campaign) {
    return $campaign->getIsSending() || $campaign->getIsSent() || $campaign->getIsProcessing() || $campaign->getIsPaused();
})->filter(function (Campaign $cmp) use ($campaign) {
    return $cmp->campaign_id !== $campaign->campaign_id;
})->map(function (Campaign $campaign) {
    return CHtml::link($campaign->name, ['campaigns/overview', 'campaign_uid' => $campaign->campaign_uid]);
})->join(', '); ?>
                            </span>
                        </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaigns', 'Forwards'); ?></span><span class="cl-span"><?php echo $forwardsLink; ?></span></li>
                        <li><span class="cl-span"><?php echo t('campaigns', 'Abuse reports'); ?></span><span class="cl-span"><?php echo $abusesLink; ?></span></li>
                        <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('date_added'); ?></span><span class="cl-span"><?php echo $campaign->dateAdded; ?></span></li>
                        <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('send_at'); ?></span><span class="cl-span"><?php echo $campaign->sendAt; ?></span></li>

	                    <?php if ($campaign->isRegular && $campaign->option->getTimewarpEnabled()) { ?>
                            <li>
                                <span class="cl-span"><?php echo t('campaigns', 'Timewarp'); ?></span>
                                <span class="cl-span"><?php echo $campaign->option->timewarp_hour; ?>:<?php echo $campaign->option->timewarp_minute; ?></span>
                            </li>
                        <?php } ?>

                        <?php if ($campaign->isRegular) { ?>
                            <li><span class="cl-span"><?php echo $campaign->getAttributeLabel('totalDeliveryTime'); ?></span><span class="cl-span"><?php echo $campaign->totalDeliveryTime; ?></span></li>
                        <?php } ?>

                        <?php if ($campaign->getRegularOpenUnopenDisplayText()) { ?>
                            <li><span class="cl-span"><?php echo t('campaigns', 'Filtered sent to'); ?></span><span class="cl-span"><?php echo $campaign->getRegularOpenUnopenDisplayText(); ?></span></li>
                        <?php } ?>


                        <?php if (!empty($recurringInfo)) { ?>
                            <li><span class="cl-span"><?php echo t('campaigns', 'Recurring'); ?></span><span class="cl-span"><?php echo !empty($recurringInfo) ? $recurringInfo : t('app', 'No'); ?></span></li>
                        <?php } ?>

                        <?php if ($campaign->isAutoresponder) { ?>
                            <?php if (!empty($campaign->option->autoresponder_open_campaign_id)) { ?>
                                <li><span class="cl-span"><?php echo t('campaigns', 'Campaign to send for'); ?></span><span class="cl-span"><?php echo $campaign->option->autoresponderOpenCampaign->name; ?></span></li>
                                <li><span class="cl-span"><?php echo t('campaigns', 'Current opens count for target campaign'); ?></span><span class="cl-span"><?php echo (int)$campaign->option->autoresponderOpenCampaign->stats->getUniqueOpensCount(true); ?></span></li>
                            <?php } ?>

                            <?php if (!empty($campaign->option->autoresponder_sent_campaign_id)) { ?>
                                <li><span class="cl-span"><?php echo t('campaigns', 'Campaign to send for'); ?></span><span class="cl-span"><?php echo $campaign->option->autoresponderSentCampaign->name; ?></span></li>
                                <li><span class="cl-span"><?php echo t('campaigns', 'Current sent count for target campaign'); ?></span><span class="cl-span"><?php echo (int)$campaign->option->autoresponderSentCampaign->stats->getProcessedCount(true); ?></span></li>
                            <?php } ?>

                            <li><span class="cl-span"><?php echo t('campaigns', 'Include imported subscribers'); ?></span><span class="cl-span"><?php echo ucfirst(t('app', $campaign->option->autoresponder_include_imported)); ?></span></li>
                            <li><span class="cl-span"><?php echo t('campaigns', 'Include current subscribers'); ?></span><span class="cl-span"><?php echo ucfirst(t('app', $campaign->option->autoresponder_include_current)); ?></span></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>


<?php if (!empty($abTest)) { ?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><i class="fa fa-code-fork" aria-hidden="true"></i><?php echo t('campaigns', 'A/B Test'); ?></h3>
        </div>
        <div class="pull-right"></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="callout callout-info">
                    <?php echo html_encode((string)$abTest->getDescription()); ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a href="#tab-abtest-for-subject-lines" data-toggle="tab">
					        <?php echo t('campaigns', 'Subject lines'); ?>
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="tab-abtest-for-subject-lines">
                        <?php if (!empty($abTest->activeSubjects)) { ?>
                            <?php foreach ($abTest->activeSubjects as $subject) { ?>
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                    <div class="box-dashboard">
                                        <ul class="custom-list">
                                            <li>
                                                <span class="cl-span">
                                                    <?php echo html_encode((string)$subject->subject); ?>
                                                    <?php if ($subject->getIsWinner()) { ?><span class="badge bg-blue"><?php echo t('campaigns', 'Winner'); ?></span><?php } ?>
                                                </span>
                                                <span class="cl-span"><?php echo t('campaigns', '{opens_count} opens', ['{opens_count}' => (int)$subject->opens_count]); ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
	                        <?php echo t('campaigns', 'There is no active subject line defined'); ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if ($abTest->getIsComplete()) { ?>
        <div class="box-footer">
            <div class="callout callout-info">
                <?php echo t('campaign_reports', 'Clicking below links will show you a filtered view with results registered after the test has been completed.'); ?>
            </div>
            <div class="row">
                <?php if (!empty($abTestOpensUrl)) { ?>
                    <div class="col-lg-3 text-center">
                        <?php echo CHtml::link(IconHelper::make('glyphicon-list-alt') . ' ' . t('campaign_reports', 'Opens'), $abTestOpensUrl, ['class' => 'btn btn-primary btn-flat']); ?>
                    </div>
                <?php } ?>
                <?php if (!empty($abTestClicksUrl)) { ?>
                    <div class="col-lg-2 text-center">
                        <?php echo CHtml::link(IconHelper::make('glyphicon-list-alt') . ' ' . t('campaign_reports', 'Clicks'), $abTestClicksUrl, ['class' => 'btn btn-primary btn-flat']); ?>
                    </div>
                <?php } ?>
                <?php if (!empty($abTestUnsubscribesUrl)) { ?>
                    <div class="col-lg-2 text-center">
                        <?php echo CHtml::link(IconHelper::make('glyphicon-list-alt') . ' ' . t('campaign_reports', 'Unsubscribes'), $abTestUnsubscribesUrl, ['class' => 'btn btn-primary btn-flat']); ?>
                    </div>
                <?php } ?>
                <?php if (!empty($abTestComplainsUrl)) { ?>
                    <div class="col-lg-2 text-center">
                        <?php echo CHtml::link(IconHelper::make('glyphicon-list-alt') . ' ' . t('campaign_reports', 'Complaints'), $abTestComplainsUrl, ['class' => 'btn btn-primary btn-flat']); ?>
                    </div>
                <?php } ?>
                <?php if (!empty($abTestBouncesUrl)) { ?>
                    <div class="col-lg-3 text-center">
                        <?php echo CHtml::link(IconHelper::make('glyphicon-list-alt') . ' ' . t('campaign_reports', 'Bounces'), $abTestBouncesUrl, ['class' => 'btn btn-primary btn-flat']); ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>
<?php } ?>

<!-- modals -->
<?php if (!empty($shareReports)) { ?>
<div class="modal modal-info fade" id="page-share-stats" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php echo IconHelper::make('fa-share-square-o') . t('app', 'Share campaign stats'); ?></h4>
            </div>
            <div class="modal-body">
                <?php $form = $this->beginWidget('CActiveForm', [
                    'id'     => 'campaign-share-reports-form',
                    'action' => ['campaigns/share_reports', 'campaign_uid' => $shareReports->campaign->campaign_uid],
                ]); ?>
                <div class="row message" data-wait="<?php echo t('app', 'Please wait...'); ?>"></div>
                <div class="row">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($shareReports, 'share_reports_enabled'); ?>
                            <?php echo $form->dropDownList($shareReports, 'share_reports_enabled', $shareReports->getYesNoOptions(), $shareReports->getHtmlOptions('share_reports_enabled')); ?>
                            <?php echo $form->error($shareReports, 'share_reports_enabled'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <?php echo $form->labelEx($shareReports, 'share_reports_mask_email_addresses'); ?>
                            <?php echo $form->dropDownList($shareReports, 'share_reports_mask_email_addresses', $shareReports->getYesNoOptions(), $shareReports->getHtmlOptions('share_reports_mask_email_addresses')); ?>
                            <?php echo $form->error($shareReports, 'share_reports_mask_email_addresses'); ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <?php echo $form->labelEx($shareReports, 'share_reports_password'); ?>
                        <div class="input-group">
                            <?php echo $form->textField($shareReports, 'share_reports_password', $shareReports->getHtmlOptions('share_reports_password')); ?>
                            <span class="input-group-btn">
                                <button class="btn btn-primary btn-flat btn-generate-share-password" type="button"><?php echo IconHelper::make('refresh'); ?></button>
                            </span>
                        </div>
                        <div class="clearfix"><!-- --></div>
                        <?php echo $form->error($shareReports, 'share_reports_password'); ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <?php echo $form->labelEx($shareReports, 'shareUrl'); ?>
                            <?php echo $form->textField($shareReports, 'shareUrl', $shareReports->getHtmlOptions('shareUrl', ['readonly' => true])); ?>
                            <?php echo $form->error($shareReports, 'shareUrl'); ?>
                        </div>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div style="display: none"><?php echo $form->labelEx($shareReports, 'share_reports_emails'); ?></div>
                        <label><?php echo t('campaigns', 'Email above info to below email addresses (comma separated email list accepted)'); ?></label>
                        <div class="input-group">
                            <?php echo $form->textField($shareReports, 'share_reports_emails', $shareReports->getHtmlOptions('share_reports_emails')); ?>
                            <span class="input-group-btn">
                                <button class="btn btn-primary btn-flat btn-send-share-stats-details" type="button" data-action="<?php echo createUrl('campaigns/share_reports_send_email', ['campaign_uid' => $shareReports->campaign->campaign_uid]); ?>"><?php echo IconHelper::make('envelope') . '&nbsp;' . t('app', 'Send email'); ?></button>
                            </span>
                        </div>
                        <div class="clearfix"><!-- --></div>
                        <?php echo $form->error($shareReports, 'share_reports_email'); ?>
                    </div>
                </div>
                <hr />
                <div class="row">
                   <div class="col-lg-12">
                       <div class="pull-right">
                           <button type="submit" class="btn btn-primary btn-flat"><?php echo t('app', 'Save changes'); ?></button>
                       </div>
                   </div>
                </div>
                <div class="clearfix"><!-- --></div>
                <?php $this->endWidget(); ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<div class="modal modal-info fade" id="page-info" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
            </div>
            <div class="modal-body">
                <?php echo t('campaigns', 'Please note that the stats are based only on your list confirmed subscribers count.'); ?> <br />
                <?php echo t('campaigns', 'The number of confirmed subscribers can change during a sendout, subscribers can unsubscribe, get blacklisted or report the email as spam, case in which actions are taken and those subscribers are not confirmed anymore.'); ?><br />
                <b><?php echo t('campaigns', 'Stats data is cached for 5 minutes.'); ?> <br /></b>
            </div>
        </div>
    </div>
</div>
