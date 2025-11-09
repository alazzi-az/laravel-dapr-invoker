<?php

namespace AlazziAz\LaravelDaprInvoker;

use AlazziAz\LaravelDaprInvoker\Contracts\DaprInvokerContract;
use AlazziAz\LaravelDaprInvoker\Support\DaprInvoker;
use AlazziAz\LaravelDaprInvoker\Support\InvocationRegistry;
use AlazziAz\LaravelDaprInvoker\Support\InvocationRouteRegistrar;
use AlazziAz\LaravelDaprInvoker\Support\InvocationSignatureVerifier;
use AlazziAz\LaravelDaprInvoker\Support\QuerySafeDaprHttpClient;
use Dapr\Client\DaprClient;
use Dapr\Deserialization\DeserializationConfig;
use Dapr\Deserialization\Deserializer;
use Dapr\Serialization\SerializationConfig;
use Dapr\Serialization\Serializer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\Log\LoggerInterface;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/dapr-invocation.php' => config_path('dapr-invocation.php'),
        ], 'dapr-invocation-config');

        InvocationRouteRegistrar::register();

        if (config('dapr.invocation.auto_register', false)) {
            $this->registerRoutes();
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dapr-invocation.php', 'dapr.invocation');

        $this->app->singleton(InvocationSignatureVerifier::class);
        $this->app->singleton(InvocationRegistry::class);
        $this->app->bind(DaprClient::class, function ($app) {
            $base = rtrim(config('dapr.invocation.base_url', env('DAPR_HTTP_ENDPOINT', 'http://127.0.0.1:3500')), '/');


            $logger = $app->make(LoggerInterface::class);
            $deserializer = new Deserializer(new DeserializationConfig(), $logger);
            $serializer = new Serializer(new SerializationConfig(), $logger);


            return new QuerySafeDaprHttpClient(
                $base,
                $deserializer,
                $serializer,
                $logger
            );
        });

        $this->app->bind(DaprInvokerContract::class, function ($app) {
            return new DaprInvoker(
                client: $app->make(DaprClient::class),
                defaultAppId: config('dapr.invocation.default_app_id'),
                defaultHeaders: (array)config('dapr.invocation.default_headers', [])
            );
        });
    }

    protected function registerRoutes(): void
    {
        $router = $this->app['router'];

        $router->group([], function () {
            Route::daprInvokeController();
        });
    }
}
