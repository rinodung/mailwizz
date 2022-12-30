<?php declare(strict_types=1);

namespace Inboxroad\HttpClient;

/**
 * Interface HttpClientInterface
 * @package Inboxroad\HttpClient
 */
interface HttpClientInterface
{
    /**
     * @param string $path
     * @param array<string, mixed> $options
     *
     * @return HttpResponseInterface
     */
    public function get(string $path = '', array $options = []): HttpResponseInterface;

    /**
     * @param string $path
     * @param array<string, mixed> $options
     *
     * @return HttpResponseInterface
     */
    public function post(string $path = '', array $options = []): HttpResponseInterface;
}
