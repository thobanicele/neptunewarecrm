<?php
namespace App\Http\Middleware;

use App\Models\Deal;
use Closure;
use Illuminate\Http\Request;

class EnforceTenantLimits
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        $tenant = app('tenant');
        $plan = $tenant->plan ?? 'free';

        $limits = config("tenant_limits.$plan", config('tenant_limits.free'));

        if ($feature === 'deals.create') {
            $max = (int) ($limits['max_deals'] ?? 0);
            $count = Deal::where('tenant_id', $tenant->id)->count();

            if ($max > 0 && $count >= $max) {
                return redirect()
                    ->route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain])
                    ->with('error', "You’ve reached your plan limit ($max deals). Upgrade to add more.");
            }
        }

        return $next($request);
    }
}

