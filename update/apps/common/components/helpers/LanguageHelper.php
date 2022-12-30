<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * LanguageHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.1
 */

class LanguageHelper
{
    /**
     * @return string
     */
    public static function getAppLanguageCode(): string
    {
        $languageCode = $language = app()->getLanguage();
        if (strpos($language, '_') !== false) {
            $languageAndRegionCode = explode('_', $language);
            [$languageCode, $regionCode] = $languageAndRegionCode;
        }
        return $languageCode;
    }
}
