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
<div class="callout callout-info">
    <?php echo t('settings', 'Please note that most of the customer settings will also be found in customer groups allowing you a fine grained control over your customers and their limits/permissions.'); ?>
</div>

<?php

// since 1.9.17
/** @var array<array> $tabs */
$tabs = hooks()->applyFilters('backend_controller_settings_customer_options_tabs', [
    [
        'id'     => 'customer_common',
        'active' => $controller->getAction()->getId() == 'customer_common',
        'url'    => createUrl('settings/customer_common'),
        'label'  => t('settings', 'Common'),
    ],
    [
        'id'     => 'customer_servers',
        'active' => $controller->getAction()->getId() == 'customer_servers',
        'url'    => createUrl('settings/customer_servers'),
        'label'  => t('settings', 'Servers'),
    ],
    [
        'id'     => 'customer_domains',
        'active' => $controller->getAction()->getId() == 'customer_domains',
        'url'    => createUrl('settings/customer_domains'),
        'label'  => t('settings', 'Domains'),
    ],
    [
        'id'     => 'customer_lists',
        'active' => $controller->getAction()->getId() == 'customer_lists',
        'url'    => createUrl('settings/customer_lists'),
        'label'  => t('settings', 'Lists'),
    ],
    [
        'id'     => 'customer_campaigns',
        'active' => $controller->getAction()->getId() == 'customer_campaigns',
        'url'    => createUrl('settings/customer_campaigns'),
        'label'  => t('settings', 'Campaigns'),
    ],
    [
        'id'     => 'customer_surveys',
        'active' => $controller->getAction()->getId() == 'customer_surveys',
        'url'    => createUrl('settings/customer_surveys'),
        'label'  => t('settings', 'Surveys'),
    ],
    [
        'id'     => 'customer_quota_counters',
        'active' => $controller->getAction()->getId() == 'customer_quota_counters',
        'url'    => createUrl('settings/customer_quota_counters'),
        'label'  => t('settings', 'Quota counters'),
    ],
    [
        'id'     => 'customer_sending',
        'active' => $controller->getAction()->getId() == 'customer_sending',
        'url'    => createUrl('settings/customer_sending'),
        'label'  => t('settings', 'Sending'),
    ],
    [
        'id'     => 'customer_cdn',
        'active' => $controller->getAction()->getId() == 'customer_cdn',
        'url'    => createUrl('settings/customer_cdn'),
        'label'  => t('settings', 'CDN'),
    ],
    [
        'id'     => 'customer_registration',
        'active' => $controller->getAction()->getId() == 'customer_registration',
        'url'    => createUrl('settings/customer_registration'),
        'label'  => t('settings', 'Registration'),
    ],
    [
        'id'     => 'customer_api',
        'active' => $controller->getAction()->getId() == 'customer_api',
        'url'    => createUrl('settings/customer_api'),
        'label'  => t('settings', 'API'),
    ],
    [
        'id'     => 'customer_subaccounts',
        'active' => $controller->getAction()->getId() == 'customer_subaccounts',
        'url'    => createUrl('settings/customer_subaccounts'),
        'label'  => t('settings', 'Subaccounts'),
    ],
]);
?>
<ul class="nav nav-tabs" style="border-bottom: 0px;">
	<?php foreach ($tabs as $tab) {
    if (!isset($tab['id'], $tab['active'], $tab['url'], $tab['label'])) {
        continue;
    } ?>
        <li class="<?php echo (bool)$tab['active'] ? 'active' : 'inactive'; ?>">
            <a href="<?php echo html_encode((string)$tab['url']); ?>">
				<?php echo html_encode((string)$tab['label']); ?>
            </a>
        </li>
	<?php
} ?>
</ul>