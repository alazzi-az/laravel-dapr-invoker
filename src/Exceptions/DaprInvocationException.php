<?php

namespace AlazziAz\LaravelDaprInvoker\Exceptions;

use RuntimeException;
use Throwable;

class DaprInvocationException extends RuntimeException
{
    public function __construct(
        string $message = 'Dapr invocation failed.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}