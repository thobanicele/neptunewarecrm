<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $quote->quote_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 28px;
        }

        .muted {
            color: #6b7280;
        }

        .small {
            font-size: 11px;
        }

        .h1 {
            font-size: 26px;
            margin: 0;
        }

        .h2 {
            font-size: 18px;
            margin: 0;
        }

        .box {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 10px 8px;
            vertical-align: top;
        }

        .meta td {
            padding: 6px 0;
        }

        /* ✅ no borders */
        .meta .label {
            color: #6b7280;
            text-align: right;
            padding-right: 12px;
        }

        .meta .value {
            text-align: right;
            font-weight: 700;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .items thead th {
            background: #111827;
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: .3px;
            text-transform: uppercase;
            padding: 10px 8px;
        }

        .items tbody td {
            border-bottom: 1px solid #e5e7eb;
        }

        .totals {
            width: 42%;
        }

        .totals td {
            border: 0;
            padding: 6px 8px;
        }

        .totals .line {
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .sig-line {
            border-top: 1px solid #111;
            width: 220px;
            margin-top: 40px;
        }

        /* spacing helpers */
        .mt-6 {
            margin-top: 6px;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mt-18 {
            margin-top: 18px;
        }

        .mt-24 {
            margin-top: 24px;
        }

        /* prevent weird wrapping in address blocks */
        .pre {
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    @php
        $currency = 'R';
        $money = fn($n) => $currency . ' ' . number_format((float) $n, 2);

        $logoPath = $tenant->logo_path ? public_path('storage/' . $tenant->logo_path) : null;
        $hasLogo = $logoPath && file_exists($logoPath);

        $billing = trim((string) ($quote->company?->billing_address ?: $quote->company?->address ?: ''));
        $shipping = trim((string) ($quote->company?->shipping_address ?: $quote->company?->address ?: ''));

        $paymentTerms = trim((string) ($quote->company?->payment_terms ?? ''));
        $bankDetails = trim((string) ($tenant->bank_details ?? ''));

        $sub = round($quote->items->sum(fn($i) => (float) $i->line_total), 2);
        $vat = round($quote->items->sum(fn($i) => (float) $i->tax_amount), 2);
        $grand = round($sub + $vat, 2);

        $tenantAddress = trim((string) ($tenant->address ?? ''));
        $tenantVatNo = trim((string) ($tenant->vat_number ?? ''));
    @endphp

    {{-- HEADER (2 columns via table to avoid float weirdness) --}}
    <table>
        <tr>
            <td style="width:60%; padding-top:10px;">
                @if ($hasLogo)
                    <img src="{{ $logoPath }}" alt="Logo" style="height:60px;">
                @else
                    <div class="h2">{{ $tenant->name }}</div>
                @endif

                <div class="mt-6" style="font-weight:700;">{{ $tenant->name }}</div>

                @if ($tenantAddress)
                    <div class="small muted pre">{{ $tenantAddress }}</div>
                @endif

                @if ($tenantVatNo)
                    <div class="small muted">VAT Number: {{ $tenantVatNo }}</div>
                @endif
            </td>

            <td style="width:40%;" class="right">
                <div class="h1">Quote</div>
                <div style="margin-top:4px; font-weight:700;"># {{ $quote->quote_number }}</div>

                {{-- ✅ NO borders/lines + ✅ status removed --}}
                <table class="meta" style="margin-top:18px; width:100%;">
                    <tr>
                        <td class="label small">Estimate Date :</td>
                        <td class="value">{{ $quote->issued_at?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label small">Expiry Date :</td>
                        <td class="value">{{ $quote->valid_until?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label small">Sales person :</td>
                        <td class="right">{{ $quote->salesPerson?->name ?? '—' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- BILL TO / SHIP TO (✅ table layout prevents overlap) --}}
    <table style="margin-top:24px; table-layout:fixed;">
        <tr>
            <td style="width:50%; padding-right:10px;">
                <div class="box">
                    <div class="small muted" style="font-weight:700;">Bill To</div>
                    <div style="margin-top:4px; font-weight:700; color:#2563eb;">
                        {{ $quote->company?->name ?? '—' }}
                    </div>

                    @if ($quote->contact)
                        <div class="small muted mt-6">
                            {{ $quote->contact->name }}{{ $quote->contact->email ? ' • ' . $quote->contact->email : '' }}
                        </div>
                    @endif

                    <div class="mt-10 pre">{{ $billing ?: '—' }}</div>

                    @if ($quote->company?->vat_number)
                        <div class="small muted mt-6">VAT Number: {{ $quote->company->vat_number }}</div>
                    @endif

                    <div class="small muted mt-6"><b>Payment Terms:</b> {{ $paymentTerms ?: '—' }}</div>
                </div>
            </td>

            <td style="width:50%; padding-left:10px;">
                <div class="box">
                    <div class="small muted" style="font-weight:700;">Ship To</div>
                    <div style="margin-top:4px; font-weight:700;">
                        {{ $quote->company?->name ?? '—' }}
                    </div>
                    <div class="mt-10 pre">{{ $shipping ?: '—' }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ITEMS --}}
    <div class="mt-24">
        <table class="items">
            <thead>
                <tr>
                    <th style="width:4%;" class="center">#</th>
                    <th style="width:46%;">Item &amp; Description</th>
                    <th style="width:10%;">Unit</th>
                    <th style="width:8%;" class="right">Qty</th>
                    <th style="width:11%;" class="right">Rate</th>
                    <th style="width:11%;" class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($quote->items as $idx => $it)
                    @php $line = (float)$it->line_total; @endphp
                    <tr>
                        <td class="center">{{ $idx + 1 }}</td>
                        <td>
                            <div style="font-weight:400;">{{ $it->name }}</div>
                            <div class="small muted mt-6">{{ $it->sku ?: '—' }}</div>
                            @if ($it->description)
                                <div class="small muted mt-6">{{ $it->description }}</div>
                            @endif
                        </td>
                        <td>{{ $it->unit ?: '—' }}</td>
                        <td class="right">{{ number_format((float) $it->qty, 2) }}</td>

                        <td class="right">{{ $money((float) $it->unit_price) }}</td>
                        <td class="right">{{ $money($line) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- TOTALS (styled like your screenshot) --}}
    <table style="margin-top:18px; width:100%;">
        <tr>
            <td style="width:60%;"></td>

            <td style="width:40%;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 12px; text-align:left; color:#6b7280;">
                            Sub Total
                        </td>
                        <td style="padding:10px 12px; text-align:right; font-weight:700;">
                            {{ $money($sub) }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:10px 12px; text-align:left; color:#6b7280;">
                            Standard VAT ({{ number_format($quote->tax_rate ?? 0, 2) }}%)
                        </td>
                        <td style="padding:10px 12px; text-align:right; font-weight:700;">
                            {{ $money($vat) }}
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:12px; text-align:left; font-weight:800; background:#f3f4f6;">
                            Total
                        </td>
                        <td style="padding:12px; text-align:right; font-weight:900; background:#f3f4f6;">
                            {{ $money($grand) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>


    {{-- TERMS / BANKING --}}
    <div class="mt-24">
        <div style="font-weight:800;">Terms &amp; Conditions</div>

        @if ($bankDetails)
            <div class="mt-10">
                <div class="small muted" style="font-weight:700;">Banking details:</div>
                <div class="small pre">{{ $bankDetails }}</div>
            </div>
        @endif

        @if ($quote->terms)
            <div class="mt-10">
                <div class="small muted" style="font-weight:700;">Standard Terms and Conditions:</div>
                <div class="small pre">{{ $quote->terms }}</div>
            </div>
        @endif
    </div>

    {{-- SIGNATURES --}}
    <div class="mt-24">
        <table style="width:100%;">
            <tr>
                <td style="width:50%;">
                    <div class="sig-line"></div>
                    <div class="small muted mt-6">Prepared by</div>
                </td>
                <td style="width:50%;" class="right">
                    <div style="display:inline-block; text-align:left;">
                        <div class="sig-line"></div>
                        <div class="small muted mt-6">Accepted by (Client)</div>
                    </div>
                </td>
            </tr>
        </table>
        <div class="small muted mt-10">Date: ______________________</div>
    </div>

</body>

</html>
