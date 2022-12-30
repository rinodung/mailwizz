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
 * @since 1.3.4.3
 */


/** @var Controller $controller */
$controller = controller();

/** @var array $items */
$items = (array)$controller->getData('items');

?>
<?php foreach ($items as $value) { ?>
<li>
    <a href="<?php echo html_encode((string)$value['url']); ?>">
        <h5><?php echo html_encode((string)$value['heading']); ?> <small class="pull-right percentage"><?php echo html_encode((string)$value['percent']); ?>% <?php echo html_encode((string)$value['used']); ?>/<?php echo (string)$value['allowed']; ?></small></h5>
        <div class="progress xs">
            <div class="progress-bar progress-bar-<?php echo html_encode((string)$value['bar_color']); ?>" style="width: <?php echo html_encode((string)$value['percent']); ?>%" role="progressbar" aria-valuenow="<?php echo html_encode((string)$value['percent']); ?>" aria-valuemin="0" aria-valuemax="100">
                <span class="sr-only"><?php echo html_encode((string)$value['percent']); ?>% <?php echo t('app', 'Complete'); ?></span>
            </div>
        </div>
    </a>
</li>
<?php } ?>
