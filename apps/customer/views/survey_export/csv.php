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
 * @since 2.0
 */

/** @var Controller $controller */
$controller = controller();

/** @var Survey $survey */
$survey = $controller->getData('survey');

/** @var ListCsvExport $export */
$export = $controller->getData('export');

/** @var int $pause */
$pause = (int)$controller->getData('pause');

/** @var int $processAtOnce */
$processAtOnce = (int)$controller->getData('processAtOnce');

?>
<div class="callout callout-info">
    <?php
    $text = 'The export process will start shortly. <br />
    While the export is running it is recommended you leave this page as it is and wait for the export to finish.<br />
    The exporter runs in batches of {respondersPerBatch} responders per file with a pause of {pause} seconds between the batches, therefore 
    the export process might take a while depending on your survey size and number of responders to export.<br />
    This is a tedious process, so sit tight and wait for it to finish.';
    echo t('survey_export', StringHelper::normalizeTranslationString($text), [
        '{respondersPerBatch}' => $processAtOnce,
        '{pause}'               => $pause,
    ]);
    ?>
</div>
<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('export') . t('survey_export', 'CSV export progress'); ?>
            </h3>
        </div>
        <div class="pull-right">
            <?php echo CHtml::link(IconHelper::make('back') . t('survey_export', 'Back to export options'), ['survey_export/index', 'survey_uid' => $survey->survey_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Back to export options')]); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body" id="csv-export" data-pause="<?php echo (int)$pause; ?>" data-iframe="<?php echo createUrl('list_import/ping'); ?>" data-attributes='<?php echo json_encode($export->attributes); ?>'>
        <span class="counters">
            <?php echo t('survey_export', 'From a total of {total} responders, so far {totalProcessed} have been processed, {successfullyProcessed} successfully and {errorProcessing} with errors. {percentage} completed.', [
                '{total}' => '<span class="total">0</span>',
                '{totalProcessed}' => '<span class="total-processed">0</span>',
                '{successfullyProcessed}' => '<span class="success">0</span>',
                '{errorProcessing}' => '<span class="error">0</span>',
                '{percentage}'  => '<span class="percentage">0%</span>',
            ]); ?>
        </span>
        <div class="progress progress-striped active">
            <div class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                <span class="sr-only">0% <?php echo t('app', 'Complete'); ?></span>
            </div>
        </div>
        <div class="alert alert-info log-info">
             <?php echo t('survey_export', 'The export process is starting, please wait...'); ?>
        </div>
        <div class="log-errors"></div>
    </div>
    <div class="box-footer"></div>
</div>
