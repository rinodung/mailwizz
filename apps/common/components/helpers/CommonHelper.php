<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * CommonHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.1
 */

class CommonHelper
{
    /**
     * @param string $sqlFile
     * @param string $dbPrefix
     *
     * @return Generator
     */
    public static function getQueriesFromSqlFile(string $sqlFile, string $dbPrefix = ''): Generator
    {
        if (!is_file($sqlFile) || !is_readable($sqlFile)) {
            return;
        }

        $search  = [];
        $replace = [];

        if (!empty($dbPrefix)) {
            $searchReplace = [
                'CREATE TABLE IF NOT EXISTS `'  => 'CREATE TABLE IF NOT EXISTS `' . $dbPrefix,
                'CREATE TABLE `'                => 'CREATE TABLE `' . $dbPrefix,
                'DROP TABLE IF EXISTS `'        => 'DROP TABLE IF EXISTS `' . $dbPrefix,
                'DROP TABLE `'                  => 'DROP TABLE `' . $dbPrefix,
                'INSERT INTO `'                 => 'INSERT INTO `' . $dbPrefix,
                'ALTER TABLE `'                 => 'ALTER TABLE `' . $dbPrefix,
                'RENAME TABLE `'                => 'RENAME TABLE `' . $dbPrefix,
                'ALTER IGNORE TABLE `'          => 'ALTER IGNORE TABLE `' . $dbPrefix,
                'REFERENCES `'                  => 'REFERENCES `' . $dbPrefix,
                'UPDATE `'                      => 'UPDATE `' . $dbPrefix,
                ' FROM `'                       => ' FROM `' . $dbPrefix,
                ' TO `'                         => ' TO `' . $dbPrefix,
            ];
            $search  = array_keys($searchReplace);
            $replace = array_values($searchReplace);
        }

        $query   = '';
        $lines   = (array)file($sqlFile);

        foreach ($lines as $line) {
            if (empty($line) || strpos($line, '--') === 0 || strpos($line, '#') === 0 || strpos($line, '/*!') === 0) {
                continue;
            }

            $query .= $line;

            if (!preg_match('/;\s*$/', $line)) {
                continue;
            }

            if (!empty($dbPrefix)) {
                $query = (string)str_replace($search, $replace, $query);
            }

            if (!empty($query)) {
                yield $query;
            }

            $query = '';
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function functionExists(string $name): bool
    {
        static $_exists     = [];
        static $_disabled   = null;
        static $_shDisabled = null;

        if (isset($_exists[$name]) || array_key_exists($name, $_exists)) {
            return $_exists[$name];
        }

        if (!function_exists($name)) {
            return $_exists[$name] = false;
        }

        if ($_disabled === null) {
            $_disabled = (string)ini_get('disable_functions');
            $_disabled = explode(',', $_disabled);
            $_disabled = array_map('trim', $_disabled);
        }

        if (is_array($_disabled) && in_array($name, $_disabled)) {
            return $_exists[$name] = false;
        }

        if ($_shDisabled === null) {
            $_shDisabled = (string)ini_get('suhosin.executor.func.blacklist');
            $_shDisabled = explode(',', $_shDisabled);
            $_shDisabled = array_map('trim', $_shDisabled);
        }

        if (is_array($_shDisabled) && in_array($name, $_shDisabled)) {
            return $_exists[$name] = false;
        }

        return $_exists[$name] = true;
    }

    /**
     * @return string
     */
    public static function findPhpCliPath(): string
    {
        static $cliPath;

        if ($cliPath !== null) {
            return $cliPath;
        }

        // since 1.7.0
        if (defined('PHP_BINDIR') && is_string(PHP_BINDIR) && strlen(PHP_BINDIR) >= 3) {
            $variants = ['php-cli', 'php'];
            foreach ($variants as $variant) {
                if (@is_file($cliPath = PHP_BINDIR . DIRECTORY_SEPARATOR . $variant)) {
                    return $cliPath;
                }
            }
        }
        //

        $cliPath = '/usr/bin/php';

        if (!self::functionExists('exec')) {
            return $cliPath;
        }

        $variants = ['php-cli', 'php5-cli', 'php5', 'php'];
        foreach ($variants as $variant) {
            $out = @exec(sprintf('command -v %s 2>&1', escapeshellarg($variant)), $lines, $status);
            if ($status != 0 || empty($out)) {
                continue;
            }
            $cliPath = $out;
            break;
        }

        return $cliPath;
    }

    /**
     * @param string $ipAddress
     *
     * @return string
     */
    public static function getIpAddressInfoUrl(string $ipAddress): string
    {
        $url = 'https://who.is/whois-ip/ip-address/' . $ipAddress;
        return (string)hooks()->applyFilters('get_ip_address_info_url', $url, $ipAddress);
    }

    /**
     * @param string $userAgent
     *
     * @return string
     */
    public static function getUserAgentInfoUrl(string $userAgent): string
    {
        $url = 'javascript:;';
        return (string)hooks()->applyFilters('get_user_agent_info_url', $url, $userAgent);
    }

    /**
     * @param string $string
     * @param string $separator
     *
     * @return array
     */
    public static function getArrayFromString(string $string, string $separator = ','): array
    {
        $string = trim((string)$string);
        if (empty($string)) {
            return [];
        }
        /** @var array $array */
        $array = (array)explode($separator, $string);
        $array = array_map('trim', $array);
        return array_unique($array);
    }

    /**
     * @param array $array
     * @param string $glue
     *
     * @return string
     */
    public static function getStringFromArray(array $array, string $glue = ', '): string
    {
        if (empty($array)) {
            return '';
        }
        return implode($glue, $array);
    }

    /**
     * @param string $appendThis
     *
     * @return string
     */
    public static function getCurrentHostUrl(string $appendThis = ''): string
    {
        return apps()->getCurrentHostUrl($appendThis);
    }

    /**
     * @param int $bytes
     * @param int $precision
     *
     * @return string
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        // $bytes /= pow(1024, $pow);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
