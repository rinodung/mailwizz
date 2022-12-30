<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

use Enqueue\Util\UUID;

/**
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 2.0.0
 */

class QueueMonitorProperties implements QueueMonitorPropertiesInterface
{
    /**
     * @var array
     */
    protected $properties = [];

    /**
     * QueueMonitorProperties constructor.
     *
     * @param array $properties
     * @param bool $mergeWithDefaults
     */
    public function __construct(array $properties, bool $mergeWithDefaults = false)
    {
        $this->properties = $properties;

        if ($mergeWithDefaults) {
            $this->properties = array_merge($this->getDefaultProperties(), $this->properties);
        }
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getProperty(string $key, $defaultValue = null)
    {
        return isset($this->properties[$key]) || array_key_exists($key, $this->properties) ? $this->properties[$key] : $defaultValue;
    }

    /**
     * @return array
     */
    public function getDefaultProperties(): array
    {
        return [
            'id'            => UUID::generate(),
            'user_id'       => !is_cli() ? user()->getId() : null,
            'customer_id'   => !is_cli() ? customer()->getId() : null,
            'status'        => QueueStatus::WAITING,
        ];
    }
}
