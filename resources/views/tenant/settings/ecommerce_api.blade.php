@extends('layouts.app')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex align-items-center gap-2">
            <h4 class="mb-0">Ecommerce Inbound API</h4>

            @if (config('ecommerce_internal.only', true))
                @php
                    $allowed = array_values(
                        array_filter(
                            array_map('trim', explode(',', (string) config('ecommerce_internal.allowed', ''))),
                        ),
                    );
                    $isAllowed =
                        in_array((string) $tenantModel->id, $allowed, true) ||
                        (!empty($tenantModel->subdomain) && in_array((string) $tenantModel->subdomain, $allowed, true));
                @endphp

                <span class="badge {{ $isAllowed ? 'bg-warning text-dark' : 'bg-secondary' }}">
                    Internal Only
                </span>
            @endif
        </div>

        <div class="text-muted">
            Send checkout orders from the storefront into NeptuneWare CRM.
            @if (config('ecommerce_internal.only', true))
                <span class="text-muted small ms-1">(restricted rollout)</span>
            @endif
        </div>

        @if (session('new_api_key') || session('new_api_secret'))
            <div class="alert alert-warning border">
                <div class="fw-semibold mb-1">Save these now — shown only once</div>
                <div class="small mb-2 text-muted">Store them in your storefront env vars / secrets manager.</div>

                <div class="mb-2">
                    <div class="text-muted small">X-Tenant-Key</div>
                    <code>{{ session('new_api_key') }}</code>
                </div>

                <div class="mb-2">
                    <div class="text-muted small">HMAC Secret</div>
                    <code>{{ session('new_api_secret') }}</code>
                </div>

                <div class="text-muted small">
                    After rotating keys, all old credentials are invalid immediately.
                </div>
            </div>
        @endif

        <div class="row g-3">
            {{-- Left: current keys + actions --}}
            <div class="col-12 col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Credentials</div>

                        <div class="mb-3">
                            <div class="text-muted small">Current X-Tenant-Key</div>
                            <div class="fw-semibold">{{ $maskedKey ?? '— not set —' }}</div>
                            <div class="text-muted small">Rotate to view full value again.</div>
                        </div>

                        <div class="mb-3">
                            <div class="text-muted small">HMAC signing</div>
                            <div class="fw-semibold">{{ $hasSecret ? 'Enabled' : 'Disabled' }}</div>
                            <div class="text-muted small">
                                If enabled, requests must include X-Timestamp, X-Nonce, X-Signature.
                            </div>
                        </div>

                        <form method="POST" action="{{ tenant_route('tenant.settings.ecommerce-api.rotate') }}"
                            class="mb-2">
                            @csrf
                            <button class="btn btn-primary w-100">Rotate Keys</button>
                        </form>

                        <button type="button" class="btn btn-outline-success w-100" id="btnTestEcomApi">
                            Test connection
                        </button>
                        <div id="ecomApiTestResult" class="alert alert-light border mt-3 d-none mb-0"></div>
                        <button type="button" class="btn btn-outline-primary w-100 mt-2" id="btnSendSampleOrder">
                            Send sample order
                        </button>

                        <div id="ecomApiSampleResult" class="alert alert-light border mt-3 d-none mb-0"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Endpoint</div>
                        <div class="text-muted small">Path</div>
                        <code>{{ $endpointPath }}</code>

                        <div class="mt-3 text-muted small">Full URL</div>
                        <code>{{ $endpointUrl }}</code>

                        <div class="text-muted small mt-3">
                            Method: <span class="fw-semibold">POST</span> • Content-Type: <span
                                class="fw-semibold">application/json</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: docs + curl --}}
            <div class="col-12 col-lg-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Required Headers</div>
                        <ul class="mb-0">
                            <li><code>X-Tenant-Key</code> — tenant api key (required)</li>
                            <li><code>Content-Type: application/json</code></li>
                            @if ($hasSecret)
                                <li><code>X-Timestamp</code> — unix seconds (required when HMAC enabled)</li>
                                <li><code>X-Nonce</code> — unique per request (required when HMAC enabled)</li>
                                <li><code>X-Signature</code> — base64 HMAC-SHA256 (required when HMAC enabled)</li>
                                <li class="text-muted small">Allowed time window: {{ $windowSeconds }} seconds.</li>
                            @endif
                        </ul>
                    </div>
                </div>

                @if ($hasSecret)
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">HMAC Canonical String</div>
                            <div class="text-muted small mb-2">
                                Sign this exact string (newline separated). Signature is:
                                <code>base64(hmac_sha256(canonical, secret, raw=true))</code>
                            </div>
                            <pre class="mb-0" style="white-space: pre-wrap;">{tenant_key}
{timestamp}
{nonce}
{METHOD}
{PATH?QUERY}
{SHA256(body)}</pre>
                        </div>
                    </div>
                @endif

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Payload Example</div>
                        <pre class="mb-0" style="white-space: pre-wrap;">{
  "external_order_id": "NW-100234",
  "source": "neptuneware_storefront",
  "status": "pending",
  "payment_status": "paid",
  "fulfillment_status": "fulfilled",
  "currency": "ZAR",
  "subtotal": 1200,
  "tax_total": 180,
  "discount_total": 0,
  "shipping_total": 0,
  "grand_total": 1380,
  "placed_at": "2026-02-23T10:12:00+02:00",
  "external_updated_at": "2026-02-23T10:14:10+02:00",
  "paid_at": "2026-02-23T10:13:00+02:00",
  "fulfilled_at": "2026-02-23T14:55:00+02:00",
  "customer": { "name": "Musa Cele", "email": "musa@example.com", "phone": "+27..." },
  "billing_address": { "line1": "12 Main Rd", "city": "Johannesburg" },
  "shipping_address": { "line1": "12 Main Rd", "city": "Johannesburg" },
  "items": [
    { "external_item_id": "LI-1", "sku": "BULB-9W", "name": "9W LED Bulb", "qty": 10, "unit_price": 120, "line_total": 1200 }
  ],
  "meta": { "payment_method": "card" }
}</pre>
                    </div>
                </div>

                {{-- Curl unsigned (works if tenant has no secret OR you temporarily allow unsigned) --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Sample curl (basic)</div>
                        <div class="text-muted small mb-2">
                            Works when HMAC is disabled (or in internal testing if you allow unsigned).
                        </div>
                        <pre class="mb-0" style="white-space: pre-wrap;">curl -X POST "{{ $endpointUrl }}" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: YOUR_TENANT_KEY" \
  -d '{"external_order_id":"NW-100234","source":"neptuneware_storefront","status":"pending","payment_status":"paid","fulfillment_status":"fulfilled","currency":"ZAR","subtotal":1200,"tax_total":180,"grand_total":1380,"items":[{"external_item_id":"LI-1","sku":"BULB-9W","name":"9W LED Bulb","qty":10,"unit_price":120,"line_total":1200}]}'</pre>
                    </div>
                </div>

                @if ($hasSecret)
                    <div class="card">
                        <div class="card-body">
                            <div class="fw-semibold mb-2">Sample curl (HMAC signed)</div>
                            <div class="text-muted small mb-2">
                                This uses <code>openssl</code> to compute SHA256(body) and base64(HMAC).
                            </div>

                            <pre class="mb-0" style="white-space: pre-wrap;">TENANT_KEY="YOUR_TENANT_KEY"
SECRET="YOUR_HMAC_SECRET"
URL="{{ $endpointUrl }}"
PATH="{{ $endpointPath }}"
METHOD="POST"
TS=$(date +%s)
NONCE=$(uuidgen | tr '[:upper:]' '[:lower:]')

BODY='{"external_order_id":"NW-100234","source":"neptuneware_storefront","status":"pending","payment_status":"paid","fulfillment_status":"fulfilled","currency":"ZAR","subtotal":1200,"tax_total":180,"grand_total":1380,"items":[{"external_item_id":"LI-1","sku":"BULB-9W","name":"9W LED Bulb","qty":10,"unit_price":120,"line_total":1200}]}'

BODY_HASH=$(printf "%s" "$BODY" | openssl dgst -sha256 | awk '{print $2}')

CANONICAL=$(printf "%s\n%s\n%s\n%s\n%s\n%s" "$TENANT_KEY" "$TS" "$NONCE" "$METHOD" "$PATH" "$BODY_HASH")

SIG=$(printf "%s" "$CANONICAL" | openssl dgst -sha256 -hmac "$SECRET" -binary | base64)

curl -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -H "X-Timestamp: $TS" \
  -H "X-Nonce: $NONCE" \
  -H "X-Signature: $SIG" \
  -d "$BODY"</pre>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        (function() {
            const btn = document.getElementById('btnTestEcomApi');
            const out = document.getElementById('ecomApiTestResult');
            if (!btn || !out) return;

            const testUrl = @json(tenant_route('tenant.settings.ecommerce-api.test'));

            function show(html, ok = true) {
                out.classList.remove('d-none', 'alert-light', 'alert-success', 'alert-danger');
                out.classList.add(ok ? 'alert-success' : 'alert-danger');
                out.innerHTML = html;
            }

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                show('Testing connection...', true);

                try {
                    const res = await fetch(testUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '{{ csrf_token() }}',
                        },
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok || !data.ok) {
                        throw new Error(data?.message || 'Ping failed.');
                    }

                    show(
                        `<div class="fw-semibold">Connection OK</div>
                 <div class="small">Tenant: <span class="fw-semibold">${data?.tenant?.subdomain || ''}</span></div>
                 <div class="small">Server time: ${data?.server_time || ''}</div>`,
                        true
                    );
                } catch (e) {
                    show(`<div class="fw-semibold">Connection failed</div><div class="small">${e.message}</div>`,
                        false);
                } finally {
                    btn.disabled = false;
                }
            });
        })();

        (function() {
            const btn = document.getElementById('btnSendSampleOrder');
            const out = document.getElementById('ecomApiSampleResult');
            if (!btn || !out) return;

            const url = @json(tenant_route('tenant.settings.ecommerce-api.sample-order'));

            function show(html, ok = true) {
                out.classList.remove('d-none', 'alert-light', 'alert-success', 'alert-danger');
                out.classList.add(ok ? 'alert-success' : 'alert-danger');
                out.innerHTML = html;
            }

            btn.addEventListener('click', async () => {
                btn.disabled = true;
                show('Sending sample order...', true);

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '{{ csrf_token() }}',
                        },
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok || !data.ok) {
                        throw new Error(data?.message || 'Sample order failed.');
                    }

                    const eo = data.ecommerce_order || {};
                    show(
                        `<div class="fw-semibold">Sample order sent ✅</div>
                 <div class="small">External Order ID: <code>${data.external_order_id || ''}</code></div>
                 <div class="small">CRM Ecommerce Order ID: <code>${eo.id || ''}</code></div>
                 <div class="small">Created: <span class="fw-semibold">${data.created ? 'Yes' : 'No (updated)'}</span></div>`,
                        true
                    );
                } catch (e) {
                    show(`<div class="fw-semibold">Sample order failed</div><div class="small">${e.message}</div>`,
                        false);
                } finally {
                    btn.disabled = false;
                }
            });
        })();
    </script>
@endpush
