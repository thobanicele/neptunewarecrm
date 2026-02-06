@extends('layouts.app')

@section('content')
    @php
        $money = fn($n) => 'R ' . number_format((float) $n, 2);
    @endphp

    <div class="container-fluid py-4" style="max-width:1100px;">

        <div class="d-flex align-items-start justify-content-between mb-3">
            <div>
                <h3 class="mb-0">Statement / Export</h3>
                <div class="text-muted small">Filter invoices and download a statement PDF.</div>
            </div>
            <a href="{{ tenant_route('tenant.invoices.index') }}" class="btn btn-light">Back</a>
        </div>

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ tenant_route('tenant.invoices.statement') }}"
                    class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <div class="text-muted small">Company</div>
                        <select class="form-select" name="company_id">
                            <option value="">— all —</option>
                            @foreach ($companies as $c)
                                <option value="{{ $c->id }}" @selected((string) ($filters['company_id'] ?? '') === (string) $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="text-muted small">Status</div>
                        <select class="form-select" name="status">
                            <option value="">— all —</option>
                            @foreach (['draft', 'issued', 'paid', 'void'] as $s)
                                <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ strtoupper($s) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="text-muted small">Paid</div>
                        <select class="form-select" name="paid">
                            <option value="">— all —</option>
                            <option value="paid" @selected(($filters['paid'] ?? '') === 'paid')>PAID</option>
                            <option value="unpaid" @selected(($filters['paid'] ?? '') === 'unpaid')>UNPAID</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="text-muted small">From</div>
                        <input type="date" class="form-control" name="date_from"
                            value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div class="col-md-2">
                        <div class="text-muted small">To</div>
                        <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Apply</button>

                        <a class="btn btn-outline-primary"
                            href="{{ tenant_route('tenant.invoices.statement.download', request()->query()) }}">
                            Download PDF
                        </a>

                        <a class="btn btn-light" href="{{ tenant_route('tenant.invoices.statement') }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Totals --}}
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Invoices</div>
                        <div class="fw-bold">{{ $totals['count'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Total</div>
                        <div class="fw-bold">{{ $money($totals['total']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Paid</div>
                        <div class="fw-bold">{{ $money($totals['paid']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted small">Unpaid</div>
                        <div class="fw-bold">{{ $money($totals['unpaid']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- List --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Issued</th>
                                <th class="text-end">Total</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoices as $inv)
                                <tr>
                                    <td class="fw-semibold">{{ $inv->invoice_number }}</td>
                                    <td>{{ $inv->company?->name ?? '—' }}</td>
                                    <td>{{ strtoupper((string) $inv->status) }}</td>
                                    <td>{{ $inv->issued_at ? $inv->issued_at->format('d/m/Y') : '—' }}</td>
                                    <td class="text-end fw-semibold">{{ $money($inv->total) }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.invoices.show', $inv) }}">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No invoices found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    {{ $invoices->links() }}
                </div>
            </div>
        </div>

    </div>
@endsection
