<?php

namespace App\Support;

class TenantPlan
{
    public static function resolve(?string $plan): string
    {
        $plan = $plan ?: config('plans.default_plan', 'free');

        // safety: if unknown plan stored in DB, fall back
        if (!array_key_exists($plan, config('plans.plans', []))) {
            $plan = config('plans.default_plan', 'free');
        }

        return $plan;
    }

    public static function feature(?string $plan, string $feature, bool $default = false): bool
    {
        $plan = self::resolve($plan);

        return (bool) data_get(
            config("plans.plans.$plan.features", []),
            $feature,
            $default
        );
    }

    public static function limit(?string $plan, string $path, $default = null)
    {
        $plan = self::resolve($plan);

        return data_get(
            config("plans.plans.$plan", []),
            $path,
            $default
        );
    }
}




