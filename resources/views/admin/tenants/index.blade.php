@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Tenants</h3>
                <div class="text-muted small">All workspaces registered on NeptuneWare CRM.</div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary" href="{{ route('admin.tenants.index') }}">Refresh</a>
            </div>
        </div>

        {{-- Summary row (minimal like other pages) --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3 bg-white h-100">
                    <div class="text-muted small">Total</div>
                    <div class="fs-4 fw-semibold">{{ $summary['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3 bg-white h-100">
                    <div class="text-muted small">Active (7d)</div>
                    <div class="fs-4 fw-semibold">{{ $summary['active_7d'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3 bg-white h-100">
                    <div class="text-muted small">New (30d)</div>
                    <div class="fs-4 fw-semibold">{{ $summary['new_30d'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="border rounded p-3 bg-white h-100">
                    <div class="text-muted small">Paid</div>
                    <div class="fs-4 fw-semibold">{{ $summary['paid'] ?? 0 }}</div>
                    <div class="text-muted small mt-1">
                        Premium: {{ $summary['premium'] ?? 0 }} • Business: {{ $summary['business'] ?? 0 }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">

                    <div class="col-12 col-md-4">
                        <label class="form-label">Search</label>
                        <input class="form-control" name="q" value="{{ request('q', '') }}"
                            placeholder="Tenant name, subdomain, owner email...">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Plan</label>
                        <select class="form-select" name="plan" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach (['free', 'premium', 'business', 'internal_neptuneware'] as $p)
                                <option value="{{ $p }}" @selected(request('plan', '') === $p)>
                                    {{ strtoupper($p) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach (['active', 'paused', 'suspended', 'inactive'] as $s)
                                <option value="{{ $s }}" @selected(request('status', '') === $s)>
                                    {{ strtoupper($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Active in</label>
                        <select class="form-select" name="active_in" onchange="this.form.submit()">
                            <option value="">Any</option>
                            <option value="1" @selected(request('active_in') === '1')>24h</option>
                            <option value="7" @selected(request('active_in') === '7')>7d</option>
                            <option value="30" @selected(request('active_in') === '30')>30d</option>
                            <option value="90" @selected(request('active_in') === '90')>90d</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Per page</label>
                        <select class="form-select" name="per_page" onchange="this.form.submit()">
                            @foreach ([15, 30, 50, 100] as $n)
                                <option value="{{ $n }}" @selected((int) request('per_page', 30) === (int) $n)>
                                    {{ $n }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Registered From</label>
                        <input type="date" class="form-control" name="from" value="{{ request('from', '') }}">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Registered To</label>
                        <input type="date" class="form-control" name="to" value="{{ request('to', '') }}">
                    </div>

                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                        <a class="btn btn-light w-100" href="{{ route('admin.tenants.index') }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body pb-0">
                {{-- Results summary --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small">
                        Showing <b>{{ $tenants->firstItem() ?? 0 }}</b>–<b>{{ $tenants->lastItem() ?? 0 }}</b>
                        of <b>{{ $tenants->total() }}</b> tenants
                    </div>

                    @php
                        $hasFilters =
                            request('q') ||
                            request('plan') ||
                            request('status') ||
                            request('active_in') ||
                            request('from') ||
                            request('to') ||
                            request('per_page');
                    @endphp

                    @if ($hasFilters)
                        <a class="small text-decoration-none" href="{{ route('admin.tenants.index') }}">
                            Clear filters
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Owner</th>
                            <th>Plan</th>
                            <th class="text-end">Users</th>
                            <th class="text-end">Invoices</th>
                            <th>Last seen</th>
                            <th>Registered</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($tenants as $t)
                            @php
                                $p = strtolower((string) ($t->plan ?? 'free'));
                                $cls = match ($p) {
                                    'premium' => 'text-bg-success',
                                    'business' => 'text-bg-warning text-dark',
                                    'internal_neptuneware' => 'text-bg-dark',
                                    default => 'text-bg-secondary',
                                };
                                $dashUrl = url('/t/' . $t->subdomain . '/dashboard');
                            @endphp

                            <tr>
                                <td class="fw-semibold">
                                    <a class="text-decoration-none"
                                        href="{{ route('admin.tenants.show', ['tenant' => $t->subdomain]) }}">
                                        {{ $t->name }}
                                    </a>
                                    <div class="text-muted small">
                                        <span class="badge bg-light text-dark border">{{ $t->subdomain }}</span>
                                        @if (!empty($t->status))
                                            <span
                                                class="badge bg-light text-dark border text-capitalize ms-1">{{ $t->status }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $t->owner?->name ?? '—' }}</div>
                                    <div class="text-muted small">{{ $t->owner?->email ?? '—' }}</div>
                                </td>

                                <td>
                                    <span class="badge {{ $cls }}">{{ strtoupper($p) }}</span>
                                </td>

                                <td class="text-end">
                                    <span class="badge bg-light text-dark border">{{ $t->users_count ?? 0 }}</span>
                                </td>

                                <td class="text-end">
                                    <span
                                        class="badge bg-light text-dark border">{{ $t->invoices_issued_count ?? 0 }}</span>
                                </td>

                                <td class="text-muted small">
                                    {{ $t->last_seen_at ? $t->last_seen_at->diffForHumans() : '—' }}
                                </td>

                                <td class="text-muted small">
                                    {{ optional($t->created_at)->format('Y-m-d H:i') }}
                                </td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ $dashUrl }}"
                                            target="_blank">
                                            Dashboard
                                        </a>
                                        <a class="btn btn-sm btn-outline-secondary"
                                            href="{{ route('admin.tenants.show', ['tenant' => $t->subdomain]) }}">
                                            View
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    No tenants found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $tenants->links() }}
            </div>
        </div>

    </div>
@endsection
