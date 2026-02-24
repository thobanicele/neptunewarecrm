@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Sales Orders</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                @can('create', \App\Models\SalesOrder::class)
                    <a href="{{ tenant_route('tenant.sales-orders.create') }}" class="btn btn-primary">
                        + New Sales Order
                    </a>
                @endcan

                @php $qs = http_build_query(request()->query()); @endphp

                @if (tenant_feature($tenant, 'export'))
                    @can('export', \App\Models\SalesOrder::class)
                        <a href="{{ tenant_route('tenant.sales-orders.export') }}{{ $qs ? '?' . $qs : '' }}"
                            class="btn btn-outline-secondary">
                            Export (Excel)
                        </a>
                    @endcan
                @else
                    @can('export', \App\Models\SalesOrder::class)
                        <a href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain]) }}"
                            class="btn btn-outline-secondary">
                            Export (Excel) <span class="badge bg-warning text-dark ms-1">PREMIUM</span>
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        {{-- Flash messages --}}
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
                        const alert = bootstrap.Alert.getOrCreateInstance(el);
                        alert.close();
                    }, 3500);
                </script>
            @endpush
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="flash-error">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            @push('scripts')
                <script>
                    setTimeout(() => {
                        const el = document.getElementById('flash-error');
                        if (!el) return;
                        const alert = bootstrap.Alert.getOrCreateInstance(el);
                        alert.close();
                    }, 4000);
                </script>
            @endpush
        @endif

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">

                    <div class="col-12 col-md-4">
                        <label class="form-label">Search</label>
                        <input class="form-control" name="q" value="{{ $q ?? '' }}"
                            placeholder="SO #, quote #, deal, company, contact...">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All</option>
                            {{-- Adjust statuses to match your SalesOrder statuses --}}
                            @foreach (['draft', 'issued', 'invoiced', 'cancelled'] as $s)
                                <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ strtoupper($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label">Sales Person</label>
                        <select class="form-select" name="sales_person_user_id" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach ($salesPeople ?? collect() as $u)
                                <option value="{{ $u->id }}" @selected((string) ($sales_person_user_id ?? '') === (string) $u->id)>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                        <a class="btn btn-light w-100" href="{{ tenant_route('tenant.sales-orders.index') }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $pill = fn($status) => match (strtolower((string) $status)) {
                'draft' => 'secondary',
                'issued' => 'warning',
                'invoiced' => 'success',
                'cancelled' => 'danger',
                default => 'light',
            };
        @endphp

        <div class="card">
            <div class="card-body pb-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small">
                        Showing <b>{{ $items->firstItem() ?? 0 }}</b>–<b>{{ $items->lastItem() ?? 0 }}</b>
                        of <b>{{ $items->total() }}</b> sales orders
                    </div>

                    @if (($q ?? '') !== '' || ($status ?? '') !== '' || ($sales_person_user_id ?? '') !== '')
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.sales-orders.index') }}">
                            Clear filters
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            {{-- Use your reusable sort component like you do everywhere else --}}
                            <x-index.th-sort label="Sales Order" key="sales_order_number" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Company" key="company" :sort="$sort" :dir="$dir" />
                            <th>Contact</th>
                            <th>Deal</th>
                            <x-index.th-sort label="Status" key="status" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Sub Total" key="subtotal" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Total" key="total" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Created" key="created_at" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Sales Person" key="sales_person" :sort="$sort"
                                :dir="$dir" />
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($items as $so)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ tenant_route('tenant.sales-orders.show', ['salesOrder' => $so->id]) }}">
                                        {{ $so->sales_order_number ?? 'SO-' . $so->id }}
                                    </a>
                                </td>

                                <td>
                                    <a href="{{ $so->company ? tenant_route('tenant.companies.show', $so->company) : '#' }}"
                                        class="text-decoration-none">
                                        {{ $so->company?->name ?? '—' }}
                                    </a>
                                </td>

                                <td>{{ $so->contact?->name ?? '—' }}</td>

                                <td>
                                    @if ($so->deal)
                                        {{ $so->deal->title }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    <span class="badge rounded-pill text-bg-{{ $pill($so->status) }}">
                                        {{ strtoupper($so->status) }}
                                    </span>
                                </td>

                                <td class="text-end">R {{ number_format((float) ($so->subtotal ?? 0), 2) }}</td>
                                <td class="text-end">R {{ number_format((float) ($so->total ?? 0), 2) }}</td>
                                <td>{{ $so->created_at ? $so->created_at->format('d/m/Y') : '—' }}</td>
                                <td>{{ $so->salesPerson?->name ?? '—' }}</td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.sales-orders.show', ['salesOrder' => $so->id]) }}">
                                            View
                                        </a>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('update', $so)
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ tenant_route('tenant.sales-orders.edit', ['salesOrder' => $so->id]) }}">
                                                        Edit
                                                    </a>
                                                </li>
                                            @endcan

                                            {{-- Optional PDF hooks if/when you add them --}}
                                            
                                            @can('pdf', $so)
                                                <li>
                                                    <a class="dropdown-item" target="_blank"
                                                       href="{{ tenant_route('tenant.sales-orders.pdf.stream', ['salesOrder' => $so->id]) }}">
                                                        PDF
                                                    </a>
                                                </li>
                                            @endcan
                                           
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    No sales orders yet.
                                    @can('create', \App\Models\SalesOrder::class)
                                        Click <b>+ New Sales Order</b> to create your first one.
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $items->links() }}
            </div>
        </div>

    </div>
@endsection
