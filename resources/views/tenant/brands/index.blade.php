@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Brands</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                @can('create', \App\Models\Brand::class)
                    <a href="{{ tenant_route('tenant.brands.create') }}" class="btn btn-primary">+ New Brand</a>
                @endcan
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
                <form class="row g-2 mb-3" method="GET" action="{{ tenant_route('tenant.brands.index') }}">
                    <div class="col-12 col-lg-4">
                        <input class="form-control" name="q" value="{{ $q ?? '' }}"
                            placeholder="Search name or slug...">
                    </div>

                    <div class="col-6 col-lg-2">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All status</option>
                            <option value="active" @selected(($status ?? '') === 'active')>Active</option>
                            <option value="inactive" @selected(($status ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <select class="form-select" name="sort" onchange="this.form.submit()">
                            @php
                                $sort = $sort ?? 'name';
                                $dir = $dir ?? 'asc';
                            @endphp
                            <option value="name" @selected($sort === 'name')>Sort: Name</option>
                            <option value="slug" @selected($sort === 'slug')>Sort: Slug</option>
                            <option value="is_active" @selected($sort === 'is_active')>Sort: Status</option>
                            <option value="updated_at" @selected($sort === 'updated_at')>Sort: Updated</option>
                            <option value="created_at" @selected($sort === 'created_at')>Sort: Created</option>
                        </select>
                    </div>

                    <div class="col-6 col-lg-2">
                        <select class="form-select" name="dir" onchange="this.form.submit()">
                            <option value="asc" @selected($dir === 'asc')>Asc</option>
                            <option value="desc" @selected($dir === 'desc')>Desc</option>
                        </select>
                    </div>

                    <div class="col-12 col-lg-auto d-flex gap-2">
                        <button class="btn btn-outline-secondary" type="submit">Filter</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.brands.index') }}">Reset</a>
                    </div>
                </form>

                {{-- Results summary --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small">
                        Showing <b>{{ $brands->firstItem() ?? 0 }}</b>–<b>{{ $brands->lastItem() ?? 0 }}</b>
                        of <b>{{ $brands->total() }}</b> brands
                    </div>

                    @if (($q ?? '') !== '' || ($status ?? '') !== '' || ($sort ?? '') !== '' || ($dir ?? '') !== '')
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.brands.index') }}">
                            Clear filters
                        </a>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <x-index.th-sort label="Name" key="name" :sort="$sort" :dir="$dir" />
                                <x-index.th-sort label="Slug" key="slug" :sort="$sort" :dir="$dir" />
                                <x-index.th-sort label="Status" key="is_active" :sort="$sort" :dir="$dir" />
                                <th class="text-end" style="width: 180px;">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($brands as $b)
                                <tr>
                                    <td class="fw-semibold">{{ $b->name }}</td>

                                    <td class="text-muted">{{ $b->slug }}</td>

                                    <td>
                                        @if ($b->is_active)
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ tenant_route('tenant.brands.edit', ['brand' => $b->id]) }}">
                                                Edit
                                            </a>

                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    @can('update', $b)
                                                        <a class="dropdown-item"
                                                            href="{{ tenant_route('tenant.brands.edit', ['brand' => $b->id]) }}">
                                                            Edit
                                                        </a>
                                                    @endcan
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    @can('delete', $b)
                                                        <form method="POST"
                                                            action="{{ tenant_route('tenant.brands.destroy', ['brand' => $b->id]) }}"
                                                            onsubmit="return confirm('Delete this brand?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item text-danger" type="submit">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">No brands found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $brands->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
