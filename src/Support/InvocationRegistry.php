<?php

namespace AlazziAz\LaravelDaprInvoker\Support;

use Illuminate\Contracts\Config\Repository;

class InvocationRegistry
{
    /**
     * @var array<string, mixed>
     */
    protected array $handlers = [];

    public function __construct(
        protected Repository $config
    ) {
        $this->handlers = $config->get('dapr.invocation.map', []);
    }

    public function register(string $method, mixed $handler): void
    {
        $this->handlers[$method] = $handler;
    }

    public function registerMany(array $map): void
    {
        foreach ($map as $method => $handler) {
            $this->register($method, $handler);
        }
    }

    public function all(): array
    {
        return $this->handlers;
    }

    public function resolve(string $method): mixed
    {
        return $this->handlers[$method] ?? null;
    }
}
