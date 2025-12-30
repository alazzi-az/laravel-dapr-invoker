<?php

namespace AlazziAz\LaravelDaprInvoker\Support;

final class InvocationContext
{
    public function __construct(
        private string $appId,
        private string $method,
        private mixed $payload,
        private string $httpVerb,
        /** @var array<string,mixed> */
        private array $query = [],
        /** @var array<string,string> */
        private array $headers = [],
        private bool $async = false,
    ) {}

    public function appId(): string { return $this->appId; }
    public function method(): string { return $this->method; }
    public function payload(): mixed { return $this->payload; }
    public function httpVerb(): string { return $this->httpVerb; }
    /** @return array<string,mixed> */
    public function query(): array { return $this->query; }
    /** @return array<string,string> */
    public function headers(): array { return $this->headers; }
    public function isAsync(): bool { return $this->async; }

    public function setAppId(string $appId): void { $this->appId = $appId; }
    public function setMethod(string $method): void { $this->method = $method; }
    public function setPayload(mixed $payload): void { $this->payload = $payload; }
    public function setHttpVerb(string $httpVerb): void { $this->httpVerb = $httpVerb; }
    /** @param array<string,mixed> $query */
    public function setQuery(array $query): void { $this->query = $query; }
    /** @param array<string,string> $headers */
    public function setHeaders(array $headers): void { $this->headers = $headers; }
    public function setAsync(bool $async): void { $this->async = $async; }
}