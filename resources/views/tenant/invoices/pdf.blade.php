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

        .nowrap {
            white-space: nowrap;
        }

        .wrap {
            word-wrap: break-word;
        }

        .pre {
            white-space: pre-wrap;
        }

        .box {
            border: 1px solid rgba(0, 0, 0, .12);
            border-radius: 8px;
            padding: 10px;
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
        // Totals from invoice header
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

        // People
        $salesPersonName = optional($invoice->salesPerson ?? null)->name ?? '—';
        $ownerName = optional($invoice->owner ?? null)->name ?? '—';

        // --- Address helpers ---
        $formatAddress = function ($addr) {
            if (!$addr) {
                return '';
            }
            if (is_string($addr)) {
                return trim($addr);
            }

            // If it's a model/array with common fields
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

// If controller passed $billTo/$shipTo, prefer those.
// Otherwise fall back to company addresses if they exist.
$billToText = isset($billTo) && trim((string) $billTo) !== '' ? trim((string) $billTo) : '';
$shipToText = isset($shipTo) && trim((string) $shipTo) !== '' ? trim((string) $shipTo) : '';

// Fallback (CompanyAddress model pattern)
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
    @endphp

    {{-- HEADER --}}
    <table width="100%">
        <tr>
            <td valign="top" width="60%">
                <table>
                    <tr>
                        <td valign="top" style="padding-right:10px;">
                            @if (!empty($tenant->logo_path))
                                {{-- dompdf: use public_path() for local file --}}
                                <img src="{{ public_path('storage/' . $tenant->logo_path) }}"
                                    style="height:60px; width:60px;" alt="Logo">
                            @else
                                <div
                                    style="height:60px; width:60px; border:1px solid rgba(0,0,0,.12); text-align:center; line-height:60px;">
                                    {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                </div>
                            @endif
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
                    <tr>
                        <td class="muted right" style="padding:2px 10px 2px 0;">Owner:</td>
                        <td class="nowrap"><strong>{{ $ownerName }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="hr"></div>

    {{-- BILL/SHIP --}}
    <table width="100%">
        <tr>
            <td width="50%" valign="top" style="padding-right:8px;">
                <div class="box">
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

            <td width="50%" valign="top" style="padding-left:8px;">
                <div class="box">
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
        <colgroup>
            <col style="width:30px;">
            <col style="width:auto;">
            <col style="width:60px;">
            <col style="width:60px;">
            <col style="width:90px;">
            <col style="width:90px;">
            <col style="width:100px;">
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Item &amp; Description</th>
                <th class="right">Unit</th>
                <th class="right">Qty</th>
                <th class="right">Rate</th>
                <th class="right">VAT</th>
                <th class="right">Amount</th>
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
                    <td class="right nowrap">{{ $idx + 1 }}</td>
                    <td class="wrap">
                        <div style="font-weight:800;">{{ $it->name }}</div>
                        @if (!empty($it->sku))
                            <div class="sku">SKU: {{ $it->sku }}</div>
                        @endif
                        @if (!empty($it->description))
                            <div class="muted" style="margin-top:4px;">{{ $it->description }}</div>
                        @endif
                    </td>
                    <td class="right nowrap">{{ $it->unit ?? '—' }}</td>
                    <td class="right nowrap">{{ number_format((float) $it->qty, 2) }}</td>
                    <td class="right nowrap">{{ $money((float) $it->unit_price) }}</td>
                    <td class="right nowrap">{{ $money($lineVat) }}</td>
                    <td class="right nowrap"><strong>{{ $money($incl) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    @php
        $paymentsApplied = (float) ($paymentsApplied ?? 0);
        $creditsApplied = (float) ($creditsApplied ?? 0);
        $appliedLine = $paymentsApplied + $creditsApplied; // combined
        $balanceDue = (float) ($balanceDue ?? max(0, ((float) $grand) - $appliedLine));
    @endphp

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

                    {{-- NEW: Payments/Credits line (combined, red, negative) --}}
                    <tr>
                        <td classs="label right">Payments / Credits</td>
                        <td class="right strong nowrap" style="color:#b00020;">
                            - {{ $money($appliedLine) }}
                        </td>
                    </tr>

                    {{-- NEW: Balance Due --}}
                    <tr class="grand">
                        <td class="right">Balance Due</td>
                        <td class="right nowrap">{{ $money($balanceDue) }}</td>
                    </tr>

                    {{-- Optional small breakdown line --}}
                    {{-- 
                <tr>
                    <td colspan="2" class="right" style="font-size:11px;color:#666;">
                        Payments: {{ $money($paymentsApplied) }} • Credits: {{ $money($creditsApplied) }}
                    </td>
                </tr>
                --}}
                </table>
            </td>
        </tr>
    </table>


    {{-- FOOTER --}}
    <table width="100%" style="margin-top:14px;">
        <tr>
            <td width="50%" valign="top" style="padding-right:8px;">
                <div class="box">
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
                <div class="box">
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
