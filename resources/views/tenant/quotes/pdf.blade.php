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

        .sig-line {
            border-top: 1px solid #111;
            width: 220px;
            margin-top: 40px;
        }

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

        .pre {
            white-space: pre-wrap;
        }

        .label-title {
            font-weight: 800;
            font-size: 11px;
            letter-spacing: .2px;
            text-transform: uppercase;
            color: #6b7280;
        }
    </style>
</head>

<body>
    @php
        $currency = 'R';
        $money = fn($n) => $currency . ' ' . number_format((float) $n, 2);

        $logoPath = $tenant->logo_path ? public_path('storage/' . $tenant->logo_path) : null;
        $hasLogo = $logoPath && file_exists($logoPath);

        // --- Tenant (issuer) info
        $tenantAddress = trim((string) ($tenant->address ?? ''));
        $tenantVatNo = trim((string) ($tenant->vat_number ?? ''));
        $bankDetails = trim((string) ($tenant->bank_details ?? ''));

        // --- Company + addresses (from company_addresses table)
        $company = $quote->company;

        // Ensure we have a Collection (works even if relationship not eager loaded)
        $addrCol = $company?->addresses ?? collect();

        $billingAddrModel = $addrCol
            ->where('type', 'billing')
            ->sortByDesc('is_default_billing')
            ->sortByDesc('id')
            ->first();

        $shippingAddrModel = $addrCol
            ->where('type', 'shipping')
            ->sortByDesc('is_default_shipping')
            ->sortByDesc('id')
            ->first();

        // Build display strings using your formatter
        $billingFromTable = trim((string) ($billingAddrModel?->toSnapshotString() ?? ''));
        $shippingFromTable = trim((string) ($shippingAddrModel?->toSnapshotString() ?? ''));

        // Fallbacks (legacy company fields if address table is empty)
        $billingFallback = trim((string) ($company?->billing_address ?: $company?->address ?: ''));
        $shippingFallback = trim((string) ($company?->shipping_address ?: $company?->address ?: ''));

        $billing = $billingFromTable ?: $billingFallback;
        $shipping = $shippingFromTable ?: $shippingFallback;

        $paymentTerms = trim((string) ($company?->payment_terms ?? ''));

        // Totals
        $sub = round($quote->items->sum(fn($i) => (float) $i->line_total), 2);
        $vat = round($quote->items->sum(fn($i) => (float) $i->tax_amount), 2);
        $grand = round($sub + $vat, 2);

        // Optional: show VAT label from first item if you store it there
        $taxName = $quote->items->firstWhere('tax_name')?->tax_name ?? 'VAT';
        $taxRate = $quote->items->firstWhere('tax_rate')?->tax_rate;
    @endphp

    {{-- HEADER --}}
    <table>
        <tr>
            <td style="width:60%; padding-top:10px;">
                @include('tenant.partials.pdf-transaction-header-brand', [
                    'tenant' => $tenant,
                    'logoHeight' => 70,
                    'pdfLogoPath' => $pdfLogoPath ?? null,
                ])
            </td>

            <td style="width:40%;" class="right">
                <div class="h1">Quote</div>
                <div style="margin-top:4px; font-weight:700;"># {{ $quote->quote_number }}</div>

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

    {{-- BILL TO / SHIP TO --}}
    <table style="margin-top:24px; table-layout:fixed;">
        <tr>
            <td style="width:50%; padding-right:10px;">
                <div class="box">
                    <div class="label-title">Bill To</div>

                    <div style="margin-top:4px; font-weight:700; color:#2563eb;">
                        {{ $company?->name ?? '—' }}
                    </div>

                    @if ($quote->contact)
                        <div class="small muted mt-6">
                            {{ $quote->contact->name }}{{ $quote->contact->email ? ' • ' . $quote->contact->email : '' }}
                        </div>
                    @endif

                    <div class="small muted mt-10" style="font-weight:700;">Billing Address:</div>
                    <div class="pre">{{ $billing ?: '—' }}</div>

                    @if ($company?->vat_number)
                        <div class="small muted mt-6">VAT Number: {{ $company->vat_number }}</div>
                    @endif

                    <div class="small muted mt-6"><b>Payment Terms:</b> {{ $paymentTerms ?: '—' }}</div>
                </div>
            </td>

            <td style="width:50%; padding-left:10px;">
                <div class="box">
                    <div class="label-title">Ship To</div>

                    <div style="margin-top:4px; font-weight:700;">
                        {{ $company?->name ?? '—' }}
                    </div>

                    <div class="small muted mt-10" style="font-weight:700;">Delivery Address:</div>
                    <div class="pre">{{ $shipping ?: '—' }}</div>
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
                    @php $line = (float) $it->line_total; @endphp
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

    {{-- TOTALS --}}
    <table style="margin-top:18px; width:100%;">
        <tr>
            <td style="width:60%;"></td>
            <td style="width:40%;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="padding:10px 12px; text-align:left; color:#6b7280;">Sub Total</td>
                        <td style="padding:10px 12px; text-align:right; font-weight:700;">{{ $money($sub) }}</td>
                    </tr>

                    <tr>
                        <td style="padding:10px 12px; text-align:left; color:#6b7280;">
                            {{ $taxName }}@if ($taxRate !== null)
                                ({{ number_format((float) $taxRate, 2) }}%)
                            @endif
                        </td>
                        <td style="padding:10px 12px; text-align:right; font-weight:700;">{{ $money($vat) }}</td>
                    </tr>

                    <tr>
                        <td style="padding:12px; text-align:left; font-weight:800; background:#f3f4f6;">Total</td>
                        <td style="padding:12px; text-align:right; font-weight:900; background:#f3f4f6;">
                            {{ $money($grand) }}</td>
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
