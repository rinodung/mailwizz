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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\Exception\LockReleasingException;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

class FileMutex extends BaseMutex
{
    /**
     * @var string
     */
    public $mutexPath = 'common.runtime.mutex';

    /**
     * @var int
     */
    public $fileMode = 0666;

    /**
     * @var int
     */
    public $dirMode = 0777;

    /**
     * @var LockFactory
     */
    protected $_factory;

    /**
     * @var array
     */
    protected $_activeLocks = [];

    /**
     * @return void
     */
    public function init()
    {
        $this->mutexPath = (string)Yii::getPathOfAlias($this->mutexPath);

        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->mutexPath)) {
            $filesystem->mkdir($this->mutexPath, $this->dirMode);
        }

        parent::init();
    }

    /**
     * @inheritDoc
     */
    public function acquire(string $name, int $timeout = 0): bool
    {
        $name = $this->generateUniqueKey($name);

        if (!$this->doAcquireLock($name, $timeout)) {
            return false;
        }

        $this->_lockNames[] = $name;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function release(string $name): bool
    {
        $name = $this->generateUniqueKey($name);

        if (!$this->doReleaseLock($name)) {
            return false;
        }

        $index = array_search($name, $this->_lockNames);
        if ($index !== false) {
            unset($this->_lockNames[$index]);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isAcquired(string $name): bool
    {
        $name = $this->generateUniqueKey($name);

        if (!isset($this->_activeLocks[$name])) {
            return false;
        }

        /** @var Lock $lock */
        $lock = $this->_activeLocks[$name];

        return $lock->isAcquired();
    }

    /**
     * @inheritDoc
     */
    public function isExpired(string $name): bool
    {
        $name = $this->generateUniqueKey($name);

        if (!isset($this->_activeLocks[$name])) {
            return false;
        }

        /** @var Lock $lock */
        $lock = $this->_activeLocks[$name];

        return $lock->isExpired();
    }

    /**
     * @inheritDoc
     */
    public function getRemainingLifetime(string $name): ?float
    {
        $name = $this->generateUniqueKey($name);

        if (!isset($this->_activeLocks[$name])) {
            return null;
        }

        /** @var Lock $lock */
        $lock = $this->_activeLocks[$name];

        return $lock->getRemainingLifetime();
    }

    /**
     * @inheritDoc
     */
    public function refresh(string $name, ?float $ttl = null): void
    {
        $name = $this->generateUniqueKey($name);

        if (!isset($this->_activeLocks[$name])) {
            return;
        }

        /** @var Lock $lock */
        $lock = $this->_activeLocks[$name];

        try {
            $lock->refresh($ttl);
        } catch (LockConflictedException | LockAcquiringException $e) {
        }
    }

    /**
     * @param string $name
     * @param int $timeout
     *
     * @return bool
     */
    protected function doAcquireLock(string $name, int $timeout = 0): bool
    {
        if (!isset($this->_activeLocks[$name])) {
            $this->_activeLocks[$name] = $this->getFactory()->createLock($name, $this->ttl, $this->autoRelease);
        }

        /** @var Lock $lock */
        $lock = $this->_activeLocks[$name];

        $timeout    = $timeout < 0 ? 0 : $timeout;
        $now        = time();
        $expireAt   = $now + $timeout;

        while ($expireAt >= $now) {
            try {
                $hasBeenAcquired = !$lock->isAcquired() && $lock->acquire();
                if ($hasBeenAcquired) {
                    return true;
                }
            } catch (LockConflictedException | LockAcquiringException $e) {
            }
            if ($timeout === 0) {
                break;
            }
            usleep($this->acquireLockWaitTimeout);
            $now = time();
        }

        return false;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function doReleaseLock(string $name): bool
    {
        if (!isset($this->_activeLocks[$name])) {
            return false;
        }

        /** @var Lock $lock */
        $lock = $this->_activeLocks[$name];
        if (!$lock->isAcquired()) {
            return false;
        }

        try {
            $lock->release();
            $released = true;
        } catch (LockReleasingException $e) {
            $released = false;
        }

        unset($this->_activeLocks[$name]);
        return $released;
    }

    /**
     * @return LockFactory
     */
    protected function getFactory(): LockFactory
    {
        if ($this->_factory === null) {
            $this->_factory = new LockFactory(new FlockStore($this->mutexPath));
        }
        return $this->_factory;
    }
}
