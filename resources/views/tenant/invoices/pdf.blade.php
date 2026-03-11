<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 24px 22px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #111;
        }

        .muted {
            color: #6c757d;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .wrap {
            word-wrap: break-word;
            word-break: break-word;
        }

        .pre {
            white-space: pre-wrap;
        }

        .title {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .subtle {
            font-size: 11px;
        }

        .hr {
            border-top: 1px solid rgba(0, 0, 0, .12);
            margin: 10px 0;
        }

        table {
            border-collapse: collapse;
        }

        .items {
            width: 100%;
            margin-top: 14px;
            table-layout: fixed;
        }

        .items th {
            background: #1f2937;
            color: #fff;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .3px;
            padding: 8px 6px;
        }

        .items td {
            border-bottom: 1px solid rgba(0, 0, 0, .10);
            padding: 8px 6px;
            vertical-align: top;
        }

        .desc-col {
            word-wrap: break-word;
            word-break: break-word;
        }

        .unit-col {
            white-space: nowrap;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .sku {
            font-size: 10px;
            color: #6c757d;
            margin-top: 3px;
        }

        .totals {
            width: 320px;
        }

        .totals td {
            padding: 6px 4px;
        }

        .totals .label {
            color: #6c757d;
        }

        .totals .strong {
            font-weight: 800;
        }

        .totals .grand td {
            background: #f3f4f6;
            font-weight: 900;
        }
    </style>
</head>

<body>
    @php
        $subGross = round((float) ($invoice->subtotal ?? 0), 2);
        $discount = round((float) ($invoice->discount_amount ?? 0), 2);
        $vat = round((float) ($invoice->tax_amount ?? 0), 2);
        $grand = round((float) ($invoice->total ?? $subGross - $discount + $vat), 2);

        $currencySymbol = $invoice->currency === 'ZAR' || empty($invoice->currency) ? 'R' : $invoice->currency;
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

        $salesPersonName = optional($invoice->salesPerson ?? null)->name ?? '—';
       

        $formatAddress = function ($addr) {
            if (!$addr) {
                return '';
            }
            if (is_string($addr)) {
                return trim($addr);
            }

            $get = fn($k) => data_get($addr, $k);

            $parts = array_filter([
                $get('label'),
                $get('attention'),
                $get('line1'),
                $get('line2'),
                $get('city'),
                $get('subdivision_text') ?: optional($get('subdivision'))->name ?? null,
                $get('postal_code'),
                optional($get('country'))->name ?? ($get('country') ?? null),
            ]);

            return trim(implode("\n", $parts));
        };

        $billToText = isset($billTo) && trim((string) $billTo) !== '' ? trim((string) $billTo) : '';
        $shipToText = isset($shipTo) && trim((string) $shipTo) !== '' ? trim((string) $shipTo) : '';

        if (!$billToText && $invoice->company && method_exists($invoice->company, 'addresses')) {
            $billing =
                $invoice->company->addresses->firstWhere('is_default_billing', 1) ??
                ($invoice->company->addresses->firstWhere('type', 'billing') ?? $invoice->company->addresses->first());

            $billToText = $formatAddress($billing);
        }

        if (!$shipToText && $invoice->company && method_exists($invoice->company, 'addresses')) {
            $shipping =
                $invoice->company->addresses->firstWhere('is_default_shipping', 1) ??
                ($invoice->company->addresses->firstWhere('type', 'shipping') ?? $invoice->company->addresses->first());

            $shipToText = $formatAddress($shipping);
        }

        $paymentsApplied = (float) ($paymentsApplied ?? 0);
        $creditsApplied = (float) ($creditsApplied ?? 0);
        $appliedLine = $paymentsApplied + $creditsApplied;
        $balanceDue = (float) ($balanceDue ?? max(0, ((float) $grand) - $appliedLine));
    @endphp

    {{-- HEADER --}}
    <table width="100%">
        <tr>
            <td valign="top" width="60%">
                <table>
                    <tr>
                        <td style="width:60%; padding-top:10px;">
                            @include('tenant.partials.pdf-transaction-header-brand', [
                                'tenant' => $tenant,
                                'logoHeight' => 70,
                                'pdfLogoPath' => $pdfLogoPath ?? null,
                            ])
                        </td>
                        <td valign="top">
                            <div style="font-size:16px; font-weight:800;">{{ $tenant->name }}</div>
                            <div class="muted subtle">Workspace: {{ $tenant->subdomain }}</div>

                            @if (!empty($tenant->address))
                                <div class="muted subtle pre" style="margin-top:6px;">{{ $tenant->address }}</div>
                            @endif

                            @if (!empty($tenant->vat_number))
                                <div class="muted subtle" style="margin-top:6px;">VAT Number: {{ $tenant->vat_number }}
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>

            <td valign="top" width="40%" align="right">
                <div class="title">Invoice</div>
                <div style="margin-top:4px; font-weight:800;"># {{ $invoice->invoice_number }}</div>

                <table style="margin-top:10px; font-size:12px;" align="right">
                    <tr>
                        <td class="muted right" style="padding:2px 10px 2px 0;">Invoice Date:</td>
                        <td class="nowrap"><strong>{{ $fmtDate($invoice->issued_at) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="muted right" style="padding:2px 10px 2px 0;">Due Date:</td>
                        <td class="nowrap"><strong>{{ $fmtDate($invoice->due_at) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="muted right" style="padding:2px 10px 2px 0;">Reference:</td>
                        <td class="nowrap"><strong>{{ $invoice->reference ?? '—' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="muted right" style="padding:2px 10px 2px 0;">Status:</td>
                        <td class="nowrap"><strong>{{ strtoupper((string) $invoice->status) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="muted right" style="padding:2px 10px 2px 0;">Quote #:</td>
                        <td class="nowrap"><strong>{{ $invoice->quote_number ?? '—' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="muted right" style="padding:2px 10px 2px 0;">Sales Person:</td>
                        <td class="nowrap"><strong>{{ $salesPersonName }}</strong></td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>

    <div class="hr"></div>

    {{-- BILL/SHIP --}}
    <table width="100%" style="margin-top:14px; table-layout:fixed;">
        <tr>
            <td width="50%" valign="top" style="padding-right:18px;">
                <div>
                    <div class="muted"
                        style="font-weight:800; font-size:11px; text-transform:uppercase; letter-spacing:.4px;">Bill To
                    </div>

                    <div style="margin-top:6px; font-weight:800;">{{ $invoice->company?->name ?? '—' }}</div>

                    @if ($invoice->company?->vat_number)
                        <div class="muted subtle" style="margin-top:4px;">VAT Number:
                            {{ $invoice->company->vat_number }}</div>
                    @endif

                    @if ($invoice->contact)
                        <div class="muted subtle" style="margin-top:4px;">
                            {{ $invoice->contact->name }}
                            @if ($invoice->contact->email)
                                • {{ $invoice->contact->email }}
                            @endif
                        </div>
                    @endif

                    <div class="muted subtle pre" style="margin-top:8px;">
                        {{ $billToText !== '' ? $billToText : '—' }}
                    </div>

                    @if (!empty($invoice->company?->payment_terms))
                        <div class="muted subtle" style="margin-top:8px;">
                            Payment Terms: <strong>{{ $invoice->company->payment_terms }}</strong>
                        </div>
                    @endif
                </div>
            </td>

            <td width="50%" valign="top" style="padding-left:18px;">
                <div>
                    <div class="muted"
                        style="font-weight:800; font-size:11px; text-transform:uppercase; letter-spacing:.4px;">Ship To
                    </div>

                    <div style="margin-top:6px; font-weight:800;">{{ $invoice->company?->name ?? '—' }}</div>

                    <div class="muted subtle pre" style="margin-top:8px;">
                        {{ $shipToText !== '' ? $shipToText : '—' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ITEMS --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:39%;">Item &amp; Description</th>
                <th style="width:10%;" class="right">Unit</th>
                <th style="width:10%;" class="right">Qty</th>
                <th style="width:12%;" class="right">Rate</th>
                <th style="width:12%;" class="right">VAT</th>
                <th style="width:12%;" class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $idx => $it)
                @php
                    $line = (float) ($it->line_total ?? 0);
                    $lineVat = (float) ($it->tax_amount ?? 0);
                    $incl = $line + $lineVat;
                @endphp
                <tr>
                    <td class="num">{{ $idx + 1 }}</td>

                    <td class="desc-col">
                        <div style="font-weight:800;">{{ $it->name }}</div>
                        @if (!empty($it->sku))
                            <div class="sku">SKU: {{ $it->sku }}</div>
                        @endif
                        @if (!empty($it->description))
                            <div class="muted" style="margin-top:4px;">{{ $it->description }}</div>
                        @endif
                    </td>

                    <td class="unit-col num">{{ $it->unit ?? '—' }}</td>
                    <td class="num">{{ number_format((float) $it->qty, 2) }}</td>
                    <td class="num">{{ $money((float) $it->unit_price) }}</td>
                    <td class="num">{{ $money($lineVat) }}</td>
                    <td class="num"><strong>{{ $money($incl) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <table width="100%" style="margin-top:12px;">
        <tr>
            <td></td>
            <td width="320" align="right" valign="top">
                <table class="totals" width="320">
                    <tr>
                        <td class="label right">Sub Total (gross)</td>
                        <td class="right strong nowrap">{{ $money($subGross) }}</td>
                    </tr>
                    <tr>
                        <td class="label right">Discount</td>
                        <td class="right strong nowrap">- {{ $money($discount) }}</td>
                    </tr>
                    <tr>
                        <td class="label right">
                            {{ $invoice->items->firstWhere('tax_name')?->tax_name ?? 'VAT' }}
                            @php $rate = $invoice->items->firstWhere('tax_name')?->tax_rate; @endphp
                            @if ($rate !== null)
                                ({{ number_format((float) $rate, 2) }}%)
                            @endif
                        </td>
                        <td class="right strong nowrap">{{ $money($vat) }}</td>
                    </tr>

                    <tr class="grand">
                        <td class="right">Total</td>
                        <td class="right nowrap">{{ $money($grand) }}</td>
                    </tr>

                    <tr>
                        <td class="label right">Payments / Credits</td>
                        <td class="right strong nowrap" style="color:#b00020;">
                            - {{ $money($appliedLine) }}
                        </td>
                    </tr>

                    <tr class="grand">
                        <td class="right">Balance Due</td>
                        <td class="right nowrap">{{ $money($balanceDue) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- FOOTER --}}
    <table width="100%" style="margin-top:14px;">
        <tr>
            <td width="50%" valign="top" style="padding-right:8px;">
                <div>
                    <div class="muted"
                        style="font-weight:800; font-size:11px; text-transform:uppercase; letter-spacing:.4px;">Terms
                        &amp; Conditions</div>

                    @if (!empty($tenant->bank_details))
                        <div style="margin-top:8px; font-weight:800;">Banking details:</div>
                        <div class="muted subtle pre" style="margin-top:4px;">{{ $tenant->bank_details }}</div>
                        <div class="hr"></div>
                    @endif

                    <div class="muted subtle pre">{{ !empty($invoice->terms) ? $invoice->terms : '—' }}</div>
                </div>
            </td>

            <td width="50%" valign="top" style="padding-left:8px;">
                <div>
                    <div class="muted"
                        style="font-weight:800; font-size:11px; text-transform:uppercase; letter-spacing:.4px;">Notes
                    </div>

                    <div class="muted subtle pre" style="margin-top:8px;">
                        {{ !empty($invoice->notes) ? $invoice->notes : '—' }}</div>

                    <div class="hr"></div>

                    <div class="muted subtle">Prepared by</div>
                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:18px;"></div>

                    <div style="margin-top:10px;" class="muted subtle">Received / Accepted by (Client)</div>
                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:18px;"></div>

                    <div style="margin-top:10px;" class="muted subtle">Date</div>
                    <div style="border-bottom:1px solid rgba(0,0,0,.25); height:18px;"></div>
                </div>
            </td>
        </tr>
    </table>

</body>

</html>
