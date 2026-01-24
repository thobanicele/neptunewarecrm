<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenantFromPath
{
    public function handle(Request $request, Closure $next)
    {
        $tenantParam = $request->route('tenant');

        $tenant = $tenantParam instanceof Tenant
            ? $tenantParam
            : Tenant::where('subdomain', $tenantParam)->first();

        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        app()->instance('tenant', $tenant);
        app()->instance('currentTenant', $tenant);

        $user = $request->user();

        // ✅ allow super admin to access any tenant
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return $next($request);
        }

        // regular tenant users must match tenant in path
        if ($user && (int) $user->tenant_id !== (int) $tenant->id) {
            abort(403, 'You do not belong to this tenant.');
        }

        return $next($request);
    }
}




