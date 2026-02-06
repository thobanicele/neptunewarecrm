@extends('layouts.app')

@section('content')
    @php
        $pill = fn($status) => match (strtolower((string) $status)) {
            'draft' => 'secondary',
            'issued' => 'warning',
            'paid' => 'success',
            'void' => 'dark',
            default => 'light',
        };

        $ribbonText = strtoupper((string) ($invoice->status ?? 'DRAFT'));

        // Use invoice header totals (these already include discount logic from your backend)
        $subGross = round((float) ($invoice->subtotal ?? 0), 2); // "gross subtotal"
        $discount = round((float) ($invoice->discount_amount ?? 0), 2);
        $vat = round((float) ($invoice->tax_amount ?? 0), 2);
        $grand = round((float) ($invoice->total ?? (($subGross - $discount) + $vat)), 2);

        // For table rows we compute incl = line_total + tax_amount (line_total is NET excl vat)
        $currencySymbol = $invoice->currency === 'ZAR' || empty($invoice->currency) ? 'R' : $invoice->currency;
        $money = fn($n) => $currencySymbol . ' ' . number_format((float) $n, 2);

        $fmtDate = function ($d) {
            if (!$d) return '—';
            try {
                return $d instanceof \Carbon\CarbonInterface ? $d->format('d/m/Y') : \Carbon\Carbon::parse($d)->format('d/m/Y');
            } catch (\Throwable $e) {
                return (string) $d;
            }
        };
    @endphp

    <style>
        /* --- Paper layout --- */
        .nw-paper {
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .08);
            border-radius: 12px;
            padding: 28px 22px 22px 70px; /* space for ribbon */
            position: relative;
        }
        .nw-muted { color: #6c757d; }
        .nw-hr { border-top: 1px solid rgba(0, 0, 0, .08); margin: 16px 0; }

        /* --- Status ribbon --- */
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
        .nw-ribbon.draft span { background: #6c757d; }
        .nw-ribbon.issued span { background: #f0ad4e; }
        .nw-ribbon.paid span { background: #198754; }
        .nw-ribbon.void span { background: #212529; }

        /* --- Header --- */
        .nw-header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
        }
        .nw-brand { display: flex; gap: 14px; align-items: flex-start; }
        .nw-logo { height: 64px; width: auto; object-fit: contain; }
        .nw-title { text-align: right; }
        .nw-title h1 { margin: 0; font-size: 32px; letter-spacing: .2px; }
        .nw-title .nw-quote-no { margin-top: 6px; font-weight: 700; }

        .nw-meta {
            display: grid;
            grid-template-columns: auto auto;
            gap: 6px 18px;
            margin-top: 14px;
            font-size: 13px;
            justify-content: end;
        }
        .nw-meta div:nth-child(odd) { color: #6c757d; }

        /* --- Parties --- */
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

        /* --- Items table --- */
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
        .nw-right { text-align: right; }
        .nw-item-sku { display: block; color: #6c757d; font-size: 12px; margin-top: 4px; }

        /* --- Totals --- */
        .nw-totals-wrap { display: flex; justify-content: flex-end; margin-top: 14px; }
        .nw-totals { width: 360px; border-collapse: collapse; }
        .nw-totals td { padding: 10px 8px; font-size: 13px; }
        .nw-totals .label { color: #6c757d; }
        .nw-totals .strong { font-weight: 800; }
        .nw-totals .total-row td { background: #f3f4f6; font-weight: 900; }

        /* --- Footer blocks --- */
        .nw-footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-top: 22px;
        }
        .nw-pre { white-space: pre-wrap; }
    </style>

    <div class="container-fluid py-4">
        {{-- Top bar (same as quote) --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="mb-0">{{ $invoice->invoice_number }}</h3>
                <div class="text-muted small">
                    Status:
                    <span class="badge rounded-pill text-bg-{{ $pill($invoice->status) }}">
                        {{ strtoupper((string) $invoice->status) }}
                    </span>
                    • Total: {{ $money($grand) }}
                    • Sales Person: {{ optional($invoice->salesPerson ?? null)->name ?? '—' }}
                    • Owner: {{ optional($invoice->owner ?? null)->name ?? '—' }}
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.invoices.index') }}" class="btn btn-light">Back</a>

                @if ($invoice->status === 'draft')
                    <a href="{{ tenant_route('tenant.invoices.edit', ['invoice' => $invoice->id]) }}"
                        class="btn btn-outline-secondary">Edit</a>
                @endif

                <a href="{{ tenant_route('tenant.invoices.pdf.stream', ['invoice' => $invoice->id]) }}" target="_blank"
                    class="btn btn-outline-primary">PDF</a>

                <a href="{{ tenant_route('tenant.invoices.pdf.download', ['invoice' => $invoice->id]) }}"
                    class="btn btn-primary">Download</a>

                @if ($invoice->status === 'draft')
                    <form method="POST" action="{{ tenant_route('tenant.invoices.issue', $invoice) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-success">Issue</button>
                    </form>
                @endif

                @if ($invoice->status === 'issued' && tenant_feature(app('tenant'), 'invoice_email_send'))
                    <form method="POST" action="{{ tenant_route('tenant.invoices.markPaid', $invoice) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-outline-success">Mark Paid</button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="nw-paper">

            {{-- Ribbon --}}
            <div class="nw-ribbon {{ strtolower((string) $invoice->status) }}">
                <span>{{ $ribbonText }}</span>
            </div>

            {{-- Header --}}
            <div class="nw-header">
                <div class="nw-brand">
                    @if (!empty($tenant->logo_path))
                        <img class="nw-logo" src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Logo">
                    @else
                        <div class="rounded bg-light border d-flex align-items-center justify-content-center"
                            style="height:64px; width:64px;">
                            <span class="text-muted fw-semibold">{{ strtoupper(substr($tenant->name, 0, 1)) }}</span>
                        </div>
                    @endif

                    <div>
                        <div class="fw-bold" style="font-size:18px;">{{ $tenant->name }}</div>
                        <div class="nw-muted small">Workspace: {{ $tenant->subdomain }}</div>

                        @if (!empty($tenant->address))
                            <div class="nw-muted small nw-pre" style="margin-top:8px;">{{ $tenant->address }}</div>
                        @endif

                        @if (!empty($tenant->vat_number))
                            <div class="nw-muted small" style="margin-top:6px;">VAT Number: {{ $tenant->vat_number }}</div>
                        @endif
                    </div>
                </div>

                <div class="nw-title">
                    <h1>Invoice</h1>
                    <div class="nw-quote-no"># {{ $invoice->invoice_number }}</div>

                    <div class="nw-meta">
                        <div>Invoice Date :</div>
                        <div><strong>{{ $fmtDate($invoice->issued_at) }}</strong></div>

                        <div>Due Date :</div>
                        <div><strong>{{ $fmtDate($invoice->due_at) }}</strong></div>

                        <div>Reference :</div>
                        <div><strong>{{ $invoice->reference ?? '—' }}</strong></div>

                        <div>Status :</div>
                        <div><strong>{{ strtoupper((string) $invoice->status) }}</strong></div>

                        <div>Quote # :</div>
                        <div><strong>{{ $invoice->quote_number ?? '—' }}</strong></div>
                    </div>
                </div>
            </div>

            {{-- Parties --}}
            <div class="nw-parties">
                <div class="nw-box">
                    <h6>Bill To</h6>
                    <div class="fw-bold" style="color:#2563eb;">
                        {{ $invoice->company?->name ?? '—' }}
                    </div>

                    @if ($invoice->company?->vat_number)
                        <div class="nw-muted small" style="margin-top:6px;">
                            VAT Number: {{ $invoice->company->vat_number }}
                        </div>
                    @endif

                    @if ($invoice->contact)
                        <div class="nw-muted small" style="margin-top:6px;">
                            {{ $invoice->contact->name }}
                            @if ($invoice->contact->email)
                                • {{ $invoice->contact->email }}
                            @endif
                        </div>
                    @endif

                    @if (!empty($billTo))
                        <div class="nw-pre small" style="margin-top:10px;">{{ $billTo }}</div>
                    @else
                        <div class="nw-muted small" style="margin-top:10px;">—</div>
                    @endif

                    @if (!empty($invoice->company?->payment_terms))
                        <div class="nw-muted small" style="margin-top:10px;">
                            Payment Terms: <strong>{{ $invoice->company->payment_terms }}</strong>
                        </div>
                    @endif
                </div>

                <div class="nw-box">
                    <h6>Ship To</h6>
                    <div class="fw-bold">
                        {{ $invoice->company?->name ?? '—' }}
                    </div>

                    @if (!empty($shipTo))
                        <div class="nw-pre small" style="margin-top:10px;">{{ $shipTo }}</div>
                    @else
                        <div class="nw-muted small" style="margin-top:10px;">—</div>
                    @endif
                </div>
            </div>

            {{-- Items --}}
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
                    @foreach ($invoice->items as $idx => $it)
                        @php
                            $line = (float) ($it->line_total ?? 0);     // excl VAT (net)
                            $lineVat = (float) ($it->tax_amount ?? 0);  // VAT
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
                                {{ number_format((float) $it->qty, 2) }}
                                @if (!empty($it->unit))
                                    <div class="nw-muted small">{{ $it->unit }}</div>
                                @endif
                            </td>

                            <td class="nw-right">{{ $money((float) $it->unit_price) }}</td>
                            <td class="nw-right">{{ number_format((float) ($it->discount_pct ?? 0), 2) }}%</td>
                            <td class="nw-right">{{ $money($lineVat) }}</td>
                            <td class="nw-right"><strong>{{ $money($incl) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Totals --}}
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
                            {{ $invoice->items->firstWhere('tax_name')?->tax_name ?? 'VAT' }}
                            @php $rate = $invoice->items->firstWhere('tax_name')?->tax_rate; @endphp
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

            {{-- Notes / Terms + Banking --}}
            <div class="nw-footer-grid">
                <div class="nw-box">
                    <h6>Terms &amp; Conditions</h6>

                    @if (!empty($tenant->bank_details))
                        <div class="fw-semibold">Banking details:</div>
                        <div class="nw-muted small nw-pre" style="margin-top:6px;">{{ $tenant->bank_details }}</div>
                        <div class="nw-hr"></div>
                    @endif

                    @if (!empty($invoice->terms))
                        <div class="nw-pre small">{{ $invoice->terms }}</div>
                    @else
                        <div class="nw-muted small">—</div>
                    @endif
                </div>

                <div class="nw-box">
                    <h6>Notes</h6>

                    @if (!empty($invoice->notes))
                        <div class="nw-pre small">{{ $invoice->notes }}</div>
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
                            <div class="nw-muted small">Received / Accepted by (Client)</div>
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
@endsection

