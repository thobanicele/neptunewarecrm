@extends('layouts.app')

@php
    $qs = http_build_query(request()->query());
    $qsPrefix = $qs ? '?' . $qs : '';

    $fromDate = !empty($from) ? \Illuminate\Support\Carbon::parse($from) : now()->startOfMonth();
    $toDate = !empty($to) ? \Illuminate\Support\Carbon::parse($to) : now()->endOfMonth();
@endphp

@section('content')
    <div class="container-fluid py-4">

        {{-- Top Toolbar --}}
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Customer Statement</h3>
                <div class="text-muted small">{{ $company->name }}</div>
            </div>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                <a class="btn btn-light" href="{{ tenant_route('tenant.companies.show', $company) }}">Back</a>

                {{-- Export / Actions --}}
                @can('statement', \App\Models\Invoice::class)
                    <div class="btn-group">
                        <a class="btn btn-outline-secondary"
                            href="{{ tenant_route('tenant.companies.statement.pdf', $company) }}{{ $qsPrefix }}"
                            title="Download PDF">
                            <i class="fa fa-file-pdf me-1"></i> PDF
                        </a>

                        <a class="btn btn-outline-secondary"
                            href="{{ tenant_route('tenant.companies.statement.csv', $company) }}{{ $qsPrefix }}"
                            title="Download CSV">
                            <i class="fa fa-file-csv me-1"></i> CSV
                        </a>

                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()" title="Print">
                            <i class="fa fa-print"></i>
                        </button>

                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#emailStatementModal" title="Send Email">
                            <i class="fa fa-envelope me-1"></i> Send Email
                        </button>
                    </div>
                @endcan
            </div>
        </div>

        {{-- Filters (Zoho-like presets) --}}
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-3" id="statementFilterForm">
            <div style="min-width: 220px;">
                @php $curRange = ($range ?? request('range', 'this_month')); @endphp
                <select class="form-select" name="range" id="rangeSelect">
                    <option value="today" @selected($curRange === 'today')>Today</option>
                    <option value="this_week" @selected($curRange === 'this_week')>This Week</option>
                    <option value="this_month" @selected($curRange === 'this_month')>This Month</option>
                    <option value="this_quarter" @selected($curRange === 'this_quarter')>This Quarter</option>
                    <option value="this_year" @selected($curRange === 'this_year')>This Year</option>

                    <option value="yesterday" @selected($curRange === 'yesterday')>Yesterday</option>
                    <option value="previous_week" @selected($curRange === 'previous_week')>Previous Week</option>
                    <option value="previous_month" @selected($curRange === 'previous_month')>Previous Month</option>
                    <option value="previous_quarter" @selected($curRange === 'previous_quarter')>Previous Quarter</option>
                    <option value="previous_year" @selected($curRange === 'previous_year')>Previous Year</option>

                    <option value="custom" @selected($curRange === 'custom')>Custom</option>
                </select>
            </div>

            <div class="d-flex gap-2 align-items-center" id="customDatesWrap">
                <input type="date" name="from" class="form-control" value="{{ $fromDate->format('Y-m-d') }}"
                    style="min-width: 170px;" id="fromInput">
                <input type="date" name="to" class="form-control" value="{{ $toDate->format('Y-m-d') }}"
                    style="min-width: 170px;" id="toInput">
                <button class="btn btn-primary" type="submit">Apply</button>
            </div>
        </form>

        {{-- Statement Page (PDF-like) --}}
        <div class="card shadow-sm">
            <div class="card-body">

                {{-- Header block --}}
                <div class="row g-3 align-items-start mb-3">
                    <div class="col-12 col-lg-6">
                        <div class="d-flex align-items-center gap-3">
                            @if (!empty($tenant->logo_path))
                                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Logo" style="height:64px;">
                            @else
                                <div class="rounded bg-light border d-flex align-items-center justify-content-center"
                                    style="height:64px; width:64px;">
                                    <span class="text-muted fw-semibold" style="font-size:22px;">
                                        {{ strtoupper(substr($tenant->name, 0, 1)) }}
                                    </span>
                                </div>
                            @endif

                            <div>
                                <div class="fw-semibold" style="font-size: 18px;">{{ $tenant->name }}</div>
                                <div class="text-muted small">Statement of Accounts</div>
                            </div>
                        </div>

                        <div class="mt-3 small text-muted">
                            <div><strong>Tenant:</strong> {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6 text-lg-end">
                        <div class="fw-semibold" style="font-size: 18px;">
                            Customer Statement for {{ $company->name }}
                        </div>
                        <div class="text-muted small">
                            From <strong>{{ $fromDate->format('d/m/Y') }}</strong>
                            To <strong>{{ $toDate->format('d/m/Y') }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Summary boxes --}}
                @php
                    $openingBal = (float) ($opening ?? 0);
                    $closingBal = (float) ($closing ?? 0);

                    $paid = (float) collect($ledger)->where('type', 'Payment')->sum('credit');
                    $credits = (float) collect($ledger)->where('type', 'Credit Note')->sum('credit');
                    $refunds = (float) collect($ledger)->where('type', 'Refund')->sum('debit');
                    $invoicesOnly = (float) collect($ledger)->where('type', 'Invoice')->sum('debit');
                @endphp

                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-2">To</div>
                            <div class="fw-semibold">{{ $company->name }}</div>
                            <div class="small text-muted">
                                {{ $company->email ?? '—' }}<br>
                                {{ $company->phone ?? '—' }}
                            </div>
                            <div class="small text-muted mt-2">
                                <div><strong>VAT Number:</strong> {{ $company->vat_number ?? '—' }}</div>
                                <div><strong>Payment Terms:</strong> {{ $company->payment_terms ?? '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-semibold mb-2">Account Summary</div>

                            <div class="d-flex justify-content-between small">
                                <div class="text-muted">Opening Balance</div>
                                <div class="fw-semibold">R {{ number_format($openingBal, 2) }}</div>
                            </div>

                            <div class="d-flex justify-content-between small mt-1">
                                <div class="text-muted">Invoiced Amount</div>
                                <div class="fw-semibold">R {{ number_format($invoicesOnly, 2) }}</div>
                            </div>

                            <div class="d-flex justify-content-between small mt-1">
                                <div class="text-muted">Amount Paid</div>
                                <div class="fw-semibold">R {{ number_format($paid, 2) }}</div>
                            </div>

                            <div class="d-flex justify-content-between small mt-1">
                                <div class="text-muted">Credits Issued</div>
                                <div class="fw-semibold">R {{ number_format($credits, 2) }}</div>
                            </div>

                            <div class="d-flex justify-content-between small mt-1">
                                <div class="text-muted">Refunds</div>
                                <div class="fw-semibold">R {{ number_format($refunds, 2) }}</div>
                            </div>

                            <hr class="my-2">

                            <div class="d-flex justify-content-between">
                                <div class="fw-semibold">Closing Balance</div>
                                <div class="fw-bold">R {{ number_format($closingBal, 2) }}</div>
                            </div>

                            <div class="text-muted small mt-2">
                                Balance = Opening + Debits − Credits
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Ledger Table --}}
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 110px;">Date</th>
                                <th style="width: 120px;">Type</th>
                                <th style="width: 120px;">Ref</th>
                                <th>Description</th>
                                <th class="text-end" style="width: 120px;">Debit</th>
                                <th class="text-end" style="width: 120px;">Credit</th>
                                <th class="text-end" style="width: 130px;">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Opening Balance row --}}
                            <tr>
                                <td class="text-muted">{{ $fromDate->format('Y-m-d') }}</td>
                                <td class="fw-semibold">Opening</td>
                                <td class="text-muted">—</td>
                                <td class="text-muted">Opening Balance</td>
                                <td class="text-end">—</td>
                                <td class="text-end">—</td>
                                <td class="text-end fw-semibold">R {{ number_format($openingBal, 2) }}</td>
                            </tr>

                            @forelse($ledger as $r)
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($r->date)->format('Y-m-d') }}</td>
                                    <td>
                                        @php
                                            $badge = match ($r->type) {
                                                'Invoice' => 'bg-primary',
                                                'Payment' => 'bg-success',
                                                'Credit Note' => 'bg-warning text-dark',
                                                'Refund' => 'bg-danger',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badge }}">{{ $r->type }}</span>
                                    </td>
                                    <td class="text-muted">{{ $r->ref }}</td>
                                    <td class="text-muted">{{ $r->description ?: '—' }}</td>
                                    <td class="text-end">
                                        {{ ((float) $r->debit) > 0 ? 'R ' . number_format((float) $r->debit, 2) : '—' }}
                                    </td>
                                    <td class="text-end">
                                        {{ ((float) $r->credit) > 0 ? 'R ' . number_format((float) $r->credit, 2) : '—' }}
                                    </td>
                                    <td class="text-end fw-semibold">R {{ number_format((float) $r->balance, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted">No ledger entries in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer notes --}}
                <div class="text-muted small mt-3">
                    This statement includes only financial transactions (Invoices, Payments, Credit Notes, Refunds).
                </div>
            </div>
        </div>
    </div>

    {{-- Email Statement Modal --}}
    @can('statement', \App\Models\Invoice::class)
        <div class="modal fade" id="emailStatementModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" method="POST"
                    action="{{ tenant_route('tenant.companies.statement.email', $company) }}{{ $qsPrefix }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Send Statement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="emailStatement">
                        <div class="mb-2">
                            <label class="form-label">To</label>
                            <input type="email" name="to" class="form-control" value="{{ $company->email ?? '' }}"
                                placeholder="customer@example.com" required>
                            <div class="form-text">We’ll generate the statement PDF and email it.</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Message (optional)</label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Hi, please find attached your statement."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" type="submit">Send</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection

@push('scripts')
    <script>
        (function() {
            const form = document.getElementById('statementFilterForm');
            const rangeSelect = document.getElementById('rangeSelect');
            const customWrap = document.getElementById('customDatesWrap');
            const fromInput = document.getElementById('fromInput');
            const toInput = document.getElementById('toInput');

            function setCustomEnabled(enabled) {
                customWrap.style.display = enabled ? 'flex' : 'none';
                if (fromInput) fromInput.disabled = !enabled;
                if (toInput) toInput.disabled = !enabled;
            }

            function toggleCustom() {
                const isCustom = (rangeSelect.value === 'custom');
                setCustomEnabled(isCustom);
                if (!isCustom) form.submit();
            }

            // Init
            setCustomEnabled(rangeSelect.value === 'custom');
            rangeSelect.addEventListener('change', toggleCustom);
        })();
    </script>
@endpush
