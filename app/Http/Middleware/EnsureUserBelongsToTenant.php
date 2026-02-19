<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserBelongsToTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        if (!$user || !$tenant) {
            return $next($request);
        }

        // ✅ allow super admin to access any tenant area
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return $next($request);
        }

        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403, 'You do not belong to this tenant.');

        return $next($request);
    }
}
