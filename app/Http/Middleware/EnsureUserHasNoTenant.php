<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserHasNoTenant
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->tenant_id) {
            return redirect()->route('tenant.dashboard')
                ->with('error', 'You already belong to a tenant.');
        }

        return $next($request);
    }
}

