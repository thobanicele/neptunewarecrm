<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Statement</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }

        .muted {
            color: #666;
        }

        .row {
            width: 100%;
        }

        .col {
            display: inline-block;
            vertical-align: top;
        }

        .left {
            width: 58%;
        }

        .right {
            width: 40%;
            text-align: right;
        }

        .box {
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 6px;
        }

        .h1 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        .h2 {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 7px 8px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f3f3f3;
            text-align: left;
            font-size: 11px;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .summary td {
            border-bottom: 0;
            padding: 4px 0;
        }

        .logo {
            height: 55px;
        }
    </style>
</head>

<body>

    @php
        // Best-effort company address if controller doesn't pass $companyAddress
$companyAddress = $companyAddress ?? '';

if (empty($companyAddress) && isset($company) && $company->relationLoaded('addresses')) {
    $billing = $company->addresses
        ->where('type', 'billing')
        ->sortByDesc('is_default_billing')
        ->sortByDesc('id')
        ->first();

    $shipping = $company->addresses
        ->where('type', 'shipping')
        ->sortByDesc('is_default_shipping')
        ->sortByDesc('id')
        ->first();

    $addrModel = $billing ?: $shipping;

    if ($addrModel) {
        // Use snapshot if available
        if (method_exists($addrModel, 'toSnapshotString')) {
            $companyAddress = $addrModel->toSnapshotString();
        } else {
            $lines = array_filter([
                $addrModel->label ?? null,
                $addrModel->attention ?? null,
                $addrModel->phone ?? null,
                $addrModel->line1 ?? null,
                $addrModel->line2 ?? null,
                $addrModel->city ?? null,
                $addrModel->postal_code ?? null,
            ]);
            $companyAddress = implode("\n", $lines);
        }
    }
}

// Summary breakdown from ledger types (safe fallback)
$invoicesOnly = (float) collect($ledger ?? [])
    ->where('type', 'Invoice')
    ->sum('debit');
$paymentsOnly = (float) collect($ledger ?? [])
    ->where('type', 'Payment')
    ->sum('credit');
$creditsOnly = (float) collect($ledger ?? [])
    ->where('type', 'Credit Note')
    ->sum('credit');
$refundsOnly = (float) collect($ledger ?? [])
    ->where('type', 'Refund')
    ->sum('debit');

$openingBal = (float) ($opening ?? 0);
$closingBal = (float) ($closing ?? 0);

$fromFmt = \Carbon\Carbon::parse($from)->format('d/m/Y');
$toFmt = \Carbon\Carbon::parse($to)->format('d/m/Y');

// Tenant address blocks: prefer bankable/print style
$tenantAddress = $tenant->address ?? '';
    @endphp

    <div class="row">
        <div class="col left">
            @include('tenant.partials.pdf-transaction-header-brand', [
                'tenant' => $tenant,
                'logoHeight' => 70,
                'pdfLogoPath' => $pdfLogoPath ?? null,
            ])
        </div>

        <div class="col right">
            <div style="font-weight:700;">{{ $tenant->name }}</div>

            @php $tenantAddress = $tenant->address ?? ''; @endphp
            @if (!empty($tenantAddress))
                <div class="muted" style="white-space:pre-wrap;">{{ $tenantAddress }}</div>
            @endif

            @if (!empty($tenant->phone))
                <div class="muted">Phone: {{ $tenant->phone }}</div>
            @endif
            @if (!empty($tenant->vat_number))
                <div class="muted">VAT Number: {{ $tenant->vat_number }}</div>
            @endif
        </div>
    </div>

    <div style="height:14px;"></div>

    <div class="row">
        <div class="col left">
            <div class="muted" style="font-weight:700;">To</div>
            <div style="font-weight:700; color:#0d6efd;">{{ $company->name }}</div>

            @if (!empty($companyAddress))
                <div class="muted" style="white-space:pre-wrap;">{{ $companyAddress }}</div>
            @endif

            @if (!empty($company->vat_number))
                <div class="muted">VAT Number: {{ $company->vat_number }}</div>
            @endif
            @if (!empty($company->code))
                <div class="muted">Customer Number: {{ $company->code }}</div>
            @endif
        </div>

        <div class="col right">
            <div class="h1">Statement of Accounts</div>
            <div class="muted">{{ $fromFmt }} to {{ $toFmt }}</div>

            <div style="height:10px;"></div>

            <div class="box">
                <div class="h2" style="margin-bottom:8px;">Account Summary</div>
                <table class="summary">
                    <tr>
                        <td class="muted">Opening Balance</td>
                        <td class="num">R {{ number_format($openingBal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Invoiced Amount</td>
                        <td class="num">R {{ number_format($invoicesOnly, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Payments</td>
                        <td class="num">R {{ number_format($paymentsOnly, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Credit Notes</td>
                        <td class="num">R {{ number_format($creditsOnly, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="muted">Refunds</td>
                        <td class="num">R {{ number_format($refundsOnly, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:700;">Closing Balance</td>
                        <td class="num" style="font-weight:700;">R {{ number_format($closingBal, 2) }}</td>
                    </tr>
                </table>

                <div class="muted" style="font-size:10px; margin-top:8px;">
                    Closing Balance = Opening + (Invoices + Refunds) − (Payments + Credit Notes)
                </div>
            </div>
        </div>
    </div>

    <div style="height:18px;"></div>

    <table>
        <thead>
            <tr>
                <th style="width:12%;">Date</th>
                <th style="width:14%;">Type</th>
                <th style="width:14%;">Reference</th>
                <th>Description</th>
                <th class="num" style="width:12%;">Debit</th>
                <th class="num" style="width:12%;">Credit</th>
                <th class="num" style="width:12%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6" class="muted" style="border-bottom:1px solid #eee;">Opening Balance</td>
                <td class="num" style="border-bottom:1px solid #eee;">R {{ number_format($openingBal, 2) }}</td>
            </tr>

            @forelse ($ledger as $r)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($r->date)->format('d/m/Y') }}</td>
                    <td>{{ $r->type }}</td>
                    <td>{{ $r->ref }}</td>
                    <td class="muted">{{ $r->description ?? '' }}</td>
                    <td class="num">{{ ((float) $r->debit) > 0 ? 'R ' . number_format((float) $r->debit, 2) : '' }}
                    </td>
                    <td class="num">
                        {{ ((float) $r->credit) > 0 ? 'R ' . number_format((float) $r->credit, 2) : '' }}</td>
                    <td class="num">R {{ number_format((float) $r->balance, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">No ledger entries found for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="height:18px;"></div>

    @if (!empty($tenant->bank_details))
        <div class="box">
            <div style="font-weight:700; margin-bottom:6px;">Bank Details</div>
            <div class="muted" style="white-space:pre-wrap;">{{ $tenant->bank_details }}</div>
        </div>
    @endif

</body>

</html>
