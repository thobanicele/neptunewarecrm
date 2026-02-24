<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TouchTenantLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        $res = $next($request);

        if (app()->bound('tenant')) {
            $tenant = app()->make('tenant');

            // Because Tenant::$casts has last_seen_at => datetime,
            // this will be Carbon|null
            $should = !$tenant->last_seen_at || $tenant->last_seen_at->lt(now()->subMinutes(10));

            if ($should) {
                $tenant->forceFill(['last_seen_at' => now()])->save();
            }
        }

        return $res;
    }
}
