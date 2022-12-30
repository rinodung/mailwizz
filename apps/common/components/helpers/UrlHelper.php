<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * UrlHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.1.11
 */

class UrlHelper
{
    /**
     * @param string $url
     * @return bool
     */
    public static function belongsToBackendApp(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        return self::appUrlFoundInGivenUrl($optionUrl->getBackendUrl(), $url);
    }

    /**
     * @param string $url
     * @return bool
     */
    public static function belongsToCustomerApp(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        /** @var OptionUrl $optionUrl */
        $optionUrl = container()->get(OptionUrl::class);

        return self::appUrlFoundInGivenUrl($optionUrl->getCustomerUrl(), $url);
    }

    /**
     * @param string $url
     * @return bool
     */
    public static function belongsToApp(string $url): bool
    {
        return self::belongsToBackendApp($url) || self::belongsToCustomerApp($url);
    }

    /**
     * @param string $appUrl
     * @param string $givenUrl
     *
     * @return bool
     */
    private static function appUrlFoundInGivenUrl(string $appUrl, string $givenUrl): bool
    {
        $appUrl = rtrim((string)preg_replace('#/index\.php#', '', $appUrl, 1), '/');

        $httpsUrl = (string)preg_replace('#^http:#', 'https:', $appUrl);
        $httpUrl  = (string)preg_replace('#^https:#', 'http:', $appUrl);

        $givenUrl = rtrim(urldecode($givenUrl), '/');
        return FilterVarHelper::url($givenUrl) && (
            stripos($givenUrl, $httpsUrl) === 0 || stripos($givenUrl, $httpUrl) === 0
        );
    }
}
