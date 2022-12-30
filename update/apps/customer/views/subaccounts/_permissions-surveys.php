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

/** @var OptionCustomerSubaccountPermissionsSurveys|null $surveysPermissions */
$surveysPermissions = $controller->getData('surveysPermissions');

if (empty($surveysPermissions)) {
    return;
}

?>
<div class="col-lg-3">
    <div class="form-group">
		<?php echo $form->labelEx($surveysPermissions, 'manage'); ?>
		<?php echo $form->dropDownList($surveysPermissions, 'manage', $surveysPermissions->getYesNoOptions(), $surveysPermissions->fieldDecorator->getHtmlOptions('manage')); ?>
		<?php echo $form->error($surveysPermissions, 'manage'); ?>
    </div>
</div>