<?php

namespace AlazziAz\LaravelDaprInvoker\Support;



use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;

final class HttpInvoker
{
    public function __construct(private ClientInterface $http) {}

    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     */
    public function requestAsync(
        string $httpMethod,
        string $url,
        array $query = [],
        mixed $data = null,
        array $headers = []
    ): PromiseInterface {
        $options = ['headers' => $headers];
        if ($query) {
            $options['query'] = $query; // Guzzle appends ?... (keeps '?' intact)
        }
        if ($data !== null && !in_array(strtoupper($httpMethod), ['GET','HEAD','DELETE'], true)) {
            $options['json'] = $data;
        }
        return $this->http->requestAsync($httpMethod, $url, $options);
    }
}
