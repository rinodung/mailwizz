<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Backend application main configuration file
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
    'basePath'          => (string)Yii::getPathOfAlias('backend'),
    'defaultController' => 'dashboard',

    'preload' => [
        'backendSystemInit',
    ],

    // autoloading model and component classes
    'import' => [
        'backend.components.*',
        'backend.components.db.*',
        'backend.components.db.ar.*',
        'backend.components.db.behaviors.*',
        'backend.components.utils.*',
        'backend.components.web.*',
        'backend.components.web.auth.*',
        'backend.models.*',
        'backend.models.customer-group.*',
    ],

    'components' => [

        'urlManager' => [
            'rules' => [
                ['guest/forgot_password', 'pattern' => 'guest/forgot-password'],
                ['guest/reset_password', 'pattern' => 'guest/reset-password/<reset_key:([a-zA-Z0-9]{40})>'],

                ['article_categories/<action>', 'pattern' => 'article/categories/<action:(\w+)>/*'],
                ['article_categories/<action>', 'pattern' => 'article/categories/<action:(\w+)>'],

                ['list_page_type/<action>', 'pattern' => 'list-page-type/<action:(\w+)>/*'],
                ['list_page_type/<action>', 'pattern' => 'list-page-type/<action:(\w+)>'],
                ['list_page_type', 'pattern' => 'list-page-type'],

                ['list_overview_widgets/index', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/overview-widgets/index'],
                ['list_overview_widgets/weekly_activity', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/overview-widgets/weekly-activity'],
                ['list_overview_widgets/subscribers_growth', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/overview-widgets/subscribers-growth'],
                ['list_overview_widgets/counter_boxes_averages', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/overview-widgets/counter-boxes-averages'],
                ['list_overview_widgets/<action>', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/overview-widgets/<action:(\w+)>'],

                ['delivery_server_warmup_plans/<action>', 'pattern' => 'delivery-servers/warmup-plans/<action:(\w+)>/*'],
                ['delivery_server_warmup_plans/<action>', 'pattern' => 'delivery-servers/warmup-plans/<action:(\w+)>'],
                ['delivery_server_warmup_plans', 'pattern' => 'delivery-servers/warmup-plans'],

                ['delivery_servers/warmup_plan_schedules', 'pattern' => 'delivery-servers/warmup-plan-schedules/*'],
                ['delivery_servers/warmup_plan_schedules', 'pattern' => 'delivery-servers/warmup-plan-schedules'],

                ['delivery_servers/<action>', 'pattern' => 'delivery-servers/<action:(\w+)>/*'],
                ['delivery_servers/<action>', 'pattern' => 'delivery-servers/<action:(\w+)>'],
                ['delivery_servers', 'pattern' => 'delivery-servers'],

                ['bounce_servers/<action>', 'pattern' => 'bounce-servers/<action:(\w+)>/*'],
                ['bounce_servers/<action>', 'pattern' => 'bounce-servers/<action:(\w+)>'],
                ['bounce_servers', 'pattern' => 'bounce-servers'],

                ['feedback_loop_servers/<action>', 'pattern' => 'feedback-loop-servers/<action:(\w+)>/*'],
                ['feedback_loop_servers/<action>', 'pattern' => 'feedback-loop-servers/<action:(\w+)>'],
                ['feedback_loop_servers', 'pattern' => 'feedback-loop-servers'],

                ['email_box_monitors/<action>', 'pattern' => 'email-box-monitors/<action:(\w+)>/*'],
                ['email_box_monitors/<action>', 'pattern' => 'email-box-monitors/<action:(\w+)>'],
                ['email_box_monitors', 'pattern' => 'email-box-monitors'],

                ['domain_blacklist/<action>', 'pattern' => 'domain-blacklist/<action:(\w+)>/*'],
                ['domain_blacklist/<action>', 'pattern' => 'domain-blacklist/<action:(\w+)>'],
                ['domain_blacklist', 'pattern' => 'domain-blacklist'],

                ['settings/api_ip_access', 'pattern' => 'settings/api/ip-access'],
                ['settings/system_urls', 'pattern' => 'settings/system-urls'],
                ['settings/import_export', 'pattern' => 'settings/import-export'],
                ['settings/email_templates', 'pattern' => 'settings/email-templates/<type:([a-zA-Z0-9]+)>'],
                ['settings/email_templates', 'pattern' => 'settings/email-templates'],
                ['settings/email_blacklist_monitors', 'pattern' => 'settings/email-blacklist/monitors'],
                ['settings/email_blacklist', 'pattern' => 'settings/email-blacklist'],
                ['settings/campaign_attachments', 'pattern' => 'settings/campaigns/attachments'],
                ['settings/campaign_template_tags', 'pattern' => 'settings/campaigns/template-tags'],
                ['settings/campaign_exclude_ips_from_tracking', 'pattern' => 'settings/campaigns/exclude-ips-from-tracking'],
                ['settings/campaign_blacklist_words', 'pattern' => 'settings/campaigns/blacklist-words'],
                ['settings/campaign_template_engine', 'pattern' => 'settings/campaigns/template-engine'],
                ['settings/campaign_webhooks', 'pattern' => 'settings/campaigns/webhooks'],
                ['settings/campaign_misc', 'pattern' => 'settings/campaigns/misc'],
                ['settings/campaign_options', 'pattern' => 'settings/campaign-options'],
                ['settings/customer_common', 'pattern' => 'settings/customers/common'],
                ['settings/customer_servers', 'pattern' => 'settings/customers/servers'],
                ['settings/customer_domains', 'pattern' => 'settings/customers/domains'],
                ['settings/customer_lists', 'pattern' => 'settings/customers/lists'],
                ['settings/customer_quota_counters', 'pattern' => 'settings/customers/quota-counters'],
                ['settings/customer_surveys', 'pattern' => 'settings/customers/surveys'],
                ['settings/customer_sending', 'pattern' => 'settings/customers/sending'],
                ['settings/customer_cdn', 'pattern' => 'settings/customers/cdn'],
                ['settings/customer_registration', 'pattern' => 'settings/customers/registration'],
                ['settings/customer_api', 'pattern' => 'settings/customers/api'],
                ['settings/customer_subaccounts', 'pattern' => 'settings/customers/subaccounts'],
                ['settings/customer_campaigns', 'pattern' => 'settings/customers/campaigns'],
                ['settings/monetization_orders', 'pattern' => 'settings/monetization/orders'],
                ['settings/monetization_invoices', 'pattern' => 'settings/monetization/invoices'],
                ['settings/spf_dkim', 'pattern' => 'settings/spf-dkim'],
                ['settings/social_links', 'pattern' => 'settings/social-links'],
                ['settings/reverse_proxy', 'pattern' => 'settings/reverse-proxy'],
                ['settings/transactional_email_attachments', 'pattern' => 'settings/transactional-email-attachments'],

                ['dashboard/delete_log', 'pattern' => 'dashboard/delete-log/id/<id:(\d+)>'],
                ['dashboard/delete_logs', 'pattern' => 'dashboard/delete-logs'],

                ['email_blacklist/delete_all', 'pattern' => 'email-blacklist/delete-all'],
                ['block_email_request/<action>', 'pattern' => 'block-email-request/<action:(\w+)>/*'],
                ['block_email_request/<action>', 'pattern' => 'block-email-request/<action:(\w+)>'],
                ['email_blacklist_monitors/<action>', 'pattern' => 'email-blacklist/monitors/<action:(\w+)>/*'],
                ['email_blacklist_monitors/<action>', 'pattern' => 'email-blacklist/monitors/<action:(\w+)>'],
                ['email_blacklist/<action>', 'pattern' => 'email-blacklist/<action:(\w+)>/*'],
                ['email_blacklist/<action>', 'pattern' => 'email-blacklist/<action:(\w+)>'],

                ['ip_location_services/<action>', 'pattern' => 'ip-location-services/<action:(index|create|update|delete)>'],

                ['misc/application_log', 'pattern' => 'misc/application-log/<category:(\w+)>'],
                ['misc/application_log', 'pattern' => 'misc/application-log'],
                ['misc/emergency_actions', 'pattern' => 'misc/emergency-actions'],
                ['misc/remove_sending_pid', 'pattern' => 'misc/remove-sending-pid'],
                ['misc/remove_bounce_pid', 'pattern' => 'misc/remove-bounce-pid'],
                ['misc/remove_fbl_pid', 'pattern' => 'misc/remove-fbl-pid'],
                ['misc/reset_campaigns', 'pattern' => 'misc/reset-campaigns'],
                ['misc/reset_bounce_servers', 'pattern' => 'misc/reset-bounce-servers'],
                ['misc/reset_fbl_servers', 'pattern' => 'misc/reset-fbl-servers'],
                ['misc/reset_email_box_monitors', 'pattern' => 'misc/reset-email-box-monitors'],

                ['misc/campaigns_delivery_logs', 'pattern' => 'misc/campaigns-delivery-logs/*'],
                ['misc/campaigns_delivery_logs', 'pattern' => 'misc/campaigns-delivery-logs'],
                ['misc/campaigns_bounce_logs', 'pattern' => 'misc/campaigns-bounce-logs/*'],
                ['misc/campaigns_bounce_logs', 'pattern' => 'misc/campaigns-bounce-logs'],
                ['misc/campaigns_stats', 'pattern' => 'misc/campaigns-stats/*'],
                ['misc/campaigns_stats', 'pattern' => 'misc/campaigns-stats'],
                ['misc/delivery_servers_usage_logs', 'pattern' => 'misc/delivery-servers-usage-logs/*'],
                ['misc/delivery_servers_usage_logs', 'pattern' => 'misc/delivery-servers-usage-logs'],
                ['misc/delete_delivery_temporary_errors', 'pattern' => 'misc/delete-delivery-temporary-errors'],
                ['misc/guest_fail_attempts', 'pattern' => 'misc/guest-fail-attempts/*'],
                ['misc/guest_fail_attempts', 'pattern' => 'misc/guest-fail-attempts'],
                ['misc/cron_jobs_list', 'pattern' => 'misc/cron-jobs-list/*'],
                ['misc/cron_jobs_list', 'pattern' => 'misc/cron-jobs-list'],
                ['misc/cron_jobs_history', 'pattern' => 'misc/cron-jobs-history/*'],
                ['misc/cron_jobs_history', 'pattern' => 'misc/cron-jobs-history'],
                ['misc/queue_monitor', 'pattern' => 'misc/queue-monitor/*'],
                ['misc/queue_monitor', 'pattern' => 'misc/queue-monitor'],

                ['favorite_pages/redirect_to_page', 'pattern' => 'favorite-pages/<page_uid:([a-z0-9]+)>/redirect-to-page'],
                ['favorite_pages/<action>', 'pattern' => 'favorite-pages/<page_uid:([a-z0-9]+)>/<action:(update|delete)>'],
                ['favorite_pages/add_remove', 'pattern' => 'favorite-pages/add-remove'],
                ['favorite_pages/index', 'pattern' => 'favorite-pages/index'],

                ['customers/reset_sending_quota', 'pattern' => 'customers/reset-sending-quota/id/<id:(\d+)>'],
                ['customer_groups/reset_sending_quota', 'pattern' => 'customers/groups/reset-sending-quota/id/<id:(\d+)>'],
                ['customer_groups/<action>/*', 'pattern' => 'customers/groups/<action:(\w+)>/id/<id:(\d+)>'],
                ['customer_groups/<action>', 'pattern' => 'customers/groups/<action:(\w+)>'],
                ['customer_groups/index', 'pattern' => 'customers/groups'],
                ['customers_mass_emails/<action>/*', 'pattern' => 'customers/mass-emails/<action:(\w+)>/id/<id:(\d+)>'],
                ['customers_mass_emails/<action>', 'pattern' => 'customers/mass-emails/<action:(\w+)>'],
                ['customers_mass_emails/index', 'pattern' => 'customers/mass-emails'],

                ['customer_messages/index', 'pattern' => 'customers/messages'],
                ['customer_messages/<action>', 'pattern' => 'customers/messages/<action:(\w+)>/*'],
                ['customer_messages/<action>', 'pattern' => 'customers/messages/<action:(\w+)>'],

                ['customers_notes/index', 'pattern' => 'customers/notes'],
                ['customers_notes/<action>', 'pattern' => 'customers/notes/<action:(\w+)>/*'],
                ['customers_notes/<action>', 'pattern' => 'customers/notes/<action:(\w+)>'],

                ['customer_notes/index', 'pattern' => 'customers/<customer_uid:([a-z0-9]+)>/notes'],
                ['customer_notes/create', 'pattern' => 'customers/<customer_uid:([a-z0-9]+)>/notes/create'],
                ['customer_notes/<action>', 'pattern' => 'customers/<customer_uid:([a-z0-9]+)>/notes/<note_uid:([a-z0-9]+)>/<action:(update|delete|view)>'],

                ['customer_login_logs/index', 'pattern' => 'customers/login-logs'],
                ['customer_login_logs/delete_all', 'pattern' => 'customers/login-logs/delete-all'],
                ['customer_login_logs/<action>', 'pattern' => 'customers/login-logs/<action:(\w+)>/*'],
                ['customer_login_logs/<action>', 'pattern' => 'customers/login-logs/<action:(\w+)>'],

                ['payment_gateways/<action>', 'pattern' => 'payment-gateways/<action:(index|create|update|delete)>'],

                ['price_plans/<action>', 'pattern' => 'price-plans/<action:(\w+)>/*'],
                ['price_plans/<action>', 'pattern' => 'price-plans/<action>'],

                ['promo_codes/<action>', 'pattern' => 'promo-codes/<action:(\w+)>/*'],
                ['promo_codes/<action>', 'pattern' => 'promo-codes/<action>'],

                ['orders/delete_note', 'pattern' => 'orders/delete-note/id/<id:(\d+)>'],
                ['orders/email_invoice', 'pattern' => 'orders/email-invoice/id/<id:(\d+)>'],

                ['transactional_emails/<action>', 'pattern' => 'transactional-emails/<action:(\w+)>/*'],
                ['transactional_emails/<action>', 'pattern' => 'transactional-emails/<action>'],

                ['tracking_domains/<action>', 'pattern' => 'tracking-domains/<action:(\w+)>/*'],
                ['tracking_domains/<action>', 'pattern' => 'tracking-domains/<action:(\w+)>'],
                ['tracking_domains', 'pattern' => 'tracking-domains'],

                ['sending_domains/<action>', 'pattern' => 'sending-domains/<action:(\w+)>/*'],
                ['sending_domains/<action>', 'pattern' => 'sending-domains/<action:(\w+)>'],
                ['sending_domains', 'pattern' => 'sending-domains'],

                ['email_templates_categories/<action>', 'pattern' => 'email-templates/categories/<action:(\w+)>/*'],
                ['email_templates_categories/<action>', 'pattern' => 'email-templates/categories/<action:(\w+)>'],
                ['email_templates_categories', 'pattern' => 'email-templates/categories'],

                ['email_templates_gallery/<action>', 'pattern' => 'email-templates/gallery/<template_uid:([a-z0-9]+)>/<action:(update|delete|preview|copy)>'],
                ['email_templates_gallery/<action>', 'pattern' => 'email-templates/gallery/<action:(\w+)>/*'],
                ['email_templates_gallery/<action>', 'pattern' => 'email-templates/gallery/<action:(\w+)>'],
                ['email_templates_gallery', 'pattern' => 'email-templates/gallery'],

                ['company_types/<action>', 'pattern' => 'company-types/<action:(\w+)>/*'],
                ['company_types/<action>', 'pattern' => 'company-types/<action:(\w+)>'],
                ['company_types', 'pattern' => 'company-types'],

                ['user_groups/<action>/*', 'pattern' => 'users/groups/<action:(\w+)>/id/<id:(\d+)>'],
                ['user_groups/<action>', 'pattern' => 'users/groups/<action:(\w+)>'],
                ['user_groups/index', 'pattern' => 'users/groups'],

                ['campaign_abuse_reports/<action>', 'pattern' => 'campaign-abuse-reports/<action:(\w+)>/*'],
                ['campaign_abuse_reports/<action>', 'pattern' => 'campaign-abuse-reports/<action:(\w+)>'],
                ['campaign_abuse_reports', 'pattern' => 'campaign-abuse-reports'],

                ['messages/view', 'pattern' => 'messages/<message_uid:([a-z0-9]+)>/view'],
                ['messages/delete', 'pattern' => 'messages/<message_uid:([a-z0-9]+)>/delete'],
                ['messages/mark_all_as_seen', 'pattern' => 'messages/mark-all-as-seen'],

                ['campaigns/resend_giveups', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/resend-giveups'],
                ['campaigns/pause_unpause', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/pause-unpause'],
                ['campaigns/block_unblock', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/block-unblock'],
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

                ['lists/all_subscribers', 'pattern' => 'lists/all-subscribers/*'],
                ['lists/all_subscribers', 'pattern' => 'lists/all-subscribers'],
                ['lists/list_growth_export', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/list-growth-export'],
                ['lists/list_growth', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/list-growth'],
                ['lists/toggle_archive', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/toggle-archive/*'],
                ['lists/toggle_archive', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/toggle-archive'],
                ['lists/<action>', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/<action:([a-z0-9]+)>'],

                ['list_subscribers/profile_export', 'pattern' => 'lists/subscribers/<subscriber_uid:([a-z0-9]+)>/profile/export'],
                ['list_subscribers/<action>', 'pattern' => 'lists/subscribers/<subscriber_uid:([a-z0-9]+)>/<action:(update|subscribe|unsubscribe|disable|delete|campaigns|profile)>'],

                ['surveys/<action>', 'pattern' => 'surveys/<survey_uid:([a-z0-9]+)>/<action:([a-z0-9]+)>'],

                ['start_pages/<action>', 'pattern' => 'start-pages/<action:(\w+)>/*'],
                ['start_pages/<action>', 'pattern' => 'start-pages/<action:(\w+)>'],
                ['start_pages', 'pattern' => 'start-pages'],

                ['common_email_templates/<action>', 'pattern' => 'common-email-templates/<action:(\w+)>/*'],
                ['common_email_templates/<action>', 'pattern' => 'common-email-templates/<action:(\w+)>'],

                ['translations/index', 'pattern' => 'languages/<language_id:([0-9]+)>/translations'],

                ['download_queued/index', 'pattern' => 'download-queued/<file:([a-z0-9]{40}\.(csv|txt|zip))>'],
            ],
        ],

        'assetManager' => [
            'basePath'  => (string)Yii::getPathOfAlias('root.backend.assets.cache'),
            'baseUrl'   => AppInitHelper::getBaseUrl('assets/cache'),
        ],

        'themeManager' => [
            'class'     => 'common.components.managers.ThemeManager',
            'basePath'  => (string)Yii::getPathOfAlias('root.backend.themes'),
            'baseUrl'   => AppInitHelper::getBaseUrl('themes'),
        ],

        'clientScript' => [
            'class' => 'common.components.web.ClientScript',
        ],

        'errorHandler' => [
            'errorAction'   => 'guest/error',
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
            'loginUrl'          => ['guest/index'],
            'returnUrl'         => ['dashboard/index'],
            'authTimeout'       => 7200,
            'identityCookie'    => [
                'httpOnly'  => true,
            ],
        ],

        'customer' => [
            'class'             => 'customer.components.web.auth.WebCustomer',
            'allowAutoLogin'    => true,
            'authTimeout'       => 7200,
            'identityCookie'    => [
                'httpOnly'  => true,
            ],
        ],

        'backendSystemInit' => [
            'class' => 'backend.components.init.BackendSystemInit',
        ],
    ],

    'modules' => [],

    // application-level parameters that can be accessed
    // using app_param('paramName')
    'params' => [
        // list of controllers where the user doesn't have to be logged in.
        'unprotectedControllers' => ['guest'],
    ],
];
