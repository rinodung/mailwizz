<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Api application main configuration file
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
    'basePath'          => (string)Yii::getPathOfAlias('api'),
    'defaultController' => 'site',

    'preload' => [
        'apiSystemInit',
    ],

    // autoloading model and component classes
    'import' => [
        'api.components.*',
        'api.components.db.*',
        'api.components.db.ar.*',
        'api.components.db.behaviors.*',
        'api.components.utils.*',
        'api.components.web.*',
        'api.components.web.auth.*',
        'api.models.*',
    ],

    'components' => [

        'request' => [
            'enableCsrfValidation'      => false,
            'enableCookieValidation'    => false,
        ],

        'urlManager' => [
            'rules' => [
                ['lists/index', 'pattern' => 'lists', 'verb' => 'GET'],
                ['lists/create', 'pattern' => 'lists', 'verb' => 'POST'],
                ['lists/view', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>', 'verb' => 'GET'],
                ['lists/update', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>', 'verb' => 'PUT'],
                ['lists/copy', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/copy', 'verb' => 'POST'],
                ['lists/delete', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>', 'verb' => 'DELETE'],

                ['templates/index', 'pattern' => 'templates', 'verb' => 'GET'],
                ['templates/create', 'pattern' => 'templates', 'verb' => 'POST'],
                ['templates/view', 'pattern' => 'templates/<template_uid:([a-z0-9]+)>', 'verb' => 'GET'],
                ['templates/update', 'pattern' => 'templates/<template_uid:([a-z0-9]+)>', 'verb' => 'PUT'],
                ['templates/delete', 'pattern' => 'templates/<template_uid:([a-z0-9]+)>', 'verb' => 'DELETE'],

                // since 1.3.7.3
                ['campaigns_tracking/track_opening', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/track-opening/<subscriber_uid:([a-z0-9]+)>', 'verb' => 'GET'],
                ['campaigns_tracking/track_url', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/track-url/<subscriber_uid:([a-z0-9]+)>/<hash:([a-z0-9]+)>', 'verb' => 'GET'],
                ['campaigns_tracking/track_unsubscribe', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/track-unsubscribe/<subscriber_uid:([a-z0-9]+)>', 'verb' => 'POST'],

                // since 1.4.4
                ['campaign_bounces/index', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/bounces', 'verb' => 'GET'],
                ['campaign_bounces/create', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/bounces', 'verb' => 'POST'],

                // since 2.1.0
                ['campaign_delivery_logs/email_message_id', 'pattern' => 'campaigns/delivery-logs/email-message-id/<email_message_id:([a-z0-9@\._\-]+)>', 'verb' => 'GET'],
                ['campaign_delivery_logs/index', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/delivery-logs', 'verb' => 'GET'],

                // since 1.9.15
                ['campaign_unsubscribes/index', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/unsubscribes', 'verb' => 'GET'],

                ['campaigns/index', 'pattern' => 'campaigns', 'verb' => 'GET'],
                ['campaigns/create', 'pattern' => 'campaigns', 'verb' => 'POST'],
                ['campaigns/view', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>', 'verb' => 'GET'],
                ['campaigns/update', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>', 'verb' => 'PUT'],
                ['campaigns/copy', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/copy', 'verb' => 'POST'],
                ['campaigns/delete', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>', 'verb' => 'DELETE'],
                ['campaigns/pause_unpause', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/pause-unpause', 'verb' => 'PUT'],
                ['campaigns/mark_sent', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/mark-sent', 'verb' => 'PUT'],
                ['campaigns/stats', 'pattern' => 'campaigns/<campaign_uid:([a-z0-9]+)>/stats', 'verb' => 'GET'],

                ['list_fields/index', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/fields', 'verb' => 'GET'],
                ['list_segments/index', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/segments', 'verb' => 'GET'],

                ['list_subscribers/index', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers', 'verb' => 'GET'],
                ['list_subscribers/create', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers', 'verb' => 'POST'],
                ['list_subscribers/create_bulk', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers/bulk', 'verb' => 'POST'],
                ['list_subscribers/unsubscribe', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers/<subscriber_uid:([a-z0-9]+)>/unsubscribe', 'verb' => 'PUT'],
                ['list_subscribers/update', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers/<subscriber_uid:([a-z0-9]+)>', 'verb' => 'PUT'],
                ['list_subscribers/delete', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers/<subscriber_uid:([a-z0-9]+)>', 'verb' => 'DELETE'],
                ['list_subscribers/view', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers/<subscriber_uid:([a-z0-9]+)>', 'verb' => 'GET'],
                ['list_subscribers/search_by_email', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers/search-by-email', 'verb' => 'GET'],
                ['list_subscribers/search_by_custom_fields', 'pattern' => 'lists/<list_uid:([a-z0-9]+)>/subscribers/search-by-custom-fields', 'verb' => 'GET'],
                ['list_subscribers/search_by_email_in_all_lists', 'pattern' => 'lists/subscribers/search-by-email-in-all-lists', 'verb' => 'GET'],
                ['list_subscribers/unsubscribe_by_email_from_all_lists', 'pattern' => 'lists/subscribers/unsubscribe-by-email-from-all-lists', 'verb' => 'PUT'],

                ['countries/index', 'pattern' => 'countries', 'verb' => 'GET'],
                ['countries/zones', 'pattern' => 'countries/<country_id:(\d+)>/zones', 'verb' => 'GET'],

                ['transactional_emails/index', 'pattern' => 'transactional-emails', 'verb' => 'GET'],
                ['transactional_emails/create', 'pattern' => 'transactional-emails', 'verb' => 'POST'],
                ['transactional_emails/view', 'pattern' => 'transactional-emails/<email_uid:([a-z0-9]+)>', 'verb' => 'GET'],
                ['transactional_emails/delete', 'pattern' => 'transactional-emails/<email_uid:([a-z0-9]+)>', 'verb' => 'DELETE'],

                ['customers/create', 'pattern' => 'customers', 'verb' => 'POST'],

                ['delivery_servers/index', 'pattern' => 'delivery-servers', 'verb' => 'GET'],
                ['delivery_servers/view', 'pattern' => 'delivery-servers/<server_id:(\d+)>', 'verb' => 'GET'],
            ],
        ],

        'user' => [
            'class'     => 'api.components.web.auth.WebUser',
            'loginUrl'  => null,
        ],

        'apiSystemInit' => [
            'class' => 'api.components.init.ApiSystemInit',
        ],
    ],

    'modules' => [],

    // application-level parameters that can be accessed
    'params' => [
        'unprotectedControllers' => [
            'site', 'customers',
        ],
    ],
];
