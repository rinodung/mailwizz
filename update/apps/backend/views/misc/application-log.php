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
 * @since 1.3.3
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var string $applicationLog */
$applicationLog = (string)$controller->getData('applicationLog');

/** @var string $category */
$category = (string)$controller->getData('category');

?>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('glyphicon-file') . html_encode((string)$pageHeading); ?>
            </h3>
        </div>
        <div class="pull-right">
            <?php echo CHtml::form(); ?>
            <button type="submit" name="delete" value="1" class="btn btn-danger btn-flat delete-app-log" data-message="<?php echo t('app', 'Are you sure you want to remove the application log?'); ?>"><?php echo t('app', 'Delete'); ?></button>
            <?php echo CHtml::endForm(); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body">
        <ul class="nav nav-tabs" style="border-bottom: 0px;">
            <li class="<?php echo (string)$category === 'application' ? 'active' : ''; ?>">
                <a href="<?php echo createUrl('misc/application_log'); ?>"><?php echo t('settings', 'General'); ?></a>
            </li>
            <li class="<?php echo (string)$category === '404' ? 'active' : ''; ?>">
                <a href="<?php echo createUrl('misc/application_log', ['category' => '404']); ?>"><?php echo t('settings', 'Pages not found'); ?></a>
            </li>
        </ul>
        <textarea class="form-control" rows="30"><?php echo html_encode((string)$applicationLog); ?></textarea>  
    </div>
</div>