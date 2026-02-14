@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-0 d-flex align-items-center gap-2">
                    Contacts
                    <span class="badge bg-light text-dark">
                        Total: {{ method_exists($contacts, 'total') ? $contacts->total() : $contacts->count() }}
                    </span>
                </h1>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                @php $qs = http_build_query(request()->query()); @endphp

                @if (!empty($canExport) && $canExport)
                    <a href="{{ tenant_route('tenant.contacts.export') }}{{ $qs ? '?' . $qs : '' }}"
                        class="btn btn-outline-secondary">
                        Export (Excel)
                    </a>
                @else
                    <a href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain]) }}"
                        class="btn btn-outline-secondary">
                        Export (Excel) <span class="badge bg-warning text-dark ms-1">PREMIUM</span>
                    </a>
                @endif

                <a href="{{ tenant_route('tenant.contacts.create') }}" class="btn btn-primary">+ Add Contact</a>
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
        <div class="card">
            <div class="card-body">
                <form class="row g-2 align-items-end" method="GET" action="{{ tenant_route('tenant.contacts.index') }}"
                    id="contactsFilterForm">

                    <div class="col-12 col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="q" id="qInput"
                            value="{{ $q ?? '' }}" placeholder="Name, email, phone, company...">
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Stage</label>
                        <select class="form-select" name="stage" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach ($stages ?? [] as $s)
                                <option value="{{ $s }}" @selected(($stage ?? '') === (string) $s)>
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                        <a class="btn btn-light w-100" href="{{ tenant_route('tenant.contacts.index') }}">Reset</a>
                    </div>

                    {{-- Counter + clear filters --}}
                    <div class="col-12">
                        @php
                            $hasQ = !empty(trim((string) ($q ?? '')));
                            $hasStage = !empty(trim((string) ($stage ?? '')));
                        @endphp

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="text-muted small">
                                Showing <b>{{ $contacts->firstItem() ?? 0 }}</b>–<b>{{ $contacts->lastItem() ?? 0 }}</b>
                                of <b>{{ $contacts->total() }}</b> contacts
                            </div>

                            @if ($hasQ || $hasStage)
                                <a class="small text-decoration-none" href="{{ tenant_route('tenant.contacts.index') }}">
                                    Clear filters
                                </a>
                            @endif
                        </div>
                    </div>

                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card mt-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 table-hover table-sm align-middle">
                        <thead>
                            <tr>
                                <x-index.th-sort label="Name" key="name" :sort="$sort" :dir="$dir"
                                    defaultDir="asc" />
                                <x-index.th-sort label="Email" key="email" :sort="$sort" :dir="$dir"
                                    defaultDir="asc" />
                                <x-index.th-sort label="Phone" key="phone" :sort="$sort" :dir="$dir"
                                    defaultDir="asc" />
                                <x-index.th-sort label="Company" key="company" :sort="$sort" :dir="$dir"
                                    defaultDir="asc" />
                                <x-index.th-sort label="Stage" key="lifecycle_stage" :sort="$sort" :dir="$dir"
                                    defaultDir="asc" />
                                <x-index.th-sort label="Updated" key="updated_at" :sort="$sort" :dir="$dir" />
                                <th class="text-end" style="width: 220px;">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($contacts as $c)
                                <tr>
                                    <td class="fw-semibold">{{ $c->name }}</td>
                                    <td class="text-muted">{{ $c->email ?? '—' }}</td>
                                    <td class="text-muted">{{ $c->phone ?? '—' }}</td>
                                    <td>
                                        @if ($c->company)
                                            <a class="text-decoration-none"
                                                href="{{ tenant_route('tenant.companies.show', $c->company) }}">
                                                {{ $c->company->name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ ucwords(str_replace('_', ' ', (string) $c->lifecycle_stage)) }}
                                        </span>
                                    </td>
                                    <td class="text-muted">
                                        {{ optional($c->updated_at)->format('d/m/Y') ?? '—' }}
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ tenant_route('tenant.contacts.show', $c) }}">
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
                                                        href="{{ tenant_route('tenant.contacts.edit', $c) }}">
                                                        Edit
                                                    </a>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <form method="POST"
                                                        action="{{ tenant_route('tenant.contacts.destroy', $c) }}"
                                                        onsubmit="return confirm('Delete this contact?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
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
                                    <td colspan="7" class="text-center py-4 text-muted">No contacts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        Showing {{ $contacts->firstItem() ?? 0 }}–{{ $contacts->lastItem() ?? 0 }} of
                        {{ $contacts->total() }}
                        <span class="ms-2 badge bg-light text-dark">
                            Page {{ $contacts->currentPage() }} / {{ $contacts->lastPage() }}
                        </span>
                    </div>
                    <div>
                        {{ $contacts->links() }}
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // ✅ Debounced autosubmit for search
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
