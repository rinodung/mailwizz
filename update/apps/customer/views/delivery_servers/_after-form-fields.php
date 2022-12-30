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
 * @since 2.1.10
 */

/** @var Controller $controller */
$controller = controller();

/** @var DeliveryServer $server */
$server = $controller->getData('server');

/** @var CActiveForm $form */
$form = $controller->getData('form');

?>

<div class="row">
    <div class="col-lg-12">
        <?php $controller->renderPartial('_warmup-plan', compact('form')); ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <?php $controller->renderPartial('_policies', compact('form')); ?>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <?php $controller->renderPartial('_additional-headers'); ?>
    </div>
</div>
