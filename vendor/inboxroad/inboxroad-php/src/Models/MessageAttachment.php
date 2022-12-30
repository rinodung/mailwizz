<?php declare(strict_types=1);

namespace Inboxroad\Models;

/**
 * Class MessageAttachment
 * @package Inboxroad\Models
 */
class MessageAttachment implements MessageAttachmentInterface
{
    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var string
     */
    private $mimeType = 'application/octet-stream';

    /**
     * MessageAttachment constructor.
     *
     * @param string $name
     * @param string $content
     * @param string $mimeType
     */
    public function __construct(string $name, string $content, string $mimeType = 'application/octet-stream')
    {
        $this
            ->setName($name)
            ->setContent($content)
            ->setMimeType($mimeType);
    }

    /**
     * @param string $name
     *
     * @return MessageAttachmentInterface
     */
    public function setName(string $name): MessageAttachmentInterface
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $content
     *
     * @return MessageAttachmentInterface
     */
    public function setContent(string $content): MessageAttachmentInterface
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $mimeType
     *
     * @return MessageAttachmentInterface
     */
    public function setMimeType(string $mimeType): MessageAttachmentInterface
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'content'   => $this->content,
            'mimeType'  => $this->mimeType,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function toInboxroadArray(): array
    {
        return [
            'filename'   => $this->name,
            'file_data'  => base64_encode($this->content),
            'mime_type'  => $this->mimeType,
        ];
    }
}
