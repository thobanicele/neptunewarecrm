<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenantFromPath
{
    public function handle(Request $request, Closure $next)
    {
        // Because your route is: t/{tenant:subdomain}
        $tenantParam = $request->route('tenant');

        $tenant = $tenantParam instanceof Tenant
            ? $tenantParam
            : Tenant::where('subdomain', $tenantParam)->first();

        abort_unless($tenant, 404, 'Tenant not found');

        // Bind tenant for the request lifecycle
        app()->instance('tenant', $tenant);
        app()->instance('currentTenant', $tenant);

        $user = $request->user();
        if (!$user) {
            // auth middleware might not be present for some route (e.g. invite accept)
            return $next($request);
        }

        // ✅ super_admin can view any tenant
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return $next($request);
        }

        // ✅ tenant user must belong to this tenant
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 403, 'You do not belong to this tenant.');

        return $next($request);
    }
}







