<?php declare(strict_types=1);

namespace Inboxroad\HttpClient;

use ErrorException;
use GuzzleHttp\Client;
use Inboxroad\Exception\RequestException;
use Throwable;

/**
 * Class HttpClient
 * @package Inboxroad\HttpClient
 */
class HttpClient implements HttpClientInterface
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $apiKey = '';

    /**
     * @var array<string, mixed>
     */
    private static $options = [
        'base_uri' => 'https://webapi.inboxroad.com/api/v1/',
        'headers'  => [
            'Content-Type'  => 'application/json',
            'User-Agent'    => 'inboxroad-api/inboxroad-php'
        ],
    ];

    /**
     * HttpClient constructor.
     *
     * @param string $apiKey
     * @param array<string, mixed> $options
     *
     * @throws ErrorException
     */
    public function __construct(string $apiKey = '', array $options = [])
    {
        if (empty($apiKey)) {
            throw new ErrorException('Please provide a valid api key!');
        }
        $this->apiKey = $apiKey;
        
        $options = array_merge_recursive(self::$options, $options);
        if (empty($options['headers']['Authorization'])) {
            $options['headers']['Authorization'] = sprintf('Basic %s', $apiKey);
        }
        $this->client = new Client($options);
    }

    /**
     * @param string $path
     * @param array<string, mixed> $options
     *
     * @return HttpResponseInterface
     * @throws RequestException
     */
    public function get(string $path = '', array $options = []): HttpResponseInterface
    {
        try {
            $response = $this->client->get($path, $options);
            $response = new HttpResponse((string)$response->getBody(), $response->getStatusCode(), (array)$response->getHeaders());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode(), $e->getRequest(), $e->getResponse());
        } catch (Throwable $e) {
            throw new RequestException($e->getMessage(), $e->getCode());
        }

        return $response;
    }

    /**
     * @param string $path
     * @param array<string, mixed> $options
     *
     * @return HttpResponseInterface
     * @throws RequestException
     */
    public function post(string $path = '', array $options = []): HttpResponseInterface
    {
        try {
            $response = $this->client->post($path, $options);
            $response = new HttpResponse((string)$response->getBody(), $response->getStatusCode(), (array) $response->getHeaders());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode(), $e->getRequest(), $e->getResponse());
        } catch (Throwable $e) {
            throw new RequestException($e->getMessage(), $e->getCode());
        }

        return $response;
    }
}
