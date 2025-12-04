<?php

namespace AlazziAz\LaravelDaprInvoker\Support;

use Dapr\Client\AppId;
use Dapr\Client\DaprHttpClient;
use Dapr\Deserialization\IDeserializer;
use Dapr\Serialization\ISerializer;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Dapr HTTP client that:
 * - Fixes encoding of method paths with query strings
 * - Uses sync HTTP calls by default for service invocation
 * - Exposes an async variant when needed
 */
final class QuerySafeDaprHttpClient extends DaprHttpClient
{
    private float $timeout;
    private float $connectTimeout;

    public function __construct(
        string          $defaultHttpHost,
        IDeserializer   $deserializer,
        ISerializer     $serializer,
        LoggerInterface $logger,
        float           $timeout        = 3.0,
        float           $connectTimeout = 2,
    ) {
        parent::__construct($defaultHttpHost, $deserializer, $serializer, $logger);

        $this->timeout        = $timeout;
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Synchronous service invocation (recommended from Laravel request handlers).
     */
    public function invokeMethod(
        string $httpMethod,
        AppId  $appId,
        string $methodName,
        mixed  $data     = null,
        array  $metadata = []
    ): ResponseInterface {
        [$url, $options] = $this->buildInvocationRequest($httpMethod, $appId, $methodName, $data, $metadata);

        // Log::debug("Invoking Dapr service: {$httpMethod} {$url}",$options);
        // Synchronous call – blocks current request until the sidecar responds or times out.
        return $this->httpClient->request($httpMethod, $url, $options);
    }

    /**
     * Async variant – for background work, retries, pipelines, etc.
     */
    public function invokeMethodAsync(
        string $httpMethod,
        AppId  $appId,
        string $methodName,
        mixed  $data     = null,
        array  $metadata = []
    ): PromiseInterface {
        [$url, $options] = $this->buildInvocationRequest($httpMethod, $appId, $methodName, $data, $metadata);

        // Guzzle async API – returns PromiseInterface
        return $this->httpClient->requestAsync($httpMethod, $url, $options);
    }

    /**
     * Common logic for building the URL + Guzzle options for invocation.
     *
     * - Splits "path?query" into path + query array.
     * - Encodes only path segments (keeps '/').
     * - Sends query in the "query" option so '?' is not encoded as %3F.
     */
    private function buildInvocationRequest(
        string $httpMethod,
        AppId  $appId,
        string $methodName,
        mixed  $data,
        array  $metadata
    ): array {
        // Parse "wallet.balance?hello=ok&user_id=1" etc.
        $parsed  = parse_url($methodName);
        $rawPath = ltrim((string)($parsed['path'] ?? $methodName), '/');

        $queryParams = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }

        // Encode only path segments, not the whole string
        $encodedApp  = rawurlencode($appId->getAddress());
        $encodedPath = $rawPath === ''
            ? ''
            : implode('/', array_map('rawurlencode', explode('/', $rawPath)));

        $url = "/v1.0/invoke/{$encodedApp}/method";
        if ($encodedPath !== '') {
            $url .= "/{$encodedPath}";
        }

        $upper = strtoupper($httpMethod);

        $options = [
            'headers'         => $metadata,
            'http_errors'     => false,               // don’t throw on 4xx/5xx
            'timeout'         => $this->timeout,      // total timeout
            'connect_timeout' => $this->connectTimeout, // fail fast if sidecar is down
        ];

        if ($queryParams) {
            $options['query'] = $queryParams;
        }

        // Only send a body when it makes sense
        if ($data !== null && !in_array($upper, ['GET', 'HEAD', 'DELETE'], true)) {
            $options['body'] = $this->serializer->as_json($data);
            $options['headers']['Content-Type'] ??= 'application/json';
        }

        return [$url, $options];
    }
}
