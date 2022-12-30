<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/** @var ExtensionController $extensionController */
$extensionController = controller();

$controller = $extensionController->getId();
$action     = $extensionController->getAction();
?>
<ul class="nav nav-tabs" style="border-bottom: 0px;">
    <li class="<?php echo $controller == $extensionController->getExtension()->getRoute('settings') ? 'active' : 'inactive'; ?>">
        <a href="<?php echo $extensionController->getExtension()->createUrl('settings/index'); ?>">
            <?php echo $extensionController->getExtension()->t('Common'); ?>
        </a>
    </li>
    <li class="<?php echo stripos($controller, $extensionController->getExtension()->getRoute('slideshow')) === 0 ? 'active' : 'inactive'; ?>">
        <a href="<?php echo $extensionController->getExtension()->createUrl('slideshows/index'); ?>">
            <?php echo $extensionController->getExtension()->t('Slideshows'); ?>
        </a>
    </li>
</ul>
