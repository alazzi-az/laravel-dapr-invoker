<?php

namespace AlazziAz\LaravelDaprInvoker\Support;


use JsonSerializable;
use Psr\Http\Message\ResponseInterface;

final class DaprInvocationResult implements JsonSerializable
{
    public function __construct(
        public readonly mixed            $body,      // array|string|null
        public readonly int              $status,
        public readonly array            $headers,
        public readonly ResponseInterface $raw,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function jsonSerialize(): mixed
    {
        // So you can return it directly in response()->json()
        return $this->body;
    }
}
