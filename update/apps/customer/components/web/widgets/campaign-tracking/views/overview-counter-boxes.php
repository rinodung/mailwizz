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
            <h3 class="box-title"><i class="fa fa-bar-chart-o" aria-hidden="true"></i><?php echo t('campaign_reports', 'Tracking stats'); ?></h3>
        </div>
        <div class="pull-right">
            <?php if (!empty($canExportStats) && isset($this->getController()->campaignReportsExportController)) {?>
                <a href="<?php echo createUrl($this->getController()->campaignReportsExportController . '/basic', ['campaign_uid' => $campaign->campaign_uid]); ?>" target="_blank" class="btn btn-primary btn-flat"><?php echo IconHelper::make('export') . t('campaign_reports', 'Export basic stats'); ?></a>
            <?php } ?>
        </div>
    </div>
    <div class="box-body">
        <div class="row boxes-mw-wrapper">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo $opensLink; ?></h3>
                            <p><?php echo t('campaign_reports', 'Opens'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo $clicksLink; ?></h3>
                            <p><?php echo t('campaign_reports', 'Clicks'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo $unsubscribesLink; ?></h3>
                            <p><?php echo t('campaign_reports', 'Unsubscribes'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo $complaintsLink; ?></h3>
                            <p><?php echo t('campaign_reports', 'Complaints'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo $bouncesLink; ?></h3>
                            <p><?php echo t('campaign_reports', 'Bounces'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>