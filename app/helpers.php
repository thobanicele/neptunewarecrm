<?php

use App\Models\Tenant;
use App\Support\TenantPlan;
use Illuminate\Contracts\Routing\UrlRoutable;

if (! function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}

if (! function_exists('tenant_route')) {
    function tenant_route(string $name, array|string|int|UrlRoutable $params = [], bool $absolute = true): string
    {
        $tenant = tenant();

        // Normalize params so model/string/int inputs still work
        if ($params instanceof UrlRoutable || is_string($params) || is_int($params)) {
            $params = [$params];
        } elseif (! is_array($params)) {
            $params = [];
        }

        if ($tenant) {
            $params['tenant'] = $params['tenant'] ?? ($tenant->subdomain ?? $tenant->id);
            return route($name, $params, $absolute);
        }

        // No tenant bound: do not try to generate tenant routes
        if (str_starts_with($name, 'tenant.')) {
            return '#';
        }

        return route($name, $params, $absolute);
    }
}

if (! function_exists('tenant_feature')) {
    function tenant_feature(?Tenant $tenant, string $feature, bool $default = false): bool
    {
        if (! $tenant) {
            return $default;
        }

        // Trial/subscription-aware
        if (($tenant->plan ?? null) === 'internal_neptuneware') {
            if (in_array($feature, ['ecommerce_module', 'ecommerce_inbound_api'], true)) {
                return true;
            }
        }

        return TenantPlan::feature($tenant, $feature, $default);
    }
}

if (! function_exists('tenant_limit')) {
    function tenant_limit(?Tenant $tenant, string $path, $default = null)
    {
        if (! $tenant) {
            return $default;
        }

        return TenantPlan::limit($tenant, $path, $default);
    }
}

if (! function_exists('tenant_is_internal_allowed')) {
    function tenant_is_internal_allowed(?Tenant $tenant): bool
    {
        if (! $tenant) {
            return false;
        }

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










