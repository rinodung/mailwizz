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

/** @var SettingsController $controller */
$controller = controller();

?>
<div>
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      <h4 class="modal-title"><?php echo t('settings', '.htaccess contents'); ?></h4>
    </div>
    <div class="modal-body">
        <div class="modal-message"></div>
        <?php echo CHtml::textArea('htaccess', $controller->getHtaccessContent(), ['rows' => 10, 'id' => 'htaccess', 'class' => 'form-control']); ?>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
      <button type="button" class="btn btn-primary btn-flat btn-write-htaccess" data-remote="<?php echo createUrl('settings/write_htaccess'); ?>"><?php echo t('settings', 'Write htaccess'); ?></button>
    </div>
</div>