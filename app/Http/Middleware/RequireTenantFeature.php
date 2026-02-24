<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTenantFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = tenant(); // your helper

        if (! $tenant) {
            return response()->json(['message' => 'Tenant not resolved.'], 401);
        }

        // Plan/feature gate
        if (! tenant_feature($tenant, $feature, false)) {
            return $this->deny($request, 'Feature not enabled.', 403);
        }

        // Internal-only allowlist gate (safety switch)
        if (config('ecommerce_internal.only', true)) {
            $raw = (string) config('ecommerce_internal.allowed', '');
            $allowed = array_values(array_filter(array_map('trim', explode(',', $raw))));

            $tenantId = (string) $tenant->id;
            $subdomain = (string) ($tenant->subdomain ?? '');

            $ok = in_array($tenantId, $allowed, true) || ($subdomain !== '' && in_array($subdomain, $allowed, true));

            if (! $ok) {
                return $this->deny($request, 'Feature not available yet.', 403);
            }
        }

        return $next($request);
    }

    private function deny(Request $request, string $message, int $status): Response
    {
        // API expects JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => $message], $status);
        }

        // Web: redirect back with message
        return redirect()->back()->with('error', $message);
    }
}
