<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * DeliveryServerHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.6.3
 */
class DeliveryServerHelper
{
    /**
     * @param string $str
     *
     * @return string
     */
    public static function getOptionCustomerCustomHeadersStringFromString(string $str): string
    {
        $_headers = explode("\n", $str);
        $headers  = [];
        $prefix   = (string)app_param('email.custom.header.prefix', '');

        foreach ($_headers as $header) {
            if (strpos($header, ':') === false) {
                continue;
            }

            [$name, $value] = explode(':', $header);

            if (stripos($name, 'x-') !== 0 || stripos($name, $prefix) === 0) {
                continue;
            }

            $headers[] = sprintf('%s:%s', $name, trim((string)$value));
        }

        return implode("\n", $headers);
    }

    /**
     * @param string $str
     *
     * @return array
     */
    public static function getOptionCustomerCustomHeadersArrayFromString(string $str): array
    {
        if (empty($str)) {
            return [];
        }

        $headers = [];
        $lines   = explode("\n", self::getOptionCustomerCustomHeadersStringFromString($str));
        foreach ($lines as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            [$name, $value] = explode(':', $line);
            $headers[] = ['name' => $name, 'value' => $value];
        }

        return $headers;
    }
}
