@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Refund Credit Note</h3>
                <div class="text-muted small">
                    {{ $creditNote->credit_note_number ?: 'CN-' . $creditNote->id }} •
                    {{ $creditNote->company?->name ?? '—' }}
                </div>
            </div>
            <a href="{{ tenant_route('tenant.credit-notes.show', $creditNote) }}" class="btn btn-light">Back</a>
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

        @php
            $allocated =
                (float) ($creditNote->allocations_sum_amount ?? ($creditNote->allocations?->sum('amount') ?? 0));
            $refunded = (float) ($creditNote->refunds_sum_amount ?? 0);
            $available = (float) $creditNote->amount - $allocated - $refunded;
        @endphp

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <div class="text-muted small">Available to refund</div>
                    <div class="fw-semibold">R {{ number_format($available, 2) }}</div>
                </div>

                <form method="POST" action="{{ tenant_route('tenant.credit_notes.refund.store', $creditNote) }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Refund Date</label>
                            <input type="date" class="form-control" name="refunded_at"
                                value="{{ old('refunded_at', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Method</label>
                            <select class="form-select" name="method" required>
                                @foreach (['eft', 'cash', 'card', 'other'] as $m)
                                    <option value="{{ $m }}" @selected(old('method', 'eft') === $m)>{{ strtoupper($m) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="amount"
                                value="{{ old('amount', number_format(max($available, 0), 2, '.', '')) }}" required>
                            <div class="form-text">Backend should validate this does not exceed available.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Note (optional)</label>
                            <input type="text" class="form-control" name="note" value="{{ old('note') }}">
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-danger" type="submit">Record Refund</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.credit-notes.show', $creditNote) }}">Cancel</a>
                    </div>
                </form>

            </div>
        </div>

    </div>
@endsection
