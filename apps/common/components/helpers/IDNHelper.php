<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * IDNHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.8
 */

class IDNHelper
{
    /**
     * @param string $value
     *
     * @return string
     */
    public static function encode(string $value): string
    {
        $parsed = false;
        if (CommonHelper::functionExists('idn_to_ascii')) {
            $variant = null;
            if (defined('INTL_IDNA_VARIANT_UTS46')) {
                $variant = INTL_IDNA_VARIANT_UTS46;
            } elseif (defined('INTL_IDNA_VARIANT_2003')) {
                $variant = INTL_IDNA_VARIANT_2003;
            }

            if ($variant) {
                $value  = (string)idn_to_ascii($value, 0, $variant);
                $parsed = true;
            }
        }

        if (!$parsed) {
            try {
                $idna   = new Net_IDNA2();
                $_value = (string)@$idna->encode($value);
                $value  = $_value;
            } catch (Exception $e) {
            }
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public static function decode(string $value): string
    {
        $parsed = false;
        if (CommonHelper::functionExists('idn_to_utf8')) {
            $variant = null;
            if (defined('INTL_IDNA_VARIANT_UTS46')) {
                $variant = INTL_IDNA_VARIANT_UTS46;
            } elseif (defined('INTL_IDNA_VARIANT_2003')) {
                $variant = INTL_IDNA_VARIANT_2003;
            }

            if ($variant) {
                $value  = (string)idn_to_utf8($value, 0, $variant);
                $parsed = true;
            }
        }

        if (!$parsed) {
            try {
                $idna   = new Net_IDNA2();
                $_value = (string)@$idna->decode($value);
                $value  = $_value;
            } catch (Exception $e) {
            }
        }

        return $value;
    }
}
