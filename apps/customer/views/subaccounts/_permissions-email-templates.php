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

/** @var OptionCustomerSubaccountPermissionsEmailTemplates|null $emailTemplatesPermissions */
$emailTemplatesPermissions = $controller->getData('emailTemplatesPermissions');

if (empty($emailTemplatesPermissions)) {
    return;
}

?>
<div class="col-lg-3">
    <div class="form-group">
		<?php echo $form->labelEx($emailTemplatesPermissions, 'manage'); ?>
		<?php echo $form->dropDownList($emailTemplatesPermissions, 'manage', $emailTemplatesPermissions->getYesNoOptions(), $emailTemplatesPermissions->fieldDecorator->getHtmlOptions('manage')); ?>
		<?php echo $form->error($emailTemplatesPermissions, 'manage'); ?>
    </div>
</div>