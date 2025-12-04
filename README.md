# 🧩 Dapr Events Invoker

[![Packagist Version](https://img.shields.io/packagist/v/alazziaz/laravel-dapr-invoker.svg?color=0f6ab4)](https://packagist.org/packages/alazziaz/laravel-dapr-invoker)
[![Total Downloads](https://img.shields.io/packagist/dt/alazziaz/laravel-dapr-invoker.svg)](https://packagist.org/packages/alazziaz/laravel-dapr-invoker)

Expose **Laravel actions as Dapr-invokable methods** and **call remote or local Laravel routes** through the Dapr Service Invocation API.

> Built on top of [`alazziaz/laravel-dapr-foundation`](https://packagist.org/packages/alazziaz/laravel-dapr-foundation).

---

## 🚀 Installation

```bash
composer require alazziaz/laravel-dapr-invoker
```

The package auto-discovers its service provider and registers all Dapr invocation helpers, macros, and bindings automatically.

---

## ⚡ Invoking any Laravel route — no setup required

Out of the box, the invoker can call **any existing Laravel route** (no extra configuration or registration needed).
You can invoke your app or any other Dapr-enabled Laravel app the same way:

```php
use AlazziAz\LaravelDaprInvoker\Facades\DaprInvoke;

// Invoke a normal Laravel route (even in the same app)
$response = DaprInvoke::post(
    appId: 'my-laravel-app',
    method: 'api/orders/create',
    payload: ['amount' => 99.50, 'currency' => 'USD']
);
```

✅ Works instantly with any route defined in your Laravel app — e.g.:

```php
Route::post('api/orders/create', [OrderController::class, 'store']);
```

Dapr will invoke:

```
POST /v1.0/invoke/my-laravel-app/method/api/orders/create
```

and Laravel receives it as a **normal HTTP request** with the same `$request`, middleware, and controller logic.

This lets you:

- Call local Laravel routes through Dapr (loopback mode)
- Invoke any microservice registered with Dapr by its `app-id`
- Use the same Facade methods for internal and external calls

---

## ⚙️ Registering special Dapr invocation routes

While normal Laravel routes work automatically, this package also allows **dedicated Dapr invocation route registration** for more advanced use cases — such as adding custom middleware, HMAC verification, or internal-only exposure.

```php
use Illuminate\Support\Facades\Route;

Route::daprInvoke([
    'orders.create' => \App\Http\Controllers\Orders\CreateViaInvoke::class,
    'users.list'    => \App\Http\Controllers\Users\ListUsers::class,
]);
```

Each handler receives the decoded Dapr payload as `$payload` and has access to the standard `Request` object (headers, query params, etc.).

Alternatively, register a single controller and let it resolve handlers dynamically (based on your config):

```php
Route::daprInvokeController();
```

---

### 🧠 Why use special registration?

By default, Dapr can call your normal Laravel routes directly — but
**`Route::daprInvoke()`** exists to give you _extra control_.

Use it when you need to:

- Apply **custom middleware** (`api`, `verify.dapr.signature`, etc.)
- Enforce **HMAC / shared secret validation** for inter-service calls
- Add **metrics**, **audit logging**, or **rate-limiting**
- Separate Dapr-only endpoints from public HTTP routes

Example config (`config/dapr.php`):

```php
return [
  'invocation'=[
'prefix' => 'dapr/invoke',
    'auto_register' => false,
    'middleware' => [
        // \App\Http\Middleware\Authenticate::class,
    ],
    'verify_signature' => false,
    'signature_header' => 'x-dapr-signature',
    'signature_secret' => env('DAPR_INVOKE_SECRET'),
    'map' => [
        // 'service.method' => [Controller::class, 'method'],
        // 'orders.create' => [App\Http\Controllers\OrderController::class, 'createViaInvoke'],
    ],
  ]
];
```

---

## 🔄 Invoking other Laravel apps or services

`DaprInvoker` lets you call **any other Laravel service** (local or remote) via Dapr’s service discovery.

```php
$response = DaprInvoke::get('inventory-service', 'api/products', ['sku' => 'ABC123']);
```

It behaves exactly like Laravel’s `Http` client, but Dapr-aware —
using service names instead of fixed URLs.

> 💡 Think of `DaprInvoke::post()` as a distributed, service-aware `Http::post()` —
> no hardcoded hosts, with retries, encryption, and sidecar security included.

---

## 💡 Client usage patterns

Invoke remote services synchronously or asynchronously — via **helper**, **dependency injection**, or **facade**.

### Helper

```php
$response = dapr_invoke('billing-service', 'health.check');
```

### Dependency Injection

```php
use AlazziAz\LaravelDaprInvoker\Contracts\DaprInvokerContract;

public function charge(DaprInvokerContract $dapr)
{
    return $dapr->invoke('billing-service', 'payments.charge', [
        'amount' => 1200,
        'currency' => 'USD',
    ]);
}
```

### Facade

```php
use AlazziAz\LaravelDaprInvoker\Facades\DaprInvoke;

// Synchronous
$response = DaprInvoke::post('billing-service', 'payments.charge', [
    'amount' => 1200,
    'currency' => 'USD',
]);

// Asynchronous (Promise)
$promise = DaprInvoke::getAsync('inventory-service', 'products.available', ['sku' => 'ABC123']);
$result  = $promise->wait();
```

---

## ⚙️ Configuration

Publish and customize the configuration if needed:

```bash
php artisan vendor:publish --provider="AlazziAz\\LaravelDaprInvoker\\ServiceProvider" --tag=dapr-invocation-config
```

Example `config/dapr.php`:

```php
return [
    'invocation'=>[
    'prefix' => 'dapr/invoke',
    'auto_register' => false,
    'middleware' => [],
    'verify_signature' => false,
    'signature_header' => 'x-dapr-signature',
    'signature_secret' => env('DAPR_INVOKE_SECRET'),
    'map' => [],
    ]
];
```

---

## 🧱 Technical Highlights

- Compatible with [`dapr/php-sdk`](https://github.com/dapr/php-sdk)
- Supports **calling any Laravel route** directly through Dapr (no registration)
- Supports **custom Dapr invocation routes** with middleware and security
- Provides **sync and async** invocation via Facade or DI
- Handles **query params, headers, and payloads** automatically
- SOLID, testable, Laravel-ready (auto-discovery, contracts, facades)

---

## 🧪 Example end-to-end flow

**1️⃣ Define a standard Laravel route:**

```php
Route::post('api/orders/create', [OrderController::class, 'store']);
```

**2️⃣ Call it via Dapr from another service:**

```php
$response = DaprInvoke::post('my-laravel-app', 'api/orders/create', ['amount' => 99.5]);
```

**3️⃣ Laravel receives it transparently via Dapr:**

```
POST /v1.0/invoke/my-laravel-app/method/api/orders/create
```

✅ No registration required — behaves exactly like a local HTTP request.

---

**Optional (enhanced control):**

**Register a special Dapr-only route:**

```php
Route::daprInvoke([
    'orders.create' => \App\Http\Controllers\Orders\CreateViaInvoke::class,
]);
```

Now it’s served at:

```
POST /v1.0/invoke/my-laravel-app/method/dapr/invoke/orders.create
```

and protected by your configured Dapr middleware.

---

> **Important – CSRF Exclusion Required:**
> Dapr service invocation is stateless and does not include CSRF tokens.
> To allow Dapr to call your Laravel handlers (e.g., /dapr/invoke/{method}),
> you must disable CSRF protection for that prefix:
>
> ```php
>
> $middleware->validateCsrfTokens(except: [
>   'dapr/invoke/*',
> ]);
> ```
>
> Without this, POST/PUT/PATCH/>DELETE requests invoked through Dapr
> will fail with: 419 CSRF token mismatch.

> **you can list dapr only routes by run following command:**
>
> ```bash
> php artisan dapr-invoker:list
> ```

## 🧩 Requirements

- PHP ≥ 8.2
- Laravel 10 or 11
- Running Dapr sidecar

```bash
dapr run --app-id my-laravel-app --app-port 8000 -- php artisan serve --port=8000
```

> The upstream `dapr/php-sdk` is currently on `dev-main` (PHP 8.4).
> Use `alazziaz/laravel-dapr-foundation` for 8.2/8.3 compatibility.

---

## 📜 License

MIT © 2025 Mohammed Azman / AlazziAz

---
