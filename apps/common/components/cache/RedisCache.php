<?php declare(strict_types=1);
/**
 * RedisCache
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.5.0
 */

class RedisCache extends CCache
{
    /**
     * @var string
     */
    public $hostname = '127.0.0.1';

    /**
     * @var int
     */
    public $port = 6379;

    /**
     * @var int
     */
    public $database = 1;

    /**
     * @var string
     */
    public $password;

    /**
     * @var Doctrine\Common\Cache\PredisCache
     */
    protected $_cache;

    /**
     * @var Predis\Client
     */
    protected $_client;

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        // disable the serializer
        $this->serializer = false;
    }

    /**
     * @return Doctrine\Common\Cache\PredisCache
     */
    public function getCache()
    {
        if ($this->_cache !== null) {
            return $this->_cache;
        }

        $this->_client = new Predis\Client([
            'scheme'             => 'tcp',
            'host'               => $this->hostname,
            'port'               => $this->port,
            'database'           => $this->database,
            'password'           => $this->password,
            'read_write_timeout' => 0,
        ]);

        return $this->_cache = new Doctrine\Common\Cache\PredisCache($this->_client);
    }

    /**
     * @param bool $active
     *
     * @return void
     */
    public function setConnectionActive($active = true)
    {
        if ($active) {
            $this->getCache();
        } elseif ($this->_client !== null) {
            $this->_client->disconnect();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getValue($key)
    {
        // @phpstan-ignore-next-line
        return $this->getCache()->fetch($key);
    }

    /**
     * @inheritDoc
     */
    protected function setValue($key, $value, $expire = 0)
    {
        return $this->getCache()->save($key, $value, $expire);
    }

    /**
     * @inheritDoc
     */
    protected function addValue($key, $value, $expire = 0)
    {
        if ($this->getCache()->contains($key)) {
            return false;
        }
        return $this->getCache()->save($key, $value, $expire);
    }

    /**
     * @inheritDoc
     */
    protected function deleteValue($key)
    {
        return $this->getCache()->delete($key);
    }

    /**
     * @inheritDoc
     */
    protected function flushValues()
    {
        return $this->getCache()->flushAll();
    }
}
