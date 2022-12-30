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
 * @since 1.3.4.8
 */

/** @var Controller $controller */
$controller = controller();

/** @var string $pageHeading */
$pageHeading = (string)$controller->getData('pageHeading');

/** @var Lists $list */
$list = $controller->getData('list');

/** @var ListSegment $segment */
$segment = $controller->getData('segment');

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
                    <?php echo IconHelper::make('export') . $pageHeading; ?>
                </h3>
            </div>
            <div class="pull-right">
                <?php echo CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['list_segments/index', 'list_uid' => $list->list_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]); ?>
                <?php echo CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['list_segments_export/index', 'list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-lg-2 col-xs-6">
                    <div class="small-box bg-teal">
                        <div class="inner">
                            <h3><?php echo t('list_export', 'CSV'); ?></h3>
                            <p><?php echo t('app', 'File'); ?></p>
                        </div>
                        <div class="icon">
                            <i class="ion ion-ios-download"></i>
                        </div>
                        <div class="small-box-footer">
                            <div class="pull-left">
                                &nbsp; <a href="<?php echo createUrl('list_segments_export/csv', ['list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid]); ?>" target="_blank" class="btn bg-teal btn-flat btn-xs"><?php echo IconHelper::make('export') . t('list_export', 'Click to export'); ?></a>
                            </div>
                            <div class="clearfix"><!-- --></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-footer"></div>
    </div>
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
