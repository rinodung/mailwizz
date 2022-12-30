<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Console application main configuration file
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
    'basePath' => (string)Yii::getPathOfAlias('console'),

    'preload' => [
        'consoleSystemInit',
    ],

    'import' => [
        'console.components.*',
        'console.components.db.*',
        'console.components.db.ar.*',
        'console.components.web.*',
        'console.components.console.*',
        'console.components.send-campaigns-tester.*',
        'console.components.send-campaigns-tester.testers.*',
    ],

    'commandMap' => [
        'hello' => [
            'class' => 'console.commands.HelloCommand',
        ],
        'send-campaigns' => [
            'class' => 'console.commands.SendCampaignsCommand',
        ],
        'bounce-handler' => [
            'class' => 'console.commands.BounceHandlerCommand',
        ],
        'process-delivery-and-bounce-log' => [
            'class' => 'console.commands.ProcessDeliveryAndBounceLogCommand',
        ],
        'option' => [
            'class' => 'console.commands.OptionCommand',
        ],
        'feedback-loop-handler' => [
            'class' => 'console.commands.FeedbackLoopHandlerCommand',
        ],
        'email-box-monitor-handler' => [
            'class' => 'console.commands.EmailBoxMonitorHandlerCommand',
        ],
        'send-transactional-emails' => [
            'class' => 'console.commands.SendTransactionalEmailsCommand',
        ],
        'daily' => [
            'class' => 'console.commands.DailyCommand',
        ],
        'update' => [
            'class' => 'console.commands.UpdateCommand',
        ],
        'archive-campaigns-delivery-logs' => [
            'class' => 'console.commands.ArchiveCampaignsDeliveryLogsCommand',
        ],
        'list-import' => [
            'class' => 'console.commands.ListImportCommand',
        ],
        'list-export' => [
            'class' => 'console.commands.ListExportCommand',
        ],
        'mailerq-handler-daemon' => [
            'class' => 'console.commands.MailerqHandlerDaemon',
        ],
        'table-cleaner' => [
            'class' => 'console.commands.TableCleanerCommand',
        ],
        'clear-cache' => [
            'class' => 'console.commands.ClearCacheCommand',
        ],
        'translate' => [
            'class' => 'console.commands.TranslateCommand',
        ],
        'email-blacklist-monitor' => [
            'class' => 'console.commands.EmailBlacklistMonitorCommand',
        ],
        'reset-customers-quota' => [
            'class' => 'console.commands.ResetCustomersQuotaCommand',
        ],
        'move-inactive-subscribers' => [
            'class' => 'console.commands.MoveInactiveSubscribersCommand',
        ],
        'delete-inactive-subscribers' => [
            'class' => 'console.commands.DeleteInactiveSubscribersCommand',
        ],
        'delete-campaigns' => [
            'class' => 'console.commands.DeleteCampaignsCommand',
        ],
        'hourly' => [
            'class' => 'console.commands.HourlyCommand',
        ],
        'sync-lists-custom-fields' => [
            'class' => 'console.commands.SyncListsCustomFieldsCommand',
        ],
        'sync-surveys-custom-fields' => [
            'class' => 'console.commands.SyncSurveysCustomFieldsCommand',
        ],
        'delete-mutexes' => [
            'class' => 'console.commands.DeleteMutexesCommand',
        ],
        'unsubscribe-inactive-subscribers' => [
            'class' => 'console.commands.UnsubscribeInactiveSubscribersCommand',
        ],
        'delete-campaign-delivery-logs' => [
            'class' => 'console.commands.DeleteCampaignDeliveryLogsCommand',
        ],
        'delete-campaign-bounce-logs' => [
            'class' => 'console.commands.DeleteCampaignBounceLogsCommand',
        ],
        'delete-campaign-open-logs' => [
            'class' => 'console.commands.DeleteCampaignOpenLogsCommand',
        ],
        'delete-campaign-click-logs' => [
            'class' => 'console.commands.DeleteCampaignClickLogsCommand',
        ],
        'suppression-list-import' => [
            'class' => 'console.commands.SuppressionListImportCommand',
        ],
        'validate-list-mx-records' => [
            'class' => 'console.commands.ValidateListMxRecordsCommand',
        ],
        'update-ip-location-for-campaign-opens' => [
            'class' => 'console.commands.UpdateIpLocationForCampaignOpensCommand',
        ],
        'update-ip-location-for-campaign-clicks' => [
            'class' => 'console.commands.UpdateIpLocationForCampaignClicksCommand',
        ],
        'delete-transactional-emails' => [
            'class' => 'console.commands.DeleteTransactionalEmailsCommand',
        ],
        'auto-update' => [
            'class' => 'console.commands.AutoUpdateCommand',
        ],
        'update-ip-location-timezone' => [
            'class' => 'console.commands.UpdateIpLocationTimezoneCommand',
        ],
        'email-blacklist-import' => [
            'class' => 'console.commands.EmailBlacklistImportCommand',
        ],
        'delete-email-blacklist' => [
            'class' => 'console.commands.DeleteEmailBlacklistCommand',
        ],
        'email-blacklist-regex-blacklist' => [
            'class' => 'console.commands.EmailBlacklistRegexBlacklist',
        ],
        'send-campaigns-webhooks' => [
            'class' => 'console.commands.SendCampaignsWebhooksCommand',
        ],
        'backend-dashboard-cache' => [
            'class' => 'console.commands.BackendDashboardCacheCommand',
        ],
        'delete-customer-suppression-lists-duplicate-emails' => [
            'class' => 'console.commands.DeleteCustomerSuppressionListsDuplicateEmailsCommand',
        ],
        'migrate-language-messages' => [
            'class' => 'console.commands.MigrateLanguageMessagesCommand',
        ],
        'email-blacklist-force-subscribers-blacklist-status' => [
            'class' => 'console.commands.EmailBlacklistForceSubscribersBlacklistStatusCommand',
        ],
        'delete-moved-subscribers' => [
            'class' => 'console.commands.DeleteMovedSubscribersCommand',
        ],
        'queue' => [
            'class' => 'console.commands.QueueCommand',
        ],
        'campaign-grid-stats-cache-warmup' => [
            'class' => 'console.commands.CampaignGridStatsCacheWarmupCommand',
        ],
        'send-campaigns-tester' => [
            'class' => 'console.commands.SendCampaignsTesterCommand',
        ],
        'delivery-servers-tester' => [
            'class' => 'console.commands.DeliveryServersTesterCommand',
        ],
        'delete-orphan-campaign-gallery' => [
            'class' => 'console.commands.DeleteOrphanCampaignGalleryCommand',
        ],
        'delivery-servers-warmup-handler' => [
            'class' => 'console.commands.DeliveryServersWarmupHandlerCommand',
        ],
    ],

    'components' => [
        'consoleSystemInit' => [
            'class' => 'console.components.init.ConsoleSystemInit',
        ],
    ],
];
