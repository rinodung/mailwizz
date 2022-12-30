<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Redis\RedisConnectionFactory;
use Interop\Queue\Context;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueRedis extends QueueBase
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
    public $password = '';

    /**
     * @return Context
     * @throws Exception
     */
    public function getContext(): Context
    {
        if ($this->_context === null) {
            $connectionFactory = new RedisConnectionFactory([
                'scheme'             => 'tcp',
                'host'               => $this->hostname,
                'port'               => $this->port,
                'database'           => $this->database,
                'password'           => !empty($this->password) ? $this->password : null,
                'read_write_timeout' => 0,
            ]);
            $this->_context = $connectionFactory->createContext();
        }

        return $this->_context;
    }
}
