<?php

namespace AlazziAz\LaravelDaprInvoker\Tests;

use AlazziAz\LaravelDapr\ServiceProvider as FoundationProvider;
use AlazziAz\LaravelDaprInvoker\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            FoundationProvider::class,
            ServiceProvider::class,
        ];
    }
}
