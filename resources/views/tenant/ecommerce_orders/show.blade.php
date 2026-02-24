@extends('layouts.app')

@section('content')
    <div class="container-fluid py-3">
        @php
            $unmatched = null;
            foreach ($ecommerceOrder->activityLogs ?? collect() as $log) {
                if (($log->action ?? null) === 'ecommerce_order.unmatched_skus') {
                    $unmatched = data_get($log->meta, 'unmatched_skus', null);
                    break;
                }
            }
        @endphp

        @if (!empty($unmatched))
            <div class="alert alert-warning border">
                <div class="fw-semibold">Unmatched SKUs</div>
                <div class="small">These SKUs were not found in Products and were added without product links:</div>
                <div class="small mt-1"><code>{{ implode(', ', (array) $unmatched) }}</code></div>
            </div>
        @endif

        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0">Ecommerce Order</h4>
                    <span class="badge bg-light text-dark border">{{ $ecommerceOrder->external_order_id }}</span>
                </div>
                <div class="text-muted">
                    {{ $ecommerceOrder->source ?? 'storefront' }}
                    • Placed: {{ optional($ecommerceOrder->placed_at)->format('Y-m-d H:i') ?? '—' }}
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                {{-- View Sales Order (if converted) --}}
                @if ($salesOrder)
                    <a class="btn btn-outline-primary"
                        href="{{ tenant_route('tenant.sales-orders.show', ['sales_order' => $salesOrder->id]) }}">
                        View Sales Order
                    </a>
                @endif

                {{-- View Invoice (if invoiced) --}}
                @if ($invoice)
                    <a class="btn btn-outline-success"
                        href="{{ tenant_route('tenant.invoices.show', ['invoice' => $invoice->id]) }}">
                        View Invoice
                    </a>
                @endif

                {{-- Convert --}}
                @if ($canConvert)
                    <form method="POST"
                        action="{{ tenant_route('tenant.ecommerce-orders.convert', ['ecommerceOrder' => $ecommerceOrder->id]) }}">
                        @csrf
                        <button class="btn btn-outline-primary">
                            Convert to Sales Order
                        </button>
                    </form>
                @else
                    @if (!$salesOrder)
                        <button class="btn btn-outline-secondary" disabled>Convert to Sales Order</button>
                        <div class="text-muted small mt-1">
                            @if (in_array($ecommerceOrder->payment_status, ['failed', 'refunded'], true))
                                Conversion disabled (payment {{ $ecommerceOrder->payment_status }}).
                            @else
                                Already converted.
                            @endif
                        </div>
                    @endif
                @endif

                {{-- Create Invoice --}}
                @if ($canCreateInvoice)
                    <form method="POST"
                        action="{{ tenant_route('tenant.ecommerce-orders.create-invoice', ['ecommerceOrder' => $ecommerceOrder->id]) }}">
                        @csrf
                        <button class="btn btn-primary">
                            Create Invoice
                        </button>
                    </form>
                @else
                    <button class="btn btn-secondary" disabled>Create Invoice</button>
                    <div class="text-muted small mt-1">
                        @if ($invoice)
                            Invoice already created.
                        @elseif(!$eligibleForInvoice)
                            Requires <span class="fw-semibold">paid</span> + <span class="fw-semibold">fulfilled</span>.
                        @else
                            Not eligible yet.
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick links banner --}}
        @if ($salesOrder || $invoice)
            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                <div class="small text-muted">
                    @if ($salesOrder)
                        Converted to SO: <span class="fw-semibold">{{ $salesOrder->sales_order_number }}</span>
                    @endif
                    @if ($salesOrder && $invoice)
                        •
                    @endif
                    @if ($invoice)
                        Invoiced: <span class="fw-semibold">{{ $invoice->invoice_number }}</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if ($salesOrder)
                        <a class="btn btn-sm btn-outline-primary"
                            href="{{ tenant_route('tenant.sales-orders.show', ['sales_order' => $salesOrder->id]) }}">
                            Open SO
                        </a>
                    @endif
                    @if ($invoice)
                        <a class="btn btn-sm btn-outline-success"
                            href="{{ tenant_route('tenant.invoices.show', ['invoice' => $invoice->id]) }}">
                            Open Invoice
                        </a>
                    @endif
                </div>
            </div>
        @endif

        {{-- Summary cards --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Payment Status</div>
                        <div class="fs-5 fw-semibold">{{ $ecommerceOrder->payment_status }}</div>
                        <div class="text-muted small">Paid at:
                            {{ optional($ecommerceOrder->paid_at)->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Fulfillment Status</div>
                        <div class="fs-5 fw-semibold">{{ $ecommerceOrder->fulfillment_status }}</div>
                        <div class="text-muted small">Fulfilled at:
                            {{ optional($ecommerceOrder->fulfilled_at)->format('Y-m-d H:i') ?? '—' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total</div>
                        <div class="fs-5 fw-semibold">{{ $ecommerceOrder->currency ?? 'ZAR' }}
                            {{ number_format((float) $ecommerceOrder->grand_total, 2) }}</div>
                        <div class="text-muted small">Tax: {{ number_format((float) $ecommerceOrder->tax_total, 2) }} •
                            Discount: {{ number_format((float) $ecommerceOrder->discount_total, 2) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Converted</div>
                        @if ($ecommerceOrder->converted_sales_order_id)
                            <div class="fs-6 fw-semibold">
                                Yes • SO
                                #{{ $salesOrder?->sales_order_number ?? $ecommerceOrder->converted_sales_order_id }}
                            </div>
                            <div class="text-muted small">At:
                                {{ optional($ecommerceOrder->converted_at)->format('Y-m-d H:i') ?? '—' }}</div>
                        @else
                            <div class="fs-6 fw-semibold">No</div>
                            <div class="text-muted small">—</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        @php $tab = request('tab', 'preview'); @endphp
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link @if ($tab === 'preview') active @endif"
                    href="{{ tenant_route('tenant.ecommerce-orders.show', ['ecommerceOrder' => $ecommerceOrder->id, 'tab' => 'preview']) }}">
                    Preview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if ($tab === 'activity') active @endif"
                    href="{{ tenant_route('tenant.ecommerce-orders.show', ['ecommerceOrder' => $ecommerceOrder->id, 'tab' => 'activity']) }}">
                    Activity Log
                </a>
            </li>
        </ul>

        {{-- Activity Log tab (TABULAR like Quotes) --}}
        @if ($tab === 'activity')
            <div class="card">
                <div class="card-body">
                    @php
                        $logs = ($ecommerceOrder->activityLogs ?? collect())->take(50);

                        $label = function ($action) {
                            return match ($action) {
                                'ecommerce_order.created' => 'Created',
                                'ecommerce_order.updated' => 'Updated',
                                'ecommerce_order.payment_status_changed' => 'Payment status changed',
                                'ecommerce_order.fulfillment_status_changed' => 'Fulfillment status changed',
                                'ecommerce_order.status_changed' => 'Status changed',
                                'ecommerce_order.converted_to_sales_order' => 'Converted to Sales Order',
                                'ecommerce_order.invoiced' => 'Invoiced',
                                'ecommerce_order.unmatched_skus' => 'Unmatched SKUs',
                                'ecommerce_order.customer_linked' => 'Customer linked',
                                default => $action,
                            };
                        };

                        $badge = function ($action) {
                            return match ($action) {
                                'ecommerce_order.created' => 'bg-light text-dark border',
                                'ecommerce_order.updated' => 'bg-info text-dark',
                                'ecommerce_order.payment_status_changed' => 'bg-warning text-dark',
                                'ecommerce_order.fulfillment_status_changed' => 'bg-warning text-dark',
                                'ecommerce_order.status_changed' => 'bg-warning text-dark',
                                'ecommerce_order.converted_to_sales_order' => 'bg-primary',
                                'ecommerce_order.invoiced' => 'bg-success',
                                'ecommerce_order.unmatched_skus' => 'bg-danger',
                                'ecommerce_order.customer_linked' => 'bg-primary',
                                default => 'bg-light text-dark border',
                            };
                        };
                    @endphp

                    @if ($logs->isEmpty())
                        <div class="text-muted small">No activity yet.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 170px;">When</th>
                                        <th>Activity</th>
                                        <th style="width: 180px;">By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($logs as $log)
                                        @php
                                            $meta = $log->meta ?? [];
                                            $from = data_get($meta, 'from');
                                            $to = data_get($meta, 'to');
                                            $unmatchedMeta = data_get($meta, 'unmatched_skus', []);
                                        @endphp
                                        <tr>
                                            <td class="text-muted small">
                                                {{ $fmtDateTime($log->created_at) }}
                                            </td>
                                            <td>
                                                <span class="badge {{ $badge($log->action) }}">
                                                    {{ $label($log->action) }}
                                                </span>

                                                {{-- Status change snippets --}}
                                                @if (in_array(
                                                        $log->action,
                                                        [
                                                            'ecommerce_order.payment_status_changed',
                                                            'ecommerce_order.fulfillment_status_changed',
                                                            'ecommerce_order.status_changed',
                                                        ],
                                                        true))
                                                    <span class="ms-2">
                                                        <span class="badge bg-light text-dark border">
                                                            {{ strtoupper((string) $from) }}
                                                        </span>
                                                        →
                                                        <span class="badge bg-primary">
                                                            {{ strtoupper((string) $to) }}
                                                        </span>
                                                    </span>
                                                @endif

                                                {{-- Converted --}}
                                                @if ($log->action === 'ecommerce_order.converted_to_sales_order')
                                                    <div class="text-muted small mt-1">
                                                        SO:
                                                        {{ data_get($meta, 'sales_order_number', data_get($meta, 'sales_order_id', '—')) }}
                                                    </div>
                                                @endif

                                                {{-- Invoiced --}}
                                                @if ($log->action === 'ecommerce_order.invoiced')
                                                    <div class="text-muted small mt-1">
                                                        Invoice:
                                                        {{ data_get($meta, 'invoice_number', data_get($meta, 'invoice_id', '—')) }}
                                                    </div>
                                                @endif

                                                {{-- Unmatched SKUs --}}
                                                @if ($log->action === 'ecommerce_order.unmatched_skus' && !empty($unmatchedMeta))
                                                    <div class="text-muted small mt-1">
                                                        <code>{{ implode(', ', (array) $unmatchedMeta) }}</code>
                                                    </div>
                                                @endif

                                                {{-- Customer linked --}}
                                                @if ($log->action === 'ecommerce_order.customer_linked')
                                                    <div class="text-muted small mt-1">
                                                        Company ID: {{ data_get($meta, 'company_id', '—') }}
                                                        • Contact ID: {{ data_get($meta, 'contact_id', '—') }}
                                                    </div>
                                                @endif

                                                {{-- Notes --}}
                                                @if (!empty(data_get($meta, 'note')))
                                                    <div class="text-muted small mt-1">
                                                        {{ data_get($meta, 'note') }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-muted small">
                                                {{ $log->user?->name ?? 'System' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- Preview tab --}}
            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="card">
                        <div class="card-header fw-semibold">Items</div>
                        <div class="table-responsive">
                            <table class="table mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th style="width:120px;">SKU</th>
                                        <th style="width:120px;" class="text-end">Qty</th>
                                        <th style="width:140px;" class="text-end">Unit</th>
                                        <th style="width:140px;" class="text-end">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($ecommerceOrder->items as $it)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $it->name }}</div>
                                                <div class="text-muted small">#{{ $it->external_item_id ?? '—' }}</div>
                                            </td>
                                            <td>{{ $it->sku ?? '—' }}</td>
                                            <td class="text-end">{{ number_format((float) $it->qty, 2) }}</td>
                                            <td class="text-end">{{ number_format((float) $it->unit_price, 2) }}</td>
                                            <td class="text-end fw-semibold">
                                                {{ number_format((float) $it->line_total, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No items.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header fw-semibold">Raw Payload (read-only)</div>
                        <div class="card-body">
                            <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($ecommerceOrder->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    {{-- Customer + Linking --}}
                    <div class="card mb-3">
                        <div class="card-header fw-semibold">Customer</div>
                        <div class="card-body">
                            <div class="fw-semibold">{{ $ecommerceOrder->customer_name ?? '—' }}</div>
                            <div class="text-muted">{{ $ecommerceOrder->customer_email ?? '—' }}</div>
                            <div class="text-muted">{{ $ecommerceOrder->customer_phone ?? '—' }}</div>

                            @if (isset($companies) || isset($contacts))
                                <hr>
                                <form method="POST"
                                    action="{{ tenant_route('tenant.ecommerce-orders.link-customer', ['ecommerceOrder' => $ecommerceOrder->id]) }}">
                                    @csrf

                                    <div class="mb-2">
                                        <label class="form-label small">Link Company (optional)</label>
                                        <select class="form-select form-select-sm" name="company_id">
                                            <option value="">— none —</option>
                                            @foreach ($companies ?? collect() as $c)
                                                <option value="{{ $c->id }}" @selected((string) old('company_id', $ecommerceOrder->company_id) === (string) $c->id)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small">Link Contact (optional)</label>
                                        <select class="form-select form-select-sm" name="contact_id">
                                            <option value="">— none —</option>
                                            @foreach ($contacts ?? collect() as $c)
                                                <option value="{{ $c->id }}" @selected((string) old('contact_id', $ecommerceOrder->contact_id) === (string) $c->id)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <button class="btn btn-sm btn-outline-primary">Save Link</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header fw-semibold">Billing Address</div>
                        <div class="card-body">
                            @if ($ecommerceOrder->billing_address)
                                <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($ecommerceOrder->billing_address, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                <div class="text-muted">—</div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header fw-semibold">Shipping Address</div>
                        <div class="card-body">
                            @if ($ecommerceOrder->shipping_address)
                                <pre class="mb-0" style="white-space: pre-wrap;">{{ json_encode($ecommerceOrder->shipping_address, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                <div class="text-muted">—</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
