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

abstract class BaseMutex extends CApplicationComponent implements MutexInterface
{
    /**
     * @see https://github.com/symfony/symfony/issues/40325
     *
     * @var bool
     */
    public $autoRelease = false;

    /**
     * @var bool
     */
    public $shutdownCleanup = true;

    /**
     * @var bool
     */
    public $hashKey = true;

    /**
     * @var string
     */
    public $keyPrefix = '';

    /**
     * @var int
     */
    public $ttl = 86400;

    /**
     * Time to wait between acquire checks, in microseconds.
     *
     * @var int
     */
    public $acquireLockWaitTimeout = 10000;

    /**
     * @var array
     */
    protected $_lockNames = [];

    /**
     * Initializes the mutex component.
     *
     * @return void
     */
    public function init()
    {
        if ($this->autoRelease) {
            register_shutdown_function(function (): void {
                if (!$this->shutdownCleanup) {
                    return;
                }
                foreach ($this->_lockNames as $lockName) {
                    $this->release($lockName);
                }
            });
        }

        if ($this->keyPrefix === '') {
            $this->keyPrefix = app()->getId();
        }

        parent::init();
    }

    /**
     * @param string $name
     * @param int $timeout
     *
     * @return bool
     */
    abstract public function acquire(string $name, int $timeout = 0): bool;

    /**
     * @param string $name
     *
     * @return bool
     */
    abstract public function release(string $name): bool;

    /**
     * @param string $name
     *
     * @return bool
     */
    abstract public function isAcquired(string $name): bool;

    /**
     * @param string $name
     *
     * @return bool
     */
    abstract public function isExpired(string $name): bool;

    /**
     * @param string $name
     *
     * @return float|null
     */
    abstract public function getRemainingLifetime(string $name): ?float;

    /**
     * @param string $name
     * @param float|null $ttl
     */
    abstract public function refresh(string $name, ?float $ttl = null): void;

    /**
     * @param bool $active
     *
     * @return bool
     */
    public function setConnectionActive(bool $active = true): bool
    {
        return true;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function generateUniqueKey(string $key): string
    {
        return $this->hashKey ? md5($this->keyPrefix . $key) : $this->keyPrefix . $key;
    }
}
