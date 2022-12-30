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

/** @var Survey $survey */
$survey = $controller->getData('survey');

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
                <?php echo CHtml::link(IconHelper::make('cancel') . t('app', 'Cancel'), ['surveys/overview', 'survey_uid' => $survey->survey_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Cancel')]); ?>
                <?php echo CHtml::link(IconHelper::make('refresh') . t('app', 'Refresh'), ['survey_export/index', 'survey_uid' => $survey->survey_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Refresh')]); ?>
                <?php echo CHtml::link(IconHelper::make('back') . t('app', 'Back'), ['survey_responders/index', 'survey_uid' => $survey->survey_uid], ['class' => 'btn btn-primary btn-flat', 'title' => t('app', 'Back')]); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <div class="row boxes-mw-wrapper">
                <div class="col-lg-4 col-xs-6">
                    <div class="small-box">
                        <div class="inner">
                            <div class="middle">
                                <h3><a href="<?php echo createUrl('survey_export/csv', ['survey_uid' => $survey->survey_uid]); ?>" class=""><?php echo t('survey_export', 'CSV'); ?></a></h3>
                                <p><?php echo t('app', 'File'); ?></p>
                            </div>
                        </div>
                        <div class="icon">
                            <i class="ion ion-ios-download"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <div class="small-box">
                        <div class="inner"></div>
                    </div>
                </div>
                <div class="col-lg-4 col-xs-6">
                    <div class="small-box">
                        <div class="inner"></div>
                    </div>
                </div>
            </div>
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
