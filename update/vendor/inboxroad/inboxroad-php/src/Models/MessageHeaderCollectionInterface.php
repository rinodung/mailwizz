<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Interface MessageHeaderCollectionInterface
 * @package Inboxroad\Models
 */
interface MessageHeaderCollectionInterface
{
    /**
     * @param MessageHeaderInterface $header
     *
     * @return MessageHeaderCollectionInterface
     */
    public function add(MessageHeaderInterface $header): MessageHeaderCollectionInterface;

    /**
     * @return array<int, MessageHeaderInterface>
     */
    public function getItems(): array;

    /**
     * @return array<int, array<string, string>>
     */
    public function toArray(): array;

    /**
     * @return array<string, string>
     */
    public function toInboxroadArray(): array;
    
    /**
     * @param array<int, array<string, string>> $items
     *
     * @return MessageHeaderCollectionInterface
     */
    public static function fromArray(array $items): MessageHeaderCollectionInterface;
}
