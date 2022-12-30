<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FileLogRoute
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.9.3
 */

class FileLogRoute extends CFileLogRoute
{
    /**
     * Formats a log message given different fields.
     * @param string $message message content
     * @param integer $level message level
     * @param string $category message category
     * @param integer $time timestamp
     * @return string formatted message
     */
    protected function formatLogMessage($message, $level, $category, $time)
    {
        if (!is_cli()) {
            $ip = request()->getUserHostAddress();
            return @date('Y/m/d H:i:s', (int)$time) . " [$level] [$category] [$ip] $message\n";
        }
        return @date('Y/m/d H:i:s', (int)$time) . " [$level] [$category] $message\n";
    }
}
