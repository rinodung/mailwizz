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
 * @since 1.7.8
 */

/** @var Controller $controller */
$controller = controller();

/** @var Survey $survey */
$survey = $controller->getData('survey');

/** @var int $respondersCount */
$respondersCount = (int)$controller->getData('respondersCount');

/** @var int $segmentsCount */
$segmentsCount =(int)$controller->getData('segmentsCount');

/** @var int $customFieldsCount */
$customFieldsCount = (int)$controller->getData('customFieldsCount');

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
        <div class="box-header" id="chatter-header">
            <div class="pull-left">
                <h3 class="box-title"><?php echo IconHelper::make('glyphicon-list'); ?> <?php echo t('surveys', 'Overview'); ?></h3>
            </div>
            <div class="pull-right">
                <?php echo CHtml::link(IconHelper::make('view') . t('app', 'View'), $survey->getViewUrl(), ['target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => t('app', 'View')]); ?>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
        <div class="box-body">
            <div class="row boxes-mw-wrapper">
                <div class="col-lg-4 col-xs-4">
                    <div class="small-box">
                        <div class="inner">
                            <div class="middle">
                                <h6>&nbsp;</h6>
                                <h3><?php echo CHtml::link(formatter()->formatNumber($respondersCount), 'javascript:;', ['title' => t('survey_responders', 'Responders')]); ?></h3>
                                <p><?php echo t('survey_responders', 'Responders'); ?></p>
                            </div>
                        </div>
                        <div class="icon">
                            <i class="ion ion-ios-people"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-xs-4">
                    <div class="small-box">
                        <div class="inner">
                            <div class="middle">
                                <h6>&nbsp;</h6>
                                <h3><?php echo CHtml::link(formatter()->formatNumber($customFieldsCount), 'javascript:;', ['title' => t('survey_fields', 'Custom fields')]); ?></h3>
                                <p><?php echo t('survey_fields', 'Custom fields'); ?></p>
                            </div>
                        </div>
                        <div class="icon">
                            <i class="ion ion-android-list"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-xs-4">
                    <div class="small-box">
                        <div class="inner">
                            <div class="middle">
                                <h6>&nbsp;</h6>
                                <h3><?php echo CHtml::link(formatter()->formatNumber($segmentsCount), 'javascript:;', ['title' => t('list_segments', 'Segments')]); ?></h3>
                                <p><?php echo t('survey_segments', 'Segments'); ?></p>
                            </div>
                        </div>
                        <div class="icon">
                            <i class="ion ion-gear-b"></i>
                        </div>
                    </div>
                </div>
                <div class="clearfix"><!-- --></div>    
            </div>
        </div>
    </div>

    <?php
    // since 1.5.2
    $controller->widget('customer.components.web.widgets.survey-responders.SurveyResponders7DaysActivityWidget', [
        'survey' => $survey,
    ]);
    ?>
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
