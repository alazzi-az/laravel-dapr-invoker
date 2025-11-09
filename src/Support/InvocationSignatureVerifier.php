<?php

namespace AlazziAz\LaravelDaprInvoker\Support;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

class InvocationSignatureVerifier
{
    public function __construct(
        protected Repository $config
    ) {
    }

    public function verify(Request $request): bool
    {
        if (! $this->config->get('dapr.invocation.verify_signature', false)) {
            return true;
        }

        $secret = $this->config->get('dapr.invocation.signature_secret');
        if (! $secret) {
            return false;
        }

        $header = $this->config->get('dapr.invocation.signature_header', 'x-dapr-signature');
        $provided = $request->headers->get($header);

        if (! $provided) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $provided);
    }
}
