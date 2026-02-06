<?php

use App\Models\Tenant;
use App\Support\TenantPlan;

if (! function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}

if (! function_exists('tenant_route')) {
    function tenant_route(string $name, $params = [], bool $absolute = true)
    {
        $t = tenant();

        // If tenant not bound, avoid crashing on tenant routes
        if (! $t) return '#';

        // If user passed a Model or scalar, infer the parameter name from the named route
        if (! is_array($params)) {
            $route = app('router')->getRoutes()->getByName($name);
            $names = $route ? $route->parameterNames() : [];

            // remove tenant param
            $names = array_values(array_filter($names, fn ($n) => $n !== 'tenant'));

            // If exactly one non-tenant param exists (company/deal/contact/etc), map to it
            if (count($names) === 1) {
                $params = [$names[0] => $params];
            } else {
                $params = []; // fallback
            }
        }

        // Always inject tenant subdomain
        return route($name, array_merge(['tenant' => $t->subdomain], $params), $absolute);
    }
}

if (! function_exists('tenant_feature')) {
    function tenant_feature(?Tenant $tenant, string $feature, bool $default = false): bool
    {
        // Config-driven plan features
        return TenantPlan::feature($tenant?->plan, $feature, $default);
    }
}

if (! function_exists('tenant_limit')) {
    function tenant_limit(?\App\Models\Tenant $tenant, string $path, $default = null)
    {
        return \App\Support\TenantPlan::limit($tenant?->plan, $path, $default);
    }
}











