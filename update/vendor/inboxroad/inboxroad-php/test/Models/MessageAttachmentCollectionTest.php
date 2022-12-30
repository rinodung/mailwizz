<?php declare(strict_types=1);

namespace Inboxroad\Test\Models;

use Inboxroad\Models\MessageAttachment;
use Inboxroad\Models\MessageAttachmentCollection;
use Inboxroad\Models\MessageAttachmentCollectionInterface;
use Inboxroad\Models\MessageAttachmentInterface;
use Inboxroad\Test\Base;

/**
 * Class MessageAttachmentCollectionTest
 * @package Inboxroad\Test\Models
 */
class MessageAttachmentCollectionTest extends Base
{
    /**
     * @var string
     */
    private $name = 'test.txt';

    /**
     * @var string
     */
    private $content = 'file content';

    /**
     * @var string
     */
    private $mimeType = 'text/plain';
    
    /**
     * @return void
     */
    final public function testAdd(): void
    {
        $collection = new MessageAttachmentCollection();
        $result = $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));
        
        $this->assertInstanceOf(MessageAttachmentCollectionInterface::class, $result);
        $this->assertCount(1, $collection->toArray());
    }

    /**
     * @return void
     */
    final public function testGetItems(): void
    {
        $collection = new MessageAttachmentCollection();
        $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));
        
        $this->assertCount(1, $collection->getItems());
        $this->assertInstanceOf(MessageAttachmentInterface::class, $collection->getItems()[0]);
    }

    /**
     * @return void
     */
    final public function testToArray(): void
    {
        $collection = new MessageAttachmentCollection();
        $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));
        $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));
        $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));

        $this->assertCount(3, $collection->toArray());
        $this->assertArrayHasKey('name', $collection->toArray()[0]);
        $this->assertArrayHasKey('content', $collection->toArray()[0]);
        $this->assertArrayHasKey('mimeType', $collection->toArray()[0]);
    }
    
    /**
     * @return void
     */
    final public function testToInboxroadArray(): void
    {
        $collection = new MessageAttachmentCollection();
        $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));
        $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));
        $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));
        
        $this->assertCount(3, $collection->toInboxroadArray());
        $this->assertArrayHasKey('filename', $collection->toInboxroadArray()[0]);
        $this->assertArrayHasKey('file_data', $collection->toInboxroadArray()[0]);
        $this->assertArrayHasKey('mime_type', $collection->toInboxroadArray()[0]);
    }

    /**
     * @return void
     */
    final public function testFromArray(): void
    {
        $collection = MessageAttachmentCollection::fromArray([
            ['name' => $this->name, 'content' => $this->content, 'mimeType' => $this->mimeType],
            ['name' => $this->name, 'content' => $this->content, 'mimeType' => $this->mimeType],
            ['name' => $this->name, 'content' => $this->content, 'mimeType' => $this->mimeType],
        ]);
        $collection->add(new MessageAttachment($this->name, $this->content, $this->mimeType));
        
        $this->assertCount(4, $collection->toArray());
    }
}
