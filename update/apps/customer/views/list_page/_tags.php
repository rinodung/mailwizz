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

/** @var Controller $controller */
$controller = controller();

/** @var array $tags */
$tags = (array)$controller->getData('tags');

?>
<div>
    <label><?php echo t('lists', 'Available tags:'); ?></label>
    <?php foreach ($tags as $tag) { ?>
    <a href="javascript:;" class="btn btn-xs btn-primary btn-flat" data-tag-name="<?php echo html_encode($tag['tag']); ?>">
        <?php echo html_encode($tag['tag']); ?>
    </a>
    <?php } ?>
</div>