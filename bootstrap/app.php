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
            
        ]);

        // Route middleware aliases (THIS is where your no.tenant etc must go)
        $middleware->alias([
            'identify.tenant.path' => \App\Http\Middleware\IdentifyTenantFromPath::class,
            'no.tenant' => \App\Http\Middleware\EnsureUserHasNoTenant::class,
            'tenant' => \App\Http\Middleware\TenantMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'tenant.limits' => \App\Http\Middleware\TenantLimits::class,
            'set.permission.tenant' => \App\Http\Middleware\SetPermissionTenant::class,
            'active.user' => \App\Http\Middleware\EnsureUserIsActive::class,
            'ensure.user.tenant' => \App\Http\Middleware\EnsureUserBelongsToTenant::class,
            'tenant.access' => \App\Http\Middleware\TenantAccess::class,
        ]);

        // ✅ CRITICAL: ensure tenant/team middleware runs before spatie role middleware
        $middleware->priority([
            \App\Http\Middleware\IdentifyTenantFromPath::class,
            \App\Http\Middleware\SetPermissionTenant::class,

            \Spatie\Permission\Middleware\RoleMiddleware::class,
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
            \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

