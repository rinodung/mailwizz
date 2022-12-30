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
 * @since 1.3.5.5
 */

/** @var Controller $controller */
$controller = controller();

/** @var Campaign $campaign */
$campaign = $controller->getData('campaign');

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
    <div id="campaign-overview-index-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/index', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-8 empty big"></div>
                    <div class="ph-col-2 big"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-8 empty"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4"></div>
                </div>
            </div>
        </div>
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2"></div>
                    <div class="ph-col-10 empty"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-4"></div>
                    <div class="ph-col-4 empty"></div>
                    <div class="ph-col-4 empty"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="campaign-overview-counter-boxes-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/counter_boxes', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
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

    <div id="campaign-overview-rate-boxes-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/rate_boxes', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
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

    <div id="campaign-overview-daily-performance-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/daily_performance', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
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

    <div id="campaign-overview-top-domains-opens-clicks-graph-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/top_domains_opens_clicks_graph', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
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

    <div id="campaign-overview-geo-opens-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/geo_opens', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
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

    <div id="campaign-overview-open-user-agents-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/open_user_agents', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
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

    <div id="campaign-overview-tracking-top-clicked-links-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/tracking_top_clicked_links', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-10 empty big"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="campaign-overview-tracking-latest-clicked-links-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/tracking_latest_clicked_links', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
        <div class="ph-item">
            <div class="ph-col-12">
                <div class="ph-row">
                    <div class="ph-col-2 big"></div>
                    <div class="ph-col-10 empty big"></div>
                </div>
                <div class="ph-row">
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                    <div class="ph-col-12"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div id="campaign-overview-tracking-latest-opens-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/tracking_latest_opens', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
                <div class="ph-item">
                    <div class="ph-col-12">
                        <div class="ph-row">
                            <div class="ph-col-2 big"></div>
                            <div class="ph-col-10 empty big"></div>
                        </div>
                        <div class="ph-row">
                            <div class="ph-col-12"></div>
                            <div class="ph-col-12"></div>
                            <div class="ph-col-12"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div id="campaign-overview-tracking-subscribers-with-most-opens-wrapper" data-url="<?php echo createUrl('campaign_overview_widgets/tracking_subscribers_with_most_opens', ['campaign_uid' => (string)$campaign->campaign_uid]); ?>">
                <div class="ph-item">
                    <div class="ph-col-12">
                        <div class="ph-row">
                            <div class="ph-col-2 big"></div>
                            <div class="ph-col-10 empty big"></div>
                        </div>
                        <div class="ph-row">
                            <div class="ph-col-12"></div>
                            <div class="ph-col-12"></div>
                            <div class="ph-col-12"></div>
                        </div>
                    </div>
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
