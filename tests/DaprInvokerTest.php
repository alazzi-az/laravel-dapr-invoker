<?php

use AlazziAz\LaravelDaprInvoker\Support\DaprInvoker;
use Dapr\Client\DaprClient;
use Mockery as m;

it('delegates to dapr client', function () {
    $client = m::mock(DaprClient::class);
    $client->shouldReceive('invokeMethod')
        ->once()
        ->with('orders-app', 'health/check', ['ping' => true], 'POST', [], [])
        ->andReturn(['ok' => true]);

    $this->app->instance(DaprClient::class, $client);

    $invoker = $this->app->make(DaprInvoker::class);
    expect($invoker->invoke('orders-app', 'health/check', ['ping' => true]))
        ->toEqual(['ok' => true]);
});
