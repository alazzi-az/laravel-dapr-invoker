<?php

namespace AlazziAz\LaravelDaprInvoker\Support;



use Dapr\Client\AppId;

final class InvocationUrlBuilder
{
    public function __construct(private string $base) // e.g. http://127.0.0.1:3500
    {
        $this->base = rtrim($this->base, '/');
    }

    public function build(AppId $appId, string $path): string
    {
        $encodedApp  = rawurlencode($appId->getAddress());
        $encodedPath = implode('/', array_map('rawurlencode', explode('/', trim($path, '/'))));
        return "{$this->base}/v1.0/invoke/{$encodedApp}/method/{$encodedPath}";
    }
}
