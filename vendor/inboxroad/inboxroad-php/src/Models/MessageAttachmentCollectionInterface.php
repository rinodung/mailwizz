<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Interface MessageAttachmentCollectionInterface
 * @package Inboxroad\Models
 */
interface MessageAttachmentCollectionInterface
{
    /**
     * @param MessageAttachmentInterface $attachment
     *
     * @return MessageAttachmentCollectionInterface
     */
    public function add(MessageAttachmentInterface $attachment): MessageAttachmentCollectionInterface;

    /**
     * @return array<int, MessageAttachmentInterface>
     */
    public function getItems(): array;
    
    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toInboxroadArray(): array;

    /**
     * @param array<int, array<string, string>> $items
     *
     * @return MessageAttachmentCollectionInterface
     */
    public static function fromArray(array $items): MessageAttachmentCollectionInterface;
}
