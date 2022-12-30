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

/** @var string $message */
$message = (string)$controller->getData('message');

/** @var int $code */
$code = $controller->getData('code');

?>


<div class="login-box-body">
    <p class="login-box-msg">
    <h3><?php echo t('app', 'Error {code}!', ['{code}' => (int)$code]); ?></h3>
    </p>
    <div class="row">
        <div class="col-lg-12">
            <h5><?php echo html_encode($message); ?></h5>
        </div>
    </div>
    <hr />
    <div class="row">
        <div class="col-lg-12">
            <div class="pull-right">
                <a href="javascript:history.back(-1);" class="btn btn-default"> <i class="glyphicon glyphicon-circle-arrow-left"></i> <?php echo t('app', 'Back'); ?></a>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
    </div>
</div>