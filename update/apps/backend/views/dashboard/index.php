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

/** @var bool $checkVersionUpdate */
$checkVersionUpdate = $controller->getData('checkVersionUpdate');

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
if ($viewCollection->itemAt('renderContent')) {
    ?>
    <div id="dashboard-start-page-wrapper" style="display: none">
        <div class="box box-primary borderless">
            <div class="box-header">
                <div class="pull-left">
                    <h3 class="box-title"><?php echo IconHelper::make('info') . html_encode((string)$pageHeading); ?></h3>
                </div>
                <div class="pull-right"></div>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-body">
			    <?php
                /**
                 * This widget renders default getting started page for this particular section.
                 * @since 1.3.9.3
                 */
                $controller->widget('common.components.web.widgets.StartPagesWidget', [
                    'collection' => $collection = new CAttributeCollection([
                        'controller' => $controller,
                        'renderGrid' => true,
                    ]),
                    'enabled' => true,
                ]); ?>
            </div>
        </div>
    </div>
    
    <div id="dashboard-glance-stats-wrapper" data-url="<?php echo createUrl('dashboard/glance_stats'); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                </div>
                <div class="ph-picture"></div>
            </div>
        </div>
    </div>
    
    <div id="dashboard-timeline-items-wrapper" data-url="<?php echo createUrl('dashboard/timeline_items'); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                </div>
                <div class="ph-picture"></div>
            </div>
        </div>
    </div>
    
    <div class="clearfix" id="dashboard-update" data-checkupdateenabled="<?php echo (int)$checkVersionUpdate; ?>" data-checkupdateurl="<?php echo createUrl('dashboard/check_update'); ?>"><!-- --></div>
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
