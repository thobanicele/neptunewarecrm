{{-- resources/views/tenant/sales_orders/show.blade.php --}}
@extends('layouts.app')

@section('content')
    @php
        $pill = fn($status) => match (strtolower((string) $status)) {
            'draft' => 'secondary',
            'issued' => 'warning',
            'cancelled' => 'dark',
            'converted' => 'success',
            default => 'light',
        };

        $ribbonText = strtoupper((string) ($salesOrder->status ?? 'DRAFT'));

        $sub = round((float) ($salesOrder->subtotal ?? 0), 2);
        $discount = round((float) ($salesOrder->discount_amount ?? 0), 2);
        $vat = round((float) ($salesOrder->tax_amount ?? 0), 2);
        $grand = round((float) ($salesOrder->total ?? max(0, $sub - $discount + $vat)), 2);

        $currencySymbol = $salesOrder->currency === 'ZAR' || empty($salesOrder->currency) ? 'R' : $salesOrder->currency;
        $money = fn($n) => $currencySymbol . ' ' . number_format((float) $n, 2, '.', ' ');

        $st = strtolower((string) ($salesOrder->status ?? 'draft'));

        // Prefer snapshots (like invoices). If empty, show dash.
        $billTo = trim((string) ($salesOrder->billing_address_snapshot ?? ''));
        $shipTo = trim((string) ($salesOrder->shipping_address_snapshot ?? ''));
    @endphp

    <style>
        .nw-paper {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 12px;
            padding: 28px 22px 22px 70px;
            position: relative;
        }

        .nw-muted {
            color: #6c757d;
        }

        .nw-hr {
            border-top: 1px solid rgba(0, 0, 0, .08);
            margin: 16px 0;
        }

        .nw-ribbon {
            position: absolute;
            top: -6px;
            left: -6px;
            width: 120px;
            height: 120px;
            overflow: hidden;
            pointer-events: none;
            z-index: 2;
        }

        .nw-ribbon span {
            position: absolute;
            display: block;
            width: 180px;
            padding: 8px 0;
            background: #495057;
            color: #fff;
            text-align: center;
            font-weight: 700;
            font-size: 12px;
            transform: rotate(-45deg);
            top: 28px;
            left: -52px;
            letter-spacing: .5px;
        }

        .nw-ribbon.draft span {
            background: #6c757d;
        }

        .nw-ribbon.issued span {
            background: #f0ad4e;
        }

        .nw-ribbon.cancelled span {
            background: #212529;
        }

        .nw-ribbon.converted span {
            background: #198754;
        }

        .nw-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
        }

        .nw-brand {
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }

        .nw-logo {
            height: 64px;
            width: auto;
            object-fit: contain;
        }

        .nw-title {
            text-align: right;
        }

        .nw-title h1 {
            margin: 0;
            font-size: 32px;
            letter-spacing: .2px;
        }

        .nw-title .nw-quote-no {
            margin-top: 6px;
            font-weight: 700;
        }

        .nw-meta {
            display: grid;
            grid-template-columns: auto auto;
            gap: 6px 18px;
            margin-top: 14px;
            font-size: 13px;
            justify-content: end;
        }

        .nw-meta div:nth-child(odd) {
            color: #6c757d;
        }

        .nw-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 18px;
        }

        .nw-box {
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 10px;
            padding: 14px;
        }

        .nw-box h6 {
            margin: 0 0 10px 0;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6c757d;
        }

        .nw-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .nw-table th {
            background: #1f2937;
            color: #fff;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 10px;
        }

        .nw-table td {
            border-bottom: 1px solid rgba(0, 0, 0, .08);
            padding: 12px 10px;
            vertical-align: top;
            font-size: 13px;
        }

        .nw-right {
            text-align: right;
        }

        .nw-item-sku {
            display: block;
            color: #6c757d;
            font-size: 12px;
            margin-top: 4px;
        }

        .nw-totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-top: 14px;
        }

        .nw-totals {
            width: 360px;
            border-collapse: collapse;
        }

        .nw-totals td {
            padding: 10px 8px;
            font-size: 13px;
        }

        .nw-totals .label {
            color: #6c757d;
        }

        .nw-totals .strong {
            font-weight: 800;
        }

        .nw-totals .total-row td {
            background: #f3f4f6;
            font-weight: 900;
        }

        .nw-footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 22px;
        }

        .nw-pre {
            white-space: pre-wrap;
        }

        .nw-tabs .nav-link {
            font-weight: 600;
        }
    </style>

    <div class="container-fluid py-4">

        {{-- Top header + actions --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="mb-0">{{ $salesOrder->sales_order_number }}</h3>
                <div class="text-muted small">
                    Status:
                    <span class="badge rounded-pill text-bg-{{ $pill($salesOrder->status) }}">
                        {{ strtoupper((string) $salesOrder->status) }}
                    </span>
                    • Total: {{ $money($grand) }}
                    • Quote: {{ $salesOrder->quote_number ?? '—' }}
                    @if (!empty($salesOrder->reference))
                        • Ref: {{ $salesOrder->reference }}
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center flex-wrap justify-content-end">
                <a href="{{ tenant_route('tenant.sales-orders.index') }}" class="btn btn-light">Back</a>

                {{-- ✅ PDF + Download --}}
                @can('pdf', $salesOrder)
                    <a href="{{ tenant_route('tenant.sales-orders.pdf.stream', ['salesOrder' => $salesOrder->id]) }}"
                        target="_blank" class="btn btn-outline-primary">
                        PDF
                    </a>

                    <a href="{{ tenant_route('tenant.sales-orders.pdf.download', ['salesOrder' => $salesOrder->id]) }}"
                        class="btn btn-primary">
                        Download
                    </a>
                @endcan

                {{-- Actions dropdown --}}
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Actions
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        @can('update', $salesOrder)
                            @if ($st === 'draft')
                                <li>
                                    <form method="POST"
                                        action="{{ tenant_route('tenant.sales-orders.issue', ['salesOrder' => $salesOrder->id]) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Issue</button>
                                    </form>
                                </li>
                            @endif

                            @if (!in_array($st, ['cancelled', 'converted'], true))
                                <li>
                                    <form method="POST"
                                        action="{{ tenant_route('tenant.sales-orders.cancel', ['salesOrder' => $salesOrder->id]) }}"
                                        onsubmit="return confirm('Cancel this sales order?');">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">Cancel</button>
                                    </form>
                                </li>
                            @endif

                            @if ($st === 'cancelled')
                                <li>
                                    <form method="POST"
                                        action="{{ tenant_route('tenant.sales-orders.reopen', ['salesOrder' => $salesOrder->id]) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Reopen (back to Issued)</button>
                                    </form>
                                </li>
                            @endif
                        @endcan

                        {{-- Convert (blocked if cancelled/converted) --}}
                        @if (!in_array($st, ['cancelled', 'converted'], true))
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <form method="POST"
                                    action="{{ tenant_route('tenant.sales-orders.convertToInvoice', ['salesOrder' => $salesOrder->id]) }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item fw-semibold">Convert to Invoice</button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </div>

                {{-- Optional visible CTA --}}
                @if (!in_array($st, ['cancelled', 'converted'], true))
                    <form method="POST"
                        action="{{ tenant_route('tenant.sales-orders.convertToInvoice', ['salesOrder' => $salesOrder->id]) }}">
                        @csrf
                        <button class="btn btn-success">Convert to Invoice</button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Tabs --}}
        <ul class="nav nav-tabs nw-tabs mb-3" id="soTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview"
                    type="button" role="tab" aria-controls="preview" aria-selected="true">
                    Preview
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button"
                    role="tab" aria-controls="activity" aria-selected="false">
                    Activity Log
                    @if (($salesOrder->activityLogs?->count() ?? 0) > 0)
                        <span class="badge bg-light text-dark border ms-1">{{ $salesOrder->activityLogs->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="tab-content" id="soTabsContent">

            {{-- Preview --}}
            <div class="tab-pane fade show active" id="preview" role="tabpanel" aria-labelledby="preview-tab">
                <div class="nw-paper">

                    <div class="nw-ribbon {{ strtolower((string) $salesOrder->status) }}">
                        <span>{{ $ribbonText }}</span>
                    </div>

                    <div class="nw-header">
                        <div class="nw-brand">
                            @include('tenant.partials.transaction-header-brand', [
                                'tenant' => $tenant,
                                'logoHeight' => 72,
                                'logoMaxWidth' => 260,
                                'showAddress' => true,
                                'showMeta' => true,
                            ])
                        </div>

                        <div class="nw-title">
                            <h1>Sales Order</h1>
                            <div class="nw-quote-no"># {{ $salesOrder->sales_order_number }}</div>

                            <div class="nw-meta">
                                <div>Issued Date :</div>
                                <div><strong>{{ $salesOrder->issued_at?->format('d/m/Y') ?? '—' }}</strong></div>

                                <div>Reference :</div>
                                <div><strong>{{ $salesOrder->reference ?? '—' }}</strong></div>

                                <div>Quote # :</div>
                                <div><strong>{{ $salesOrder->quote_number ?? '—' }}</strong></div>

                                <div>Status :</div>
                                <div><strong>{{ strtoupper((string) $salesOrder->status) }}</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="nw-parties">
                        <div class="nw-box">
                            <h6>Bill To</h6>
                            <div class="fw-bold" style="color:#2563eb;">
                                {{ $salesOrder->company?->name ?? '—' }}
                            </div>

                            @if ($salesOrder->company?->vat_number)
                                <div class="nw-muted small" style="margin-top:6px;">
                                    VAT Number: {{ $salesOrder->company->vat_number }}
                                </div>
                            @endif

                            @if ($salesOrder->contact)
                                <div class="nw-muted small" style="margin-top:6px;">
                                    {{ $salesOrder->contact->name }}
                                    @if ($salesOrder->contact->email)
                                        • {{ $salesOrder->contact->email }}
                                    @endif
                                </div>
                            @endif

                            @if (!empty($billTo))
                                <div class="nw-pre small" style="margin-top:10px;">{{ $billTo }}</div>
                            @else
                                <div class="nw-muted small" style="margin-top:10px;">—</div>
                            @endif

                            @if (!empty($salesOrder->company?->payment_terms))
                                <div class="nw-muted small" style="margin-top:10px;">
                                    Payment Terms: <strong>{{ $salesOrder->company->payment_terms }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="nw-box">
                            <h6>Ship To</h6>
                            <div class="fw-bold">
                                {{ $salesOrder->company?->name ?? '—' }}
                            </div>

                            @if (!empty($shipTo))
                                <div class="nw-pre small" style="margin-top:10px;">{{ $shipTo }}</div>
                            @else
                                <div class="nw-muted small" style="margin-top:10px;">—</div>
                            @endif
                        </div>
                    </div>

                    <table class="nw-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Item &amp; Description</th>
                                <th class="nw-right" style="width:90px;">Qty</th>
                                <th class="nw-right" style="width:120px;">Rate</th>
                                <th class="nw-right" style="width:120px;">Disc %</th>
                                <th class="nw-right" style="width:140px;">VAT Amt</th>
                                <th class="nw-right" style="width:140px;">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($salesOrder->items as $idx => $it)
                                @php
                                    $line = (float) ($it->line_total ?? 0);
                                    $lineVat = (float) ($it->tax_amount ?? 0);
                                    $incl = $line + $lineVat;
                                @endphp
                                <tr>
                                    <td class="nw-right">{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $it->name }}</div>
                                        @if (!empty($it->sku))
                                            <span class="nw-item-sku">SKU : {{ $it->sku }}</span>
                                        @endif
                                        @if (!empty($it->description))
                                            <div class="nw-muted" style="margin-top:6px;">{{ $it->description }}</div>
                                        @endif
                                    </td>
                                    <td class="nw-right">
                                        {{ number_format((float) $it->qty, 2, '.', ' ') }}
                                        @if (!empty($it->unit))
                                            <div class="nw-muted small">{{ $it->unit }}</div>
                                        @endif
                                    </td>
                                    <td class="nw-right">{{ $money((float) $it->unit_price) }}</td>
                                    <td class="nw-right">
                                        {{ number_format((float) ($it->discount_pct ?? 0), 2, '.', ' ') }}%</td>
                                    <td class="nw-right">{{ $money($lineVat) }}</td>
                                    <td class="nw-right"><strong>{{ $money($incl) }}</strong></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="nw-totals-wrap">
                        <table class="nw-totals">
                            <tr>
                                <td class="label nw-right">Sub Total</td>
                                <td class="nw-right strong">{{ $money($sub) }}</td>
                            </tr>

                            @if ($discount > 0)
                                <tr>
                                    <td class="label nw-right">Discount</td>
                                    <td class="nw-right strong text-danger">- {{ $money($discount) }}</td>
                                </tr>
                            @endif

                            <tr>
                                <td class="label nw-right">
                                    {{ $salesOrder->items->firstWhere('tax_name')?->tax_name ?? 'VAT' }}
                                    @php $rate = $salesOrder->items->firstWhere('tax_name')?->tax_rate; @endphp
                                    @if ($rate !== null)
                                        ({{ number_format((float) $rate, 2) }}%)
                                    @endif
                                </td>
                                <td class="nw-right strong">{{ $money($vat) }}</td>
                            </tr>

                            <tr class="total-row">
                                <td class="nw-right">Total</td>
                                <td class="nw-right">{{ $money($grand) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="nw-footer-grid">
                        <div class="nw-box">
                            <h6>Terms &amp; Conditions</h6>

                            @if (!empty($tenant->bank_details))
                                <div class="fw-semibold">Banking details:</div>
                                <div class="nw-muted small nw-pre" style="margin-top:6px;">{{ $tenant->bank_details }}
                                </div>
                                <div class="nw-hr"></div>
                            @endif

                            @if (!empty($salesOrder->terms))
                                <div class="nw-pre small">{{ $salesOrder->terms }}</div>
                            @else
                                <div class="nw-muted small">—</div>
                            @endif
                        </div>

                        <div class="nw-box">
                            <h6>Notes</h6>
                            @if (!empty($salesOrder->notes))
                                <div class="nw-pre small">{{ $salesOrder->notes }}</div>
                            @else
                                <div class="nw-muted small">—</div>
                            @endif

                            <div class="nw-hr"></div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="nw-muted small">Prepared by</div>
                                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:22px;"></div>
                                </div>
                                <div class="col-12">
                                    <div class="nw-muted small">Accepted by (Client)</div>
                                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:22px;"></div>
                                </div>
                                <div class="col-12">
                                    <div class="nw-muted small">Date</div>
                                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:22px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Activity tab (tabular like Quotes) --}}
            <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                <div class="card">
                    <div class="card-body">
                        @php
                            $logs = ($salesOrder->activityLogs ?? collect())->take(50);

                            $label = fn($action) => match ($action) {
                                'sales_order.created' => 'Created',
                                'sales_order.updated' => 'Updated',
                                'sales_order.status_changed' => 'Status changed',
                                'sales_order.created_from_quote' => 'Created from Quote',
                                'sales_order.converted_to_invoice' => 'Converted to Invoice',
                                'sales_order.pdf_viewed' => 'PDF viewed',
                                'sales_order.pdf_downloaded' => 'PDF downloaded',
                                default => $action,
                            };

                            $badge = fn($action) => match ($action) {
                                'sales_order.created' => 'bg-light text-dark border',
                                'sales_order.updated' => 'bg-info text-dark',
                                'sales_order.status_changed' => 'bg-warning text-dark',
                                'sales_order.created_from_quote' => 'bg-success',
                                'sales_order.converted_to_invoice' => 'bg-success',
                                'sales_order.pdf_viewed' => 'bg-secondary',
                                'sales_order.pdf_downloaded' => 'bg-secondary',
                                default => 'bg-light text-dark border',
                            };
                        @endphp

                        @if ($logs->isEmpty())
                            <div class="text-muted small">No activity yet.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width:170px;">When</th>
                                            <th>Activity</th>
                                            <th style="width:180px;">By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($logs as $log)
                                            @php
                                                $meta = $log->meta ?? [];
                                                $from = data_get($meta, 'from');
                                                $to = data_get($meta, 'to');
                                                $oldTotal = data_get($meta, 'old_total');
                                                $newTotal = data_get($meta, 'new_total');
                                            @endphp

                                            <tr>
                                                <td class="text-muted small">{{ $log->created_at?->format('d/m/Y H:i') }}
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge {{ $badge($log->action) }}">{{ $label($log->action) }}</span>

                                                    @if ($log->action === 'sales_order.status_changed')
                                                        <span class="ms-2">
                                                            <span
                                                                class="badge bg-light text-dark border">{{ strtoupper((string) $from) }}</span>
                                                            →
                                                            <span
                                                                class="badge bg-primary">{{ strtoupper((string) $to) }}</span>
                                                        </span>
                                                    @endif

                                                    @if (
                                                        $log->action === 'sales_order.updated' &&
                                                            $oldTotal !== null &&
                                                            $newTotal !== null &&
                                                            (float) $oldTotal !== (float) $newTotal)
                                                        <div class="text-muted small mt-1">
                                                            Updated total {{ $money((float) $oldTotal) }} →
                                                            {{ $money((float) $newTotal) }}
                                                        </div>
                                                    @endif

                                                    @if ($log->action === 'sales_order.created_from_quote')
                                                        <div class="text-muted small mt-1">Quote:
                                                            {{ data_get($meta, 'quote_number', '—') }}</div>
                                                    @endif

                                                    @if ($log->action === 'sales_order.converted_to_invoice')
                                                        <div class="text-muted small mt-1">Invoice:
                                                            {{ data_get($meta, 'invoice_number', '—') }}</div>
                                                    @endif

                                                    @if (!empty(data_get($meta, 'note')))
                                                        <div class="text-muted small mt-1">{{ data_get($meta, 'note') }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="text-muted small">{{ $log->user?->name ?? 'System' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
