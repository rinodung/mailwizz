<?php declare(strict_types=1);

namespace Inboxroad\Test\Models;

use Inboxroad\Models\MessageAttachment;
use Inboxroad\Models\MessageAttachmentInterface;
use Inboxroad\Test\Base;

/**
 * Class MessageAttachmentTest
 * @package Inboxroad\Test\Models
 */
class MessageAttachmentTest extends Base
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
     * @var MessageAttachment
     */
    private $attachment;

    /**
     * @return void
     * @throws \ErrorException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->attachment = new MessageAttachment($this->name, $this->content, $this->mimeType);
    }

    /**
     * @return void
     */
    final public function testSetName(): void
    {
        $this->assertInstanceOf(MessageAttachmentInterface::class, $this->attachment->setName(''));
    }

    /**
     * @return void
     */
    final public function testGetName(): void
    {
        $this->attachment->setName('');
        $this->assertEquals('', $this->attachment->getName());

        $this->attachment->setName($this->name);
        $this->assertEquals($this->name, $this->attachment->getName());
    }

    /**
     * @return void
     */
    final public function testSetContent(): void
    {
        $this->assertInstanceOf(MessageAttachmentInterface::class, $this->attachment->setContent(''));
    }

    /**
     * @return void
     */
    final public function testGetContent(): void
    {
        $this->attachment->setContent('');
        $this->assertEquals('', $this->attachment->getContent());

        $this->attachment->setContent($this->content);
        $this->assertEquals($this->content, $this->attachment->getContent());
    }

    /**
     * @return void
     */
    final public function testSetMimeType(): void
    {
        $this->assertInstanceOf(MessageAttachmentInterface::class, $this->attachment->setMimeType(''));
    }

    /**
     * @return void
     */
    final public function testGetMimeType(): void
    {
        $this->attachment->setMimeType('');
        $this->assertEquals('', $this->attachment->getMimeType());

        $this->attachment->setMimeType($this->mimeType);
        $this->assertEquals($this->mimeType, $this->attachment->getMimeType());
    }

    /**
     * @return void
     */
    final public function testToArray(): void
    {
        $this->assertCount(3, $this->attachment->toArray());
        $this->assertArrayHasKey('name', $this->attachment->toArray());
        $this->assertArrayHasKey('content', $this->attachment->toArray());
        $this->assertArrayHasKey('mimeType', $this->attachment->toArray());

        $this->assertEquals($this->attachment->getName(), $this->attachment->toArray()['name'] ?? '');
        $this->assertEquals($this->attachment->getContent(), $this->attachment->toArray()['content'] ?? '');
        $this->assertEquals($this->attachment->getMimeType(), $this->attachment->toArray()['mimeType'] ?? '');
    }
    
    /**
     * @return void
     */
    final public function testToInboxroadArray(): void
    {
        $this->assertCount(3, $this->attachment->toArray());
        $this->assertArrayHasKey('filename', $this->attachment->toInboxroadArray());
        $this->assertArrayHasKey('file_data', $this->attachment->toInboxroadArray());
        $this->assertArrayHasKey('mime_type', $this->attachment->toInboxroadArray());
        
        $this->assertEquals($this->attachment->getName(), $this->attachment->toInboxroadArray()['filename'] ?? '');
        $this->assertEquals($this->attachment->getContent(), base64_decode($this->attachment->toInboxroadArray()['file_data'] ?? ''));
        $this->assertEquals($this->attachment->getMimeType(), $this->attachment->toInboxroadArray()['mime_type'] ?? '');
    }
}
