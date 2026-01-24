@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0">Products</h3>
            <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
        </div>
        <a href="{{ tenant_route('tenant.products.create') }}" class="btn btn-primary">+ New Product</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form class="row g-2 mb-3" method="GET" action="{{ tenant_route('tenant.products.index') }}">
                <div class="col-12 col-lg-4">
                    <input class="form-control" name="q" value="{{ $q }}" placeholder="Search SKU or Name...">
                </div>
                <div class="col-12 col-lg-auto">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                    <a class="btn btn-light" href="{{ tenant_route('tenant.products.index') }}">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th class="text-end">Rate</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $p)
                            <tr>
                                <td class="fw-semibold">{{ $p->sku }}</td>
                                <td>
                                    <a href="{{ tenant_route('tenant.products.show', ['product' => $p->id]) }}" class="text-decoration-none">
                                        {{ $p->name }}
                                    </a>
                                    @if($p->description)
                                        <div class="text-muted small">{{ $p->description }}</div>
                                    @endif
                                </td>
                                <td class="text-end">R{{ number_format((float)$p->unit_rate, 2) }}</td>
                                <td>{{ $p->unit ?? '—' }}</td>
                                <td>
                                    @if($p->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="{{ tenant_route('tenant.products.edit', ['product' => $p->id]) }}">Edit</a>

                                    <form class="d-inline" method="POST"
                                          action="{{ tenant_route('tenant.products.destroy', ['product' => $p->id]) }}"
                                          onsubmit="return confirm('Delete this product?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No products found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
