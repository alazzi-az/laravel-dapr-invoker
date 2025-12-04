<?php

namespace AlazziAz\LaravelDaprInvoker\Contracts;


use AlazziAz\LaravelDaprInvoker\Support\DaprInvocationResult;
use GuzzleHttp\Promise\PromiseInterface;

interface DaprInvokerContract
{
    /**
     * @param  string               $appId
     * @param  string               $method
     * @param  mixed                $payload
     * @param  string               $httpVerb   One of: GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD
     * @param  array<string,mixed>  $query
     * @param  array<string,string> $headers
     * @return mixed
     */
    public function invokeRaw(
        string $appId,
        string $method,
        mixed  $payload = null,
        string $httpVerb = 'POST',
        array  $query = [],
        array  $headers = []
    ): mixed;
    /**
     * @param  string               $appId
     * @param  string               $method
     * @param  mixed                $payload
     * @param  string               $httpVerb   One of: GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD
     * @param  array<string,mixed>  $query
     * @param  array<string,string> $headers
     * @return mixed
     */
    public function invoke(
        string $appId,
        string $method,
        mixed  $payload = null,
        string $httpVerb = 'POST',
        array  $query = [],
        array  $headers = []
    ): DaprInvocationResult;

    public function invokeAsync(
        string $appId,
        string $method,
        mixed  $payload = null,
        string $httpVerb = 'POST',
        array  $query = [],
        array  $headers = []
    ): PromiseInterface;

    public function get(string $appId, string $method, array $query = [], array $headers = []): mixed;
    public function post(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []): mixed;
    public function put(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []): mixed;
    public function delete(string $appId, string $method, array $query = [], array $headers = []): mixed;

    public function getAsync(string $appId, string $method, array $query = [], array $headers = []): PromiseInterface;
    public function postAsync(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []): PromiseInterface;
    public function putAsync(string $appId, string $method, mixed $payload = null, array $query = [], array $headers = []): PromiseInterface;
    public function deleteAsync(string $appId, string $method, array $query = [], array $headers = []): PromiseInterface;

}
