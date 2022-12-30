<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * FilterVarHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.5.9
 */

class FilterVarHelper
{
    /**
     * @param string $variable
     * @param int $filter
     * @param mixed $options
     *
     * @return mixed
     */
    public static function filter($variable, $filter = FILTER_DEFAULT, $options = [])
    {
        return filter_var($variable, $filter, $options);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public static function email(string $email): bool
    {
        static $validator;
        if ($validator === null) {
            $validator = new CEmailValidator();
            $validator->validateIDN = true;
        }
        return $validator->validateValue($email);
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public static function url(string $url): bool
    {
        // because it is not multibyte aware...
        // return self::filter($url, FILTER_VALIDATE_URL);
        return (bool)preg_match('/^https?.*/i', $url);
    }

    /**
     * @param string $domain
     *
     * @return bool
     */
    public static function domain(string $domain): bool
    {
        return (bool)preg_match('/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/m', $domain);
    }

    /**
     * @param string $ip
     *
     * @return bool
     */
    public static function ip(string $ip): bool
    {
        if (strpos($ip, '/') !== false) {
            $min = 0;
            $max = 32;

            // ipv6
            if (substr_count($ip, ':') > 1) {
                $min = 1;
                $max = 128;
            }
            $ip = explode('/', $ip, 2);

            if ((int)$ip[1] < $min || (int)$ip[1] > $max) {
                return false;
            }
            $ip = array_shift($ip);
        }
        return (bool)self::filter((string)$ip, FILTER_VALIDATE_IP);
    }

    /**
     * @param string $phone
     *
     * @return bool
     */
    public static function phoneUrl(string $phone): bool
    {
        return (bool)preg_match('/^tel:([\+0-9\s\(\)]{3,100})/i', $phone);
    }

    /**
     * @param string $mailto
     *
     * @return bool
     */
    public static function mailtoUrl(string $mailto): bool
    {
        if (!preg_match('/^mailto:((.*){5,1000})/i', $mailto, $matches)) {
            return false;
        }

        return self::email($matches[1]);
    }

    /**
     * @param string $input
     *
     * @return bool
     */
    public static function urlAnyScheme(string $input): bool
    {
        return self::url($input) || self::mailtoUrl($input) || self::phoneUrl($input);
    }
}
