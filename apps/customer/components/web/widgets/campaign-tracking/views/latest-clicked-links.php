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

<div class="box borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title"><i class="fa fa-link" aria-hidden="true"></i><?php echo t('campaign_reports', 'Latest clicked links'); ?></h3>
        </div>
        <div class="pull-right">
            <?php if ($this->showDetailLinks && isset($this->getController()->campaignReportsController)) { ?>
                <a href="<?php echo createUrl($this->getController()->campaignReportsController . '/click', ['campaign_uid' => $campaign->campaign_uid]); ?>" class="btn btn-primary btn-flat"><?php echo IconHelper::make('view') . t('campaign_reports', 'View all clicks'); ?></a>
                <a href="<?php echo createUrl($this->getController()->campaignReportsController . '/click', ['campaign_uid' => $campaign->campaign_uid, 'show' => 'latest']); ?>" class="btn btn-primary btn-flat"><?php echo IconHelper::make('view') . t('campaign_reports', 'View latest clicks'); ?></a>
            <?php } ?>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="box-dashboard">
                    <ul class="custom-list">
                        <?php foreach ($models as $model) { ?>
                            <li><span class="cl-span">
                                <a href="<?php echo html_encode(FilterVarHelper::urlAnyScheme((string)$model->url->destination) ? (string)$model->url->destination : 'javascript:'); ?>"<?php if (FilterVarHelper::urlAnyScheme((string)$model->url->destination)) { ?> target="_blank"<?php } ?>><?php echo html_encode((string)$model->url->destination); ?></a>
                            </span>
                                <span class="cl-span">
                                <?php echo t('campaign_reports', 'by {email} at {date}', [
                                    '{email}'   => $model->subscriber->getDisplayEmail(),
                                    '{date}'    => $model->dateAdded,
                                ]); ?>
                                </span>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
