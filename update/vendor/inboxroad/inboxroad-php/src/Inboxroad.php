<?php declare(strict_types=1);

namespace Inboxroad;

use Inboxroad\Api\Messages;
use Inboxroad\Api\MessagesInterface;
use Inboxroad\HttpClient\HttpClient;

/**
 * Class Inboxroad
 * @package Inboxroad
 */
class Inboxroad implements InboxroadInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Inboxroad constructor.
     *
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return MessagesInterface
     */
    public function messages(): MessagesInterface
    {
        return new Messages($this->httpClient);
    }
}
