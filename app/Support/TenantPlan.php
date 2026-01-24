<?php

namespace App\Support;

class TenantPlan
{
    public static function planKey(?string $plan): string
    {
        $plan = $plan ?: config('tenant_limits.default_plan');
        return array_key_exists($plan, config('tenant_limits.plans'))
            ? $plan
            : config('tenant_limits.default_plan');
    }

    /** Get current tenant plan (from app('tenant')) */
    public static function currentPlanKey(): string
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        return self::planKey($tenant?->plan);
    }

    /** Limit for a specific plan key */
    public static function limit(string $plan, string $key, $default = null)
    {
        $plan = self::planKey($plan);
        return data_get(config("tenant_limits.plans.$plan"), $key, $default);
    }

    /** Limit for current tenant */
    public static function currentLimit(string $key, $default = null)
    {
        $plan = self::currentPlanKey();
        return data_get(config("tenant_limits.plans.$plan"), $key, $default);
    }

    /** Feature flag for a specific plan key */
    public static function feature(string $plan, string $feature, bool $default = false): bool
    {
        $plan = self::planKey($plan);
        return (bool) data_get(config("tenant_limits.plans.$plan.features"), $feature, $default);
    }

    /** Feature flag for current tenant */
    public static function currentFeature(string $feature, bool $default = false): bool
    {
        $plan = self::currentPlanKey();
        return (bool) data_get(config("tenant_limits.plans.$plan.features"), $feature, $default);
    }
}

