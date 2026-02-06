@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Refund Credit Note #{{ $creditNote->id }}</h3>
                <div class="text-muted small">{{ $creditNote->company?->name }}</div>
            </div>
            <a href="{{ tenant_route('credit-notes.show', ['credit_note' => $creditNote->id]) }}"
                class="btn btn-light">Back</a>
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

        <div class="row g-3">
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Available to refund</div>
                        <div class="display-6 mb-0">R {{ number_format((float) $remaining, 2) }}</div>
                        <div class="text-muted small mt-1">You can only refund up to the remaining credit.</div>

                        <hr>

                        <div class="text-muted small">Credit Note Amount</div>
                        <div class="fw-semibold">R {{ number_format((float) $creditNote->amount, 2) }}</div>

                        @if (!empty($creditNote->reason))
                            <div class="text-muted small mt-2">Reason</div>
                            <div class="fw-semibold">{{ $creditNote->reason }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <form method="POST"
                    action="{{ tenant_route('tenant.credit_notes.refund.store', ['creditNote' => $creditNote->id]) }}">
                    @csrf

                    <div class="card">
                        <div class="card-body">

                            <div class="row g-3">
                                <div class="col-12 col-lg-4">
                                    <label class="form-label">Refund Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('refunded_at') is-invalid @enderror"
                                        name="refunded_at" value="{{ old('refunded_at', now()->toDateString()) }}" required>
                                    @error('refunded_at')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01"
                                        class="form-control @error('amount') is-invalid @enderror" name="amount"
                                        value="{{ old('amount') }}" placeholder="0.00" required>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Max: R {{ number_format((float) $remaining, 2) }}</div>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label">Method</label>
                                    <input type="text" class="form-control" name="method" value="{{ old('method') }}"
                                        placeholder="EFT / Cash / Card">
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label">Reference</label>
                                    <input type="text" class="form-control" name="reference"
                                        value="{{ old('reference') }}" placeholder="Bank ref / receipt #">
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label class="form-label">Notes</label>
                                    <input type="text" class="form-control" name="notes" value="{{ old('notes') }}">
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button class="btn btn-primary" type="submit">Record Refund</button>
                                <a class="btn btn-light"
                                    href="{{ tenant_route('credit-notes.show', ['credit_note' => $creditNote->id]) }}">Cancel</a>
                            </div>

                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
@endsection
