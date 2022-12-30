<?php declare(strict_types=1);

namespace Inboxroad\HttpClient;

/**
 * Interface HttpResponseInterface
 * @package Inboxroad\HttpClient
 */
interface HttpResponseInterface
{
    /**
     * HttpResponseInterface constructor.
     *
     * @param string $body
     * @param int $code
     * @param array<int, mixed> $headers
     */
    public function __construct(string $body, int $code, array $headers = []);
    
    /**
     * @param HttpResponseInterface $response
     *
     * @return HttpResponseInterface
     */
    public static function fromResponse(HttpResponseInterface $response): HttpResponseInterface;

    /**
     * @return array<string, mixed>
     */
    public function getResponseData(): array;

    /**
     * @return string
     */
    public function getBody(): string;

    /**
     * @return int
     */
    public function getCode(): int;

    /**
     * @return array<int, mixed>
     */
    public function getHeaders(): array;
}
