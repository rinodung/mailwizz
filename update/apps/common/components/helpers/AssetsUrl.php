<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AssetsUrl
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class AssetsUrl
{
    /**
     * @param string $uri
     * @param bool $absolute
     * @param string $appName
     *
     * @return string
     */
    public static function base(string $uri = '', bool $absolute = false, string $appName = ''): string
    {
        if ($appName === '' && apps()->isAppName('frontend')) {
            $appName = 'frontend';
        }

        $extra = ($appName === 'frontend' ? '/frontend/' : '');

        return apps()->getAppUrl($appName, ltrim((string)$extra, '/') . 'assets/' . $uri, $absolute, true);
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param string $appName
     *
     * @return string
     */
    public static function img(string $uri, bool $absolute = false, string $appName = ''): string
    {
        $folderName = 'img';
        return self::base($folderName . '/' . $uri, $absolute, $appName);
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param string $appName
     *
     * @return string
     */
    public static function css(string $uri, bool $absolute = false, string $appName = ''): string
    {
        $folderName = 'css';
        return self::base($folderName . '/' . $uri, $absolute, $appName);
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param string $appName
     *
     * @return string
     */
    public static function js(string $uri, bool $absolute = false, string $appName = ''): string
    {
        $folderName = 'js';
        return self::base($folderName . '/' . $uri, $absolute, $appName);
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param string $appName
     *
     * @return string
     * @throws CHttpException
     */
    public static function themeBase(string $uri = '', bool $absolute = false, string $appName = ''): string
    {
        /** @var CWebApplication $app */
        $app = app();

        if (!$app->hasComponent('themeManager') || !$app->getTheme()) {
            throw new CHttpException(500, __METHOD__ . ' can only be called from within a theme');
        }

        if ($appName === '' && apps()->isAppName('frontend')) {
            $appName = 'frontend';
        }

        $extra = ($appName === 'frontend' ? '/frontend/' : '');

        $name = $app->getTheme()->getName();

        return apps()->getAppUrl($appName, ltrim((string)$extra, '/') . 'themes/' . $name . '/assets/' . $uri, $absolute, true);
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param string $appName
     *
     * @return string
     * @throws CHttpException
     */
    public static function themeImg(string $uri, bool $absolute = false, string $appName = ''): string
    {
        $folderName = 'img';
        return self::themeBase($folderName . '/' . $uri, $absolute, $appName);
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param string $appName
     *
     * @return string
     * @throws CHttpException
     */
    public static function themeCss(string $uri, bool $absolute = false, string $appName = ''): string
    {
        $folderName = 'css';
        return self::themeBase($folderName . '/' . $uri, $absolute, $appName);
    }

    /**
     * @param string $uri
     * @param bool $absolute
     * @param string $appName
     *
     * @return string
     * @throws CHttpException
     */
    public static function themeJs(string $uri, bool $absolute = false, string $appName = ''): string
    {
        $folderName = 'js';
        return self::themeBase($folderName . '/' . $uri, $absolute, $appName);
    }
}
