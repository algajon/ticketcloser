<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'resolve.workspace' => \App\Http\Middleware\ResolveWorkspace::class,
            'verify.workspace.token' => \App\Http\Middleware\VerifyWorkspaceToken::class,
            'verify.server.token' => \App\Http\Middleware\VerifyServerToken::class,
            'subscribed' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
        ]);

        // Exclude Stripe webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            '/webhooks/stripe',
        ]);

        // Trust all proxies (ngrok, load balancers, etc.)
        $middleware->trustProxies(at: '*');
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
