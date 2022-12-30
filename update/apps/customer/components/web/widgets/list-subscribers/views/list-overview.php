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

/** @var string $createLink */
/** @var string $updateLink */
/** @var string $confirmedSubscribersCountLink */
/** @var string $subscribersCountLink */
/** @var string $segmentsCountLink */
/** @var string $customFieldsCountLink */
/** @var string $pagesCountLink */
/** @var string $formsLink */
/** @var string $toolsLink */

/** @var bool $canSegmentLists */
?>

<div class="box box-primary borderless">
    <div class="box-header" id="chatter-header">
        <div class="pull-left">
            <h3 class="box-title"><?php echo IconHelper::make('list'); ?> <?php echo t('lists', 'Overview'); ?></h3>
        </div>
        <div class="pull-right">
            <?php echo $createLink; ?>
            <?php echo $updateLink; ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <div class="row boxes-mw-wrapper">
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h6><?php echo $confirmedSubscribersCountLink; ?></h6>
                            <h3><?php echo $subscribersCountLink; ?></h3>
                            <p><?php echo t('list_subscribers', 'Subscribers'); ?></p>
                        </div>
                    </div>
                    <div class="icon">
                        <i class="ion ion-ios-people"></i>
                    </div>
                </div>
            </div>
            <?php if (!empty($canSegmentLists)) { ?>
                <div class="col-lg-2 col-xs-6">
                    <div class="small-box">
                        <div class="inner">
                            <div class="middle">
                                <h6>&nbsp;</h6>
                                <h3><?php echo $segmentsCountLink; ?></h3>
                                <p><?php echo t('list_segments', 'Segments'); ?></p>
                            </div>
                        </div>
                        <div class="icon">
                            <i class="ion ion-gear-b"></i>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h6>&nbsp;</h6>
                            <h3><?php echo $customFieldsCountLink; ?></h3>
                            <p><?php echo t('list_fields', 'Custom fields'); ?></p>
                        </div>
                    </div>
                    <div class="icon">
                        <i class="ion ion-android-list"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h6>&nbsp;</h6>
                            <h3><?php echo $pagesCountLink; ?></h3>
                            <p><?php echo t('list_pages', 'Pages'); ?></p>
                        </div>
                    </div>
                    <div class="icon">
                        <i class="ion ion-folder"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h6>&nbsp;</h6>
                            <h3><?php echo $formsLink; ?></h3>
                            <p><?php echo t('app', 'Tools'); ?></p>
                        </div>
                    </div>
                    <div class="icon">
                        <i class="ion ion-ios-photos"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xs-6">
                <div class="small-box">
                    <div class="inner">
                        <div class="middle">
                            <h6>&nbsp;</h6>
                            <h3><?php echo $toolsLink; ?></h3>
                            <p><?php echo t('lists', 'List tools'); ?></p>
                        </div>
                    </div>
                    <div class="icon">
                        <i class="ion ion-hammer"></i>
                    </div>
                </div>
            </div>

            <div class="clearfix"><!-- --></div>
        </div>
    </div>
</div>
