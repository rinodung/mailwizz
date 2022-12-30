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
 * @since 1.3.4.4
 */

/** @var Controller $controller */
$controller = controller();

?>
<ul class="nav nav-tabs" style="border-bottom: 0px;">
    <li class="<?php echo (string)$controller->getAction()->getId() == 'monetization' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/monetization'); ?>">
            <?php echo t('settings', 'Monetization'); ?>
        </a>
    </li>
    <li class="<?php echo (string)$controller->getAction()->getId() == 'monetization_orders' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/monetization_orders'); ?>">
            <?php echo t('settings', 'Orders'); ?>
        </a>
    </li>
    <li class="<?php echo (string)$controller->getAction()->getId() == 'monetization_invoices' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/monetization_invoices'); ?>">
            <?php echo t('settings', 'Invoices'); ?>
        </a>
    </li>
</ul>