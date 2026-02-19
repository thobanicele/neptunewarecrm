<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetPermissionTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // If not logged in, do nothing
        if (!$user) {
            return $next($request);
        }

        // ✅ super_admin stays global
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
            return $next($request);
        }

        // ✅ tenant scoped
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        $user?->unsetRelation('roles');
        $user?->unsetRelation('permissions');

        if ($tenant) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        }

        return $next($request);
    }
}

