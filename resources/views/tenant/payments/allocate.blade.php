@extends('layouts.app')

@section('content')
    @php
        $money = fn($n) => 'R ' . number_format((float) $n, 2);
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
    @endphp

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="mb-0">Apply Payment</h3>
                <div class="text-muted small">
                    Customer: <strong>{{ $payment->company?->name ?? '—' }}</strong>
                    • Payment: <strong>{{ $money($payment->amount) }}</strong>
                    • Unallocated: <strong
                        class="{{ $unallocated > 0 ? 'text-danger' : '' }}">{{ $money($unallocated) }}</strong>
                    • Paid: <strong>{{ $fmtDate($payment->paid_at) }}</strong>
                </div>
            </div>

            <a href="{{ tenant_route('tenant.payments.show', ['payment' => $payment->id]) }}" class="btn btn-light">Back</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix the errors below:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ tenant_route('tenant.payments.allocate.store', ['payment' => $payment->id]) }}">
            @csrf

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Applied Date</label>
                            <input type="date" name="applied_at" class="form-control"
                                value="{{ old('applied_at', $payment->paid_at?->toDateString() ?? now()->toDateString()) }}">
                        </div>

                        <div class="col-12 col-md-9">
                            <div class="alert alert-info mb-0">
                                Enter amounts per invoice. Total must be ≤ <strong>{{ $money($unallocated) }}</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Outstanding Invoices</strong>
                    <span class="text-muted small">Oldest first</span>
                </div>

                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Date</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Applied</th>
                                <th class="text-end">Outstanding</th>
                                <th class="text-end" style="width: 180px;">Apply Now</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $i => $r)
                                <tr>
                                    <td>
                                        <a href="{{ tenant_route('tenant.invoices.show', ['invoice' => $r->id]) }}"
                                            class="text-decoration-none">
                                            {{ $r->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="text-muted">{{ $fmtDate($r->issued_at) }}</td>
                                    <td class="text-end">{{ $money($r->total) }}</td>
                                    <td class="text-end text-muted">{{ $money($r->applied_total) }}</td>
                                    <td class="text-end fw-semibold">{{ $money($r->outstanding) }}</td>
                                    <td class="text-end">
                                        <input type="hidden" name="allocations[{{ $i }}][invoice_id]"
                                            value="{{ $r->id }}">
                                        <input type="number" step="0.01" min="0" max="{{ $r->outstanding }}"
                                            class="form-control form-control-sm text-end applyAmount"
                                            name="allocations[{{ $i }}][amount]"
                                            value="{{ old("allocations.$i.amount", 0) }}">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">No invoices for this customer.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <hr>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Total to apply:
                            <strong>R <span id="applyTotal">0.00</span></strong>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="autoFillBtn">
                                Auto-fill oldest
                            </button>
                            <button class="btn btn-primary">Save Allocations</button>
                        </div>
                    </div>

                    <div class="text-muted small mt-2">
                        Auto-fill uses remaining unallocated amount and fills oldest outstanding invoices.
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        (function() {
            const inputs = Array.from(document.querySelectorAll('.applyAmount'));
            const applyTotalEl = document.getElementById('applyTotal');
            const autoFillBtn = document.getElementById('autoFillBtn');
            const unallocated = {{ (float) $unallocated }};

            function money(n) {
                const x = Number(n || 0);
                return x.toLocaleString('en-ZA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function sumInputs() {
                let s = 0;
                inputs.forEach(i => s += Number(i.value || 0));
                applyTotalEl.textContent = money(s);
            }

            inputs.forEach(i => i.addEventListener('input', sumInputs));

            autoFillBtn?.addEventListener('click', () => {
                let remaining = unallocated;
                inputs.forEach(i => {
                    const max = Number(i.getAttribute('max') || 0);
                    const use = Math.min(max, remaining);
                    i.value = use > 0 ? use.toFixed(2) : '0';
                    remaining = Math.max(0, remaining - use);
                });
                sumInputs();
            });

            sumInputs();
        })();
    </script>
@endsection
