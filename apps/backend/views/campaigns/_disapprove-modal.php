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
 * @since 2.0.18
 */

/** @var Controller $controller */
$controller = controller();

?>
<div class="modal modal-info fade" id="disapprove-campaign-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"><?php echo IconHelper::make('glyphicon-remove-circle') . t('campaigns', 'Disapprove campaign'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="form-group">
                        <textarea class="form-control" id="disapprove-message" name="disapprove-message" rows="5" placeholder="<?php echo html_encode(t('app', 'Enter the message that explains why you disapprove this campaign')); ?>"></textarea>
                        <div class="errorMessage" style="display: none"><?php echo html_encode(t('app', 'Please specify a message that is at least {min} characters in length', ['{min}' => 5])); ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
                <button type="button" class="btn btn-danger btn-flat btn-disapprove-campaign no-spin"><?php echo IconHelper::make('glyphicon-remove-circle') . '&nbsp;' . t('app', 'Disapprove campaign'); ?></button>
            </div>
        </div>
    </div>
</div>