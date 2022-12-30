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

class QueueMessage implements QueueMessageInterface
{
    /**
     * @var string
     */
    protected $body = '';

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var null|int
     */
    protected $deliveryDelay;

    /**
     * @var null|int
     */
    protected $priority;

    /**
     * @var null|int
     */
    protected $timeToLive;

    /**
     * QueueMessage constructor.
     *
     * @param string $body
     * @param array $properties
     * @param array $headers
     * @param int|null $deliveryDelay
     * @param int|null $priority
     * @param int|null $timeToLive
     */
    public function __construct(
        string $body = '',
        array $properties = [],
        array $headers = [],
        ?int $deliveryDelay = null,
        ?int $priority = null,
        ?int $timeToLive = null
    ) {
        $this->body = $body;
        $this->properties = $properties;
        $this->headers = $headers;
        $this->deliveryDelay = $deliveryDelay;
        $this->priority = $priority;
        $this->timeToLive = $timeToLive;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return null|int
     */
    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    /**
     * @return null|int
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @return null|int
     */
    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }
}
