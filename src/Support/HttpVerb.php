<?php

namespace AlazziAz\LaravelDaprInvoker\Support;

enum HttpVerb: string
{
    case GET     = 'GET';
    case POST    = 'POST';
    case PUT     = 'PUT';
    case PATCH   = 'PATCH';
    case DELETE  = 'DELETE';
    case OPTIONS = 'OPTIONS';
    case HEAD    = 'HEAD';

    public static function fromString(string $verb): self
    {
        $v = strtoupper($verb);
        return self::tryFrom($v) ?? self::POST;
    }

    public function sendsBody(): bool
    {
        return \in_array($this, [self::POST, self::PUT, self::PATCH], true);
    }
}