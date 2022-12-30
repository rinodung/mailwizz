<?php declare(strict_types=1);

namespace Inboxroad\Api;

use Inboxroad\Exception\RequestException;
use Inboxroad\Models\MessageInterface;
use Inboxroad\Response\MessagesResponse;

/**
 * Class Messages
 * @package Inboxroad
 */
interface MessagesInterface
{
    /**
     * @param array<string, mixed>|MessageInterface $message
     *
     * @return MessagesResponse
     * @throws RequestException
     */
    public function send($message): MessagesResponse;
}
