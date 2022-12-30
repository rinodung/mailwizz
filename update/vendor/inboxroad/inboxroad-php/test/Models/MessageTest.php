<?php declare(strict_types=1);

namespace Inboxroad\Test\Models;

use Inboxroad\Models\Message;
use Inboxroad\Models\MessageAttachment;
use Inboxroad\Models\MessageAttachmentCollection;
use Inboxroad\Models\MessageAttachmentCollectionInterface;
use Inboxroad\Models\MessageHeader;
use Inboxroad\Models\MessageHeaderCollection;
use Inboxroad\Models\MessageHeaderCollectionInterface;
use Inboxroad\Models\MessageInterface;
use Inboxroad\Test\Base;

/**
 * Class MessageTest
 * @package Inboxroad\Test\Models
 */
class MessageTest extends Base
{
    /**
     * @var string
     */
    private $messageId = '1234-1234-1234-1234';

    /**
     * @var string
     */
    private $email = 'test@inboxroad.com';

    /**
     * @var string
     */
    private $name = 'Test Inboxroad';

    /**
     * @var string
     */
    private $subject = 'Email subject';

    /**
     * @var string
     */
    private $text = 'Email plain text';

    /**
     * @var string
     */
    private $html = '<strong>Email html content</strong>';

    /**
     * @var MessageHeaderCollectionInterface
     */
    private $headers;

    /**
     * @var MessageAttachmentCollectionInterface
     */
    private $attachments;

    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->message = new Message();

        $this->headers = new MessageHeaderCollection();
        $this->headers->add(new MessageHeader('X-Inboxroad', 'Company'));
        
