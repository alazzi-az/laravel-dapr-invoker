<?php

namespace AlazziAz\LaravelDaprInvoker\Http;

use AlazziAz\LaravelDaprInvoker\Support\InvocationRegistry;
use AlazziAz\LaravelDaprInvoker\Support\InvocationSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class InvokeController
{
    public function __construct(
        protected InvocationRegistry $registry,
        protected InvocationSignatureVerifier $verifier
    ) {
    }

    public function __invoke(Request $request, string $method)
    {
        if (! $this->verifier->verify($request)) {
            abort(403, 'Invalid Dapr invocation signature.');
        }

        $handler = $this->resolveHandler($method);


        if (! $handler) {
            abort(404, "No handler registered for invoke method [$method].");
        }

        $payload = $this->extractPayload($request);

        Log::info('Handling Dapr invocation.', [
            'method' => $method,
            'handler' => is_string($handler) ? $handler : get_debug_type($handler),
        ]);
        if (is_array($handler) && count($handler) === 2) {
            $handler = $handler[0].'@'.$handler[1];
        }
        return app()->call($handler, [
            'request' => $request,
            'payload' => $payload,
        ]);
    }

    protected function resolveHandler(string $method): mixed
    {
        $variants = Arr::prepend(
            array_unique([
                $method,
                str_replace('/', '.', $method),
                str_replace('.', '/', $method),
            ]),
            $method
        );

        foreach ($variants as $candidate) {
            if ($handler = $this->registry->resolve($candidate)) {
                return $handler;
            }
        }

        return null;
    }

    protected function extractPayload(Request $request): mixed
    {
        $content = $request->getContent();

        if ($content === '' || $content === null) {
            return $request->query();
        }

        $decoded = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $content;
    }
}
