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

/** @var OptionCustomerSubaccountPermissionsLists|null $listsPermissions */
$listsPermissions = $controller->getData('listsPermissions');

if (empty($listsPermissions)) {
    return;
}

?>
<div class="col-lg-3">
    <div class="form-group">
		<?php echo $form->labelEx($listsPermissions, 'manage'); ?>
		<?php echo $form->dropDownList($listsPermissions, 'manage', $listsPermissions->getYesNoOptions(), $listsPermissions->fieldDecorator->getHtmlOptions('manage')); ?>
		<?php echo $form->error($listsPermissions, 'manage'); ?>
    </div>
</div>