<?php

namespace App\Http\Middleware;

use App\Models\Deal;
use App\Models\Pipeline;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\TenantPlan;

class TenantLimits
{
    public function handle(Request $request, Closure $next, string $rule): Response
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        $user   = $request->user();

        if (!$tenant) {
            return $next($request);
        }

        // super admin bypass
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return $next($request);
        }

        $plan = $tenant->plan;

        switch ($rule) {

            case 'deals.create':
                $max = (int) TenantPlan::limit($plan, 'deals.max', 0);
                if ($max > 0) {
                    $count = Deal::where('tenant_id', $tenant->id)->count();
                    if ($count >= $max) {
                        return $this->blocked($request, 'deals.create', $tenant, "You've reached the Free plan limit of {$max} deals.");
                    }
                }
                break;

            case 'pipelines.create':
                $max = (int) TenantPlan::limit($plan, 'pipelines.max', 0);
                if ($max > 0) {
                    $count = Pipeline::where('tenant_id', $tenant->id)->count();
                    if ($count >= $max) {
                        return $this->blocked($request, 'pipelines.create', $tenant, "You've reached the Free plan limit of {$max} pipelines.");
                    }
                }
                break;

            case 'feature.export':
                if (!TenantPlan::feature($plan, 'export', false)) {
                    return $this->blocked($request, 'export', $tenant, "Export is a Premium feature.");
                }
                break;

            case 'feature.custom_branding':
                if (!TenantPlan::feature($plan, 'custom_branding', false)) {
                    return $this->blocked($request, 'custom_branding', $tenant, "Custom branding is a Premium feature.");
                }
                break;
            case 'feature.ecommerce_module':
                if (!TenantPlan::feature($plan, 'ecommerce_module', false)) {
                    return $this->blocked($request, 'ecommerce_module', $tenant, "Ecommerce module is not enabled for your plan.");
                }
                break;

            case 'feature.ecommerce_inbound_api':
                if (!TenantPlan::feature($plan, 'ecommerce_inbound_api', false)) {
                    return $this->blocked($request, 'ecommerce_inbound_api', $tenant, "Ecommerce inbound API is not enabled for your plan.");
                }
                break;
        }

        return $next($request);
    }

    private function blocked(Request $request, string $featureOrRule, $tenant, string $message)
    {
        // AJAX/JSON clients
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'reason' => 'premium_required',
                'feature' => $featureOrRule,
                'message' => $message,
                'upgrade_url' => route('tenant.billing.upgrade', ['tenant' => $tenant]),
            ], 402);
        }

        // Premium Preview UX: go back + open modal (instead of hard redirect)
        return redirect()
            ->back()
            ->with('upgrade_feature', $featureOrRule)
            ->with('upgrade_message', $message)
            ->with('upgrade_url', route('tenant.billing.upgrade', ['tenant' => $tenant]));
    }
}

