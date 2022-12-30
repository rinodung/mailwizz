<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Interface MessageInterface
 * @package Inboxroad\Models
 */
interface MessageInterface
{
    /**
     * @param string $messageId
     *
     * @return MessageInterface
     */
    public function setMessageId(string $messageId): MessageInterface;

    /**
     * @return string
     */
    public function getMessageId(): string;
    
    /**
     * @param string $email
     *
     * @return MessageInterface
     */
    public function setFromEmail(string $email): MessageInterface;

    /**
     * @return string
     */
    public function getFromEmail(): string;

    /**
     * @param string $name
     *
     * @return MessageInterface
     */
    public function setFromName(string $name): MessageInterface;

    /**
     * @return string
     */
    public function getFromName(): string;

    /**
     * @param string $email
     *
     * @return MessageInterface
     */
    public function setToEmail(string $email): MessageInterface;

    /**
     * @return string
     */
    public function getToEmail(): string;

    /**
     * @param string $name
     *
     * @return MessageInterface
     */
    public function setToName(string $name): MessageInterface;

    /**
     * @return string
     */
    public function getToName(): string;

    /**
     * @param string $email
     *
     * @return MessageInterface
     */
    public function setReplyToEmail(string $email): MessageInterface;

    /**
     * @return string
     */
    public function getReplyToEmail(): string;

    /**
     * @param string $subject
     *
     * @return MessageInterface
     */
    public function setSubject(string $subject): MessageInterface;

    /**
     * @return string
     */
    public function getSubject(): string;

    /**
     * @param string $text
     *
     * @return MessageInterface
     */
    public function setText(string $text): MessageInterface;

    /**
     * @return string
     */
    public function getText(): string;

    /**
     * @param string $html
     *
     * @return MessageInterface
     */
    public function setHtml(string $html): MessageInterface;

    /**
     * @return string
     */
    public function getHtml(): string;

    /**
     * @param MessageHeaderCollectionInterface $headers
     *
     * @return MessageInterface
     */
    public function setHeaders(MessageHeaderCollectionInterface $headers): MessageInterface;

    /**
     * @return MessageHeaderCollectionInterface
     */
    public function getHeaders(): MessageHeaderCollectionInterface;

    /**
     * @param MessageAttachmentCollectionInterface $attachments
     *
     * @return MessageInterface
     */
    public function setAttachments(MessageAttachmentCollectionInterface $attachments): MessageInterface;

    /**
     * @return MessageAttachmentCollectionInterface
     */
    public function getAttachments(): MessageAttachmentCollectionInterface;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * @return array<string, mixed>
     */
    public function toInboxroadArray(): array;
    
    /**
     * @param array<string, mixed> $params
     *
     * @return MessageInterface
     */
    public static function fromArray(array $params): MessageInterface;
}
