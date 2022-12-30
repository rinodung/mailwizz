<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

return [
    [
        'name'      => 'Delivery server validation',
        'slug'      => 'delivery-server-validation',
        'subject'   => 'Please validate this server.',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[HOSTNAME]',
                'description'   => 'The delivery server hostname',
            ],
            [
                'tag'           => '[CONFIRMATION_URL]',
                'description'   => 'The confirmation url for this server',
            ],
            [
                'tag'           => '[CONFIRMATION_KEY]',
                'description'   => 'The confirmation key for this server',
            ],
        ],
    ],
    [
        'name'      => 'Campaign pending approval',
        'slug'      => 'campaign-pending-approval',
        'subject'   => 'A campaign requires approval before sending!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CAMPAIGN_OVERVIEW_URL]',
                'description'   => 'The campaign overview url',
            ],
        ],
    ],
    [
        'name'      => 'Campaign pending approval - approved',
        'slug'      => 'campaign-pending-approval-approved',
        'subject'   => 'Your campaign has been approved!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CAMPAIGN_OVERVIEW_URL]',
                'description'   => 'The campaign overview url',
            ],
        ],
    ],
    [
        'name'      => 'Campaign pending approval - disapproved',
        'slug'      => 'campaign-pending-approval-disapproved',
        'subject'   => 'Your campaign has been disapproved!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CAMPAIGN_OVERVIEW_URL]',
                'description'   => 'The campaign overview url',
            ],
            [
                'tag'           => '[DISAPPROVED_MESSAGE]',
                'description'   => 'The message that explains why the campaign has been disapproved',
            ],
        ],
    ],
    [
        'name'      => 'Campaign has been blocked',
        'slug'      => 'campaign-has-been-blocked',
        'subject'   => 'A campaign has been blocked!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CAMPAIGN_OVERVIEW_URL]',
                'description'   => 'The campaign overview url',
            ],
        ],
    ],
    [
        'name'      => 'Campaign stats',
        'slug'      => 'campaign-stats',
        'subject'   => 'The campaign [CAMPAIGN_NAME] has finished sending, here are the stats',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CAMPAIGN_NAME]',
                'description'   => 'The campaign name',
            ],
            [
                'tag'           => '[CAMPAIGN_OVERVIEW_URL]',
                'description'   => 'The campaign overview url',
            ],
            [
                'tag'           => '[STATS_TABLE]',
                'description'   => 'The html table containing the stats',
            ],
        ],
    ],
    [
        'name'      => 'Campaign share reports access',
        'slug'      => 'campaign-share-reports-access',
        'subject'   => 'Campaign share reports access!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CAMPAIGN_NAME]',
                'description'   => 'The campaign name',
            ],
            [
                'tag'           => '[CAMPAIGN_REPORTS_URL]',
                'description'   => 'The campaign reports url',
            ],
            [
                'tag'           => '[CAMPAIGN_REPORTS_PASSWORD]',
                'description'   => 'The password to view the campaign reports',
            ],
        ],
    ],
    [
        'name'      => 'Customer confirm registration',
        'slug'      => 'customer-confirm-registration',
        'subject'   => 'Please confirm your account!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CONFIRMATION_URL]',
                'description'   => 'The confirmation url',
            ],
        ],
    ],
    [
        'name'      => 'New customer registration',
        'slug'      => 'new-customer-registration',
        'subject'   => 'New customer registration!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CUSTOMER_INFO]',
                'description'   => 'The information related to this customer',
            ],
            [
                'tag'           => '[CUSTOMER_URL]',
                'description'   => 'The url to view the customer',
            ],
        ],
    ],
    [
        'name'      => 'New list subscriber',
        'slug'      => 'new-list-subscriber',
        'subject'   => 'New list subscriber!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CUSTOMER_INFO]',
                'description'   => 'The information related to this customer',
            ],
            [
                'tag'           => '[CUSTOMER_URL]',
                'description'   => 'The url to view the customer',
            ],
        ],
    ],
    [
        'name'      => 'Email blacklist import finished',
        'slug'      => 'email-blacklist-import-finished',
        'subject'   => 'Email blacklist import has finished!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[USER_NAME]',
                'description'   => 'The name of the user receiving the email',
            ],
            [
                'tag'           => '[FILE_NAME]',
                'description'   => 'The file name which finished importing',
            ],
            [
                'tag'           => '[OVERVIEW_URL]',
                'description'   => 'The overview url',
            ],
        ],
    ],
    [
        'name'      => 'Auto update notification',
        'slug'      => 'auto-update-notification',
        'subject'   => 'Automatic update notification!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[LOGS]',
                'description'   => 'The auto-update logs',
            ],
        ],
    ],
    [
        'name'      => 'Email blacklist monitor results',
        'slug'      => 'email-blacklist-monitor-results',
        'subject'   => 'Blacklist monitor results for: [MONITOR_NAME]',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[MONITOR_NAME]',
                'description'   => 'The name of the monitor',
            ],
            [
                'tag'           => '[COUNT]',
                'description'   => 'The number of records processed',
            ],
            [
                'tag'           => '[SUCCESS_COUNT]',
                'description'   => 'The number of records deleted with success',
            ],
            [
                'tag'           => '[ERROR_COUNT]',
                'description'   => 'The number of records which shown errors while removing',
            ],
        ],
    ],
    [
        'name'      => 'Suppression list import finished',
        'slug'      => 'suppression-list-import-finished',
        'subject'   => 'Suppression list import has finished!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CUSTOMER_NAME]',
                'description'   => 'The name of the custyomer receiving the email',
            ],
            [
                'tag'           => '[LIST_NAME]',
                'description'   => 'The list name which finished importing',
            ],
            [
                'tag'           => '[OVERVIEW_URL]',
                'description'   => 'The overview url',
            ],
        ],
    ],
    [
        'name'      => 'Order invoice',
        'slug'      => 'order-invoice',
        'subject'   => 'Your requested invoice - [REF]!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[REF]',
                'description'   => 'Invoice reference',
            ],
            [
                'tag'           => '[CUSTOMER_NAME]',
                'description'   => 'The name of the customer receiving the email',
            ],
        ],
    ],
    [
        'name'      => 'List import finished',
        'slug'      => 'list-import-finished',
        'subject'   => 'List import has finished!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CUSTOMER_NAME]',
                'description'   => 'The name of the customer receiving the email',
            ],
            [
                'tag'           => '[LIST_NAME]',
                'description'   => 'The list name which finished importing',
            ],
            [
                'tag'           => '[OVERVIEW_URL]',
                'description'   => 'The overview url',
            ],
            [
                'tag'           => '[ERRORS_SUMMARY]',
                'description'   => 'The errors summary output',
            ],
        ],
    ],
    [
        'name'      => 'Password reset request',
        'slug'      => 'password-reset-request',
        'subject'   => 'Password reset request!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CONFIRMATION_URL]',
                'description'   => 'The url where to confirm the reset of the password',
            ],
        ],
    ],
    [
        'name'      => 'New login info',
        'slug'      => 'new-login-info',
        'subject'   => 'Your new login info!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[LOGIN_EMAIL]',
                'description'   => 'The login email',
            ],
            [
                'tag'           => '[LOGIN_PASSWORD]',
                'description'   => 'The login password',
            ],
            [
                'tag'           => '[LOGIN_URL]',
                'description'   => 'The login url',
            ],
        ],
    ],
    [
        'name'      => 'New order placed - user',
        'slug'      => 'new-order-placed-user',
        'subject'   => 'A new order has been placed!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[USER_NAME]',
                'description'   => 'The name of the user which will receive the notification',
            ],
            [
                'tag'           => '[CUSTOMER_NAME]',
                'description'   => 'The name of the customer who made the order',
            ],
            [
                'tag'           => '[PLAN_NAME]',
                'description'   => 'The plan that has been bought',
            ],
            [
                'tag'           => '[ORDER_SUBTOTAL]',
                'description'   => 'The order subtotal amount, formatted',
            ],
            [
                'tag'           => '[ORDER_TAX]',
                'description'   => 'The order tax amount, formatted',
            ],
            [
                'tag'           => '[ORDER_DISCOUNT]',
                'description'   => 'The order discount, formatted',
            ],
            [
                'tag'           => '[ORDER_TOTAL]',
                'description'   => 'The order total, formatted',
            ],
            [
                'tag'           => '[ORDER_STATUS]',
                'description'   => 'The status of the order',
            ],
            [
                'tag'           => '[ORDER_OVERVIEW_URL]',
                'description'   => 'The url where this order can be seen',
            ],
        ],
    ],
    [
        'name'      => 'New order placed - customer',
        'slug'      => 'new-order-placed-customer',
        'subject'   => 'Your order details!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CUSTOMER_NAME]',
                'description'   => 'The name of the customer who made the order',
            ],
            [
                'tag'           => '[PLAN_NAME]',
                'description'   => 'The plan that has been bought',
            ],
            [
                'tag'           => '[ORDER_SUBTOTAL]',
                'description'   => 'The order subtotal amount, formatted',
            ],
            [
                'tag'           => '[ORDER_TAX]',
                'description'   => 'The order tax amount, formatted',
            ],
            [
                'tag'           => '[ORDER_DISCOUNT]',
                'description'   => 'The order discount, formatted',
            ],
            [
                'tag'           => '[ORDER_TOTAL]',
                'description'   => 'The order total, formatted',
            ],
            [
                'tag'           => '[ORDER_STATUS]',
                'description'   => 'The status of the order',
            ],
            [
                'tag'           => '[ORDER_OVERVIEW_URL]',
                'description'   => 'The url where this order can be seen',
            ],
        ],
    ],
    [
        'name'      => 'Account approved',
        'slug'      => 'account-approved',
        'subject'   => 'Your account has been approved!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[LOGIN_URL]',
                'description'   => 'The url to login page',
            ],
        ],
    ],
    [
        'name'      => 'Account details',
        'slug'      => 'account-details',
        'subject'   => 'Your account details!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[LOGIN_URL]',
                'description'   => 'The url to login page',
            ],
            [
                'tag'           => '[LOGIN_EMAIL]',
                'description'   => 'The email used for login',
            ],
            [
                'tag'           => '[LOGIN_PASSWORD]',
                'description'   => 'The password for login',
            ],
        ],
    ],
    [
        'name'      => 'List subscriber unsubscribed',
        'slug'      => 'list-subscriber-unsubscribed',
        'subject'   => 'List subscriber unsubscribed!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[LIST_NAME]',
                'description'   => 'The name of the e mail list',
            ],
            [
                'tag'           => '[SUBSCRIBER_EMAIL]',
                'description'   => 'The email address of the subscriber',
            ],
        ],
    ],
    [
        'name'      => 'Confirm block email request',
        'slug'      => 'confirm-block-email-request',
        'subject'   => 'Confirm the block email request!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CONFIRMATION_URL]',
                'description'   => 'The url where to confirm the request',
            ],
        ],
    ],
    [
        'name'      => 'Forward campaign to a friend',
        'slug'      => 'forward-campaign-friend',
        'subject'   => 'Your friend [FROM_NAME] thought you might like this!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[TO_NAME]',
                'description'   => 'The name to which this email is addresssed to',
            ],
            [
                'tag'           => '[FROM_NAME]',
                'description'   => 'The name where this email originates from',
            ],
            [
                'tag'           => '[MESSAGE]',
                'description'   => 'Additional message set by [FROM_NAME] for [TO_NAME]',
            ],
            [
                'tag'           => '[CAMPAIGN_URL]',
                'description'   => 'The url to the forwarded campaign',
            ],
        ],
    ],
    [
        'name'      => 'New abuse report',
        'slug'      => 'new-abuse-report',
        'subject'   => 'New abuse report!',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CUSTOMER_NAME]',
                'description'   => 'The name of the customer this email is addressed to',
            ],
            [
                'tag'           => '[CAMPAIGN_NAME]',
                'description'   => 'The name of the campaign for which the abuse report is made',
            ],
            [
                'tag'           => '[ABUSE_REPORTS_URL]',
                'description'   => 'The url to view the abuse reports',
            ],
        ],
    ],
    [
        'name'      => 'Scheduled inactive customers',
        'slug'      => 'scheduled-inactive-customers',
        'subject'   => 'Scheduled inactive customers',
        'content'   => '',
        'tags'      => [
            [
                'tag'           => '[CUSTOMERS_LIST]',
                'description'   => 'The customers that were marked as inactive as per your settings',
            ],
        ],
    ],
];
