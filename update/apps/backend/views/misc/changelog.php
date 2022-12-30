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
 * @since 1.5.2
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var string $changeLog */
$changeLog = (string)$controller->getData('changeLog');

?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('glyphicon-file') . html_encode((string)$pageHeading); ?>
            </h3>
        </div>
        <div class="pull-right">
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <textarea class="form-control" rows="30"><?php echo html_encode((string)$changeLog); ?></textarea>  
    </div>
</div>