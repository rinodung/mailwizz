<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * AppInitHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class AppInitHelper
{
    /**
     * @var string
     */
    private static $_entryScriptUrl;

    /**
     * @var string
     */
    private static $_baseUrl;

    /**
     * @return string
     * @throws Exception
     */
    public static function getEntryScriptUrl(): string
    {
        if (self::$_entryScriptUrl === null) {
            $scriptName = basename($_SERVER['SCRIPT_FILENAME']);

            if (basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                self::$_entryScriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (basename($_SERVER['PHP_SELF']) === $scriptName) {
                self::$_entryScriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                self::$_entryScriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                self::$_entryScriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']) === 0) {
                self::$_entryScriptUrl = (string)str_replace('\\', '/', (string)str_replace($_SERVER['DOCUMENT_ROOT'], '', $_SERVER['SCRIPT_FILENAME']));
            } else {
                throw new Exception('Unable to determine the entry script URL.');
            }
        }
        return self::$_entryScriptUrl;
    }

    /**
     * @param string $appendThis
     *
     * @return string
     * @throws Exception
     */
    public static function getBaseUrl(string $appendThis = ''): string
    {
        if (self::$_baseUrl === null) {
            self::$_baseUrl = rtrim(dirname(self::getEntryScriptUrl()), '\\/');
        }
        return self::$_baseUrl . (!empty($appendThis) ? '/' . trim((string)$appendThis, '/') : '');
    }

    /**
     * TODO: Remove method in v2.1.20
     *
     * @deprecated
     * @return void
     */
    public static function fixRemoteAddress(): void
    {
        Yii::log(__METHOD__ . ' is deprecated since v2.1.10', CLogger::LEVEL_ERROR);
    }

    /**
     * @return bool
     */
    public static function isModRewriteEnabled(): bool
    {
        return CommonHelper::functionExists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : true;
    }

    /**
     * @return bool
     */
    public static function isSecureConnection(): bool
    {
        return !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off');
    }
}
