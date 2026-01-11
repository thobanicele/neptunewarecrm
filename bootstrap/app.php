<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global web middleware additions (optional)
        $middleware->web(append: [
            \App\Http\Middleware\IdentifyTenant::class,
        ]);

        // Route middleware aliases (THIS is where your no.tenant etc must go)
        $middleware->alias([
            'no.tenant' => \App\Http\Middleware\EnsureUserHasNoTenant::class,
            'identify.tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,

            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

