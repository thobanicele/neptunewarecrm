@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <div class="mb-1">
                    <a class="text-decoration-none small text-muted"
                        href="{{ tenant_route('tenant.settings.index', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                        <i class="fa-solid fa-gear me-1"></i> Settings
                    </a>
                    <span class="text-muted small mx-2">/</span>
                    <span class="text-muted small">Organization</span>
                </div>
                <h1 class="h3 mb-0">Profile</h1>
                <div class="text-muted small">Workspace details for PDFs: address, VAT, registration, bank details.</div>
            </div>

            <div class="d-flex gap-2">
                {{-- Branding has its own page --}}
                <a class="btn btn-outline-secondary"
                    href="{{ tenant_route('tenant.settings.branding', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                    <i class="fa-solid fa-palette me-2"></i> Branding
                </a>

                <a class="btn btn-outline-secondary"
                    href="{{ tenant_route('tenant.settings.index', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                    Back to Settings
                </a>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-success">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            @push('scripts')
                <script>
                    setTimeout(() => {
                        const el = document.getElementById('flash-success');
                        if (!el) return;
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    }, 3500);
                </script>
            @endpush
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-semibold mb-2">Please fix the following:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

        {{-- Profile form (PDF/company info only) --}}
        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <div class="fw-semibold">Company details for PDFs</div>
                        <div class="text-muted small">These details print on Quotes, Invoices and Statements.</div>
                    </div>
                    <a class="btn btn-outline-secondary"
                        href="{{ tenant_route('tenant.settings.branding', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                        <i class="fa-solid fa-palette me-2"></i> Go to Branding
                    </a>
                </div>

                <form method="POST" action="{{ tenant_route('tenant.settings.update', ['tenant' => $tenant]) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="_section" value="profile">

                    <div class="row g-3">

                        {{-- Address --}}
                        <div class="col-12">
                            <label class="form-label">Company Address</label>
                            <textarea name="company_address" class="form-control" rows="5"
                                placeholder="Street&#10;Suburb&#10;City&#10;Province&#10;Postal Code&#10;Country">{{ old('company_address', $tenant->company_address ?? '') }}</textarea>
                            <div class="form-text">Printed on PDFs (header/footer/company block).</div>
                        </div>

                        {{-- VAT + Registration --}}
                        <div class="col-md-6">
                            <label class="form-label">VAT Number</label>
                            <input type="text" name="vat_number" class="form-control"
                                value="{{ old('vat_number', $tenant->vat_number ?? '') }}" placeholder="e.g. 4123456789">
                            <div class="form-text">Optional. Shows on PDFs where required.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Company Registration Number</label>
                            <input type="text" name="registration_number" class="form-control"
                                value="{{ old('registration_number', $tenant->registration_number ?? '') }}"
                                placeholder="e.g. 2019/123456/07">
                            <div class="form-text">Optional. Shows on PDFs & legal docs.</div>
                        </div>

                        {{-- Bank details --}}
                        <div class="col-12">
                            <label class="form-label">Bank Details</label>
                            <textarea name="bank_details" class="form-control" rows="6"
                                placeholder="Account Name&#10;Bank&#10;Account Number&#10;Branch Code&#10;Swift (optional)&#10;Reference">{{ old('bank_details', $tenant->bank_details ?? '') }}</textarea>
                            <div class="form-text">Printed on Quote PDF under “Bank Details”.</div>
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
