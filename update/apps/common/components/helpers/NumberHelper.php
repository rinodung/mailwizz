<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * NumberHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.6.8
 */

class NumberHelper
{
    /**
     * @param int $min
     * @param int $max
     * @param int $step
     * @param string $prefix
     *
     * @return int
     */
    public static function pullUniqueFromRange(int $min = 1, int $max = 10, int $step = 1, string $prefix = ''): int
    {
        $range  = range($min, $max, $step);
        $key    = sha1($prefix . json_encode($range) . date('Ymd'));

        if (!mutex()->acquire($key, 5)) {
            shuffle($range);
            $number = array_shift($range);
            return (int)$number;
        }

        $cachedRange = cache()->get($key);
        $cachedRange = !empty($cachedRange) && is_array($cachedRange) ? $cachedRange : $range;

        shuffle($cachedRange);

        $number = array_shift($cachedRange);

        cache()->set($key, $cachedRange);

        mutex()->release($key);

        return (int)$number;
    }
}
