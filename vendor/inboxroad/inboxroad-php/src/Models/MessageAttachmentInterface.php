<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Interface MessageAttachmentInterface
 * @package Inboxroad\Models
 */
interface MessageAttachmentInterface
{
    /**
     * MessageAttachmentInterface constructor.
     *
     * @param string $name
     * @param string $content
     * @param string $mimeType
     */
    public function __construct(string $name, string $content, string $mimeType = 'application/octet-stream');
    
    /**
     * @param string $name
     *
     * @return MessageAttachmentInterface
     */
    public function setName(string $name): MessageAttachmentInterface;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $content
     *
     * @return MessageAttachmentInterface
     */
    public function setContent(string $content): MessageAttachmentInterface;

    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @param string $mimeType
     *
     * @return MessageAttachmentInterface
     */
    public function setMimeType(string $mimeType): MessageAttachmentInterface;

    /**
     * @return string
     */
    public function getMimeType(): string;

    /**
     * @return array<string, string>
     */
    public function toArray(): array;

    /**
     * @return array<string, string>
     */
    public function toInboxroadArray(): array;
}
