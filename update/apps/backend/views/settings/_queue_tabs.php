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
 * @since 1.3.5
 */

/** @var Controller $controller */
$controller = controller();

?>
<ul class="nav nav-tabs" style="border-bottom: 0px;">
    <li class="<?php echo (string)$controller->getAction()->getId() == 'redis_queue' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo createUrl('settings/redis_queue'); ?>">
            <?php echo t('settings', 'Redis'); ?>
        </a>
    </li>
</ul>