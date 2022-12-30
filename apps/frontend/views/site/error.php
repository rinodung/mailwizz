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

/** @var int $code */
$code = (int)$controller->getData('code');

/** @var string $message */
$message = (string)$controller->getData('message');

?>

<div class="box box-primary borderless">
    <div class="box-heading"><h3 class="box-title"><?php echo t('app', 'Error {code}!', ['{code}' => (int)$code]); ?></h3></div>
    <div class="box-body">
        <p class="info"><?php echo html_encode((string)$message); ?></p>
    </div>
    <div class="box-footer">
        <div class="pull-right">
            <a href="<?php echo createUrl('site/index'); ?>" class="btn btn-default"> <i class="glyphicon glyphicon-circle-arrow-left"></i> <?php echo t('app', 'Back'); ?></a>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
</div>
