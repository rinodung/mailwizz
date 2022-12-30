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
 * @since 1.3.4.3
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Lists $list */
$list = $controller->getData('list');

/** @var string $fromText */
$fromText = (string)$controller->getData('fromText');

/** @var string $jsonAttributes */
$jsonAttributes = (string)$controller->getData('jsonAttributes');

?>

<div class="callout callout-info">
    <?php echo $fromText; ?>
</div>

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('glyphicon-share') . $pageHeading; ?> 
            </h3>
        </div>
        <div class="pull-right">
            <?php echo CHtml::link(t('lists', 'Back to tools'), ['list_tools/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Back')]); ?>
            <?php echo CHtml::link(t('lists', 'Back to list overview'), ['lists/overview', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Back')]); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body" id="copy-list-subscribers-box" data-attributes='<?php echo $jsonAttributes; ?>'>
        <span class="counters">
            <?php echo t('list_import', 'From a total of {total} subscribers, so far {totalProcessed} have been processed, {successfullyProcessed} successfully and {errorProcessing} with errors. {percentage} completed.', [
                '{total}'                   => '<span class="total" data-bind="text: total">0</span>',
                '{totalProcessed}'          => '<span class="total-processed" data-bind="text: processedTotal">0</span>',
                '{successfullyProcessed}'   => '<span class="success" data-bind="text: processedSuccess">0</span>',
                '{errorProcessing}'         => '<span class="error" data-bind="text: processedError">0</span>',
                '{percentage}'              => '<span class="percentage" data-bind="text: percentage">0</span>%',
            ]); ?>
        </span>
        <div class="progress progress-striped active">
            <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-bind="style: {width: widthPercentage()}">
                <span class="sr-only"><span data-bind="text: percentage">0</span>% <?php echo t('app', 'Complete'); ?></span>
            </div>
        </div>
        <div class="alert alert-info log-info" data-bind="text: progressText">
        </div>
        <div class="log-errors"></div>
    </div>
    <div class="box-footer"></div>
</div>