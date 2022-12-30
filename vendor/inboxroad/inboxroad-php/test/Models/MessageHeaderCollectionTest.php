<?php declare(strict_types=1);

namespace Inboxroad\Test\Models;

use Inboxroad\Models\MessageHeader;
use Inboxroad\Models\MessageHeaderCollection;
use Inboxroad\Models\MessageHeaderCollectionInterface;
use Inboxroad\Models\MessageHeaderInterface;
use Inboxroad\Test\Base;

/**
 * Class MessageHeaderCollectionTest
 * @package Inboxroad\Test\Models
 */
class MessageHeaderCollectionTest extends Base
{
    /**
     * @var string
     */
    private $key = 'X-Inboxroad';

    /**
     * @var string
     */
    private $value = 'Company';
    
    /**
     * @return void
     */
    final public function testAdd(): void
    {
        $collection = new MessageHeaderCollection();
        $result = $collection->add(new MessageHeader($this->key, $this->value));
        
        $this->assertInstanceOf(MessageHeaderCollectionInterface::class, $result);
        $this->assertCount(1, $collection->toArray());
    }

    /**
     * @return void
     */
    final public function testGetItems(): void
    {
        $collection = new MessageHeaderCollection();
        $collection->add(new MessageHeader($this->key, $this->value));

        $this->assertCount(1, $collection->getItems());
        $this->assertInstanceOf(MessageHeaderInterface::class, $collection->getItems()[0]);
    }

    /**
     * @return void
     */
    final public function testToArray(): void
    {
        $collection = new MessageHeaderCollection();
        $collection->add(new MessageHeader($this->key, $this->value));
        $collection->add(new MessageHeader($this->key, $this->value));
        $collection->add(new MessageHeader($this->key, $this->value));
        
        $this->assertCount(3, $collection->toArray());
        $this->assertArrayHasKey('key', $collection->toArray()[0]);
        $this->assertArrayHasKey('value', $collection->toArray()[0]);
    }

    /**
     * @return void
     */
    final public function testToInboxroadArray(): void
    {
        $collection = new MessageHeaderCollection();
        $collection->add(new MessageHeader($this->key, $this->value));
        $collection->add(new MessageHeader($this->key, $this->value));
        $collection->add(new MessageHeader($this->key, $this->value));

        $this->assertCount(1, $collection->toInboxroadArray());
        $this->assertEquals([$collection->getItems()[0]->getKey() => $collection->getItems()[0]->getValue()], $collection->toInboxroadArray());
        
        $collection->add(new MessageHeader('X-Test', 'Test'));
        $this->assertCount(2, $collection->toInboxroadArray());
    }

    /**
     * @return void
     */
    final public function testFromArray(): void
    {
        $collection = MessageHeaderCollection::fromArray([
            ['key' => $this->key, 'value' => $this->value],
            ['key' => $this->key, 'value' => $this->value],
            ['key' => $this->key, 'value' => $this->value],
        ]);
        $collection->add(new MessageHeader($this->key, $this->value));
        
        $this->assertCount(4, $collection->toArray());
    }
}
