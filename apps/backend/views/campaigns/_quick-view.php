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
 * @since 1.9.17
 */

/** @var Controller $controller */
$controller = controller();

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var CampaignAbtest|null $abTest */
$abTest = $controller->getData('abTest');

?>
<div class="">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="list-wrapper">
	        <?php if (!empty($abTest) && !empty($abTest->activeSubjects)) { ?>
                <div class="mouse-scroll-indicator top-right no-border">
                    <span class="sm"></span>
                </div>
	        <?php } ?>
            <ul class="custom-list">
                <li><span class="cl-span"><?php echo t('campaigns', 'Type'); ?></span><span class="cl-span"><?php echo ucfirst(t('campaigns', html_encode((string)$campaign->type))); ?></span></li>
                <li><span class="cl-span"><?php echo t('campaigns', 'Status'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStatusName()); ?></span></li>
                <li><span class="cl-span"><?php echo t('campaigns', 'Name'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->name); ?></span></li>
                <li><span class="cl-span"><?php echo t('campaigns', 'List/Segment'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getListSegmentName()); ?></span></li>
                <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('subject')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->subject); ?></span></li>

                <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('from_name')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->from_name); ?></span></li>
                <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('from_email')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->from_email); ?></span></li>
                <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('reply_to')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->reply_to); ?></span></li>
                <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('to_name')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->to_name); ?></span></li>

                <?php if ($campaign->getDeliveryServersNames()) { ?>
                    <li><span class="cl-span"><?php echo t('campaigns', 'Delivery servers'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getDeliveryServersNames()); ?></span></li>
                <?php } ?>

                <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('date_added')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->dateAdded); ?></span></li>
                <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('send_at')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getSendAt()); ?></span></li>

				<?php if ($campaign->getIsRegular()) { ?>
                    <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('lastOpen')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getLastOpen()); ?></span></li>
                    <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('started_at')); ?></span><span class="cl-span"><?php echo (string)$campaign->getStartedAt() ? html_encode((string)$campaign->getStartedAt()) : html_encode((string)$campaign->getSendAt()); ?></span></li>
                    <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('finished_at')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getFinishedAt()); ?></span></li>
				<?php } ?>
				<?php if ($campaign->getIsAutoresponder()) { ?>
                    <li><span class="cl-span"><?php echo t('campaigns', 'Autoresponder event'); ?></span><span class="cl-span"><?php echo t('campaigns', html_encode((string)$campaign->option->autoresponder_event)); ?></span></li>
                    <li><span class="cl-span"><?php echo t('campaigns', 'Autoresponder time unit'); ?></span><span class="cl-span"><?php echo ucfirst(t('app', html_encode((string)$campaign->option->autoresponder_time_unit))); ?></span></li>
                    <li><span class="cl-span"><?php echo t('campaigns', 'Autoresponder time value'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->option->autoresponder_time_value); ?></span></li>
					<?php if ($arTimeMinHourMinute = $campaign->option->getAutoresponderTimeMinHourMinute()) { ?>
                        <li><span class="cl-span"><?php echo t('campaigns', 'Send only at/after this time'); ?></span><span class="cl-span"><?php echo html_encode((string)$arTimeMinHourMinute); ?> (UTC 00:00)</span></li>
					<?php } ?>
                    <li><span class="cl-span"><?php echo t('campaigns', 'Include imported subscribers'); ?></span><span class="cl-span"><?php echo ucfirst(t('app', html_encode((string)$campaign->option->autoresponder_include_imported))); ?></span></li>
                    <li><span class="cl-span"><?php echo t('campaigns', 'Include current subscribers'); ?></span><span class="cl-span"><?php echo ucfirst(t('app', html_encode((string)$campaign->option->autoresponder_include_current))); ?></span></li>
				<?php } ?>
            </ul>

	        <?php if (!empty($abTest) && !empty($abTest->activeSubjects)) { ?>
                <hr />
                <h5><?php echo t('campaigns', 'A/B Test Subjects'); ?></h5>
                <ul class="custom-list">
			        <?php foreach ($abTest->activeSubjects as $subject) { ?>
                        <li>
                            <span class="cl-span">
                                <?php echo html_encode($subject->subject); ?>
	                            <?php if ($subject->getIsWinner()) { ?><span class="badge bg-blue"><?php echo t('campaigns', 'Winner'); ?></span><?php } ?>
                            </span>
                            <span class="cl-span"><?php echo t('campaigns', '{opens_count} opens', ['{opens_count}' => (int)$subject->opens_count]); ?></span>
                        </li>
			        <?php } ?>
                </ul>
	        <?php } ?>
            
        </div>
    </div>
</div>
