@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Invoices</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                @can('create', \App\Models\Invoice::class)
                    <a href="{{ tenant_route('tenant.invoices.create') }}" class="btn btn-primary">+ Add Invoice</a>
                @endcan

                @php $qs = http_build_query(request()->query()); @endphp

                @can('export', \App\Models\Invoice::class)
                    @if ($canExport ?? tenant_feature($tenant, 'export'))
                        <a href="{{ tenant_route('tenant.invoices.export') }}{{ $qs ? '?' . $qs : '' }}"
                            class="btn btn-outline-secondary">
                            Export (Excel)
                        </a>
                    @else
                        <a href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain]) }}"
                            class="btn btn-outline-secondary">
                            Export (Excel) <span class="badge bg-warning text-dark ms-1">PREMIUM</span>
                        </a>
                    @endif
                @endcan
            </div>
        </div>

        {{-- Flash --}}
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
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-12 col-md-4">
                        <label class="form-label">Search</label>
                        <input class="form-control" name="q" value="{{ $q ?? '' }}"
                            placeholder="Invoice #, reference, company...">
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach (['draft', 'issued', 'void'] as $s)
                                <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ strtoupper($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label">Payment</label>
                        <select class="form-select" name="payment_status" onchange="this.form.submit()">
                            <option value="">All</option>
                            @foreach (['unpaid', 'partially_paid', 'paid'] as $ps)
                                <option value="{{ $ps }}" @selected(($payment_status ?? '') === $ps)>{{ strtoupper($ps) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
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

                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                        <a class="btn btn-light w-100" href="{{ tenant_route('tenant.invoices.index') }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $pillStatus = fn($s) => match (strtolower((string) $s)) {
                'issued' => 'success',
                'draft' => 'secondary',
                'void' => 'dark',
                default => 'light',
            };

            $pillPay = fn($s) => match (strtolower((string) $s)) {
                'paid' => 'success',
                'unpaid' => 'danger',
                'partially_paid' => 'warning',
                default => 'light',
            };
        @endphp

        <div class="card">
            <div class="card-body pb-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="text-muted small">
                        Showing <b>{{ $invoices->firstItem() ?? 0 }}</b>–<b>{{ $invoices->lastItem() ?? 0 }}</b>
                        of <b>{{ $invoices->total() }}</b> invoices
                    </div>

                    @if (
                        ($q ?? '') !== '' ||
                            ($status ?? '') !== '' ||
                            ($payment_status ?? '') !== '' ||
                            ($sales_person_user_id ?? '') !== '')
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.invoices.index') }}">
                            Clear filters
                        </a>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <x-index.th-sort label="Invoice #" key="invoice_number" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Reference" key="reference" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Company" key="company" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Status" key="status" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Sub Total" key="subtotal" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Total" key="total" class="text-end" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Payment Status" key="payment_status" :sort="$sort"
                                :dir="$dir" />
                            <x-index.th-sort label="Issued Date" key="issued_at" :sort="$sort" :dir="$dir" />
                            <x-index.th-sort label="Sales Person" key="sales_person" :sort="$sort"
                                :dir="$dir" />
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($invoices as $inv)
                            <tr>
                                <td class="fw-semibold">
                                    <a href="{{ tenant_route('tenant.invoices.show', $inv) }}">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>

                                <td class="text-muted">
                                    {{ $inv->reference ?? ($inv->quote_number ?? '—') }}
                                </td>

                                <td>
                                    <a href="{{ tenant_route('tenant.companies.show', $inv->company) }}"
                                        class="text-decoration-none">
                                        {{ $inv->company?->name ?? '—' }}
                                    </a>
                                </td>

                                <td>
                                    <span class="badge rounded-pill text-bg-{{ $pillStatus($inv->status) }}">
                                        {{ strtoupper($inv->status) }}
                                    </span>
                                </td>

                                <td class="text-end">R {{ number_format((float) $inv->subtotal, 2) }}</td>
                                <td class="text-end">R {{ number_format((float) $inv->total, 2) }}</td>

                                @php
                                    $payVal = $inv->payment_status ?? ($inv->paymentStatus ?? 'unpaid');
                                @endphp
                                <td>
                                    <span class="badge rounded-pill text-bg-{{ $pillPay($payVal) }}">
                                        {{ strtoupper($payVal) }}
                                    </span>
                                </td>

                                <td>
                                    {{ $inv->issued_at ? \Illuminate\Support\Carbon::parse($inv->issued_at)->format('d/m/Y') : '—' }}
                                </td>

                                <td>{{ $inv->salesperson?->name ?? ($inv->salesPerson?->name ?? '—') }}</td>

                                <td class="text-end">
                                    <div class="btn-group">
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.invoices.show', $inv) }}">
                                            View
                                        </a>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if ($inv->status === 'draft')
                                                @can('update', $inv)
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ tenant_route('tenant.invoices.edit', $inv) }}">
                                                            Edit
                                                        </a>
                                                    </li>
                                                @endcan
                                            @endif

                                            @if (tenant_feature($tenant, 'invoice_email_send'))
                                                @can('sendEmail', $inv)
                                                    <li>
                                                        <form method="POST"
                                                            action="{{ tenant_route('tenant.invoices.sendEmail', $inv) }}">
                                                            @csrf
                                                            <button class="dropdown-item" type="submit">Send Email</button>
                                                        </form>
                                                    </li>
                                                @endcan
                                            @else
                                                @can('sendEmail', $inv)
                                                    <li>
                                                        <a href="#" class="dropdown-item" data-upgrade-modal
                                                            data-upgrade-text="Emailing invoices is available on the Premium plan."
                                                            data-upgrade-cta="Upgrade to Premium"
                                                            data-upgrade-href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant->subdomain]) }}">
                                                            Send Email <span
                                                                class="badge bg-warning text-dark ms-1">PREMIUM</span>
                                                        </a>
                                                    </li>
                                                @endcan
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    No invoices yet. Click <b>+ Add Invoice</b> to create your first one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-body">
                {{ $invoices->links() }}
            </div>
        </div>

    </div>
@endsection
