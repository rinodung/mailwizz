<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Class MessageHeaderCollection
 * @package Inboxroad\Models
 */
class MessageHeaderCollection implements MessageHeaderCollectionInterface
{
    /**
     * @var array<MessageHeaderInterface>
     */
    private $collection = [];

    /**
     * @param MessageHeaderInterface $header
     *
     * @return MessageHeaderCollectionInterface
     */
    public function add(MessageHeaderInterface $header): MessageHeaderCollectionInterface
    {
        $this->collection[] = $header;
        return $this;
    }

    /**
     * @return array<int, MessageHeaderInterface>
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
     * @return array<string, string>
     */
    public function toInboxroadArray(): array
    {
        $data = [];
        foreach ($this->getItems() as $header) {
            $data = array_merge($data, [$header->getKey() => $header->getValue()]);
        }
        
        return $data;
    }

    /**
     * @param array<int, array<string, string>> $items
     *
     * @return MessageHeaderCollectionInterface
     */
    public static function fromArray(array $items): MessageHeaderCollectionInterface
    {
        $collection = new self();
        foreach ($items as $item) {
            $collection->add(new MessageHeader($item['key'] ?? '', $item['value'] ?? ''));
        }
        return $collection;
    }
}
