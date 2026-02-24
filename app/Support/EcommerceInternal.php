<?php

if (! function_exists('tenant_is_internal_allowed')) {
    function tenant_is_internal_allowed(?\App\Models\Tenant $tenant): bool
    {
        if (! $tenant) return false;

        if (! config('ecommerce_internal.only', true)) {
            return true; // not restricted
        }

        $raw = (string) config('ecommerce_internal.allowed', '');
        $allowed = array_values(array_filter(array_map('trim', explode(',', $raw))));

        $id = (string) $tenant->id;
        $sub = (string) ($tenant->subdomain ?? '');

        return in_array($id, $allowed, true) || ($sub !== '' && in_array($sub, $allowed, true));
    }
}