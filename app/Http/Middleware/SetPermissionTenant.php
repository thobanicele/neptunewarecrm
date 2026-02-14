<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionTenant
{
    public function handle($request, Closure $next)
    {
        $registrar = app(PermissionRegistrar::class);

        $tenant = app('tenant');
        if ($tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    return $next($request);
    }
}


