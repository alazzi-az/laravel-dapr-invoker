<?php

namespace AlazziAz\LaravelDaprInvoker\Http;

use AlazziAz\LaravelDaprInvoker\Support\InvocationRegistry;
use AlazziAz\LaravelDaprInvoker\Support\InvocationSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InvokeController
{
    public function __construct(
        protected InvocationRegistry $registry,
        protected InvocationSignatureVerifier $verifier
    ) {}

    public function __invoke(Request $request, string $method)
    {

        if (! $this->verifier->verify($request)) {
            abort(Response::HTTP_FORBIDDEN, 'Invalid Dapr invocation signature.');
        }

        $handler = $this->resolveHandler($method);

        if (! $handler) {
            abort(Response::HTTP_NOT_FOUND, "No handler registered for invoke method [$method].");
        }

        // Decode body (JSON  / raw)
        $payload = $this->extractPayload($request);

        // Merge into request input so FormRequest works natively
        if (is_array($payload)) {
            $data = $this->unwrapCloudData($payload);

            if (is_array($data)) {
                $request->merge($data);
            }
        }

        Log::info('Handling Dapr invocation.', [
            'method' => $method,
            'handler' => $this->stringifyHandler($handler),
        ]);

        if (is_array($handler) && count($handler) === 2) {
            $handler = $handler[0].'@'.$handler[1];
        }

        return app()->call($handler);
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

    protected function unwrapCloudData(array $payload): mixed
    {
        if (! array_key_exists('data', $payload)) {
            return $payload;
        }

        $data = $payload['data'];

        if (is_array($data) && array_key_exists('data', $data)) {
            return $data['data'];
        }

        return $data;
    }

    protected function stringifyHandler(mixed $handler): string
    {
        if (is_string($handler)) {
            return $handler;
        }

        if (is_array($handler) && count($handler) === 2) {
            $left = is_object($handler[0]) ? get_class($handler[0]) : (string) $handler[0];

            return $left.'@'.$handler[1];
        }

        return get_debug_type($handler);
    }
}