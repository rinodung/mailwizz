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
 * @since 1.3.4
 */

/** @var Controller $controller */
$controller = controller();

?>
<ul class="nav nav-tabs" style="border-bottom: 0px;">
    <li class="<?php echo (string)$controller->getAction()->getId() == 'campaign_attachments' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/campaign_attachments'); ?>">
            <?php echo t('settings', 'Attachments'); ?>
        </a>
    </li>
    <li class="<?php echo (string)$controller->getAction()->getId() == 'campaign_template_tags' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/campaign_template_tags'); ?>">
            <?php echo t('settings', 'Template tags'); ?>
        </a>
    </li>
    <li class="<?php echo (string)$controller->getAction()->getId() == 'campaign_exclude_ips_from_tracking' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/campaign_exclude_ips_from_tracking'); ?>">
            <?php echo t('settings', 'Exclude IPs from tracking'); ?>
        </a>
    </li>
    <li class="<?php echo (string)$controller->getAction()->getId() == 'campaign_blacklist_words' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/campaign_blacklist_words'); ?>">
            <?php echo t('settings', 'Blacklist words'); ?>
        </a>
    </li>
    <li class="<?php echo (string)$controller->getAction()->getId() == 'campaign_template_engine' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/campaign_template_engine'); ?>">
            <?php echo t('settings', 'Template engine'); ?>
        </a>
    </li>
    <li class="<?php echo (string)$controller->getAction()->getId() == 'campaign_webhooks' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/campaign_webhooks'); ?>">
			<?php echo t('settings', 'Webhooks'); ?>
        </a>
    </li>
    <li class="<?php echo (string)$controller->getAction()->getId() == 'campaign_misc' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/campaign_misc'); ?>">
            <?php echo t('settings', 'Miscellaneous'); ?>
        </a>
    </li>
</ul>
