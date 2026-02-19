<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantAccess
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = app('tenant');          // set by identify.tenant.path
        $user   = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 1) User must belong to this tenant (1 tenant per user)
        if ((int) $user->tenant_id !== (int) $tenant->id) {
            abort(403, 'You do not belong to this workspace.');
        }

        // 2) Super admin bypass (optional)
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return $next($request);
        }

        // 3) Must have at least one role in THIS tenant context
        // With teams enabled, hasAnyRole will check current team_id (tenant_id)
        $hasAnyRole = method_exists($user, 'roles') && $user->roles()->exists();

        if (!$hasAnyRole) {
            // You can either:
            // A) block with a friendly message:
            abort(403, 'No role assigned for this workspace. Contact an admin.');
            // B) OR auto-assign a default role (viewer) if you want:
            // $user->assignRole('viewer');
        }

        return $next($request);
    }
}
