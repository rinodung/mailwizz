<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Class MessageHeader
 * @package Inboxroad\Models
 */
class MessageHeader implements MessageHeaderInterface
{
    /**
     * @var string
     */
    private $key = '';

    /**
     * @var string
     */
    private $value = '';

    /**
     * MessageHeader constructor.
     *
     * @param string $key
     * @param string $value
     */
    public function __construct(string $key, string $value)
    {
        $this
            ->setKey($key)
            ->setValue($value);
    }

    /**
     * @param string $key
     *
     * @return MessageHeaderInterface
     */
    public function setKey(string $key): MessageHeaderInterface
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $value
     *
     * @return MessageHeaderInterface
     */
    public function setValue(string $value): MessageHeaderInterface
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'key'   => $this->key,
            'value' => $this->value,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toInboxroadArray(): array
    {
        return [$this->key => $this->value];
    }
}
