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

?>
<div class="row">
	<div class="col-lg-12">
		<h4><?php echo t('customers', 'Permissions'); ?></h4>
		<div class="row">
			<?php foreach (['lists', 'campaigns', 'servers', 'surveys', 'api-keys', 'domains', 'email-templates', 'blacklists'] as $view) {
    $controller->renderPartial('_permissions-' . $view, [
                    'form' => $form,
                ]);
} ?>
		</div>
	</div>
</div>
                
