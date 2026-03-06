@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Payment Terms</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>
            <a href="{{ tenant_route('tenant.settings.payment_terms.create', ['tenant' => $tenant->subdomain]) }}"
                class="btn btn-primary">
                + New Payment Term
            </a>
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Term Name</th>
                                <th class="text-end" style="width: 180px;">Number of Days</th>
                                <th style="width: 160px;">Status</th>
                                <th class="text-end" style="width: 420px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($terms as $t)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $t->name }}
                                        @if (($t->companies_count ?? 0) > 0)
                                            <div class="text-muted small">
                                                Used by {{ $t->companies_count }}
                                                compan{{ $t->companies_count == 1 ? 'y' : 'ies' }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-end">{{ $t->days }}</td>

                                    <td>
                                        @if ($t->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.settings.payment_terms.edit', ['tenant' => $tenant->subdomain, 'paymentTerm' => $t->id]) }}">
                                            Edit
                                        </a>

                                        <form class="d-inline" method="POST"
                                            action="{{ tenant_route('tenant.settings.payment_terms.toggle', ['tenant' => $tenant->subdomain, 'paymentTerm' => $t->id]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">
                                                {{ $t->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        @if (($t->companies_count ?? 0) == 0)
                                            <form class="d-inline" method="POST"
                                                action="{{ tenant_route('tenant.settings.payment_terms.destroy', ['tenant' => $tenant->subdomain, 'paymentTerm' => $t->id]) }}"
                                                onsubmit="return confirm('Delete this payment term?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    Delete
                                                </button>
                                            </form>
                                        @else
                                            <button class="btn btn-sm btn-outline-danger" type="button" disabled
                                                title="This term is assigned to companies. Deactivate it instead.">
                                                Delete
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">No payment terms yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Tip: Use <span class="fw-semibold">0</span> days for “Due on Receipt”. Number of days must be unique per
                    tenant.
                </div>
            </div>
        </div>
    </div>
@endsection
