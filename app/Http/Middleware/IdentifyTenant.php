<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();                 // e.g. acme.yourapp.com
        $parts = explode('.', $host);
        $subdomain = $parts[0] ?? null;

        // OPTIONAL: if you also support non-subdomain access (e.g. localhost or www),
        // then skip tenant resolution when subdomain is not a real tenant key.
        // Adjust this list to your environment.
        $ignore = ['www', 'localhost', '127', 'admin','crm'];
        if (!$subdomain || in_array($subdomain, $ignore, true)) {
            return $next($request);
        }

        $tenant = Tenant::where('subdomain', $subdomain)->first();
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }

        // Bind tenant in container (keep backward compatibility)
        app()->instance('tenant', $tenant);
        app()->instance('currentTenant', $tenant);

        // If user is logged in, enforce they belong to this tenant
        if ($request->user() && (int) $request->user()->tenant_id !== (int) $tenant->id) {
            abort(403, 'You do not belong to this tenant.');
        }

        return $next($request);
    }
}
