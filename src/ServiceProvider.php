<?php

namespace AlazziAz\LaravelDaprInvoker;

use AlazziAz\LaravelDaprInvoker\Console\ListInvocationRoutesCommand;
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

        InvocationRouteRegistrar::register();


        if (config('dapr.invocation.auto_register', false)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/dapr-invocation.php');
        }
        $this->app->booted(function () {
            $this->ensureCachedDaprInvokeRouteExists();
            $this->hydrateHandlersFromRoutes();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListInvocationRoutesCommand::class,
            ]);
        }
    }

    public function register(): void
    {

        $this->mergeConfigFrom(__DIR__.'/../config/dapr-invocation.php', 'dapr');
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
                $logger,
                timeout: config('dapr.client.timeout', 10),
                connectTimeout: config('dapr.client.connect_timeout', 10)
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

    /**
     * When Laravel route caching is enabled the package route file will be skipped.
     * Make sure the Dapr invoke endpoint is still registered so the cached app
     * continues to expose /dapr/invoke/{method}.
     */
    protected function ensureCachedDaprInvokeRouteExists(): void
    {
        if (! $this->app->routesAreCached()) {
            return;
        }

        if (! config('dapr.invocation.auto_register', false)) {
            return;
        }

        $routes = $this->app['router']->getRoutes();

        if ($routes->hasNamedRoute('dapr.invoke')) {
            return;
        }

        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $router = $this->app['router'];

        $router->group([], function () {
            Route::daprInvokeController();
        });
    }

    protected function rebuildInvocationRegistryFromRoutes(): void
    {
        /** @var InvocationRegistry $registry */
        $registry = $this->app->make(InvocationRegistry::class);

        $router = $this->app['router'];

        foreach ($router->getRoutes() as $route) {
            $handlers = $route->defaults['dapr_invoke_handlers'] ?? null;

            if (is_array($handlers) && ! empty($handlers)) {
                $registry->registerMany($handlers);
            }
        }
    }

    protected function hydrateHandlersFromRoutes(): void
    {
        $registry = $this->app->make(InvocationRegistry::class);
        $routes = $this->app['router']->getRoutes();

        foreach ($routes as $route) {
            if ($handlers = $route->defaults['_dapr_handlers'] ?? null) {
                $registry->registerMany($handlers);
            }
        }
    }
}
