<?php

namespace AlazziAz\LaravelDaprInvoker\Support;



final class MethodTarget
{
    public function __construct(
        public readonly string $path,   // path only (no leading slash, no query)
        /** @var array<string, scalar|array|null> */
        public readonly array $query    // merged & raw; HTTP client will encode
    ) {}

    public static function from(string $method): self
    {
        $parsed  = parse_url($method);
        $rawPath = trim((string)($parsed['path'] ?? $method), '/');

        $qs = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $qs);
        }

        return new self($rawPath, $qs);
    }

    /** Merge external query (caller wins). */
    public function withQuery(array $query): self
    {
        return new self($this->path, array_merge($this->query, $query));
    }
}
