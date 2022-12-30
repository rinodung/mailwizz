<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 */

class SearchExtBehaviorMiscController extends SearchExtBaseBehavior
{
    /**
     * @return array
     */
    public function searchableActions(): array
    {
        $defaultKeywords = ['miscellaneous', 'utils'];
        return [
            'index' => [
                'keywords'  => array_merge($defaultKeywords, []),
                'skip'      => [$this, '_indexSkip'],
            ],
            'emergency_actions' => [
                'keywords'  => array_merge($defaultKeywords, []),
            ],
            'application_log' => [
                'keywords'  => array_merge($defaultKeywords, ['application logs', 'logging']),
            ],
            'campaigns_delivery_logs' => [
                'keywords'  => array_merge($defaultKeywords, ['logging', 'campaigns logs', 'campaign logs', 'campaign delivery']),
            ],
            'campaigns_bounce_logs' => [
                'keywords'  => array_merge($defaultKeywords, ['logging', 'campaigns logs', 'campaign logs', 'campaign bounce', 'bounce logs']),
            ],
            'campaigns_stats' => [
                'keywords'  => array_merge($defaultKeywords, ['logging', 'campaigns logs', 'campaign logs', 'campaign stat', 'stats logs']),
            ],
            'delivery_servers_usage_logs' => [
                'keywords'  => array_merge($defaultKeywords, ['logging']),
            ],
            'guest_fail_attempts' => [
                'keywords'  => array_merge($defaultKeywords, ['logging', 'login failed attempts']),
            ],
            'cron_jobs_list' => [
                'keywords'  => array_merge($defaultKeywords, ['cron list']),
            ],
            'cron_jobs_history' => [
                'keywords'  => array_merge($defaultKeywords, ['cron history']),
            ],
            'phpinfo' => [
                'keywords'  => array_merge($defaultKeywords, ['system info', 'php info']),
            ],
            'changelog' => [
                'keywords'  => array_merge($defaultKeywords, ['system info', 'change log', 'version info', 'updates']),
            ],
        ];
    }

    /**
     * @return bool
     */
    public function _indexSkip()
    {
        return true;
    }
}
