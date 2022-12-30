<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Interface MessageHeaderInterface
 * @package Inboxroad\Models
 */
interface MessageHeaderInterface
{
    /**
     * MessageHeader constructor.
     *
     * @param string $key
     * @param string $value
     */
    public function __construct(string $key, string $value);
    
    /**
     * @param string $key
     *
     * @return MessageHeaderInterface
     */
    public function setKey(string $key): MessageHeaderInterface;

    /**
     * @return string
     */
    public function getKey(): string;

    /**
     * @param string $value
     *
     * @return MessageHeaderInterface
     */
    public function setValue(string $value): MessageHeaderInterface;

    /**
     * @return string
     */
    public function getValue(): string;

    /**
     * @return array<string, string>
     */
    public function toArray(): array;

    /**
     * @return array<string, string>
     */
    public function toInboxroadArray(): array;
}
