<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}
/** @var Campaign $campaign */
?>
<?php
/** @var OptionUrl $optionUrl */
$optionUrl = container()->get(OptionUrl::class); ?>
<?php echo t('campaign_reports', 'The campaign {name} has finished sending, here are the stats', ['{name}' => $campaign->name]); ?>:<br />
<br /><br />

<table cellpadding="0" cellspacing="0" border="0" style="background:#f5f5f5;font-size:12px; padding:5px;width: 100%;">
    <tr style="background:#eeeeee;">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Processed'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getProcessedCount(true); ?></td>
    </tr>
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Sent with success'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getDeliverySuccessCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Sent success rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getDeliverySuccessRate(true); ?>%</td>
    </tr>
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Send error'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getDeliveryErrorCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Send error rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getDeliveryErrorRate(true); ?>%</td>
    </tr>
    
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Unique opens'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getUniqueOpensCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Unique open rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getUniqueOpensRate(true); ?>%</td>
    </tr>
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'All opens'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getOpensCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'All opens rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getOpensRate(true); ?>%</td>
    </tr>

    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Bounced back'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getBouncesCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Bounce rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getBouncesRate(true); ?>%</td>
    </tr>
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Hard bounce'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getHardBouncesCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Hard bounce rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getHardBouncesRate(true); ?>%</td>
    </tr>
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Soft bounce'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getSoftBouncesCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Soft bounce rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getSoftBouncesRate(true); ?>%</td>
    </tr>
    
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Unsubscribe'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getUnsubscribesCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Unsubscribe rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getUnsubscribesRate(true); ?>%</td>
    </tr>
    
    <?php if ($campaign->option->url_tracking == CampaignOption::TEXT_YES) { ?>
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Click through rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getClicksThroughRate(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Total urls for tracking'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getTrackingUrlsCount(true); ?></td>
    </tr>
    <tr style="background:ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Unique clicks'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getUniqueClicksCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'Unique clicks rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getUniqueClicksRate(true); ?>%</td>
    </tr>
    <tr style="background:#ffffff">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'All clicks'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getClicksCount(true); ?></td>
    </tr>
    <tr style="background:#eeeeee">
        <td style="padding:5px;"><?php echo t('campaign_reports', 'All clicks rate'); ?></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"><?php echo $campaign->getStats()->getClicksRate(true); ?>%</td>
    </tr>
    <tr style="background:#ffffff">
        <td style="padding:5px;"></td>
        <td style="padding:5px;text-align: right; font-weight:bold;"></td>
    </tr>
    <?php } ?>
</table>

<br /><br />
<?php echo t('campaign_reports', 'Please note, you can view the full campaign reports by clicking on the link below'); ?><br />
<?php $url = $optionUrl->getCustomerUrl('campaigns/' . $campaign->campaign_uid . '/overview'); ?>
<a href="<?php echo $url; ?>"><?php echo $url; ?></a>

<br /><br />
<?php echo t('campaign_reports', 'The web version of this campaign is located at:'); ?><br />
<?php $url = $optionUrl->getFrontendUrl('campaigns/' . $campaign->campaign_uid); ?>
<a href="<?php echo $url; ?>"><?php echo $url; ?></a>