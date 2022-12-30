<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * LogHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class LogHelper
{
    /**
     * Disable logging app wide
     */
    public static function disableLogging(): void
    {
        collect(app()->getComponent('log')->routes)->each(function (CLogRoute $route) {
            $route->enabled = false;
        });
    }

    /**
     * Enable logging app wide
     */
    public static function enableLogging(): void
    {
        collect(app()->getComponent('log')->routes)->each(function (CLogRoute $route) {
            $route->enabled = true;
        });
    }
}
