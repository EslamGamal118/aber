<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Al Rajhi Bank posts payment callbacks server-to-server (no CSRF token)
        $middleware->validateCsrfTokens(except: [
            'payment/callback',
            'payment/failed',
        ]);

        $middleware->alias([
            'auth.admin' => \App\Http\Middleware\RedirectIfNotAdmin::class,
            'guest.admin' => \App\Http\Middleware\RedirectIfAdmin::class,
            'guest' => \App\Http\Middleware\Guest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
    
