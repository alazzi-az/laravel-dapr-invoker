<?php

namespace AlazziAz\LaravelDaprInvoker\Support;

use AlazziAz\LaravelDaprInvoker\Http\InvokeController;
use Illuminate\Support\Facades\Route;

class InvocationRouteRegistrar
{
    public static function register(): void
    {
        if (! Route::hasMacro('daprInvokeController')) {
            Route::macro('daprInvokeController', function (string $controller = InvokeController::class, array $options = []) {
                $prefix = trim(config('dapr.invocation.prefix', 'dapr/invoke'), '/');
                $middleware = $options['middleware'] ?? config('dapr.invocation.middleware', []);

                return Route::prefix($prefix)
                    ->middleware($middleware)
                    ->group(function () use ($controller) {
                        Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/{method}', $controller)
                            ->where('method', '[A-Za-z0-9._-]+')
                            ->name('dapr.invoke');
                    });
            });
        }

        if (! Route::hasMacro('daprInvoke')) {
            Route::macro('daprInvoke', function (array $handlers = [], array $options = []) {
                /** @var InvocationRegistry $registry */
                $registry = app(InvocationRegistry::class);
                $registry->registerMany($handlers);

                return Route::daprInvokeController($options['controller'] ?? InvokeController::class, $options);
            });
        }
    }
}
