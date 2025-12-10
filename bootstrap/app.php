<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Register Backpack auth routes manually
            Route::group([
                'prefix' => config('backpack.base.route_prefix', 'admin'),
                'middleware' => config('backpack.base.web_middleware', 'web'),
            ], function () {
                // Login routes
                Route::get('login', [\Backpack\CRUD\app\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('backpack.auth.login');
                Route::post('login', [\Backpack\CRUD\app\Http\Controllers\Auth\LoginController::class, 'login']);
                Route::post('logout', [\Backpack\CRUD\app\Http\Controllers\Auth\LoginController::class, 'logout'])->name('backpack.auth.logout');

                // Password reset routes (only if enabled)
                if (config('backpack.base.setup_password_recovery_routes', true)) {
                    Route::get('password/reset', [\Backpack\CRUD\app\Http\Controllers\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('backpack.auth.password.reset');
                    Route::post('password/reset', [\Backpack\CRUD\app\Http\Controllers\Auth\ResetPasswordController::class, 'reset'])->name('backpack.auth.password.reset.post');
                    Route::get('password/reset/{token}', [\Backpack\CRUD\app\Http\Controllers\Auth\ResetPasswordController::class, 'showResetForm'])->name('backpack.auth.password.reset.token');
                    Route::post('password/email', [\Backpack\CRUD\app\Http\Controllers\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('backpack.auth.password.email');
                }
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register custom middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckIfAdmin::class,
        ]);

        // Register additional common middleware aliases for convenience
        $middleware->alias([
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ]);

        // Add admin middleware to the existing admin group (used by Backpack)
        // This ensures our CheckIfAdmin middleware is included in admin routes
        $middleware->appendToGroup('admin', [
            \App\Http\Middleware\CheckIfAdmin::class,
        ]);

        // Add admin middleware to web group when admin functionality is needed
        // This allows admin routes to use 'web' group and still have admin protection
        $middleware->web(append: [
            // Note: Admin middleware is applied via alias in routes, not globally here
        ]);

        // Add admin middleware to API group for admin API endpoints
        $middleware->api(append: [
            // Note: Admin middleware can be applied to specific API routes as needed
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
