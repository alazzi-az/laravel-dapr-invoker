<?php

namespace AlazziAz\LaravelDaprInvoker\Middleware;

use AlazziAz\LaravelDaprInvoker\Support\InvocationContext;
use Closure;
interface InvokerMiddleware
{
    /**
     * @param  InvocationContext  $context
     * @param  Closure(InvocationContext): mixed  $next
     */
    public function handle(InvocationContext $context, Closure $next): mixed;
}