@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Quotes</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.quotes.create') }}" class="btn btn-primary">+ New Quote</a>

                @php $qs = http_build_query(request()->query()); @endphp

                @if (tenant_feature($tenant, 'export'))
                    <a href="{{ tenant_route('tenant.quotes.export') }}{{ $qs ? '?' . $qs : '' }}"
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

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Search</label>
                        <input class="form-control" name="q" value="{{ $q ?? '' }}"
                            placeholder="Quote #, deal, company, contact...">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach (['draft', 'sent', 'accepted', 'declined', 'expired'] as $s)
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
                        <a class="btn btn-light w-100" href="{{ tenant_route('tenant.quotes.index') }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $pill = fn($status) => match (strtolower((string) $status)) {
                'draft' => 'secondary',
                'sent' => 'warning',
                'accepted' => 'success',
                'declined' => 'danger',
                'expired' => 'dark',
                default => 'light',
            };
        @endphp

        <div class="card">
            <div class="card-body pb-0">
                {{-- Results summary --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small">
                        Showing <b>{{ $items->firstItem() ?? 0 }}</b>–<b>{{ $items->lastItem() ?? 0 }}</b>
                        of <b>{{ $items->total() }}</b> quotes
                    </div>

                    @if (($q ?? '') !== '' || ($status ?? '') !== '' || ($sales_person_user_id ?? '') !== '')
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.quotes.index') }}">
                            Clear filters
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <x-index.th-sort label="Quote" key="quote_number" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Company" key="company" :sort="$sort" :dir="$dir" />
                            <th>Contact</th>
                            <th>Deal</th>
                            <x-index.th-sort label="Status" key="status" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Sub Total" key="subtotal" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Total" key="total" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Quoted Date" key="created_at" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Sales Person" key="sales_person" :sort="$sort"
                                :dir="$dir" />
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($items as $qte)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ tenant_route('tenant.quotes.show', ['quote' => $qte->id]) }}">
                                        {{ $qte->quote_number }}
                                    </a>
                                </td>

                                <td>
                                    <a href="{{ tenant_route('tenant.companies.show', $qte->company) }}"
                                        class="text-decoration-none">
                                        {{ $qte->company?->name ?? '—' }}
                                    </a>
                                </td>

                                <td>{{ $qte->contact?->name ?? '—' }}</td>

                                <td>
                                    @if ($qte->deal)
                                        {{ $qte->deal->title }}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    <span class="badge rounded-pill text-bg-{{ $pill($qte->status) }}">
                                        {{ strtoupper($qte->status) }}
                                    </span>
                                </td>

                                <td class="text-end">R {{ number_format((float) $qte->subtotal, 2) }}</td>
                                <td class="text-end">R {{ number_format((float) $qte->total, 2) }}</td>
                                <td>{{ $qte->created_at ? $qte->created_at->format('d/m/Y') : '—' }}</td>
                                <td>{{ $qte->salesPerson?->name ?? '—' }}</td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.quotes.show', ['quote' => $qte->id]) }}">
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
                                                    href="{{ tenant_route('tenant.quotes.edit', ['quote' => $qte->id]) }}">
                                                    Edit
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" target="_blank"
                                                    href="{{ tenant_route('tenant.quotes.pdf.stream', ['quote' => $qte->id]) }}">
                                                    PDF
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    No quotes yet. Click <b>+ New Quote</b> to create your first one.
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
