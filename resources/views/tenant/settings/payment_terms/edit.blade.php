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
                    <a class="text-decoration-none small text-muted"
                        href="{{ tenant_route('tenant.settings.payment_terms.index') }}">
                        Payment Terms
                    </a>
                    <span class="text-muted small mx-2">/</span>
                    <span class="text-muted small">Edit</span>
                </div>

                <h1 class="h3 mb-0">Edit Payment Term</h1>
                <div class="text-muted small">Update this payment term.</div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ tenant_route('tenant.settings.payment_terms.index') }}">
                    Back
                </a>
            </div>
        </div>

        {{-- Flash --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
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

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.settings.payment_terms.update', $paymentTerm) }}">
                    @csrf
                    @method('PUT')

                    @include('tenant.settings.payment_terms._form', ['term' => $paymentTerm])

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">
                            <i class="fa-solid fa-save me-2"></i> Save changes
                        </button>
                        <a class="btn btn-outline-secondary"
                            href="{{ tenant_route('tenant.settings.payment_terms.index') }}">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
