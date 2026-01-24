@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Tax Types</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>
            <a href="{{ tenant_route('tenant.tax-types.create') }}" class="btn btn-primary">+ New Tax Type</a>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
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
                                <th>Name</th>
                                <th class="text-end">Rate</th>
                                <th>Status</th>
                                <th>Default</th>
                                <th class="text-end" style="width: 300px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($taxTypes as $t)
                                <tr>
                                    <td class="fw-semibold">{{ $t->name }}</td>
                                    <td class="text-end">{{ number_format((float) $t->rate, 2) }}%</td>
                                    <td>
                                        @if ($t->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($t->is_default)
                                            <span class="badge bg-primary-subtle text-primary">Default</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        {{-- Resource route uses {tax_type} --}}
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.tax-types.edit', ['tax_type' => $t->id]) }}">
                                            Edit
                                        </a>

                                        {{-- Custom route uses {taxType} --}}
                                        <form class="d-inline" method="POST"
                                            action="{{ tenant_route('tenant.tax-types.toggle', ['taxType' => $t->id]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">
                                                {{ $t->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        @if (!$t->is_default)
                                            <form class="d-inline" method="POST"
                                                action="{{ tenant_route('tenant.tax-types.default', ['taxType' => $t->id]) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success" type="submit"
                                                    {{ !$t->is_active ? 'disabled' : '' }}>
                                                    Make Default
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">No tax types yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Default VAT is used for new quote rows. Each quote line stores its own selected VAT type.
                </div>
            </div>
        </div>
    </div>
@endsection
