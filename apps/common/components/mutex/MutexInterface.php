<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

interface MutexInterface
{
    /**
     * @param string $name
     * @param int $timeout
     *
     * @return bool
     */
    public function acquire(string $name, int $timeout = 0): bool;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function release(string $name): bool;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isAcquired(string $name): bool;

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isExpired(string $name): bool;

    /**
     * @param string $name
     *
     * @return float|null
     */
    public function getRemainingLifetime(string $name): ?float;

    /**
     * @param string $name
     * @param float|null $ttl
     */
    public function refresh(string $name, ?float $ttl = null): void;
}
