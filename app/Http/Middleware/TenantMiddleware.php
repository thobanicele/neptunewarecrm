<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->has('tenant') && !app()->has('currentTenant')) {
            abort(404, 'Tenant context missing.');
        }

        return $next($request);
    }
}

