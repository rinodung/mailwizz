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

    <div id="list-overview-header-wrapper" data-url="<?php echo createUrl('list_overview_widgets/index', ['list_uid' => (string)$list->list_uid]); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-10 empty big"></div>
                </div>
            </div>
            <div class="ph-col-3">
                <div class="ph-picture"></div>
            </div>
            <div class="ph-col-3">
                <div class="ph-picture"></div>
            </div>
            <div class="ph-col-3">
                <div class="ph-picture"></div>
            </div>
            <div class="ph-col-3">
                <div class="ph-picture"></div>
            </div>
        </div>
    </div>

    <div id="list-overview-weekly-activity-wrapper" data-url="<?php echo createUrl('list_overview_widgets/weekly_activity', ['list_uid' => (string)$list->list_uid]); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-10 empty big"></div>
                </div>
            </div>
            <div class="ph-col-12">
                <div class="ph-picture"></div>
            </div>
        </div>
    </div>

    <div id="list-overview-subscribers-growth-wrapper" data-url="<?php echo createUrl('list_overview_widgets/subscribers_growth', ['list_uid' => (string)$list->list_uid]); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-10 empty big"></div>
                </div>
            </div>
            <div class="ph-col-12">
                <div class="ph-picture"></div>
            </div>
        </div>
    </div>

    <div id="list-overview-counter-boxes-averages-wrapper" data-url="<?php echo createUrl('list_overview_widgets/counter_boxes_averages', ['list_uid' => (string)$list->list_uid]); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-10 empty big"></div>
                </div>
            </div>
            <div class="ph-col-3">
                <div class="ph-picture"></div>
            </div>
            <div class="ph-col-3">
                <div class="ph-picture"></div>
            </div>
            <div class="ph-col-3">
                <div class="ph-picture"></div>
            </div>
            <div class="ph-col-3">
                <div class="ph-picture"></div>
            </div>
        </div>
    </div>

    <div id="campaigns-overview-wrapper" data-url="<?php echo createUrl('dashboard/campaigns'); ?>" data-list="<?php echo $list->list_id; ?>">
        <div class="ph-item">
            <div class="col-12 col-sm-4">
                <div class="ph-row">
                    <div class="ph-col-2"></div>
                    <div class="ph-col-8 empty"></div>
                    <div class="ph-col-2"></div>
                    <div class="ph-col-12 big"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                </div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="ph-row">
                    <div class="ph-col-2"></div>
                    <div class="ph-col-8 empty"></div>
                    <div class="ph-col-2"></div>
                    <div class="ph-col-12 big"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                </div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="ph-row">
                    <div class="ph-col-2"></div>
                    <div class="ph-col-8 empty"></div>
                    <div class="ph-col-2"></div>
                    <div class="ph-col-12 big"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                </div>
            </div>
        </div>
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
