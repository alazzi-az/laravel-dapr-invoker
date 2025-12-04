<?php

use AlazziAz\LaravelDaprInvoker\Contracts\DaprInvokerContract;
use AlazziAz\LaravelDaprInvoker\Facades\DaprInvoke;
use AlazziAz\LaravelDaprInvoker\Support\DaprInvocationResult;

if (! function_exists('dapr_invoke')) {
    function dapr_invoke(
        string $appId,
        string $method,
        mixed $payload = null,
        string $httpVerb = 'POST',
        array $query = [],
        array $headers = []
    ): DaprInvocationResult {
        return DaprInvoke::invoke($appId, $method, $payload, $httpVerb, $query, $headers);
    }
}

if (! function_exists('dapr_invoke_async')) {
    function dapr_invoke_async(
        string $appId,
        string $method,
        mixed $payload = null,
        string $httpVerb = 'POST',
        array $query = [],
        array $headers = []
    ): void {
        DaprInvoke::invokeAsync($appId, $method, $payload, $httpVerb, $query, $headers);
    }
}




