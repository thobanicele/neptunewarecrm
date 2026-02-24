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
        if (! $tenant) return $default;

        // ✅ Trial/subscription-aware
        if ($tenant && ($tenant->plan ?? null) === 'internal_neptuneware') {
            if (in_array($feature, ['ecommerce_module','ecommerce_inbound_api'], true)) return true;
        }
        return TenantPlan::feature($tenant, $feature, $default);
    }
}

if (! function_exists('tenant_limit')) {
    function tenant_limit(?Tenant $tenant, string $path, $default = null)
    {
        if (! $tenant) return $default;

        return TenantPlan::limit($tenant, $path, $default);
    }
}

if (! function_exists('tenant_is_internal_allowed')) {
    function tenant_is_internal_allowed(?Tenant $tenant): bool
    {
        if (! $tenant) return false;

        // if internal-only is OFF, allow all tenants
        if (! config('ecommerce_internal.only', true)) {
            return true;
        }

        $raw = (string) config('ecommerce_internal.allowed', '');
        $allowed = array_values(array_filter(array_map('trim', explode(',', $raw))));

        $id = (string) $tenant->id;
        $sub = (string) ($tenant->subdomain ?? '');

        return in_array($id, $allowed, true) || ($sub !== '' && in_array($sub, $allowed, true));
    }
}











