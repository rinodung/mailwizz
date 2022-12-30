<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link http://www.mailwizz.com/
 * @copyright MailWizz EMA (http://www.mailwizz.com)
 * @license http://www.mailwizz.com/license/
 */

/** @var ExtensionController $controller */
$controller = controller();

?>
<ul class="nav nav-tabs" style="border-bottom: 0px;">
    <li class="<?php echo $controller->getAction()->getId() == 'index' ? 'active' : 'inactive'; ?>">
        <a href="<?php echo $controller->getExtension()->createUrl('emailable/index'); ?>">
            <?php echo t('app', 'Common'); ?>
        </a>
    </li>
</ul>
