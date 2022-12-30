<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Frontend application main configuration file
 *
 * This file should not be altered in any way!
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

return [
    'basePath'          => (string)Yii::getPathOfAlias('frontend'),
    'defaultController' => 'site',

    'preload' => [
        'frontendSystemInit',
    ],

    // autoloading model and component classes
    'import' => [
        'frontend.components.*',
        'frontend.components.db.*',
        'frontend.components.db.ar.*',
        'frontend.components.db.behaviors.*',
        'frontend.components.utils.*',
        'frontend.components.web.*',
        'frontend.components.web.auth.*',
        'frontend.models.*',
    ],

    'components' => [

        'request' => [
            'class'                   => 'frontend.components.web.FrontendHttpRequest',
            'noCsrfValidationRoutes'  => ['lists/*', 'dswh/*'],
        ],

        'urlManager' => [
            'rules' => [
                ['site/index', 'pattern' => ''],

                ['lists/subscribe_confirm', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/confirm-subscribe/<subscriber_uid:([a-z0-9]+)>/<do:([a-z0-9\_\-]+)>'],
                ['lists/subscribe_confirm', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/confirm-subscribe/<subscriber_uid:([a-z0-9]+)>'],

                ['lists/unsubscribe_confirm', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/confirm-unsubscribe/<subscriber_uid:([a-z0-9]+)>/<campaign_uid:([a-z0-9]+)>'],
                ['lists/unsubscribe_confirm', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/confirm-unsubscribe/<subscriber_uid:([a-z0-9]+)>'],

                ['lists/update_profile', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/update-profile/<subscriber_uid:([a-z0-9]+)>'],
                ['lists/subscribe_pending', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/pending-subscribe/<subscriber_uid:([a-z0-9]+)>'],
                ['lists/subscribe_pending', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/pending-subscribe'],

                ['lists/unsubscribe', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/unsubscribe/<subscriber_uid:([a-z0-9]+)>/<campaign_uid:([a-z0-9]+)>/<type:(unsubscribe\-([a-z]+))>'],
                ['lists/unsubscribe', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/unsubscribe/<subscriber_uid:([a-z0-9]+)>/<campaign_uid:([a-z0-9]+)>'],
                ['lists/unsubscribe', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/unsubscribe/<subscriber_uid:([a-z0-9]+)>'],
                ['lists/unsubscribe', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/unsubscribe/<subscriber_uid:([a-z0-9]+)>/<type:(unsubscribe\-([a-z]+))>'],

                ['lists/subscribe', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribe/<subscriber_uid:([a-z0-9]+)>'],
                ['lists/block_address_confirmation', 'pattern' => 'lists/block-address-confirmation/<key:([a-z0-9]{40})>'],
                ['lists/block_address', 'pattern' => 'lists/block-address'],
                ['lists/unsubscribe_from_customer', 'pattern' => 'lists/unsubscribe-from-customer/<customer_uid:([a-z0-9]+)>/<subscriber_uid:([a-z0-9]+)>/<campaign_uid:([a-z0-9]+)>'],
                ['lists/unsubscribe_from_customer', 'pattern' => 'lists/unsubscribe-from-customer/<customer_uid:([a-z0-9]+)>/<subscriber_uid:([a-z0-9]+)>'],
                ['lists/unsubscribe_from_customer', 'pattern' => 'lists/unsubscribe-from-customer/<customer_uid:([a-z0-9]+)>'],
                ['lists/vcard', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/vcard'],
                ['lists/<action>', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/<action>'],

                ['campaigns_reports/open_by_subscriber', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/open-by-subscriber/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns_reports/click_by_subscriber_unique', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/click-by-subscriber-unique/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns_reports/click_by_subscriber', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/click-by-subscriber/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns_reports/open_unique', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/open-unique'],
                ['campaigns_reports/click_url', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/click-url'],
                ['campaigns_reports/forward_friend', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/forward-friend'],
                ['campaigns_reports/abuse_reports', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/abuse-reports'],
                ['campaigns_reports/<action>', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/<action:(\w+)>/*'],
                ['campaigns_reports/<action>', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports/<action:(\w+)>'],

                ['campaigns/web_version', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/web-version/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns/track_opening', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/track-opening/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns/track_url', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/track-url/<subscriber_uid:([a-z0-9]+)>/<hash:([a-z0-9\.\s\-\_=]+)>'],
                ['campaigns/web_version', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>'],
                ['campaigns/forward_friend', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/forward-friend/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns/forward_friend', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/forward-friend'],
                ['campaigns/report_abuse', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/report-abuse/<list_uid:([a-z0-9]+)>/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns/vcard', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/vcard'],
                ['campaigns/<action>', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/<action:(\w+)>'],

                ['campaign_overview_widgets/index', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/index'],
                ['campaign_overview_widgets/counter_boxes', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/counter-boxes'],
                ['campaign_overview_widgets/rate_boxes', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/rate-boxes'],
                ['campaign_overview_widgets/daily_performance', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/daily-performance'],
                ['campaign_overview_widgets/top_domains_opens_clicks_graph', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/top-domains-opens-clicks-graph'],
                ['campaign_overview_widgets/geo_opens', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/geo-opens'],
                ['campaign_overview_widgets/open_user_agents', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/open-user-agents'],

                ['campaign_overview_widgets/tracking_top_clicked_links', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/tracking-top-clicked-links'],
                ['campaign_overview_widgets/tracking_latest_clicked_links', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/tracking-latest-clicked-links'],
                ['campaign_overview_widgets/tracking_latest_opens', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/tracking-latest-opens'],
                ['campaign_overview_widgets/tracking_subscribers_with_most_opens', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/tracking-subscribers-with-most-opens'],

                ['campaign_overview_widgets/<action>', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/overview-widgets/<action:(\w+)>'],

                ['campaigns_reports_export/basic', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports-export/basic'],
                ['campaigns_reports_export/click_url', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports-export/click-url'],
                ['campaigns_reports_export/click_by_subscriber', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports-export/click-by-subscriber/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns_reports_export/click_by_subscriber_unique', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports-export/click-by-subscriber-unique/<subscriber_uid:([a-z0-9]+)>'],
                ['campaigns_reports_export/<action>', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/reports-export/<action:(\w+)>'],

                ['articles/index', 'pattern' => 'articles/page/<page:(\d+)>'],
                ['articles/index', 'pattern' => 'articles'],
                ['articles/category', 'pattern' => 'articles/<slug:(.*)>'],
                ['articles/view', 'pattern' => 'article/<slug:(.*)>'],

                ['pages/view', 'pattern' => 'page/<slug:(.*)>'],

                ['dswh/index', 'pattern' => 'dswh/<id:([0-9]+)>'],

                ['surveys/index', 'pattern' => 'surveys/<survey_uid:([a-z0-9]+)>/<subscriber_uid:([a-z0-9]+)>/<campaign_uid:([a-z0-9]+)>'],
                ['surveys/index', 'pattern' => 'surveys/<survey_uid:([a-z0-9]+)>/<subscriber_uid:([a-z0-9]+)>'],
                ['surveys/index', 'pattern' => 'surveys/<survey_uid:([a-z0-9]+)>'],
                ['surveys/<action>', 'pattern' => 'surveys/<survey_uid:([a-z0-9]+)>/<action>'],

            ],
        ],

        'assetManager' => [
            'basePath'  => (string)Yii::getPathOfAlias('root.frontend.assets.cache'),
            'baseUrl'   => AppInitHelper::getBaseUrl('frontend/assets/cache'),
        ],

        'themeManager' => [
            'class'     => 'common.components.managers.ThemeManager',
            'basePath'  => (string)Yii::getPathOfAlias('root.frontend.themes'),
            'baseUrl'   => AppInitHelper::getBaseUrl('frontend/themes'),
        ],

        'clientScript' => [
            'class' => 'common.components.web.ClientScript',
        ],

        'errorHandler' => [
            'errorAction'   => 'site/error',
        ],

        'session' => [
            'class'                  => 'system.web.CDbHttpSession',
            'connectionID'           => 'db',
            'sessionName'            => 'mwsid',
            'timeout'                => 7200,
            'sessionTableName'       => '{{session}}',
            'autoCreateSessionTable' => false,
            'cookieParams'           => [
                'httponly' => true,
            ],
        ],

        'user' => [
            'class'             => 'backend.components.web.auth.WebUser',
            'allowAutoLogin'    => true,
            'authTimeout'       => 7200,
            'identityCookie'    => [
                'httpOnly'      => true,
            ],
        ],

        'customer' => [
            'class'             => 'customer.components.web.auth.WebCustomer',
            'allowAutoLogin'    => true,
            'authTimeout'       => 7200,
            'identityCookie'    => [
                'httpOnly'      => true,
            ],
        ],

        'frontendSystemInit' => [
            'class' => 'frontend.components.init.FrontendSystemInit',
        ],
    ],

    'modules' => [],

    // application-level parameters that can be accessed
    // using app_param('paramName')
    'params' => [],
];