        $this->attachments = new MessageAttachmentCollection();
        $this->attachments->add(new MessageAttachment('text.txt', base64_encode('text'), 'text/plain'));
    }

    /**
     * @return void
     */
    final public function testSetMessageId(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setMessageId($this->messageId));
    }

    /**
     * @return void
     */
    final public function testGetMessageId(): void
    {
        $this->message->setMessageId($this->messageId);
        
        $this->assertIsString($this->message->getMessageId());
        $this->assertEquals($this->messageId, $this->message->getMessageId());
    }

    /**
     * @return void
     */
    final public function testSetFromEmail(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setFromEmail($this->email));
    }

    /**
     * @return void
     */
    final public function testGetFromEmail(): void
    {
        $this->message->setFromEmail($this->email);

        $this->assertIsString($this->message->getFromEmail());
        $this->assertEquals($this->email, $this->message->getFromEmail());
    }

    /**
     * @return void
     */
    final public function testSetFromName(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setFromName($this->name));
    }

    /**
     * @return void
     */
    final public function testGetFromName(): void
    {
        $this->message->setFromName($this->name);

        $this->assertIsString($this->message->getFromName());
        $this->assertEquals($this->name, $this->message->getFromName());
    }

    /**
     * @return void
     */
    final public function testSetToEmail(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setToEmail($this->email));
    }

    /**
     * @return void
     */
    final public function testGetToEmail(): void
    {
        $this->message->setToEmail($this->email);

        $this->assertIsString($this->message->getToEmail());
        $this->assertEquals($this->email, $this->message->getToEmail());
    }

    /**
     * @return void
     */
    final public function testSetToName(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setToName($this->name));
    }

    /**
     * @return void
     */
    final public function testGetToName(): void
    {
        $this->message->setToName($this->name);

        $this->assertIsString($this->message->getToName());
        $this->assertEquals($this->name, $this->message->getToName());
    }

    /**
     * @return void
     */
    final public function testSetReplyToEmail(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setReplyToEmail($this->email));
    }

    /**
     * @return void
     */
    final public function testGetReplyToEmail(): void
    {
        $this->message->setReplyToEmail($this->email);

        $this->assertIsString($this->message->getReplyToEmail());
        $this->assertEquals($this->email, $this->message->getReplyToEmail());
    }

    /**
     * @return void
     */
    final public function testSetSubject(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setSubject($this->subject));
    }

    /**
     * @return void
     */
    final public function testGetSubject(): void
    {
        $this->message->setSubject($this->subject);

        $this->assertIsString($this->message->getSubject());
        $this->assertEquals($this->subject, $this->message->getSubject());
    }

    /**
     * @return void
     */
    final public function testSetText(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setText($this->text));
    }

    /**
     * @return void
     */
    final public function testGetText(): void
    {
        $this->message->setText($this->text);

        $this->assertIsString($this->message->getText());
        $this->assertEquals($this->text, $this->message->getText());
    }

    /**
     * @return void
     */
    final public function testSetHtml(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setText($this->html));
    }

    /**
     * @return void
     */
    final public function testGetHtml(): void
    {
        $this->message->setHtml($this->html);

        $this->assertIsString($this->message->getHtml());
        $this->assertEquals($this->html, $this->message->getHtml());
    }

    /**
     * @return void
     */
    final public function testSetHeaders(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setHeaders($this->headers));
    }

    /**
     * @return void
     */
    final public function testGetHeaders(): void
    {
        $this->message->setHeaders($this->headers);

        $this->assertInstanceOf(MessageHeaderCollectionInterface::class, $this->message->getHeaders());
        $this->assertCount(1, $this->message->getHeaders()->toArray());
    }

    /**
     * @return void
     */
    final public function testSetAttachments(): void
    {
        $this->assertInstanceOf(Message::class, $this->message->setAttachments($this->attachments));
    }

    /**
     * @return void
     */
    final public function testGetAttachments(): void
    {
        $this->message->setAttachments($this->attachments);

        $this->assertInstanceOf(MessageAttachmentCollectionInterface::class, $this->message->getAttachments());
        $this->assertCount(1, $this->message->getAttachments()->toArray());
    }

    /**
     * @return void
     */
    final public function testFromArray(): void
    {
        $message = Message::fromArray([
            'fromEmail'     => $this->email,
            'toEmail'       => $this->email,
            'toName'        => $this->name,
            'replyToEmail'  => $this->email,
            'subject'       => $this->subject,
            'text'          => $this->text,
            'html'          => $this->html,
            'headers'       => $this->headers->toArray(),
            'attachments'   => $this->attachments->toArray()
        ]);

        $this->assertIsString($message->getFromEmail());
        $this->assertEquals($this->email, $message->getFromEmail());

        $this->assertIsString($message->getToEmail());
        $this->assertEquals($this->email, $message->getToEmail());

        $this->assertIsString($message->getToName());
        $this->assertEquals($this->name, $message->getToName());

        $this->assertIsString($message->getReplyToEmail());
        $this->assertEquals($this->email, $message->getReplyToEmail());

        $this->assertIsString($message->getSubject());
        $this->assertEquals($this->subject, $message->getSubject());

        $this->assertIsString($message->getText());
        $this->assertEquals($this->text, $message->getText());

        $this->assertIsString($message->getHtml());
        $this->assertEquals($this->html, $message->getHtml());

        $this->assertInstanceOf(MessageHeaderCollectionInterface::class, $message->getHeaders());
        $this->assertCount(1, $message->getHeaders()->toArray());

        $this->assertInstanceOf(MessageAttachmentCollectionInterface::class, $message->getAttachments());
        $this->assertCount(1, $message->getAttachments()->toArray());
    }
    
    /**
     * @return void
     */
    final public function testToArray(): void
    {
        $message = (new Message())
            ->setFromEmail($this->email)
            ->setFromName($this->name)
            ->setToEmail($this->email)
            ->setToName($this->name)
            ->setReplyToEmail($this->email)
            ->setSubject($this->subject)
            ->setText($this->text)
            ->setHtml($this->html)
            ->setHeaders($this->headers)
            ->setAttachments($this->attachments);

        $params = $message->toArray();

        $this->assertArrayHasKey('fromEmail', $params);
        $this->assertIsString($params['fromEmail']);
        $this->assertEquals($this->email, $params['fromEmail']);

        $this->assertArrayHasKey('fromName', $params);
        $this->assertIsString($params['fromName']);
        $this->assertEquals($this->name, $params['fromName']);

        $this->assertArrayHasKey('toEmail', $params);
        $this->assertIsString($params['toEmail']);
        $this->assertEquals($this->email, $params['toEmail']);

        $this->assertArrayHasKey('toName', $params);
        $this->assertIsString($params['toName']);
        $this->assertEquals($this->name, $params['toName']);

        $this->assertArrayHasKey('replyToEmail', $params);
        $this->assertIsString($params['replyToEmail']);
        $this->assertEquals($this->email, $params['replyToEmail']);

        $this->assertArrayHasKey('subject', $params);
        $this->assertIsString($params['subject']);
        $this->assertEquals($this->subject, $params['subject']);

        $this->assertArrayHasKey('text', $params);
        $this->assertIsString($params['text']);
        $this->assertEquals($this->text, $params['text']);

        $this->assertArrayHasKey('html', $params);
        $this->assertIsString($params['html']);
        $this->assertEquals($this->html, $params['html']);

        $this->assertArrayHasKey('headers', $params);
        $this->assertInstanceOf(MessageHeaderCollectionInterface::class, $message->getHeaders());
        $this->assertCount(1, $message->getHeaders()->toInboxroadArray());

        $this->assertArrayHasKey('attachments', $params);
        $this->assertInstanceOf(MessageAttachmentCollectionInterface::class, $message->getAttachments());
        $this->assertCount(1, $message->getAttachments()->toInboxroadArray());
    }

    /**
     * @return void
     */
    final public function testToInboxroadArray(): void
    {
        $message = (new Message())
            ->setFromEmail($this->email)
            ->setFromName($this->name)
            ->setToEmail($this->email)
            ->setToName($this->name)
            ->setReplyToEmail($this->email)
            ->setSubject($this->subject)
            ->setText($this->text)
            ->setHtml($this->html)
            ->setHeaders($this->headers)
            ->setAttachments($this->attachments);
        
        $params = $message->toInboxroadArray();

        $this->assertArrayHasKey('from_email', $params);
        $this->assertIsString($params['from_email']);
        $this->assertEquals($this->email, $params['from_email']);

        $this->assertArrayHasKey('from_name', $params);
        $this->assertIsString($params['from_name']);
        $this->assertEquals($this->name, $params['from_name']);
        
        $this->assertArrayHasKey('to_email', $params);
        $this->assertIsString($params['to_email']);
        $this->assertEquals($this->email, $params['to_email']);

        $this->assertArrayHasKey('to_name', $params);
        $this->assertIsString($params['to_name']);
        $this->assertEquals($this->name, $params['to_name']);

        $this->assertArrayHasKey('reply_to_email', $params);
        $this->assertIsString($params['reply_to_email']);
        $this->assertEquals($this->email, $params['reply_to_email']);

        $this->assertArrayHasKey('subject', $params);
        $this->assertIsString($params['subject']);
        $this->assertEquals($this->subject, $params['subject']);

        $this->assertArrayHasKey('text', $params);
        $this->assertIsString($params['text']);
        $this->assertEquals($this->text, $params['text']);

        $this->assertArrayHasKey('html', $params);
        $this->assertIsString($params['html']);
        $this->assertEquals($this->html, $params['html']);

        $this->assertArrayHasKey('headers', $params);
        $this->assertInstanceOf(MessageHeaderCollectionInterface::class, $message->getHeaders());
        $this->assertCount(1, $message->getHeaders()->toInboxroadArray());

        $this->assertArrayHasKey('attachments', $params);
        $this->assertInstanceOf(MessageAttachmentCollectionInterface::class, $message->getAttachments());
        $this->assertCount(1, $message->getAttachments()->toInboxroadArray());
    }
}
