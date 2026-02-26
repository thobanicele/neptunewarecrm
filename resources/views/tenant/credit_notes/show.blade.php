@extends('layouts.app')

@section('content')
    @php
        $pill = fn($status) => match (strtolower((string) $status)) {
            'draft' => 'secondary',
            'issued' => 'warning',
            'applied' => 'info',
            'refunded' => 'primary',
            'void' => 'dark',
            default => 'light',
        };

        $statusText = strtoupper((string) ($creditNote->status ?? 'ISSUED'));
        $ribbonText = $statusText;

        // Totals (prefer header fields if you store them)
        $subGross = round((float) ($creditNote->subtotal ?? 0), 2);
        $discount = round((float) ($creditNote->discount_amount ?? 0), 2);
        $vat = round((float) ($creditNote->tax_amount ?? 0), 2);

        // CN total: prefer amount
        $grand = round((float) ($creditNote->amount ?? $subGross - $discount + $vat), 2);

        $currencySymbol = ($creditNote->currency ?? 'ZAR') === 'ZAR' ? 'R' : $creditNote->currency ?? 'R';
        $money = fn($n) => $currencySymbol . ' ' . number_format((float) $n, 2);

        $fmtDate = function ($d) {
            if (!$d) {
                return '—';
            }
            try {
                return $d instanceof \Carbon\CarbonInterface
                    ? $d->format('d/m/Y')
                    : \Carbon\Carbon::parse($d)->format('d/m/Y');
            } catch (\Throwable $e) {
                return (string) $d;
            }
        };

        // Allocation / refund summary
        $allocated = method_exists($creditNote, 'allocatedTotal')
            ? (float) $creditNote->allocatedTotal()
            : (float) ($allocatedTotal ?? 0);
        $refunded = method_exists($creditNote, 'refundedTotal')
            ? (float) $creditNote->refundedTotal()
            : (float) ($refundedTotal ?? 0);
        $available = method_exists($creditNote, 'availableTotal')
            ? (float) $creditNote->availableTotal()
            : max(0, (float) $grand - $allocated - $refunded);
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

        .nw-ribbon.applied span {
            background: #0dcaf0;
        }

        .nw-ribbon.refunded span {
            background: #0d6efd;
        }

        .nw-ribbon.void span {
            background: #212529;
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

        .nw-title .nw-doc-no {
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
    </style>

    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="mb-0">{{ $creditNote->credit_note_number ?? 'CN-' . $creditNote->id }}</h3>
                <div class="text-muted small">
                    Status:
                    <span class="badge rounded-pill text-bg-{{ $pill($creditNote->status) }}">
                        {{ $statusText }}
                    </span>
                    • Total: {{ $money($grand) }}
                    • Available: {{ $money($available) }}
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.credit-notes.index') }}" class="btn btn-light">Back</a>

                {{-- PDF (add routes if you implement controllers) --}}
                @if (\Illuminate\Support\Facades\Route::has('tenant.credit-notes.pdf.stream'))
                    <a href="{{ tenant_route('tenant.credit-notes.pdf.stream', ['credit_note' => $creditNote->id]) }}"
                        target="_blank" class="btn btn-outline-primary">PDF</a>
                @endif
                @if (\Illuminate\Support\Facades\Route::has('tenant.credit-notes.pdf.download'))
                    <a href="{{ tenant_route('tenant.credit-notes.pdf.download', ['credit_note' => $creditNote->id]) }}"
                        class="btn btn-primary">Download</a>
                @endif

                <a class="btn btn-outline-danger"
                    href="{{ tenant_route('tenant.credit_notes.refund.create', $creditNote) }}">
                    Refund
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="nw-paper">

            <div class="nw-ribbon {{ strtolower((string) $creditNote->status) }}">
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
                    <h1>Credit Note</h1>
                    <div class="nw-doc-no"># {{ $creditNote->credit_note_number ?? 'CN-' . $creditNote->id }}</div>

                    <div class="nw-meta">
                        <div>Credit Note Date :</div>
                        <div><strong>{{ $fmtDate($creditNote->issued_at) }}</strong></div>

                        <div>Reference :</div>
                        <div><strong>{{ $creditNote->reference ?? '—' }}</strong></div>

                        <div>Status :</div>
                        <div><strong>{{ $statusText }}</strong></div>

                        <div>Reason :</div>
                        <div><strong>{{ $creditNote->reason ?? '—' }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="nw-parties">
                <div class="nw-box">
                    <h6>Credit To</h6>
                    <div class="fw-bold" style="color:#2563eb;">
                        {{ $creditNote->company?->name ?? '—' }}
                    </div>

                    @if ($creditNote->company?->vat_number)
                        <div class="nw-muted small" style="margin-top:6px;">
                            VAT Number: {{ $creditNote->company->vat_number }}
                        </div>
                    @endif

                    @if ($creditNote->contact)
                        <div class="nw-muted small" style="margin-top:6px;">
                            {{ $creditNote->contact->name }}
                            @if ($creditNote->contact->email)
                                • {{ $creditNote->contact->email }}
                            @endif
                        </div>
                    @endif

                    @if (!empty($billTo))
                        <div class="nw-pre small" style="margin-top:10px;">{{ $billTo }}</div>
                    @else
                        <div class="nw-muted small" style="margin-top:10px;">—</div>
                    @endif
                </div>

                <div class="nw-box">
                    <h6>Ship To</h6>
                    <div class="fw-bold">
                        {{ $creditNote->company?->name ?? '—' }}
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
                    @forelse ($creditNote->items as $idx => $it)
                        @php
                            $line = (float) ($it->line_total ?? 0); // excl VAT (net)
                            $lineVat = (float) ($it->tax_amount ?? 0); // VAT
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

                            <td class="nw-right">{{ number_format((float) $it->qty, 2) }}</td>
                            <td class="nw-right">{{ $money((float) $it->unit_price) }}</td>
                            <td class="nw-right">{{ number_format((float) ($it->discount_pct ?? 0), 2) }}%</td>
                            <td class="nw-right">{{ $money($lineVat) }}</td>
                            <td class="nw-right"><strong>{{ $money($incl) }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="nw-muted">No items on this credit note.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="nw-totals-wrap">
                <table class="nw-totals">
                    <tr>
                        <td class="label nw-right">Sub Total (gross)</td>
                        <td class="nw-right strong">{{ $money($subGross) }}</td>
                    </tr>
                    <tr>
                        <td class="label nw-right">Discount</td>
                        <td class="nw-right strong">- {{ $money($discount) }}</td>
                    </tr>
                    <tr>
                        <td class="label nw-right">
                            {{ $creditNote->items->firstWhere('tax_name')?->tax_name ?? 'VAT' }}
                            @php $rate = $creditNote->items->firstWhere('tax_name')?->tax_rate; @endphp
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
                    <h6>Summary</h6>
                    <div class="d-flex justify-content-between">
                        <div class="nw-muted small">Allocated</div>
                        <div class="fw-semibold">{{ $money($allocated) }}</div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <div class="nw-muted small">Refunded</div>
                        <div class="fw-semibold">{{ $money($refunded) }}</div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <div class="nw-muted small">Available</div>
                        <div class="fw-semibold">{{ $money($available) }}</div>
                    </div>

                    <div class="nw-hr"></div>

                    <div class="nw-muted small">Notes</div>
                    <div class="nw-pre small">{{ !empty($creditNote->notes) ? $creditNote->notes : '—' }}</div>
                </div>

                <div class="nw-box">
                    <h6>Sign-off</h6>

                    <div class="nw-muted small">Prepared by</div>
                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:22px;"></div>

                    <div style="margin-top:10px;" class="nw-muted small">Received / Accepted by (Client)</div>
                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:22px;"></div>

                    <div style="margin-top:10px;" class="nw-muted small">Date</div>
                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:22px;"></div>
                </div>
            </div>

        </div>
    </div>
@endsection
