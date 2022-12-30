<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Class MessageAttachmentCollection
 * @package Inboxroad\Models
 */
class MessageAttachmentCollection implements MessageAttachmentCollectionInterface
{
    /**
     * @var array<MessageAttachmentInterface>
     */
    private $collection = [];

    /**
     * @param MessageAttachmentInterface $attachment
     *
     * @return MessageAttachmentCollectionInterface
     */
    public function add(MessageAttachmentInterface $attachment): MessageAttachmentCollectionInterface
    {
        $this->collection[] = $attachment;
        return $this;
    }

    /**
     * @return array<int, MessageAttachmentInterface>
     */
    public function getItems(): array
    {
        return $this->collection;
    }
    
    /**
     * @return array<int, array<string, string>>
     */
    public function toArray(): array
    {
        $data = [];
        foreach ($this->collection as $item) {
            $data[] = $item->toArray();
        }
        return $data;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function toInboxroadArray(): array
    {
        $data = [];
        foreach ($this->collection as $item) {
            $data[] = $item->toInboxroadArray();
        }
        return $data;
    }

    /**
     * @param array<int, array<string, string>> $items
     *
     * @return MessageAttachmentCollectionInterface
     */
    public static function fromArray(array $items): MessageAttachmentCollectionInterface
    {
        $collection = new self();
        foreach ($items as $item) {
            $collection->add(new MessageAttachment($item['name'] ?? '', $item['content'] ?? '', $item['mimeType'] ?? ''));
        }
        return $collection;
    }
}
