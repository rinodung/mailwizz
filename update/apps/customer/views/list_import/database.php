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

/** @var Lists $list */
$list = $controller->getData('list');

/** @var ListCsvImport $import */
$import = $controller->getData('import');

/** @var int $pause */
$pause = (int)$controller->getData('pause');

/** @var int $importAtOnce */
$importAtOnce = (int)$controller->getData('importAtOnce');
?>

<div class="callout callout-info">
    <?php
    $text = 'The import process will start shortly. <br />
    While the import is running it is recommended you leave this page as it is and wait for the import to finish.<br />
    The importer runs in batches of {subscribersPerBatch} subscribers with a pause of {pause} seconds between the batches, therefore 
    the import process might take a while depending on your file size and number of subscribers to import.<br />
    This is a tedious process, so sit tight and wait for it to finish.';
    echo t('list_import', StringHelper::normalizeTranslationString($text), [
        '{subscribersPerBatch}' => $importAtOnce,
        '{pause}' => $pause,
    ]);
    ?>
</div>

<div class="box box-primary borderless">
    <div class="box-header">
        <div class="pull-left">
            <h3 class="box-title">
                <?php echo IconHelper::make('import') . t('list_import', 'Database import progress'); ?> 
            </h3>
        </div>
        <div class="pull-right">
            <?php echo CHtml::link(IconHelper::make('back') . t('list_import', 'Back to import options'), ['list_import/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Back')]); ?>
        </div>
        <div class="clearfix"><!-- --></div>
    </div>
    <div class="box-body" id="database-import" data-model="<?php echo $import->getModelName(); ?>" data-pause="<?php echo (int)$pause; ?>" data-iframe="<?php echo createUrl('list_import/ping'); ?>" data-attributes='<?php echo json_encode($import->attributes); ?>'>
        <span class="counters">
            <?php echo t('list_import', 'From a total of {total} possible subscribers, so far {totalProcessed} have been processed, {successfullyProcessed} successfully and {errorProcessing} with errors. {percentage} completed.', [
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
             <?php echo t('list_import', 'The import process is starting, please wait...'); ?>
        </div>
        <div class="log-errors"></div>
    </div>
    <div class="box-footer"></div>
</div>