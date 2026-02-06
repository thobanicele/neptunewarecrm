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
                        <select class="form-select" name="status">
                            <option value="">All</option>
                            @foreach (['draft', 'sent', 'accepted', 'declined', 'expired'] as $s)
                                <option value="{{ $s }}" @selected(($status ?? '') === $s)>{{ strtoupper($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label">Sales Person</label>
                        <select class="form-select" name="sales_person_user_id">
                            <option value="">All</option>
                            @foreach ($salesPeople ?? collect() as $u)
                                <option value="{{ $u->id }}" @selected((string) ($sales_person_user_id ?? '') === (string) $u->id)>{{ $u->name }}
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
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Quote</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Deal</th>
                            <th>Sales Person</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th>Quoted Date</th>
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

                                <td>{{ $qte->company?->name ?? '—' }}</td>
                                <td>{{ $qte->contact?->name ?? '—' }}</td>

                                <td>
                                    @if ($qte->deal)
                                        {{-- <a href="{{ tenant_route('tenant.deals.show'), ['deal' => $qte->deal->id]) }}"
                                            class="small"> --}}
                                        {{ $qte->deal->title }}
                                        {{-- </a> --}}
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>{{ $qte->salesPerson?->name ?? '—' }}</td>

                                <td>
                                    <span class="badge rounded-pill text-bg-{{ $pill($qte->status) }}">
                                        {{ strtoupper($qte->status) }}
                                    </span>
                                </td>
                                <td class="text-end">R {{ number_format((float) $qte->total, 2) }}</td>
                                <td>{{ $qte->created_at }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2">
                                        <a class="btn btn-sm btn-outline-secondary"
                                            href="{{ tenant_route('tenant.quotes.edit', ['quote' => $qte->id]) }}">Edit</a>
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.quotes.pdf.stream', ['quote' => $qte->id]) }}"
                                            target="_blank">PDF</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
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
