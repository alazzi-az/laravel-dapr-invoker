<?php

namespace AlazziAz\LaravelDaprInvoker;

use AlazziAz\LaravelDaprInvoker\Contracts\DaprInvokerContract;
use AlazziAz\LaravelDaprInvoker\Support\DaprInvoker;
use Dapr\Client\DaprClient;
use Illuminate\Support\Facades\Route;
use AlazziAz\LaravelDaprInvoker\Support\InvocationRegistry;
use AlazziAz\LaravelDaprInvoker\Support\InvocationRouteRegistrar;
use AlazziAz\LaravelDaprInvoker\Support\InvocationSignatureVerifier;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dapr-invocation.php', 'dapr.invocation');

        $this->app->singleton(InvocationSignatureVerifier::class);
        $this->app->singleton(InvocationRegistry::class);

        $this->app->bind(DaprInvokerContract::class, function ($app) {
            return new DaprInvoker(
                client: $app->make(DaprClient::class),
                defaultAppId: config('dapr.invocation.default_app_id'),
                defaultHeaders: (array) config('dapr.invocation.default_headers', [])
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/dapr-invocation.php' => config_path('dapr-invocation.php'),
        ], 'dapr-invocation-config');

        InvocationRouteRegistrar::register();

        if (config('dapr.invocation.auto_register', false)) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        $router = $this->app['router'];

        $router->group([], function () {
            Route::daprInvokeController();
        });
    }
}
