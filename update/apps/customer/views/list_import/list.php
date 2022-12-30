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

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Lists $list */
$list = $controller->getData('list');

/** @var bool $webEnabled */
$webEnabled = (bool)$controller->getData('webEnabled');

/** @var bool $cliEnabled */
$cliEnabled = (bool)$controller->getData('cliEnabled');

/** @var bool $urlEnabled */
$urlEnabled = (bool)$controller->getData('urlEnabled');

/** @var string $maxUploadSize */
$maxUploadSize = (string)$controller->getData('maxUploadSize');

/** @var ListCsvImport $importCsv */
$importCsv = $controller->getData('importCsv');

/** @var ListDatabaseImport $importDb */
$importDb = $controller->getData('importDb');

/** @var ListUrlImport $importUrl */
$importUrl = $controller->getData('importUrl');

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->add('renderContent', false)}
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
hooks()->doAction('before_view_file_content', $viewCollection = new CAttributeCollection([
    'controller'    => $controller,
    'renderContent' => true,
]));

// and render if allowed
if ($viewCollection->itemAt('renderContent')) { ?>
    <div class="box box-primary borderless">
        <div class="box-header">
            <div class="pull-left">
                <h3 class="box-title">
                    <?php echo IconHelper::make('import') . $pageHeading; ?>
                </h3>
            </div>
            <div class="pull-right">
                <?php echo CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['lists/overview', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]); ?>
                <?php echo CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['list_import/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <div class="row boxes-mw-wrapper">

                <?php if (!empty($webEnabled)) { ?>
                    <div class="col-lg-2 col-xs-6">
                        <div class="small-box">
                            <div class="inner">
                                <div class="middle">
                                    <h3><a href="#csv-upload-modal" data-toggle="modal" class="btn-csv-import"><?php echo t('list_import', 'CSV'); ?></a></h3>
                                    <p><?php echo t('app', 'File (live import)'); ?></p>
                                </div>
                            </div>
                            <div class="icon">
                                <i class="ion ion-ios-upload"></i>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <?php if (!empty($cliEnabled)) {?>
                    <div class="col-lg-2 col-xs-6">
                        <div class="small-box">
                            <div class="inner">
                                <div class="middle">
                                    <h3><a href="#csv-queue-upload-modal" data-toggle="modal" class="btn-csv-import"><?php echo t('list_import', 'CSV'); ?></a></h3>
                                    <p><?php echo t('app', 'File (queue import)'); ?></p>
                                </div>
                            </div>
                            <div class="icon">
                                <i class="ion ion-ios-upload"></i>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <div class="col-lg-2 col-xs-6">
                    <div class="small-box">
                        <div class="inner">
                            <div class="middle">
                                <h3><a href="#database-import-modal" data-toggle="modal" class="btn-database-import"><?php echo t('list_import', 'Database'); ?></a></h3>
                                <p><?php echo t('app', 'Sql import (live import)'); ?></p>
                            </div>
                        </div>
                        <div class="icon">
                            <i class="ion ion-ios-upload"></i>
                        </div>
                    </div>
                </div>

                <?php if (!empty($cliEnabled) && !empty($urlEnabled)) {?>
                    <div class="col-lg-2 col-xs-6">
                        <div class="small-box">
                            <div class="inner">
                                <div class="middle">
                                    <h3><a href="#url-recurring-import-modal" data-toggle="modal" class="btn-text-import"><?php echo t('list_import', 'Url'); ?></a></h3>
                                    <p><?php echo t('app', 'Recurring import'); ?></p>
                                </div>
                            </div>
                            <div class="icon">
                                <i class="ion ion-ios-upload"></i>
                            </div>
                        </div>
                    </div>
                <?php } else {  ?>
                    <div class="col-lg-2 col-xs-6">
                        <div class="small-box">
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>

    <?php if (!empty($webEnabled)) { ?>
    <div class="modal fade" id="csv-upload-modal" tabindex="-1" role="dialog" aria-labelledby="csv-upload-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('list_import', 'Import from CSV file'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                    <?php
                    $text = 'Please note, we only accept valid CSV files that contain a header, that is the column names for the data to be imported.<br />
                     We also have a limit on the file size you are allowed to upload, that is {uploadLimit}.<br />
                     The import process might fail with some of the files, mainly because these are not correctly formatted or they contain invalid data.<br />
                     You should first do a test import(in a test list) and see if that goes as planned then do it for your actual list.<br />
                     <strong>Important</strong>: The CSV file column names will be used to create the list TAGS, if a tag does not exist, it will be created.<br />
                     You can also click <a href="{exampleArchiveHref}" target="_blank">here</a> to see a csv file example.';
                    echo t('list_import', StringHelper::normalizeTranslationString($text), [
                        '{uploadLimit}'         => $maxUploadSize . 'MB',
                        '{exampleArchiveHref}'  => apps()->getAppUrl('customer', 'assets/files/example-csv-import.csv', false, true),
                    ]);
                    ?>
                 </div>
                <?php
                /** @var CActiveForm $form */
                $form = $controller->beginWidget('CActiveForm', [
                    'action'        => ['list_import/csv', 'list_uid' => $list->list_uid],
                    'htmlOptions'   => [
                        'id'        => 'upload-csv-form',
                        'enctype'   => 'multipart/form-data',
                    ],
                ]);
                ?>
                <div class="form-group">
                    <?php echo $form->labelEx($importCsv, 'file'); ?>
                    <?php echo $form->fileField($importCsv, 'file', $importCsv->fieldDecorator->getHtmlOptions('file')); ?>
                    <?php echo $form->error($importCsv, 'file'); ?>
                </div>
                <?php $controller->endWidget(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#upload-csv-form').submit();"><?php echo t('list_import', 'Upload file'); ?></button>
            </div>
          </div>
        </div>
    </div>
    <?php } ?>

    <?php if (!empty($cliEnabled)) { ?>
    <div class="modal fade" id="csv-queue-upload-modal" tabindex="-1" role="dialog" aria-labelledby="csv-queue-upload-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('list_import', 'Import from CSV file'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                    <?php
                    $text = 'Please note, we only accept valid CSV files that contain a header, that is the column names for the data to be imported.<br />
                     We also have a limit on the file size you are allowed to upload, that is {uploadLimit}.<br />
                     The import process might fail with some of the files, mainly because these are not correctly formatted or they contain invalid data.<br />
                     You should first do a test import(in a test list) and see if that goes as planned then do it for your actual list.<br />
                     <strong>Important</strong>: The CSV file column names will be used to create the list TAGS, if a tag does not exist, it will be created.<br />
                     You can also click <a href="{exampleArchiveHref}" target="_blank">here</a> to see a csv file example.';
                    echo t('list_import', StringHelper::normalizeTranslationString($text), [
                        '{uploadLimit}'         => $maxUploadSize . 'MB',
                        '{exampleArchiveHref}'  => apps()->getAppUrl('customer', 'assets/files/example-csv-import.csv', false, true),
                    ]);
                    ?>
                 </div>
                <?php
                $form = $controller->beginWidget('CActiveForm', [
                    'action'        => ['list_import/csv_queue', 'list_uid' => $list->list_uid],
                    'htmlOptions'   => [
                        'id'        => 'upload-csv-queue-form',
                        'enctype'   => 'multipart/form-data',
                    ],
                ]);
                ?>
                <div class="form-group">
                    <?php echo $form->labelEx($importCsv, 'file'); ?>
                    <?php echo $form->fileField($importCsv, 'file', $importCsv->fieldDecorator->getHtmlOptions('file')); ?>
                    <?php echo $form->error($importCsv, 'file'); ?>
                </div>
                <?php $controller->endWidget(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#upload-csv-queue-form').submit();"><?php echo t('list_import', 'Upload file'); ?></button>
            </div>
          </div>
        </div>
    </div>
    <?php } ?>

    <div class="modal fade" id="database-import-modal" tabindex="-1" role="dialog" aria-labelledby="database-import-modal-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo t('list_import', 'Import from external SQL database'); ?></h4>
            </div>
            <div class="modal-body">
                 <div class="callout callout-info">
                    <?php echo t('list_import', 'Please enter your credentials for the external database in order to start the import.'); ?>
                 </div>
                <?php
                $form = $controller->beginWidget('CActiveForm', [
                    'action'        => ['list_import/database', 'list_uid' => $list->list_uid],
                    'htmlOptions'   => [
                        'id'        => 'import-database-form',
                    ],
                ]);
                ?>
                <div class="form-group col-lg-12">
                    <?php echo $form->labelEx($importDb, 'server_type'); ?>
                    <?php echo $form->dropDownList($importDb, 'server_type', $importDb->getServerTypes(), $importDb->fieldDecorator->getHtmlOptions('server_type')); ?>
                    <?php echo $form->error($importDb, 'server_type'); ?>
                </div>
                <div class="clearfix"><!-- --></div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($importDb, 'hostname'); ?>
                    <?php echo $form->textField($importDb, 'hostname', $importDb->fieldDecorator->getHtmlOptions('hostname')); ?>
                    <?php echo $form->error($importDb, 'hostname'); ?>
                </div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($importDb, 'port'); ?>
                    <?php echo $form->textField($importDb, 'port', $importDb->fieldDecorator->getHtmlOptions('port')); ?>
                    <?php echo $form->error($importDb, 'port'); ?>
                </div>
                <div class="clearfix"><!-- --></div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($importDb, 'username'); ?>
                    <?php echo $form->textField($importDb, 'username', $importDb->fieldDecorator->getHtmlOptions('username')); ?>
                    <?php echo $form->error($importDb, 'username'); ?>
                </div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($importDb, 'password'); ?>
                    <?php echo $form->passwordField($importDb, 'password', $importDb->fieldDecorator->getHtmlOptions('password')); ?>
                    <?php echo $form->error($importDb, 'password'); ?>
                </div>
                <div class="clearfix"><!-- --></div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($importDb, 'database_name'); ?>
                    <?php echo $form->textField($importDb, 'database_name', $importDb->fieldDecorator->getHtmlOptions('database_name')); ?>
                    <?php echo $form->error($importDb, 'database_name'); ?>
                </div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($importDb, 'table_name'); ?>
                    <?php echo $form->textField($importDb, 'table_name', $importDb->fieldDecorator->getHtmlOptions('table_name')); ?>
                    <?php echo $form->error($importDb, 'table_name'); ?>
                </div>
                <div class="clearfix"><!-- --></div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($importDb, 'email_column'); ?>
                    <?php echo $form->textField($importDb, 'email_column', $importDb->fieldDecorator->getHtmlOptions('email_column')); ?>
                    <?php echo $form->error($importDb, 'email_column'); ?>
                </div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($importDb, 'ignored_columns'); ?>
                    <?php echo $form->textField($importDb, 'ignored_columns', $importDb->fieldDecorator->getHtmlOptions('ignored_columns')); ?>
                    <?php echo $form->error($importDb, 'ignored_columns'); ?>
                </div>
                <div class="clearfix"><!-- --></div>
                <?php $controller->endWidget(); ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
              <button type="button" class="btn btn-primary btn-flat" onclick="$('#import-database-form').submit();"><?php echo t('list_import', 'Connect and import'); ?></button>
            </div>
          </div>
        </div>
    </div>

    <?php if (!empty($cliEnabled) && !empty($urlEnabled)) { ?>
        <div class="modal fade" id="url-recurring-import-modal" tabindex="-1" role="dialog" aria-labelledby="text-queue-upload-modal-label" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title"><?php echo t('list_import', 'Import from url'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="callout callout-info">
                            <?php echo t('list_import', 'Add a url from where we will import subscribers on a daily basis!'); ?><br />
                            <?php echo t('list_import', 'Please note that the url has to point to a valid .csv or .txt file'); ?><br />
                        </div>
                        <?php
                        $form = $controller->beginWidget('CActiveForm', [
                            'action'        => ['list_import/url', 'list_uid' => $list->list_uid],
                            'htmlOptions'   => [
                                'id'        => 'remote-url-import-form',
                            ],
                        ]);
                        ?>
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="form-group">
                                    <?php echo $form->labelEx($importUrl, 'url'); ?>
                                    <?php echo $form->textField($importUrl, 'url', $importUrl->fieldDecorator->getHtmlOptions('url')); ?>
                                    <?php echo $form->error($importUrl, 'url'); ?>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="form-group">
                                    <?php echo $form->labelEx($importUrl, 'status'); ?>
                                    <?php echo $form->dropDownList($importUrl, 'status', $importUrl->getStatusesList(), $importUrl->fieldDecorator->getHtmlOptions('status')); ?>
                                    <?php echo $form->error($importUrl, 'status'); ?>
                                </div>
                            </div>
                        </div>
                        <?php $controller->endWidget(); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo t('app', 'Close'); ?></button>
                        <button type="button" class="btn btn-primary btn-flat" onclick="$('#remote-url-import-form').submit();"><?php echo t('list_import', 'Save url'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->getData()}
 * @since 1.3.3.1
 */
hooks()->doAction('after_view_file_content', new CAttributeCollection([
    'controller'        => $controller,
    'renderedContent'   => $viewCollection->itemAt('renderContent'),
]));
