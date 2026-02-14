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

        $totalAllocated = (float) ($allocations?->sum('amount_applied') ?? 0);
        $unallocated = round(max(0, (float) $payment->amount - $totalAllocated), 2);

        $method = $payment->method ?: '—';
        $ref = $payment->reference ?: '—';

        // Payment date column name differs in your codebase sometimes (paid_at vs received_at)
        $paidAt = $payment->paid_at ?? ($payment->received_at ?? null);

        $statusText =
            $totalAllocated <= 0 ? 'Unallocated' : ($unallocated > 0 ? 'Partially Allocated' : 'Fully Allocated');

        $statusColor = $totalAllocated <= 0 ? 'secondary' : ($unallocated > 0 ? 'warning' : 'success');
    @endphp

    <div class="container-fluid py-4">

        {{-- Top bar --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h3 class="mb-0">Payment</h3>
                <div class="text-muted small">
                    {{ $payment->company?->name ?? '—' }}
                    • Amount: <strong>{{ $money($payment->amount) }}</strong>
                    • Date: <strong>{{ $fmtDate($paidAt) }}</strong>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.payments.index') }}" class="btn btn-light">Back</a>

                <a href="{{ tenant_route('tenant.payments.allocate.form', ['payment' => $payment->id]) }}"
                    class="btn btn-outline-primary">
                    Apply to Invoices
                </a>

                {{-- Optional: reset allocations button if you implement it --}}
                {{-- 
                @if ($totalAllocated > 0)
                    <form method="POST"
                        action="{{ tenant_route('tenant.payments.allocations.reset', ['payment' => $payment->id]) }}"
                        onsubmit="return confirm('Remove ALL allocations for this payment?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger">Reset Allocations</button>
                    </form>
                @endif
                --}}
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-3">

            {{-- Payment details --}}
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Details</strong>
                        <span class="badge text-bg-{{ $statusColor }}">
                            {{ $statusText }}
                        </span>
                    </div>
                    <div class="card-body">

                        <div class="d-flex justify-content-between">
                            <div class="text-muted">Customer</div>
                            <div class="fw-semibold">{{ $payment->company?->name ?? '—' }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Contact</div>
                            <div class="fw-semibold">{{ $payment->contact?->name ?? '—' }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Payment Date</div>
                            <div class="fw-semibold">{{ $fmtDate($paidAt) }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Method</div>
                            <div class="fw-semibold">{{ $method }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Reference</div>
                            <div class="fw-semibold">{{ $ref }}</div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <div class="text-muted">Total Amount</div>
                            <div class="fw-bold">{{ $money($payment->amount) }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Allocated</div>
                            <div class="fw-semibold">{{ $money($totalAllocated) }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Unallocated</div>
                            <div class="fw-semibold {{ $unallocated > 0 ? 'text-danger' : '' }}">
                                {{ $money($unallocated) }}
                            </div>
                        </div>

                        @if (!empty($payment->notes))
                            <hr>
                            <div class="text-muted small mb-1">Notes</div>
                            <div style="white-space: pre-wrap;">{{ $payment->notes }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Allocations --}}
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <strong>Allocations</strong>
                        <div class="text-muted small">How this payment was applied to invoices.</div>
                    </div>

                    <div class="card-body table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th class="text-end">Applied</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allocations as $a)
                                    @php
                                        $inv = $a->invoice ?? null; // ✅ eager-loaded in controller
                                        $invLabel = $inv?->invoice_number ?: 'INV-' . $a->invoice_id;
                                        $invDate = $inv?->issued_at ?? null;
                                    @endphp

                                    <tr>
                                        <td>
                                            @if ($inv)
                                                <a href="{{ tenant_route('tenant.invoices.show', ['invoice' => $inv->id]) }}"
                                                    class="text-decoration-none">
                                                    {{ $invLabel }}
                                                </a>
                                            @else
                                                <span class="text-muted">{{ $invLabel }}</span>
                                            @endif
                                        </td>

                                        <td class="text-muted">
                                            {{ $fmtDate($invDate) }}
                                        </td>

                                        <td class="text-end fw-semibold">
                                            {{ $money($a->amount_applied) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">No allocations yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if ($unallocated > 0)
                            <div class="alert alert-warning mb-0">
                                <strong>{{ $money($unallocated) }}</strong> is still unallocated.
                                You can apply it using “Apply to Invoices” or leave it as customer credit.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
