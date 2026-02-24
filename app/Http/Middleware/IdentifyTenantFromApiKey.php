<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenantFromApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = (string) $request->header('X-Tenant-Key');

        if ($apiKey === '') {
            return response()->json(['message' => 'Missing X-Tenant-Key header.'], 401);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::where('api_key', $apiKey)->first();

        if (!$tenant) {
            return response()->json(['message' => 'Invalid tenant key.'], 401);
        }

        // Optional: require HTTPS in production for signed requests
        if (
            config('ecommerce_api.require_https_in_production', true)
            && app()->environment('production')
            && !$request->isSecure()
        ) {
            return response()->json(['message' => 'HTTPS is required.'], 400);
        }

        // Make tenant available like your existing path-based tenancy
        app()->instance('tenant', $tenant);
        app()->instance('currentTenant', $tenant);
        $request->attributes->set('tenant', $tenant);

        // If tenant has an HMAC secret, enforce signature + replay protection
        if (!empty($tenant->api_hmac_secret)) {
            $timestamp = (string) $request->header('X-Timestamp');
            $nonce = (string) $request->header('X-Nonce');
            $signature = (string) $request->header('X-Signature');

            if ($timestamp === '' || $nonce === '' || $signature === '') {
                return response()->json([
                    'message' => 'Missing X-Timestamp, X-Nonce, or X-Signature header.',
                ], 401);
            }

            // Validate timestamp
            $ts = (int) $timestamp;
            $now = now()->timestamp;
            $window = (int) config('ecommerce_api.signature_window_seconds', 300);

            if ($ts <= 0 || abs($now - $ts) > $window) {
                return response()->json([
                    'message' => 'Signature timestamp is outside the allowed window.',
                ], 401);
            }

            // Nonce replay protection
            // Store nonce for (window + small buffer) seconds to block replays
            $storeName = config('ecommerce_api.nonce_cache_store');
            $cache = $storeName ? Cache::store($storeName) : Cache::store();
            $prefix = (string) config('ecommerce_api.nonce_cache_prefix', 'ecomm_api_nonce:');

            // Key includes tenant + timestamp bucket + nonce
            // (timestamp included to keep keys bounded even if nonces are random forever)
            $bucket = intdiv($ts, max(1, $window));
            $nonceKey = $prefix . $tenant->id . ':' . $bucket . ':' . hash('sha256', $nonce);

            if ($cache->has($nonceKey)) {
                return response()->json([
                    'message' => 'Replay detected (nonce already used).',
                ], 409);
            }

            // Canonical request string
            $method = strtoupper($request->getMethod());

            // Use raw path + query exactly as received (prevents signature reuse across endpoints)
            $pathWithQuery = $request->getRequestUri(); // includes query string

            $rawBody = (string) $request->getContent();
            $bodyHash = hash('sha256', $rawBody);

            $canonical = implode("\n", [
                $apiKey,
                (string) $ts,
                $nonce,
                $method,
                $pathWithQuery,
                $bodyHash,
            ]);

            // Compute expected signature (base64 of raw HMAC)
            $expectedRaw = hash_hmac('sha256', $canonical, $tenant->api_hmac_secret, true);
            $expected = base64_encode($expectedRaw);

            // Constant-time compare
            if (!hash_equals($expected, $signature)) {
                return response()->json([
                    'message' => 'Invalid signature.',
                ], 401);
            }

            // Mark nonce as used
            $cache->put($nonceKey, 1, $window + 30);
        }

        return $next($request);
    }
}
