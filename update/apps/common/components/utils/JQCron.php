<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * JQCron
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0
 */

class JQCron extends Cron
{
    /**
     *
     * @param string $lang 'fr' or 'en'
     * @return string
     */
    public function getText($lang)
    {
        if (!isset($this->texts[$lang])) {
            $lang = 'en';
        }

        return parent::getText($lang);
    }
}
