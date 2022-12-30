<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AssetsPath
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.1
 */

class AssetsPath
{
    /**
     * @param string $path
     * @param string $appName
     *
     * @return string
     */
    public static function base(string $path = '', string $appName = ''): string
    {
        if ($appName === '') {
            $appName = apps()->getCurrentAppName();
        }

        $base = (string)Yii::getPathOfAlias('root.' . $appName . '.assets');
        $base = $base . '/' . $path;

        return str_replace('//', '/', $base);
    }

    /**
     * @param string $path
     * @param string $appName
     *
     * @return string
     */
    public static function img(string $path, string $appName = ''): string
    {
        $folderName = 'img';
        return self::base($folderName . '/' . $path, $appName);
    }

    /**
     * @param string $path
     * @param string $appName
     *
     * @return string
     */
    public static function css(string $path, string $appName = ''): string
    {
        $folderName = 'css';
        return self::base($folderName . '/' . $path, $appName);
    }

    /**
     * @param string $path
     * @param string $appName
     *
     * @return string
     */
    public static function js(string $path, string $appName = ''): string
    {
        $folderName = 'js';
        return self::base($folderName . '/' . $path, $appName);
    }

    /**
     * @param string $path
     * @param string $appName
     *
     * @return string
     * @throws CHttpException
     */
    public static function themeBase(string $path = '', string $appName = ''): string
    {
        /** @var CWebApplication $app */
        $app = app();

        if (!$app->hasComponent('themeManager') || !$app->getTheme()) {
            throw new CHttpException(500, __METHOD__ . ' can only be called from within a theme');
        }

        if ($appName === '') {
            $appName = apps()->getCurrentAppName();
        }

        $name = $app->getTheme()->getName();
        $base = (string)Yii::getPathOfAlias('root.' . $appName . '.themes.' . $name . '.assets');
        $base = $base . '/' . $path;

        return str_replace('//', '/', $base);
    }

    /**
     * @param string $path
     * @param string $appName
     *
     * @return string
     * @throws CHttpException
     */
    public static function themeImg(string $path, string $appName = ''): string
    {
        $folderName = 'img';
        return self::themeBase($folderName . '/' . $path, $appName);
    }

    /**
     * @param string $path
     * @param string $appName
     *
     * @return string
     * @throws CHttpException
     */
    public static function themeCss(string $path, string $appName = ''): string
    {
        $folderName = 'css';
        return self::themeBase($folderName . '/' . $path, $appName);
    }

    /**
     * @param string $path
     * @param string $appName
     *
     * @return string
     * @throws CHttpException
     */
    public static function themeJs(string $path, string $appName = ''): string
    {
        $folderName = 'js';
        return self::themeBase($folderName . '/' . $path, $appName);
    }
}
