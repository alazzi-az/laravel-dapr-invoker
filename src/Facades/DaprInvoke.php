<?php

namespace AlazziAz\LaravelDaprInvoker\Facades;

use AlazziAz\LaravelDaprInvoker\Contracts\DaprInvokerContract;
use Illuminate\Support\Facades\Facade;




use GuzzleHttp\Promise\PromiseInterface;

/**
 * @method static mixed invoke(string $appId, string $method, mixed $payload = null, string $httpVerb = 'POST', array $query = [], array $headers = []) Invoke a Dapr-enabled service method synchronously.
 * @method static PromiseInterface invokeAsync(string $appId, string $method, mixed $payload = null, string $httpVerb = 'POST', array $query = [], array $headers = []) Invoke a Dapr-enabled service method asynchronously.
 * @method static mixed get(string $appId, string $method, array $query = [], array $headers = []) Perform a GET invocation synchronously.
 * @method static mixed post(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []) Perform a POST invocation synchronously.
 * @method static mixed put(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []) Perform a PUT invocation synchronously.
 * @method static mixed delete(string $appId, string $method, array $query = [], array $headers = []) Perform a DELETE invocation synchronously.
 * @method static PromiseInterface getAsync(string $appId, string $method, array $query = [], array $headers = []) Perform a GET invocation asynchronously.
 * @method static PromiseInterface postAsync(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []) Perform a POST invocation asynchronously.
 * @method static PromiseInterface putAsync(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []) Perform a PUT invocation asynchronously.
 * @method static PromiseInterface deleteAsync(string $appId, string $method, array $query = [], array $headers = []) Perform a DELETE invocation asynchronously.
 *
 * @see \AlazziAz\LaravelDaprInvoker\Support\DaprInvoker
 */
class DaprInvoke extends Facade
{
    /**
     * Get the registered component key in the service container.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return DaprInvokerContract::class;
    }
}
