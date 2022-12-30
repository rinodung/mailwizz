<?php declare(strict_types=1);

namespace Inboxroad\Api;

use Inboxroad\Exception\RequestException;
use Inboxroad\HttpClient\HttpClient;
use Inboxroad\HttpClient\HttpResponse;
use Inboxroad\Models\Message;
use Inboxroad\Models\MessageInterface;
use Inboxroad\Response\MessagesResponse;

/**
 * Class Messages
 * @package Inboxroad
 */
class Messages implements MessagesInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Messages constructor.
     *
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param array<string, mixed>|MessageInterface $message
     *
     * @return MessagesResponse
     * @throws RequestException
     */
    public function send($message): MessagesResponse
    {
        if (!($message instanceof MessageInterface)) {
            $message = Message::fromArray($message);
        }
        
        /** @var HttpResponse $response */
        $response = $this->httpClient->post('messages/', [
            'json' => $message->toInboxroadArray(),
        ]);

        /** @var MessagesResponse $response */
        $response = MessagesResponse::fromResponse($response);
        
        if ($response->getMessageId()) {
            $message->setMessageId($response->getMessageId());
        }

        return $response;
    }
}
