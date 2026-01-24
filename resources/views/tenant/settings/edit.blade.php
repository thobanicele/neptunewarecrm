@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Workspace Settings</h1>
            <a class="btn btn-outline-secondary" href="{{ tenant_route('tenant.dashboard', ['tenant' => $tenant]) }}">Back</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Quick links --}}
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <div class="fw-semibold">Quick links</div>
                    <div class="text-muted small">Manage workspace configuration and related setup.</div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ tenant_route('tenant.tax-types.index') }}"
                        class="btn btn-outline-primary {{ request()->routeIs('tenant.tax-types.*') ? 'active' : '' }}">
                        <i class="fe fe-percent me-2"></i> Tax Types (VAT)
                    </a>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.settings.update', ['tenant' => $tenant]) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Workspace name</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $tenant->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Subdomain</label>
                            <div class="input-group">
                                <span class="input-group-text">/t/</span>
                                <input type="text" name="subdomain" class="form-control"
                                    value="{{ old('subdomain', $tenant->subdomain) }}" required>
                            </div>
                            <div class="form-text">
                                Lowercase letters/numbers + hyphens only. Changing this changes your workspace URL.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">

                            @if ($tenant->logo_path)
                                <div class="mt-2 d-flex align-items-center gap-3">
                                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Logo"
                                        style="height:48px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" name="remove_logo"
                                            id="remove_logo">
                                        <label class="form-check-label" for="remove_logo">Remove logo</label>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- ✅ NEW: Bank details --}}
                        <div class="col-md-12">
                            <label class="form-label">Bank Details (for Quotes / Invoices)</label>
                            <textarea name="bank_details" class="form-control" rows="6"
                                placeholder="Account Name&#10;Bank&#10;Account Number&#10;Branch Code&#10;Swift (optional)&#10;Reference">{{ old('bank_details', $tenant->bank_details) }}</textarea>
                            <div class="form-text">This prints on your Quote PDF under “Bank Details”.</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary">Save changes</button>
                    </div>

                </form>
            </div>
        </div>

    </div>
@endsection
