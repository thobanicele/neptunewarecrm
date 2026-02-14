@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Products</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.products.create') }}" class="btn btn-primary">+ New Product</a>

                @php $qs = http_build_query(request()->query()); @endphp

                @if ($canExport)
                    <a href="{{ tenant_route('tenant.products.export') }}{{ $qs ? '?' . $qs : '' }}"
                        class="btn btn-outline-secondary">
                        Export (Excel)
                    </a>
                @else
                    <a href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain]) }}"
                        class="btn btn-outline-secondary">
                        Export (Excel) <span class="badge bg-warning text-dark ms-1">PREMIUM</span>
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-body">

                {{-- Filters --}}
                <form class="row g-2 mb-3" method="GET" action="{{ tenant_route('tenant.products.index') }}">
                    <div class="col-12 col-lg-4">
                        <input class="form-control" name="q" value="{{ $q ?? '' }}"
                            placeholder="Search SKU or Name...">
                    </div>

                    <div class="col-6 col-lg-2">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All status</option>
                            <option value="active" @selected(($status ?? '') === 'active')>Active</option>
                            <option value="inactive" @selected(($status ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <select class="form-select" name="unit" onchange="this.form.submit()">
                            <option value="">All units</option>
                            @foreach ($units ?? collect() as $u)
                                <option value="{{ $u }}" @selected(($unit ?? '') === $u)>{{ $u }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-lg-auto d-flex gap-2">
                        <button class="btn btn-outline-secondary" type="submit">Filter</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.products.index') }}">Reset</a>
                    </div>
                </form>

                {{-- Results summary --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small">
                        Showing <b>{{ $products->firstItem() ?? 0 }}</b>–<b>{{ $products->lastItem() ?? 0 }}</b>
                        of <b>{{ $products->total() }}</b> products
                    </div>

                    @if (($q ?? '') !== '' || ($status ?? '') !== '' || ($unit ?? '') !== '')
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.products.index') }}">
                            Clear filters
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <x-index.th-sort label="SKU" key="sku" :sort="$sort" :dir="$dir" />
                                <x-index.th-sort label="Name" key="name" :sort="$sort" :dir="$dir" />
                                <x-index.th-sort label="Rate" key="unit_rate" class="text-end" :sort="$sort"
                                    :dir="$dir" />
                                <x-index.th-sort label="Unit" key="unit" :sort="$sort" :dir="$dir" />
                                <x-index.th-sort label="Status" key="is_active" :sort="$sort" :dir="$dir" />
                                <th class="text-end" style="width: 180px;">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($products as $p)
                                <tr>
                                    <td class="fw-semibold">{{ $p->sku }}</td>

                                    <td>
                                        <a href="{{ tenant_route('tenant.products.show', ['product' => $p->id]) }}"
                                            class="text-decoration-none">
                                            {{ $p->name }}
                                        </a>

                                        @if ($p->description)
                                            <div class="text-muted small">{{ $p->description }}</div>
                                        @endif
                                    </td>

                                    <td class="text-end">R{{ number_format((float) $p->unit_rate, 2) }}</td>
                                    <td>{{ $p->unit ?? '—' }}</td>

                                    <td>
                                        @if ($p->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ tenant_route('tenant.products.show', ['product' => $p->id]) }}">
                                                View
                                            </a>

                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ tenant_route('tenant.products.edit', ['product' => $p->id]) }}">
                                                        Edit
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <form method="POST"
                                                        action="{{ tenant_route('tenant.products.destroy', ['product' => $p->id]) }}"
                                                        onsubmit="return confirm('Delete this product?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger" type="submit">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">No products found.</td>
                                </tr>
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
