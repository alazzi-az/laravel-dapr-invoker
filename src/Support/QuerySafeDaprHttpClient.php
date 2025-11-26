<?php

namespace AlazziAz\LaravelDaprInvoker\Support;


use Dapr\Client\DaprHttpClient;
use Dapr\Client\AppId;
use Dapr\Deserialization\IDeserializer;
use Dapr\Serialization\ISerializer;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Fixes service invocation query handling by encoding only the path segments
 * and passing the query via HTTP client's 'query' option (so '?' isn't %3F).
 * Inherits all other features from DaprHttpClient.
 */
final class QuerySafeDaprHttpClient extends DaprHttpClient
{
    private string $base;
    private GuzzleClient $http;

    public function __construct(
        string $defaultHttpHost,
        IDeserializer $deserializer,
        ISerializer $serializer,
        LoggerInterface $logger
    ) {
        parent::__construct($defaultHttpHost, $deserializer, $serializer, $logger);

        $this->base = rtrim($defaultHttpHost, '/');
        $this->http = new GuzzleClient([
            'base_uri'    => $this->base,
            'http_errors' => false,
        ]);
    }

    /** Async invocation — fixed */
    public function invokeMethodAsync(
        string $httpMethod,
        AppId $appId,
        string $methodName,
        mixed $data = null,
        array $metadata = []
    ): PromiseInterface {
        // Split "path?..." into path + array
        $parsed  = parse_url($methodName);
        $rawPath = trim((string)($parsed['path'] ?? $methodName), '/');

        $qs = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $qs);
        }

        // Encode PATH segments only (keep slashes)
        $encodedApp  = rawurlencode($appId->getAddress());
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $rawPath)));

        $url = "/v1.0/invoke/{$encodedApp}/method/{$encodedPath}";

        $options = ['headers' => $metadata];

        // Guzzle will append "?..." correctly (no %3F)
        if ($qs) {
            $options['query'] = $qs;
        }

        // JSON body only for verbs that carry one
        $upper = strtoupper($httpMethod);
        if ($data !== null && !in_array($upper, ['GET','HEAD','DELETE'], true)) {
            $options['body'] = $this->serializer->as_json($data);
            $options['headers']['Content-Type'] = 'application/json';
        }

        return $this->http->requestAsync($httpMethod, $url, $options);
    }

    public function invokeMethod(
        string $httpMethod,
        AppId $appId,
        string $methodName,
        mixed $data = null,
        array $metadata = []
    ): ResponseInterface {
        return $this->invokeMethodAsync($httpMethod, $appId, $methodName, $data, $metadata)->wait();
    }
}
