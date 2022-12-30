<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * EmailHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.29
 */

class EmailHelper
{
    /**
     * @param string $email
     * @return string
     */
    public static function getDomainFromEmail(string $email): string
    {
        if (!FilterVarHelper::email($email)) {
            return '';
        }
        $domain = explode('@', $email);
        return array_pop($domain);
    }
}
