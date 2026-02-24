<?php

namespace App\Support;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantAddon;

class TenantPlan
{
    /**
     * Resolve a plan key safely from a raw plan string.
     */
    public static function resolve(?string $plan): string
    {
        $plan = $plan ?: config('plans.default_plan', 'free');

        if (!array_key_exists($plan, config('plans.plans', []))) {
            $plan = config('plans.default_plan', 'free');
        }

        return $plan;
    }

    /**
     * Trial/Subscription-aware plan for a given tenant.
     * This is the key fix that unlocks premium features during trial.
     */
    public static function effectivePlan(Tenant $tenant): string
    {
        $base = self::resolve($tenant->plan);

        $sub = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        if (!$sub) return $base;

        // Cancelled => fall back
        if (!is_null($sub->canceled_at)) return $base;

        // Trialing premium => premium
        if (
            ($sub->plan === 'premium') &&
            $sub->trial_ends_at &&
            now()->lt($sub->trial_ends_at)
        ) {
            return 'premium';
        }

        // Active premium => premium (choose your condition)
        if ($sub->plan === 'premium') {
            if ($sub->expires_at && now()->lt($sub->expires_at)) return 'premium';
            if (!empty($sub->paystack_subscription_code)) return 'premium';
        }

        return $base;
    }

    /**
     * Feature check.
     * Accepts either:
     *  - a plan string
     *  - a Tenant instance (recommended)
     */
    public static function feature(string|Tenant|null $planOrTenant, string $feature, bool $default = false): bool
    {
        $tenant = $planOrTenant instanceof Tenant ? $planOrTenant : null;

        $plan = $tenant
            ? self::effectivePlan($tenant)
            : self::resolve($planOrTenant);

        $enabledByPlan = (bool) data_get(
            config("plans.plans.$plan.features", []),
            $feature,
            $default
        );

        if ($enabledByPlan) {
            return true;
        }

        // ✅ Add-on fallback (tenant only)
        if (!$tenant) {
            return false;
        }

        // Map feature flags to addon keys
        $featureToAddon = [
            'ecommerce_module' => 'ecommerce',
            'ecommerce_inbound_api' => 'ecommerce',
        ];

        $addonKey = $featureToAddon[$feature] ?? null;
        if (!$addonKey) {
            return false;
        }

        return TenantAddon::query()
            ->where('tenant_id', $tenant->id)
            ->where('key', $addonKey)
            ->where('is_enabled', true)
            ->exists();
    }

    /**
     * Limits check (same dual-accept behavior as feature()).
     */
    public static function limit(string|Tenant|null $planOrTenant, string $path, $default = null)
    {
        $plan = $planOrTenant instanceof Tenant
            ? self::effectivePlan($planOrTenant)
            : self::resolve($planOrTenant);

        return data_get(
            config("plans.plans.$plan", []),
            $path,
            $default
        );
    }
}





