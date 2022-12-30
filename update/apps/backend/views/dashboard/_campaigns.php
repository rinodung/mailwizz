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
 * @since 1.3.8.7
 */

/** @var Controller $controller */
$controller = controller();

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

/** @var array $campaignsList */
$campaignsList = $controller->getData('campaignsList');

?>
<hr />

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><?php echo IconHelper::make('fa-envelope'); ?> <?php echo t('lists', 'Recently sent campaigns'); ?></h3>
        </div>
        <div class="pull-right">
            <?php echo CHtml::dropDownList('campaign_id', $campaign->campaign_id, $campaignsList); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard" style="padding-bottom: 0px">
                    <div class="progress-box" style="padding-bottom: 0px">
                        <div class="info">
                            <span class="name"><?php echo t('campaign_reports', 'Recipients'); ?></span><span class="number"><?php echo CHtml::link($campaign->getStats()->getProcessedCount(true), 'javascript:;'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaigns', 'List/Segment'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getListSegmentName()); ?></span></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo html_encode((string)$campaign->getAttributeLabel('subject')); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->subject); ?></span></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <div class="progress-box">
                        <div class="info">
                            <span class="name"><?php echo t('campaign_reports', 'Clicks rate'); ?></span><span class="number"><?php echo html_encode((string)$campaign->getStats()->getUniqueClicksRate(true)); ?>%</span>
                        </div>
                        <div class="bar"><div class="progress" style="width: <?php echo StringHelper::asPercentFloat($campaign->getStats()->getClicksRate(true)); ?>"></div></div>
                    </div>
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Unique clicks'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getUniqueClicksCount(true)); ?></span></li>
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Total clicks / Total clicks rate'); ?></span></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getClicksCount(true)); ?> / <?php echo html_encode((string)$campaign->getStats()->getClicksRate(true)); ?>%</span></li>
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Clicks to opens rate'); ?></span></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getClicksToOpensRate(true)); ?>%</span></li>
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Click through rate'); ?></span></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getClicksThroughRate(true)); ?>%</span></li>
                        <?php if ($campaign->getStats()->getIndustryClicksRate() && $campaign->getStats()->getIndustry()) { ?>
                            <li><span class="cl-span"><?php echo t('campaign_reports', 'Industry avg({industry})', ['{industry}' => CHtml::link(html_encode((string)$campaign->getStats()->getIndustry()->name), apps()->isAppName('customer') ? ['account/company'] : 'javascript:;')]); ?></span> <span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getIndustryClicksRate(true)); ?>%</span></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <div class="progress-box">
                        <div class="info">
                            <span class="name"><?php echo t('campaign_reports', 'Opens rate'); ?></span><span class="number"><?php echo html_encode((string)$campaign->getStats()->getUniqueOpensRate(true)); ?>%</span>
                        </div>
                        <div class="bar"><div class="progress" style="width: <?php echo html_encode(StringHelper::asPercentFloat($campaign->getStats()->getUniqueOpensRate(true))); ?>"></div></div>
                    </div>
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Unique opens'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getUniqueOpensCount(true)); ?></span></li>
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Total opens / Total opens rate'); ?></span></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getOpensCount(true)); ?> / <?php echo html_encode((string)$campaign->getStats()->getOpensRate(true)); ?>%</span></li>
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Opens to clicks rate'); ?></span></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getOpensToClicksRate(true)); ?>%</span></li>
                        <?php if ($campaign->getStats()->getIndustryOpensRate() && $campaign->getStats()->getIndustry()) { ?>
                            <li><span class="cl-span"><?php echo t('campaign_reports', 'Industry avg({industry})', ['{industry}' => CHtml::link($campaign->getStats()->getIndustry()->name, apps()->isAppName('customer') ? ['account/company'] : 'javascript:;')]); ?></span> <span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getIndustryOpensRate(true)); ?>%</span></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <div class="clearfix hidden-lg"></div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <div class="progress-box">
                        <div class="info">
                            <span class="name"><?php echo t('campaign_reports', 'Bounce rate'); ?></span><span class="number"><?php echo html_encode((string)$campaign->getStats()->getBouncesRate(true)); ?>%</span>
                        </div>
                        <div class="bar"><div class="progress" style="width: <?php echo html_encode(StringHelper::asPercentFloat($campaign->getStats()->getBouncesRate(true))); ?>"></div></div>
                    </div>
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Hard bounces'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getHardBouncesCount(true)); ?></span></li>
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Hard bounces rate'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getHardBouncesRate(true)); ?>%</span></li>
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Soft bounces'); ?></span></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getSoftBouncesCount(true)); ?></span></li>
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Soft bounces rate'); ?></span></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getSoftBouncesRate(true)); ?>%</span></li>
                    </ul>
                </div>
            </div>
            <div class="clearfix hidden-md hidden-sm"></div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <div class="progress-box">
                        <div class="info">
                            <span class="name"><?php echo t('campaign_reports', 'Unsubscribe rate'); ?></span><span class="number"><?php echo html_encode((string)$campaign->getStats()->getUnsubscribesRate(true)); ?>%</span>
                        </div>
                        <div class="bar"><div class="progress" style="width: <?php echo html_encode(StringHelper::asPercentFloat($campaign->getStats()->getUnsubscribesRate(true))); ?>"></div></div>
                    </div>
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Unsubscribes'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getUnsubscribesCount(true)); ?></span></li>
                    </ul>
                </div>
            </div>
            <div class="clearfix hidden-lg"></div>
            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
                <div class="box-dashboard">
                    <div class="progress-box">
                        <div class="info">
                            <span class="name"><?php echo t('campaign_reports', 'Complaints rate'); ?></span><span class="number"><?php echo html_encode((string)$campaign->getStats()->getComplaintsRate(true)); ?>%</span>
                        </div>
                        <div class="bar"><div class="progress" style="width: <?php echo html_encode(StringHelper::asPercentFloat($campaign->getStats()->getComplaintsRate(true))); ?>"></div></div>
                    </div>
                    <ul class="custom-list">
                        <li><span class="cl-span"><?php echo t('campaign_reports', 'Complaints'); ?></span><span class="cl-span"><?php echo html_encode((string)$campaign->getStats()->getComplaintsCount(true)); ?></span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            <a href="<?php echo createUrl('campaigns/overview', ['campaign_uid' => html_encode((string)$campaign->campaign_uid)]); ?>" class="btn btn-primary btn-flat" title="<?php echo t('campaign_reports', 'View campaign reports'); ?>"><?php echo IconHelper::make('view') . t('campaign_reports', 'View campaign reports'); ?></a>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>