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
 * @since 2.1.6
 */

?>

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><i class="fa fa-bar-chart-o" aria-hidden="true"></i><?php echo t('lists', 'Tracking stats averages'); ?></h3>
        </div>
    </div>
    <div class="box-body">
        <div class="row boxes-mw-wrapper">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo html_encode(formatter()->formatNumber($opensAverage)); ?></h3>
                            <p><?php echo t('lists', 'Opens'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo html_encode(formatter()->formatNumber($clicksAverage)); ?></h3>
                            <p><?php echo t('lists', 'Clicks'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo html_encode(formatter()->formatNumber($unsubscribesAverage)); ?></h3>
                            <p><?php echo t('lists', 'Unsubscribes'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo html_encode(formatter()->formatNumber($complaintsAverage)); ?></h3>
                            <p><?php echo t('lists', 'Complaints'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h3><?php echo html_encode(formatter()->formatNumber($bouncesAverage)); ?></h3>
                            <p><?php echo t('lists', 'Bounces'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
