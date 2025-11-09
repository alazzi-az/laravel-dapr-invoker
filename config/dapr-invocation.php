<?php

return [
    'prefix' => 'dapr/invoke',

    'auto_register' => false,

    'middleware' => [
        // \App\Http\Middleware\Authenticate::class,
    ],

    'verify_signature' => false,
    'signature_header' => 'x-dapr-signature',
    'signature_secret' => env('DAPR_INVOKE_SECRET'),

    'map' => [
        // 'service.method' => App\Http\Controllers\InvokeTargetController::class,
        // 'orders.create' => [App\Http\Controllers\OrderController::class, 'createViaInvoke'],
    ],
];
