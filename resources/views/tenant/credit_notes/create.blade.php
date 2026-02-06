@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">New Credit Note</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>
            <a href="{{ url()->previous() }}" class="btn btn-light">Back</a>
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

        <form method="POST" action="{{ tenant_route('credit-notes.store') }}">
            @csrf

            <div class="card">
                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-12 col-lg-7">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select @error('company_id') is-invalid @enderror" name="company_id"
                                id="companySelect" required>
                                <option value="">— select company —</option>
                                @foreach ($companies ?? collect() as $c)
                                    <option value="{{ data_get($c, 'id') }}" @selected((string) old('company_id', $prefillCompanyId ?? null) === (string) data_get($c, 'id'))>
                                        {{ data_get($c, 'name') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Credit will auto-allocate unless you pick a specific invoice below.</div>
                        </div>

                        <div class="col-12 col-lg-5">
                            <label class="form-label">Contact (optional)</label>
                            <select class="form-select" name="contact_id">
                                <option value="">— none —</option>
                                @foreach ($contacts ?? collect() as $p)
                                    <option value="{{ data_get($p, 'id') }}" @selected((string) old('contact_id', $prefillContactId ?? null) === (string) data_get($p, 'id'))>
                                        {{ data_get($p, 'name') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Credit Note Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('issued_at') is-invalid @enderror"
                                name="issued_at" value="{{ old('issued_at', now()->toDateString()) }}" required>
                            @error('issued_at')
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
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Apply to specific invoice (optional)</label>
                            <select class="form-select" name="apply_invoice_id">
                                <option value="">— auto allocate —</option>
                                @foreach ($invoices ?? collect() as $inv)
                                    <option value="{{ data_get($inv, 'id') }}" @selected((string) old('apply_invoice_id', $prefillInvoiceId ?? null) === (string) data_get($inv, 'id'))>
                                        INV #{{ data_get($inv, 'id') }} • {{ data_get($inv, 'issued_at') }} • R
                                        {{ number_format((float) data_get($inv, 'total', 0), 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Reason</label>
                            <input type="text" class="form-control" name="reason" value="{{ old('reason') }}"
                                placeholder="Return / discount / correction">
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" name="notes" value="{{ old('notes') }}">
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary" type="submit">Create Credit Note</button>
                        <a class="btn btn-light" href="{{ url()->previous() }}">Cancel</a>
                    </div>

                </div>
            </div>

        </form>

    </div>
@endsection
