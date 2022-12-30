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
 * @since 2.0.0
 */

/** @var Controller $controller */
$controller = controller();

/** @var CActiveForm $form */
$form = $controller->getData('form');

/** @var OptionCustomerSubaccountPermissionsBlacklists|null $blacklistsPermissions */
$blacklistsPermissions = $controller->getData('blacklistsPermissions');

if (empty($blacklistsPermissions)) {
    return;
}

?>
<div class="col-lg-3">
    <div class="form-group">
		<?php echo $form->labelEx($blacklistsPermissions, 'manage'); ?>
		<?php echo $form->dropDownList($blacklistsPermissions, 'manage', $blacklistsPermissions->getYesNoOptions(), $blacklistsPermissions->fieldDecorator->getHtmlOptions('manage')); ?>
		<?php echo $form->error($blacklistsPermissions, 'manage'); ?>
    </div>
</div>