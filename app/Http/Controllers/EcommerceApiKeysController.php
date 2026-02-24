<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class EcommerceApiKeysController extends Controller
{
    public function show(Request $request, string $tenant)
    {
        $tenantModel = app('tenant');

        // Gate: only tenants with feature can view/use API settings
        if (function_exists('tenant_feature')) {
            abort_unless(tenant_feature($tenantModel, 'ecommerce_inbound_api'), 403);
        }

        $maskedKey = $tenantModel->api_key ? Str::mask($tenantModel->api_key, '*', 6) : null;
        $hasSecret = !empty($tenantModel->api_hmac_secret);

        // Endpoint (relative path shown + computed full)
        $endpointPath = '/api/v1/ecommerce/orders';
        $endpointUrl = url($endpointPath);

        // Docs values
        $windowSeconds = (int) config('ecommerce_api.signature_window_seconds', 300);

        return view('tenant.settings.ecommerce_api', compact(
            'tenantModel',
            'maskedKey',
            'hasSecret',
            'endpointPath',
            'endpointUrl',
            'windowSeconds'
        ));
    }

    public function test(\Illuminate\Http\Request $request, string $tenant)
    {
        $tenantModel = app('tenant');

        abort_unless(tenant_feature($tenantModel, 'ecommerce_inbound_api', false), 403);

        if (config('ecommerce_internal.only', true)) {
            $raw = (string) config('ecommerce_internal.allowed', '');
            $allowed = array_values(array_filter(array_map('trim', explode(',', $raw))));
            $ok = in_array((string) $tenantModel->id, $allowed, true)
                || (!empty($tenantModel->subdomain) && in_array((string) $tenantModel->subdomain, $allowed, true));
            abort_unless($ok, 403);
        }

        if (empty($tenantModel->api_key)) {
            return response()->json([
                'ok' => false,
                'message' => 'No tenant API key is set. Click "Rotate Keys" first.',
            ], 422);
        }

        $path = '/api/v1/ecommerce/ping';
        $url = url($path);

        try {
            $headers = [
                'X-Tenant-Key' => $tenantModel->api_key,
                'Accept' => 'application/json',
            ];

            // ✅ If HMAC enabled, sign request (GET has empty body)
            if (!empty($tenantModel->api_hmac_secret)) {
                $ts = (string) time();
                $nonce = (string) \Illuminate\Support\Str::uuid();

                $method = 'GET';
                $bodyHash = hash('sha256', ''); // GET body is empty
                $canonical = implode("\n", [
                    $tenantModel->api_key,
                    $ts,
                    $nonce,
                    $method,
                    $path,      // IMPORTANT: must match request->getRequestUri() in middleware (no host)
                    $bodyHash,
                ]);

                $sig = base64_encode(hash_hmac('sha256', $canonical, $tenantModel->api_hmac_secret, true));

                $headers['X-Timestamp'] = $ts;
                $headers['X-Nonce'] = $nonce;
                $headers['X-Signature'] = $sig;
            }

            $res = \Illuminate\Support\Facades\Http::timeout(8)
                ->withHeaders($headers)
                ->get($url);

            $body = $res->json();
            if (!$res->successful()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Ping failed.',
                    'status' => $res->status(),
                    'response' => $body ?: $res->body(),
                ], 500);
            }

            return response()->json([
                'ok' => true,
                'tenant' => data_get($body, 'tenant'),
                'server_time' => data_get($body, 'server_time'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Ping exception: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function sendSampleOrder(Request $request, string $tenant)
    {
        $tenantModel = app('tenant');

        if (function_exists('tenant_feature')) {
            abort_unless(tenant_feature($tenantModel, 'ecommerce_inbound_api'), 403);
        }

        // Internal-only allowlist safety (same as test)
        if (config('ecommerce_internal.only', true)) {
            $allowed = array_values(array_filter(array_map('trim', explode(',', (string) config('ecommerce_internal.allowed', '')))));
            $ok = in_array((string) $tenantModel->id, $allowed, true)
                || (!empty($tenantModel->subdomain) && in_array((string) $tenantModel->subdomain, $allowed, true));
            abort_unless($ok, 403);
        }

        if (empty($tenantModel->api_key)) {
            return response()->json([
                'ok' => false,
                'message' => 'No tenant API key is set. Please rotate keys first.',
            ], 422);
        }

        // Pick an existing product to make SKU matching work out-of-the-box (if any)
        $product = \App\Models\Product::query()
            ->where('tenant_id', $tenantModel->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first(['id', 'sku', 'name']);

        $externalOrderId = 'TEST-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(4));

        $payload = [
            'external_order_id' => $externalOrderId,
            'source' => 'crm_test',
            'status' => 'pending',
            'payment_status' => 'paid',
            'fulfillment_status' => 'fulfilled',
            'currency' => 'ZAR',
            'subtotal' => 1000,
            'tax_total' => 150,
            'discount_total' => 0,
            'shipping_total' => 0,
            'grand_total' => 1150,
            'placed_at' => now()->toIso8601String(),
            'external_updated_at' => now()->toIso8601String(),
            'paid_at' => now()->toIso8601String(),
            'fulfilled_at' => now()->toIso8601String(),
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '+27 00 000 0000',
            ],
            'billing_address' => [
                'line1' => '1 Test Street',
                'city' => 'Johannesburg',
                'country' => 'ZA',
            ],
            'shipping_address' => [
                'line1' => '1 Test Street',
                'city' => 'Johannesburg',
                'country' => 'ZA',
            ],
            'items' => [
                [
                    'external_item_id' => 'LI-1',
                    'position' => 0,
                    'sku' => $product?->sku ?? 'TEST-SKU-1',
                    'name' => $product?->name ?? 'Test Item',
                    'qty' => 1,
                    'unit_price' => 1000,
                    'tax_total' => 150,
                    'discount_total' => 0,
                    'line_total' => 1000,
                ],
            ],
            'meta' => [
                'test' => true,
                'note' => 'Sample order created from CRM settings page',
                'product_id_used' => $product?->id,
            ],
        ];

        $url = url('/api/v1/ecommerce/orders');

        try {
            $headers = [
                'X-Tenant-Key' => $tenantModel->api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            // If you have HMAC enabled on the middleware, sign it server-side
            if (!empty($tenantModel->api_hmac_secret)) {
                $ts = (string) time();
                $nonce = (string) Str::uuid();
                $path = '/api/v1/ecommerce/orders';
                $method = 'POST';
                $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $bodyHash = hash('sha256', (string) $body);

                $canonical = implode("\n", [
                    $tenantModel->api_key,
                    $ts,
                    $nonce,
                    $method,
                    $path,
                    $bodyHash,
                ]);

                $sig = base64_encode(hash_hmac('sha256', $canonical, $tenantModel->api_hmac_secret, true));

                $headers['X-Timestamp'] = $ts;
                $headers['X-Nonce'] = $nonce;
                $headers['X-Signature'] = $sig;
            }

            $res = Http::timeout(12)
                ->withHeaders($headers)
                ->post($url, $payload);

            $json = $res->json();

            if (!$res->successful()) {
                return response()->json([
                    'ok' => false,
                    'message' => data_get($json, 'message', 'Sample order request failed.'),
                    'status' => $res->status(),
                    'response' => $json,
                ], 500);
            }

            return response()->json([
                'ok' => true,
                'created' => (bool) data_get($json, 'created', false),
                'external_order_id' => $externalOrderId,
                'ecommerce_order' => data_get($json, 'ecommerce_order'),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Sample order failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function rotate(Request $request, string $tenant)
    {
        $tenantModel = app('tenant');

        if (function_exists('tenant_feature')) {
            abort_unless(tenant_feature($tenantModel, 'ecommerce_inbound_api'), 403);
        }

        $apiKey = Str::random(48);
        $secret = Str::random(64);

        $tenantModel->api_key = $apiKey;
        $tenantModel->api_hmac_secret = $secret;
        $tenantModel->save();

        return back()
            ->with('success', 'API keys rotated.')
            ->with('new_api_key', $apiKey)
            ->with('new_api_secret', $secret);
    }
}

