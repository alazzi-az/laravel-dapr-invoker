<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('dapr.invocation.verify_signature', false);

    Route::daprInvoke([
        'orders.create' => TestInvokeHandler::class,
    ]);
});

it('dispatches registered handler for invocation', function () {
    $response = $this->postJson('/dapr/invoke/orders.create', [
        'order' => 123,
    ]);

    $response->assertOk()
        ->assertJson([
            'handled' => true,
            'payload' => [
                'order' => 123,
            ],
        ]);
});

it('rejects invocation when signature invalid', function () {
    config()->set('dapr.invocation.verify_signature', true);
    config()->set('dapr.invocation.signature_secret', 'secret');

    $response = $this->postJson('/dapr/invoke/orders.create', [
        'order' => 123,
    ]);

    $response->assertStatus(403);
});

class TestInvokeHandler
{
    public function __invoke(array $payload): array
    {
        return [
            'handled' => true,
            'payload' => $payload,
        ];
    }
}
