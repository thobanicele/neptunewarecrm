<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{

    protected $middleware = [
        // global middlewares
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        
    ];


    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
    protected $routeMiddleware = [];


    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'identify.tenant.path' => \App\Http\Middleware\IdentifyTenantFromPath::class,
        'tenant.last_seen' => \App\Http\Middleware\TouchTenantLastSeen::class,
        'platform.owner' => \App\Http\Middleware\EnsurePlatformOwner::class,
        'tenant' => \App\Http\Middleware\TenantMiddleware::class,
        'no.tenant' => \App\Http\Middleware\EnsureUserHasNoTenant::class,
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        'tenant.key' => \App\Http\Middleware\IdentifyTenantFromApiKey::class,
        'tenant.feature' => \App\Http\Middleware\RequireTenantFeature::class,
        
    ];
}