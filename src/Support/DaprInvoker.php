<?php

namespace AlazziAz\LaravelDaprInvoker\Support;

use Dapr\Client\AppId;
use Dapr\Client\DaprClient;
use GuzzleHttp\Promise\PromiseInterface;
use AlazziAz\LaravelDaprInvoker\Contracts\DaprInvokerContract;
use AlazziAz\LaravelDaprInvoker\Exceptions\DaprInvocationException;

/**
 * @internal
*/
class DaprInvoker implements DaprInvokerContract
{
    public function __construct(
        protected DaprClient $client,
        protected ?string $defaultAppId = null,
        /** @var array<string,string> */
        protected array $defaultHeaders = [],
    ) {
        // Allow pulling defaults from Laravel config if available
        if (\function_exists('config')) {
            $this->defaultAppId   = $this->defaultAppId   ?? (string) (config('dapr.invocation.default_app_id') ?? '');
            $this->defaultHeaders = $this->defaultHeaders ?: (array)  (config('dapr.invocation.default_headers') ?? []);
        }
    }

    public function invoke(
        string $appId,
        string $method,
        mixed  $payload = null,
        string $httpVerb = 'POST',
        array  $query = [],
        array  $headers = []
    ): mixed {
        [$methodPath, $verb, $data, $meta] = $this->buildInvocation($appId, $method, $payload, $httpVerb, $query, $headers);

        try {
            return $this->client->invokeMethod(
                httpMethod: $verb->value,
                appId: new AppId($appId ?: $this->requireDefaultAppId()),
                methodName: $methodPath,
                data: $data,
                metadata: $meta
            );
        } catch (\Throwable $e) {
            throw new DaprInvocationException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function invokeAsync(
        string $appId,
        string $method,
        mixed  $payload = null,
        string $httpVerb = 'POST',
        array  $query = [],
        array  $headers = []
    ): PromiseInterface {
        [$methodPath, $verb, $data, $meta] = $this->buildInvocation($appId, $method, $payload, $httpVerb, $query, $headers);

        try {
            return $this->client->invokeMethodAsync(
                httpMethod: $verb->value,
                appId: new AppId($appId ?: $this->requireDefaultAppId()),
                methodName: $methodPath,
                data: $data,
                metadata: $meta
            );
        } catch (\Throwable $e) {
            // For async flows we still surface an immediate construction error;
            // request-level failures will arrive via the promise.
            throw new DaprInvocationException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function get(string $appId, string $method, array $query = [], array $headers = []): mixed
    {
        return $this->invoke($appId, $method, null, HttpVerb::GET->value, $query, $headers);
    }

    public function post(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []): mixed
    {
        return $this->invoke($appId, $method, $payload, HttpVerb::POST->value, $query, $headers);
    }

    public function put(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []): mixed
    {
        return $this->invoke($appId, $method, $payload, HttpVerb::PUT->value, $query, $headers);
    }

    public function delete(string $appId, string $method, array $query = [], array $headers = []): mixed
    {
        return $this->invoke($appId, $method, null, HttpVerb::DELETE->value, $query, $headers);
    }

    /**
     * @return array{0:string,1:HttpVerb,2:mixed,3:array<string,string>}
     */
    protected function buildInvocation(
        string $appId,
        string $method,
        mixed  $payload,
        string $httpVerb,
        array  $query,
        array  $headers
    ): array {
        $verb = HttpVerb::fromString($httpVerb);

        // Build full method path with query string (Dapr expects query in URL)
        $methodPath = ltrim($method, '/');
        if (!empty($query)) {
            $qs = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            $methodPath .= (str_contains($methodPath, '?') ? '&' : '?') . $qs;
        }

        // Only send body for methods that should have one
        $data = $verb->sendsBody() ? $payload : null;

        // Merge default headers (config) with request headers (request wins)
        /** @var array<string,string> $meta */
        $meta = $this->defaultHeaders ? array_merge($this->defaultHeaders, $headers) : $headers;


        if ($appId === '' && $this->defaultAppId === '') {
            throw new DaprInvocationException('No appId provided and no default_app_id configured.');
        }

        return [$methodPath, $verb, $data, $meta];
    }

    protected function requireDefaultAppId(): string
    {
        if ($this->defaultAppId === '' || $this->defaultAppId === null) {
            throw new DaprInvocationException('No default_app_id configured for Dapr invocation.');
        }
        return $this->defaultAppId;
    }

    public function getAsync(string $appId, string $method, array $query = [], array $headers = []): PromiseInterface
    {
        return $this->invokeAsync($appId, $method, null, HttpVerb::GET->value, $query, $headers);
    }

    public function postAsync(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []): PromiseInterface
    {
        return $this->invokeAsync($appId, $method, $payload, HttpVerb::POST->value, $query, $headers);
    }

    public function putAsync(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []): PromiseInterface
    {
        return $this->invokeAsync($appId, $method, $payload, HttpVerb::PUT->value, $query, $headers);
    }

    public function deleteAsync(string $appId, string $method, array $query = [], array $headers = []): PromiseInterface
    {
        return $this->invokeAsync($appId, $method, null, HttpVerb::DELETE->value, $query, $headers);
    }
}
