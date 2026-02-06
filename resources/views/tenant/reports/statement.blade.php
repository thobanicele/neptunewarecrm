@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4" style="max-width:1100px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Statement</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" method="GET" action="{{ tenant_route('tenant.reports.statement') }}">
                    <div class="col-md-3">
                        <label class="form-label">From</label>
                        <input type="date" name="from" class="form-control"
                            value="{{ $from ? $from->toDateString() : '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To</label>
                        <input type="date" name="to" class="form-control"
                            value="{{ $to ? $to->toDateString() : '' }}">
                    </div>
                    <div class="col-md-6 d-flex gap-2">
                        <button class="btn btn-primary">Apply</button>

                        <a class="btn btn-outline-primary"
                            href="{{ tenant_route('tenant.reports.statement.pdf') . '?' . http_build_query(request()->only(['from', 'to'])) }}">
                            Download PDF
                        </a>

                        <a class="btn btn-outline-secondary"
                            href="{{ tenant_route('tenant.reports.statement.csv') . '?' . http_build_query(request()->only(['from', 'to'])) }}">
                            Export CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-muted small">Invoices</div>
                        <div class="fw-semibold">{{ $summary['count'] }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Total</div>
                        <div class="fw-semibold">R {{ number_format($summary['total'], 2) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Paid</div>
                        <div class="fw-semibold">R {{ number_format($summary['paid'], 2) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small">Unpaid</div>
                        <div class="fw-semibold">R {{ number_format($summary['unpaid'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Company</th>
                            <th>Issued</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                            <th>Paid At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                            <tr>
                                <td>
                                    <a href="{{ tenant_route('tenant.invoices.show', $inv) }}">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td>{{ $inv->company?->name ?? '—' }}</td>
                                <td>{{ $inv->issued_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ strtoupper($inv->status) }}</td>
                                <td class="text-end">R {{ number_format((float) $inv->total, 2) }}</td>
                                <td>{{ $inv->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">No invoices found for this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
