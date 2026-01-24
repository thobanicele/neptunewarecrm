@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">New Tax Type</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>
            <a href="{{ tenant_route('tenant.tax-types.index') }}" class="btn btn-light">Back</a>
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

        <form method="POST" action="{{ tenant_route('tenant.tax-types.store') }}">
            @csrf

            <div class="card">
                <div class="card-header fw-semibold">Tax Type Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Name</label>
                            <input class="form-control" name="name" value="{{ old('name') }}"
                                placeholder="Standard VAT" required>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Rate (%)</label>
                            <input class="form-control" type="number" step="0.01" min="0" max="100"
                                name="rate" value="{{ old('rate', 0) }}" required>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_default" id="is_default"
                                    @checked(old('is_default'))>
                                <label class="form-check-label" for="is_default">Set as Default</label>
                            </div>
                            <div class="form-text">Only one default tax type per tenant.</div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                    @checked(old('is_active', true))>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary" type="submit">Save Tax Type</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.tax-types.index') }}">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
