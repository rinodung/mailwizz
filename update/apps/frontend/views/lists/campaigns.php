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
 * @since 2.1.8
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var string $pageSubHeading */
$pageSubHeading = (string)$controller->getData('pageSubHeading');

/** @var Lists $list */
$list = $controller->getData('list');

/** @var Campaign[] $latestCampaigns */
$latestCampaigns = $controller->getData('latestCampaigns');

?>

<div class="row">
    <div class="col-lg-6 col-lg-push-3 col-md-6 col-md-push-3 col-sm-12">
        <div class="box box-primary borderless">
            <div class="box-header">
                <h3 class="box-title"><?php echo $pageHeading; ?></h3>
	            <?php if (!empty($pageSubHeading)) { ?>
                    <h6>
			            <?php echo t('lists', 'from {company}', [
                            '{company}' => html_encode($pageSubHeading),
                        ]); ?>
                    </h6>
	            <?php } ?>
                <div>
                    <a class="btn btn-primary btn-flat" href="<?php echo apps()->getAppUrl('frontend', 'lists/' . $list->list_uid . '/subscribe', true); ?>" target="_blank"><?php echo t('lists', 'Subscribe'); ?></a>
                </div>
            </div>
            <div class="box-body">
                <ul style="padding-left: 0px">
                    <?php foreach ($latestCampaigns as $campaign) { ?>
                        <li class="list-group-item">
                            <?php echo sprintf('%s - %s', html_encode($campaign->getSendAt()), CHtml::link($campaign->name, sprintf('%scampaigns/%s', (string)options()->get('system.urls.frontend_absolute_url', ''), $campaign->campaign_uid), ['target' => '_blank'])); ?>
                        </li>
                    <?php }?>
                </ul>
            </div>
        </div
    </div>
</div>
