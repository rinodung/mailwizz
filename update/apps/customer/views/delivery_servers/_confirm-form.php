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

/** @var Controller $controller */
$controller = controller();

/** @var DeliveryServer $server */
$server = $controller->getData('server');

?>

<?php
if (!$server->getIsNewRecord() && $server->status === DeliveryServer::STATUS_INACTIVE) {
    /** @var CActiveForm $form */
    $form = $controller->beginWidget('CActiveForm', [
        'action'    => createUrl('delivery_servers/validate', ['id' => $server->server_id]),
        'id'        => $server->getModelName() . '-form',
    ]); ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <h3 class="box-title">
                <?php echo CHtml::link(IconHelper::make('info'), '#page-info-validate', ['class' => 'btn btn-primary btn-xs btn-flat', 'title' => t('app', 'Info'), 'data-toggle' => 'modal']); ?>
                <?php echo t('servers', 'Validate this server'); ?>
            </h3>
        </div>
        <div class="box-body">
            <div class="form-group">
                <?php echo CHtml::label(t('servers', 'The email address where the validation email will be sent.'), 'email', []); ?>
                <?php echo CHtml::emailField('email', '', ['class' => 'form-control', 'placeholder' => t('servers', 'me@domain.com')]); ?>
            </div>
        </div>
        <div class="box-footer">
            <div class="pull-right">
                <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('fa-send') . '&nbsp;' . t('servers', 'Validate server'); ?></button>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
    </div>
    <?php $controller->endWidget(); ?>
    <hr />
    <!-- modals -->
    <div class="modal modal-info fade" id="page-info-validate" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><?php echo IconHelper::make('info') . t('app', 'Info'); ?></h4>
                </div>
                <div class="modal-body">
                    <p>
                        <?php
                        $text = 'In order to start sending emails using this server, we need to make sure that it works, therefore 
                        we need to send you an email with a confirmation link. 
                        Once you confirm this server, this message will go away and the server will become active and ready to be used.<br />
                        Please note, for sending the confirmation email, we will use the information you provided when you created this server. 
                        <br />
                        If you think you need to adjust the options, please feel free to do it now and save your changes before going through the validation process.';
    echo t('servers', StringHelper::normalizeTranslationString($text)); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php
} ?>