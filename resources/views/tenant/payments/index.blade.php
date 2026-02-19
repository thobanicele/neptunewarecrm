@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Payments</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                @can('create', \App\Models\Payment::class)
                    <a href="{{ tenant_route('tenant.payments.create') }}" class="btn btn-primary">Record Payment</a>
                @endcan
                @php $qs = http_build_query(request()->query()); @endphp

                @if (($canExport ?? false) || tenant_feature($tenant, 'export'))
                    @can('export', \App\Models\Payment::class)
                        <a href="{{ tenant_route('tenant.payments.export') }}{{ $qs ? '?' . $qs : '' }}"
                            class="btn btn-outline-secondary">
                            Export (Excel)
                        </a>
                    @endcan
                @else
                    @can('export', \App\Models\Payment::class)
                        <a href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain]) }}"
                            class="btn btn-outline-secondary">
                            Export (Excel) <span class="badge bg-warning text-dark ms-1">PREMIUM</span>
                        </a>
                    @endcan
                @endif
            </div>
        </div>

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
                <form method="GET" class="row g-2 align-items-end" id="paymentsFilterForm">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Search</label>
                        <input class="form-control" name="q" id="qInput" value="{{ $q ?? '' }}"
                            placeholder="Customer, reference, method...">
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label">Customer</label>
                        <select class="form-select" name="company_id" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach ($companies ?? collect() as $c)
                                <option value="{{ $c->id }}" @selected((string) ($company_id ?? '') === (string) $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Method</label>
                        <select class="form-select" name="method" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach ($methods ?? collect() as $m)
                                <option value="{{ $m }}" @selected((string) ($method ?? '') === (string) $m)>
                                    {{ $m }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">State</label>
                        <select class="form-select" name="state" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="allocated" @selected(($state ?? '') === 'allocated')>Fully allocated</option>
                            <option value="unallocated" @selected(($state ?? '') === 'unallocated')>Has unallocated</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-1 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted small">
                                Showing <b>{{ $payments->firstItem() ?? 0 }}</b>–<b>{{ $payments->lastItem() ?? 0 }}</b>
                                of <b>{{ $payments->total() }}</b> payments
                            </div>

                            @if (($q ?? '') !== '' || ($company_id ?? '') !== '' || ($method ?? '') !== '' || ($state ?? '') !== '')
                                <a class="small text-decoration-none" href="{{ tenant_route('tenant.payments.index') }}">
                                    Clear filters
                                </a>
                            @else
                                <a class="small text-decoration-none" href="{{ tenant_route('tenant.payments.index') }}">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <x-index.th-sort label="Date" key="paid_at" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Customer" key="company" :sort="$sort" :dir="$dir"
                                defaultDir="asc" />
                            <x-index.th-sort label="Method" key="method" :sort="$sort" :dir="$dir"
                                defaultDir="asc" />
                            <x-index.th-sort label="Reference" key="reference" :sort="$sort" :dir="$dir"
                                defaultDir="asc" />
                            <x-index.th-sort label="Amount" key="amount" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Allocated" key="allocated" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Unallocated" key="unallocated" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($payments as $p)
                            @php
                                $allocated = (float) ($p->allocated_total ?? $p->allocatedTotal());
                                $unallocated =
                                    (float) ($p->unallocated_total ?? max(0, (float) $p->amount - $allocated));
                            @endphp

                            <tr>
                                <td class="text-muted">
                                    {{ optional($p->paid_at)->format('Y-m-d') ?? '—' }}
                                </td>

                                <td>
                                    <a href="{{ tenant_route('tenant.companies.show', $p->company) }}"
                                        class="text-decoration-none">
                                        {{ $p->company->name ?? '—' }}
                                    </a>
                                </td>

                                <td class="text-muted">{{ $p->method ?? '—' }}</td>
                                <td class="text-muted">{{ $p->reference ?? '—' }}</td>

                                <td class="text-end">R {{ number_format((float) $p->amount, 2) }}</td>
                                <td class="text-end">R {{ number_format($allocated, 2) }}</td>
                                <td class="text-end fw-semibold">R {{ number_format(max(0, $unallocated), 2) }}</td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.payments.show', $p) }}">
                                            View
                                        </a>

                                        <a class="btn btn-sm btn-outline-secondary {{ $unallocated <= 0.009 ? 'disabled' : '' }}"
                                            href="{{ tenant_route('tenant.payments.allocate.form', $p) }}">
                                            Allocate
                                        </a>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">More</span>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ tenant_route('tenant.payments.allocate.form', $p) }}">
                                                    Allocate / Re-allocate
                                                </a>
                                            </li>

                                            @if ($p->invoice_id)
                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ tenant_route('tenant.invoices.show', ['invoice' => $p->invoice_id]) }}">
                                                        View linked invoice
                                                    </a>
                                                </li>
                                            @endif

                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>

                                            @if ($allocated > 0.009)
                                                <li>
                                                    @can('update', $p)
                                                        <form method="POST"
                                                            action="{{ tenant_route('tenant.payments.allocations.reset', $p) }}"
                                                            onsubmit="return confirm('Remove ALL allocations for this payment?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="dropdown-item text-danger" type="submit">
                                                                Reset allocations
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </li>
                                            @else
                                                <li><span class="dropdown-item text-muted">No allocations to reset</span>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted px-3 py-4">No payments yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $payments->links() }}
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // ✅ Debounced autosubmit for search input
        (function() {
            const form = document.getElementById('paymentsFilterForm');
            const input = document.getElementById('qInput');
            if (!form || !input) return;

            let t = null;
            input.addEventListener('input', function() {
                clearTimeout(t);
                t = setTimeout(() => {
                    // reset page when searching
                    const url = new URL(window.location.href);
                    url.searchParams.set('q', input.value || '');
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                }, 450);
            });
        })();
    </script>
@endpush
