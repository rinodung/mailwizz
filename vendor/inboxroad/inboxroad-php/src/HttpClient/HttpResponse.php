<?php declare(strict_types=1);

namespace Inboxroad\HttpClient;

/**
 * Class HttpResponse
 * @package Inboxroad\HttpClient
 */
class HttpResponse implements HttpResponseInterface
{
    /**
     * @var string
     */
    private $body;
    
    /**
     * @var int
     */
    private $code;
    
    /**
     * @var array<int, mixed>
     */
    private $headers;

    /**
     * @var array<string, mixed>
     */
    private $responseData;

    /**
     * @param string $body
     * @param int $code
     * @param array<int, mixed> $headers
     */
    public function __construct(string $body, int $code, array $headers = [])
    {
        $this->body    = $body;
        $this->code    = $code;
        $this->headers = $headers;

        $this->responseData = (array)json_decode($this->body, true);
    }

    /**
     * @param HttpResponseInterface $response
     *
     * @return HttpResponseInterface
     */
    public static function fromResponse(HttpResponseInterface $response): HttpResponseInterface
    {
        return new static($response->getBody(), $response->getCode(), $response->getHeaders());
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return array<int, mixed>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
