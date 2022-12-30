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

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var array $tabs */
$tabs = $controller->getData('tabs');

?>
<ul class="nav nav-tabs" style="border-bottom: 0px;">
    <?php foreach ($tabs as $tab) { ?>
        <li class="<?php echo (string)$tab['id'] === 'common' ? 'active' : ''; ?>">
            <a href="#tab-<?php echo html_encode((string)$tab['id']); ?>" data-toggle="tab"><?php echo html_encode((string)$tab['label']); ?></a>
        </li>
    <?php } ?>
</ul>

<div class="tab-content">
    <?php foreach ($tabs as $tab) { ?>
        <div class="tab-pane <?php echo (string)$tab['id'] === 'common' ? 'active' : ''; ?>" id="tab-<?php echo html_encode((string)$tab['id']); ?>">
            <?php $controller->renderPartial($tab['view'], ['model' => $tab['model'], 'form' => $form]); ?>
        </div>
    <?php } ?>
</div>
