@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Credit Notes</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                @can('create', \App\Models\CreditNote::class)
                    <a href="{{ tenant_route('tenant.credit-notes.create') }}" class="btn btn-primary">New Credit Note</a>
                @endcan

                @php $qs = http_build_query(request()->query()); @endphp

                @if (!empty($canExport) && $canExport)
                    @if (auth()->user()->can('export.run'))
                        <a href="{{ tenant_route('tenant.credit-notes.export') }}{{ $qs ? '?' . $qs : '' }}"
                            class="btn btn-outline-secondary">
                            Export (Excel)
                        </a>
                    @endif
                @else
                    @if (auth()->user()->can('export.run'))
                        <a href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain]) }}"
                            class="btn btn-outline-secondary">
                            Export (Excel) <span class="badge bg-warning text-dark ms-1">PREMIUM</span>
                        </a>
                    @endif
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
                        bootstrap.Alert.getOrCreateInstance(el).close();
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
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    }, 4000);
                </script>
            @endpush
        @endif

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end" id="creditNotesFilterForm"
                    action="{{ tenant_route('tenant.credit-notes.index') }}">

                    <div class="col-12 col-md-4">
                        <label class="form-label">Search</label>
                        <input class="form-control" name="q" id="qInput" value="{{ $q ?? '' }}"
                            placeholder="Credit note #, customer...">
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

                    <div class="col-6 col-md-3">
                        <label class="form-label">State</label>
                        <select class="form-select" name="state" onchange="this.form.submit()">
                            <option value="">All</option>
                            <option value="available" @selected(($state ?? '') === 'available')>Available</option>
                            <option value="allocated" @selected(($state ?? '') === 'allocated')>Fully Allocated</option>
                            <option value="refunded" @selected(($state ?? '') === 'refunded')>Refunded</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                        <a class="btn btn-light w-100" href="{{ tenant_route('tenant.credit-notes.index') }}">Reset</a>
                    </div>

                    {{-- Summary / Clear --}}
                    <div class="col-12">
                        @php
                            $hasQ = !empty(trim((string) ($q ?? '')));
                            $hasCompany = !empty(trim((string) ($company_id ?? '')));
                            $hasState = !empty(trim((string) ($state ?? '')));
                        @endphp

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted small">
                                Showing
                                <b>{{ $creditNotes->firstItem() ?? 0 }}</b>–<b>{{ $creditNotes->lastItem() ?? 0 }}</b>
                                of <b>{{ $creditNotes->total() }}</b> credit notes
                            </div>

                            @if ($hasQ || $hasCompany || $hasState)
                                <a class="small text-decoration-none"
                                    href="{{ tenant_route('tenant.credit-notes.index') }}">
                                    Clear filters
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
                            <x-index.th-sort label="Date" key="issued_at" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Credit Note #" key="credit_note_number" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Customer" key="company" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Amount" key="amount" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Allocated" key="allocated" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Refunded" key="refunded" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Available" key="available" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($creditNotes as $cn)
                            @php
                                $allocated = (float) ($cn->allocated_total ?? 0);
                                $refunded = (float) ($cn->refunded_total ?? 0);
                                $available =
                                    (float) ($cn->available_total ??
                                        max(0, (float) $cn->amount - $allocated - $refunded));
                            @endphp

                            <tr>
                                <td class="text-muted">{{ optional($cn->issued_at)->format('d/m/Y') ?? '—' }}</td>

                                <td class="fw-semibold">
                                    <a class="text-decoration-none"
                                        href="{{ tenant_route('tenant.credit-notes.show', $cn) }}">
                                        {{ $cn->credit_note_number ?: 'CN-' . $cn->id }}
                                    </a>
                                </td>

                                <td>
                                    @if ($cn->company)
                                        <a href="{{ tenant_route('tenant.companies.show', $cn->company) }}"
                                            class="text-decoration-none">
                                            {{ $cn->company->name }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td class="text-end">R {{ number_format((float) $cn->amount, 2) }}</td>
                                <td class="text-end">R {{ number_format($allocated, 2) }}</td>
                                <td class="text-end">R {{ number_format($refunded, 2) }}</td>
                                <td class="text-end fw-semibold">R {{ number_format($available, 2) }}</td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.credit-notes.show', $cn) }}">
                                            View
                                        </a>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('refund', $cn)
                                                @if ($available > 0)
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ tenant_route('tenant.credit_notes.refund.create', $cn) }}">
                                                            Refund
                                                        </a>
                                                    </li>
                                                @endif
                                            @endcan

                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ tenant_route('tenant.credit-notes.pdf.stream', ['credit_note' => $cn->id]) }}">
                                                    View PDF
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item"
                                                    href="{{ tenant_route('tenant.credit-notes.pdf.download', ['credit_note' => $cn->id]) }}">
                                                    Download PDF
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted px-3 py-4">No credit notes yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $creditNotes->links() }}
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // Debounced autosubmit for search (optional, matches your other indexes)
        (function() {
            const input = document.getElementById('qInput');
            if (!input) return;

            let t = null;
            input.addEventListener('input', function() {
                clearTimeout(t);
                t = setTimeout(() => {
                    const url = new URL(window.location.href);
                    url.searchParams.set('q', input.value || '');
                    url.searchParams.set('page', '1');
                    window.location.href = url.toString();
                }, 450);
            });
        })();
    </script>
@endpush
