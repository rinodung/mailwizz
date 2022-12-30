<?php declare(strict_types=1);

namespace Inboxroad\Test\Models;

use Inboxroad\Models\MessageHeader;
use Inboxroad\Models\MessageHeaderInterface;
use Inboxroad\Test\Base;

/**
 * Class MessageHeaderTest
 * @package Inboxroad\Test\Models
 */
class MessageHeaderTest extends Base
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
     * @var MessageHeader
     */
    private $header;

    /**
     * @return void
     * @throws \ErrorException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->header = new MessageHeader($this->key, $this->value);
    }

    /**
     * @return void
     */
    final public function testSetKey(): void
    {
        $this->assertInstanceOf(MessageHeaderInterface::class, $this->header->setKey(''));
    }

    /**
     * @return void
     */
    final public function testGetKey(): void
    {
        $this->header->setKey('');
        $this->assertEquals('', $this->header->getKey());

        $this->header->setKey($this->key);
        $this->assertEquals($this->key, $this->header->getKey());
    }

    /**
     * @return void
     */
    final public function testSetValue(): void
    {
        $this->assertInstanceOf(MessageHeaderInterface::class, $this->header->setValue(''));
    }

    /**
     * @return void
     */
    final public function testGetValue(): void
    {
        $this->header->setValue('');
        $this->assertEquals('', $this->header->getValue());

        $this->header->setValue($this->value);
        $this->assertEquals($this->value, $this->header->getValue());
    }
    
    /**
     * @return void
     */
    final public function testToArray(): void
    {
        $this->assertCount(2, $this->header->toArray());
        $this->assertArrayHasKey('key', $this->header->toArray());
        $this->assertArrayHasKey('value', $this->header->toArray());
        
        $this->assertEquals($this->header->getKey(), $this->header->toArray()['key'] ?? '');
        $this->assertEquals($this->header->getValue(), $this->header->toArray()['value'] ?? '');
    }

    /**
     * @return void
     */
    final public function testToInboxroadArray(): void
    {
        $this->assertCount(1, $this->header->toInboxroadArray());
        $this->assertEquals([$this->header->getKey() => $this->header->getValue()], $this->header->toInboxroadArray());
    }
}
