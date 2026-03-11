<?php

use App\Models\Tenant;
use App\Support\TenantPlan;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Support\Facades\Route;

if (! function_exists('tenant')) {
    function tenant(): ?Tenant
    {
        return app()->bound('tenant') ? app('tenant') : null;
    }
}

if (! function_exists('tenant_route')) {
    function tenant_route(string $name, $params = [], bool $absolute = true): string
    {
        $t = app()->bound('tenant') ? app('tenant') : null;

        if (! $t) {
            // fallback to normal route if tenant not bound
            return route($name, is_array($params) ? $params : [], $absolute);
        }

        // If a Model/UrlRoutable is passed, infer the param name from the named route
        if ($params instanceof UrlRoutable) {
            $route = Route::getRoutes()->getByName($name);

            if ($route) {
                $paramNames = $route->parameterNames();       // e.g. ['tenant', 'company']
                $paramNames = array_values(array_diff($paramNames, ['tenant']));
                $key = $paramNames[0] ?? null;

                $params = $key ? [$key => $params] : [];
            } else {
                $params = [];
            }
        }

        // If scalar is passed (like id), try infer similarly
        if (! is_array($params)) {
            $route = Route::getRoutes()->getByName($name);
            if ($route) {
                $paramNames = array_values(array_diff($route->parameterNames(), ['tenant']));
                $key = $paramNames[0] ?? null;
                $params = $key ? [$key => $params] : [];
            } else {
                $params = [];
            }
        }

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











