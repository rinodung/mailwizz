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

/** @var Campaign[] $campaigns */
$campaigns = $controller->getData('campaigns');

?>
<div class="sticky-cols-wrapper">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="table-responsive">
            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th class="sticky-col campaign-name"><?php echo t('campaign_reports', 'Campaign'); ?></th>
                        <th><?php echo t('campaign_reports', 'Recipients'); ?></th>
    
                        <th><?php echo t('campaign_reports', 'Opens rate'); ?></th>
                        <th><?php echo t('campaign_reports', 'Unique opens'); ?></th>
                        <th><?php echo t('campaign_reports', 'Total opens'); ?></th>
    
                        <th><?php echo t('campaign_reports', 'Clicks rate'); ?></th>
                        <th><?php echo t('campaign_reports', 'Unique clicks'); ?></th>
                        <th><?php echo t('campaign_reports', 'Total clicks'); ?></th>
                        
                        <th><?php echo t('campaign_reports', 'Bounce rate'); ?></th>
                        <th><?php echo t('campaign_reports', 'Hard bounces'); ?></th>
                        <th><?php echo t('campaign_reports', 'Soft bounces'); ?></th>
                        <th><?php echo t('campaign_reports', 'Internal bounces'); ?></th>
                        
                        <th><?php echo t('campaign_reports', 'Unsubscribe rate'); ?></th>
                        <th><?php echo t('campaign_reports', 'Unsubscribes'); ?></th>
                        
                        <th><?php echo t('campaign_reports', 'Complaints rate'); ?></th>
                        <th><?php echo t('campaign_reports', 'Complaints'); ?></th>

                        <th><?php echo t('campaign_reports', 'Status'); ?></th>
                        <th><?php echo t('campaign_reports', 'Total delivery time'); ?></th>
                    </tr>
                </thead>
                <tbody>
		        <?php foreach ($campaigns as $campaign) { ?>
                    <tr>
                        <td class="sticky-col campaign-name"><?php echo html_encode((string)$campaign->name); ?></td>
                        <td><?php echo $campaign->getStats()->getProcessedCount(true); ?></td>

                        <td><?php echo $campaign->getStats()->getUniqueOpensRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getUniqueOpensCount(true); ?> / <?php echo $campaign->getStats()->getUniqueOpensRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getOpensCount(true); ?> / <?php echo $campaign->getStats()->getOpensRate(true); ?>%</td>

                        <td><?php echo $campaign->getStats()->getUniqueClicksRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getUniqueClicksCount(true); ?> / <?php echo $campaign->getStats()->getUniqueClicksRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getClicksCount(true); ?> / <?php echo $campaign->getStats()->getClicksRate(true); ?>%</td>

                        <td><?php echo $campaign->getStats()->getBouncesRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getHardBouncesCount(true); ?> / <?php echo $campaign->getStats()->getHardBouncesRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getSoftBouncesCount(true); ?> / <?php echo $campaign->getStats()->getSoftBouncesRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getInternalBouncesCount(true); ?> / <?php echo $campaign->getStats()->getInternalBouncesRate(true); ?>%</td>
                        
                        <td><?php echo $campaign->getStats()->getUnsubscribesRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getUnsubscribesCount(true); ?></td>

                        <td><?php echo $campaign->getStats()->getComplaintsRate(true); ?>%</td>
                        <td><?php echo $campaign->getStats()->getComplaintsCount(true); ?></td>

                        <td><?php echo $campaign->getStatusName(); ?></td>
                        <td><?php echo $campaign->getTotalDeliveryTime(); ?></td>
                    </tr>
		        <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>