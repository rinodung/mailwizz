<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Common application main configuration file
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
    'basePath'          => (string)Yii::getPathOfAlias('common'),
    'runtimePath'       => (string)Yii::getPathOfAlias('common.runtime'),
    'name'              => 'MailWizz', // never change this
    'id'                => 'MailWizz', // never change this
    'sourceLanguage'    => 'en',
    'language'          => 'en',
    'defaultController' => '',
    'charset'           => 'utf-8',
    'timeZone'          => 'UTC', // make sure we stay UTC

    // preloading components
    'preload' => [
        'log', 'systemInit',
    ],

    // autoloading model and component classes
    'import' => [
        'common.components.*',
        'common.components.db.*',
        'common.components.db.ar.*',
        'common.components.db.behaviors.*',
        'common.components.helpers.*',
        'common.components.init.*',
        'common.components.managers.*',
        'common.components.models.*',
        'common.components.mutex.*',
        'common.components.mailer.*',
        'common.components.traits.*',
        'common.components.utils.*',
        'common.components.web.*',
        'common.components.web.auth.*',
        'common.components.web.response.*',
        'common.components.web.widgets.*',
        'common.components.zii.widgets.grid.*',
        'common.components.queue.*',
        'common.components.queue.processors.*',
        'common.models.collection.*',
        'common.models.option.*',
        'common.models.*',
    ],

    // application components
    'components' => [

        // will be merged with the custom one to get connection string/username/password and table prefix
        'db' => [
            'connectionString'      => '{DB_CONNECTION_STRING}',
            'username'              => '{DB_USER}',
            'password'              => '{DB_PASS}',
            'tablePrefix'           => '{DB_PREFIX}',
            'emulatePrepare'        => true,
            'charset'               => 'utf8mb4',
            'schemaCachingDuration' => MW_CACHE_TTL,
            'enableParamLogging'    => MW_DEBUG,
            'enableProfiling'       => MW_DEBUG,
            'schemaCacheID'         => 'cache',
            'queryCacheID'          => 'cache',
            'initSQLs'              => [
                'SET time_zone="+00:00"',
                'SET SQL_MODE=""',
            ], // make sure we stay UTC and utf-8,
            'autoConnect'           => true,
        ],

        'dependencyInjectionContainer' => [
            'class' => 'common.components.utils.DependencyInjectionContainer',
        ],

        'request' => [
            'class'                   => 'common.components.web.BaseHttpRequest',
            'csrfCookie'              => ['httpOnly' => true],
            'csrfTokenName'           => 'csrf_token',
            'enableCsrfValidation'    => true,
            'enableCookieValidation'  => true,
        ],

        'cache' => [
            'class'     => 'system.caching.CFileCache',
            'keyPrefix' => sprintf(':%s:', sha1(MW_ROOT_PATH)),
        ],

        'dbCache' => [
            'class'                 => 'system.caching.CDbCache',
            'keyPrefix'             => sprintf(':%s:', sha1(MW_ROOT_PATH)),
            'connectionID'          => 'db',
            'cacheTableName'        => '{{cache}}',
            'autoCreateCacheTable'  => false,
        ],

        'urlManager' => [
            'class'          => 'CUrlManager',
            'urlFormat'      => 'path',
            'showScriptName' => true,
            'caseSensitive'  => false,
            'urlSuffix'      => null,
            'rules'          => [],
        ],

        'messages' => [
            'class'                  => 'CDbMessageSource',
            'cachingDuration'        => 3600,
            'sourceMessageTable'     => '{{translation_source_message}}',
            'translatedMessageTable' => '{{translation_message}}',
        ],

        'log' => [
            'class' => 'CLogRouter',
            'routes' => [
                [
                    'class'     => 'common.components.logging.FileLogRoute',
                    'levels'    => 'error',
                    'except'    => 'exception.CHttpException.404',
                    'enabled'   => true,
                ],
                [
                    'class'         => 'common.components.logging.FileLogRoute',
                    'levels'        => 'error',
                    'categories'    => 'exception.CHttpException.404',
                    'logPath'       => MW_ROOT_PATH . '/apps/common/runtime',
                    'logFile'       => '404.log',
                    'enabled'       => true,
                ],
                [
                    'class'   => 'CWebLogRoute',
                    'filter'  => 'CLogFilter',
                    'enabled' => MW_DEBUG,
                ],
                [
                    'class'   => 'CProfileLogRoute',
                    'report'  => 'summary',
                    'enabled' => MW_DEBUG,
                ],
            ],
        ],

        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'format' => [
            'class' => 'system.utils.CLocalizedFormatter',
        ],

        'passwordHasher' => [
            'class' =>  'common.components.utils.PasswordHasher',
        ],

        'ioFilter' => [
            'class' => 'common.components.utils.IOFilter',
        ],

        'hooks' => [
            'class' => 'common.components.managers.HooksManager',
        ],

        'options' => [
            'class'     => 'common.components.managers.OptionsManager',
            'cacheTtl'  => MW_CACHE_TTL,
        ],

        'notify' => [
            'class' => 'common.components.managers.NotifyManager',
        ],

        'mailer' => [
            'class' => 'common.components.mailer.Mailer',
        ],

        'mutex' => [
            'class'     => 'common.components.mutex.FileMutex',
            'keyPrefix' => sprintf(':%s:', sha1(MW_ROOT_PATH)),
        ],

        'queue' => [
            'class'   => 'common.components.queue.QueueDatabase',
            'storage' => 'common.components.queue.QueueStorageFilesystem',
        ],

        'extensionMimes' => [
            'class' => 'common.components.utils.FileExtensionMimes',
        ],

        'extensionsManager' => [
            'class'    => 'common.components.managers.ExtensionsManager',
            'paths'    => [
                [
                    'alias'    => 'common.extensions',
                    'priority' => -1000,
                ],
                [
                    'alias'    => 'extensions',
                    'priority' => -999,
                ],
            ],
            'coreExtensionsList' => [],
        ],

        'systemInit' => [
            'class' => 'common.components.init.SystemInit',
        ],
    ],

    'modules' => [
        /*
        'gii' => [
            'class'     => 'system.gii.GiiModule',
            'password'  => 'mailwizz',
            'ipFilters' => ['*'],
        ],
        */
    ],

    // application-level parameters that can be accessed
    'params' => [

        // https://github.com/onetwist-software/mailwizz/issues/585
        // https://www.mailwizz.com/kb/enable-use-temporary-queue-tables-sending-campaigns/
        'send.campaigns.command.useTempQueueTables'                     => false,
        'send.campaigns.command.tempQueueTables.copyAtOnce'             => 500,

        // if you change this param, you know what you are doing and the implications!
        'email.custom.header.prefix' => 'X-',

        // dkim custom selectors for sending domains
        'email.custom.dkim.selector'      => 'mailer',
        'email.custom.dkim.full_selector' => 'mailer._domainkey',
        'email.custom.dkim.key.size'      => 2048,

        // since 1.7.3
        'email.custom.returnPath.enabled' => true,

        // since 1.6.3 - default email template stub
        'email.templates.stub' => '<!DOCTYPE html><html><head><meta charset="utf-8"/><title></title></head><body></body></html>',

        // use tidy for templates parsing
        'email.templates.tidy.enabled' => true,

        // tidy default options
        'email.templates.tidy.options' => [
            'indent'            => true,
            'output-xhtml'      => true,
            'wrap'              => 200,
            'fix-bad-comments'  => true,
        ],

        // custom campaign tags prefix
        'customer.campaigns.custom_tags.prefix'  => 'CCT_',
        'customer.campaigns.extra_tags.prefix'   => 'CET_',

        // since 1.3.6.6 - cache directories
        'cache.directory.aliases' => [
            'root.backend.assets.cache',
            'root.customer.assets.cache',
            'root.frontend.assets.cache',
            'common.runtime.cache',
        ],

        // since 1.3.7.2
        'store.enabled'     => true,
        'store.cache.count' => 0,

        // since 1.4.4
        'campaign.delivery.giveup.retries'                        => 3,
        'campaign.delivery.logs.delete.days_back'                 => 30,
        'campaign.delivery.logs.delete.process_campaigns_at_once' => 50,
        'campaign.delivery.logs.delete.process_logs_at_once'      => 5000,

        // since 1.7.9
        'campaign.bounce.logs.delete.days_back'                 => 5,
        'campaign.bounce.logs.delete.process_campaigns_at_once' => 50,
        'campaign.bounce.logs.delete.process_logs_at_once'      => 5000,

        // since 1.7.9
        'campaign.open.logs.delete.days_back'                 => 5,
        'campaign.open.logs.delete.process_campaigns_at_once' => 50,
        'campaign.open.logs.delete.process_logs_at_once'      => 5000,

        // since 1.7.9
        'campaign.click.logs.delete.days_back'                 => 5,
        'campaign.click.logs.delete.process_campaigns_at_once' => 50,
        'campaign.click.logs.delete.process_logs_at_once'      => 5000,

        // since 1.5.2
        'campaign.stats.processor.enable_cache' => true,

        // since 1.4.5
        'campaign.delivery.sending.check_paused_realtime' => true,

        // since 2.1.6
        'campaign.transform_links_for_tracking.parser.url.tag.url_suffix_only' => false,

        // since 1.4.5
        'ip.location.maxmind.db.path' => MW_ROOT_PATH . '/apps/common/data/maxmind/GeoLite2-City.mmdb',
        'ip.location.maxmind.db.url'  => 'https://www.maxmind.com/en/geolite2/signup',

        // since 1.5.1
        'customer.pulsate_info.enabled' => true,
        'backend.pulsate_info.enabled'  => true,

        // since 1.5.2
        'delivery_servers.show_provider_url' => true,

        // since 1.6.4
        'console.save_command_history' => true,

        //
        'servers.imap.search.mailboxes' => ['INBOX', 'Junk', 'Spam'],

        //
        'files.images.extensions' => ['jpg', 'jpeg', 'png', 'gif'],

        // since 1.9.3
        'lists.counters.cache.adapter' => 'dbCache',

        // since 2.0.30
        'dns.resolver.nameservers' => ['8.8.8.8', '8.8.4.4'],
    ],
];
