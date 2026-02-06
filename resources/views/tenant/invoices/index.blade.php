@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0">Invoices</h1>
            <a href="{{ tenant_route('tenant.invoices.create') }}" class="btn btn-primary">+ Add Invoice</a>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Reference</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Issued</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $inv)
                            <tr>
                                <td class="fw-semibold"><a
                                        href="{{ tenant_route('tenant.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a>
                                </td>
                                <td>{{ $inv->reference ?? ($inv->quote_number ?? '—') }}</td>
                                <td>{{ $inv->company?->name ?? '—' }}</td>
                                <td>{{ ucfirst($inv->status) }}</td>
                                <td>{{ number_format($inv->total, 2) }}</td>
                                <td>{{ $inv->issued_at ?? '—' }}</td>
                                <td class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-secondary"
                                        href="{{ tenant_route('tenant.invoices.show', $inv) }}">
                                        View
                                    </a>

                                    @if ($inv->status === 'draft')
                                        <a class="btn btn-sm btn-outline-primary"
                                            href="{{ tenant_route('tenant.invoices.edit', $inv) }}">
                                            Edit
                                        </a>
                                    @endif
                                    @if (tenant_feature(app('tenant'), 'invoice_email_send'))
                                        <form method="POST" action="{{ tenant_route('tenant.invoices.sendEmail', $inv) }}"
                                            class="d-inline">
                                            @csrf
                                            <button class="btn btn-outline-primary">Send Email</button>
                                        </form>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">{{ $invoices->links() }}</div>
            </div>
        </div>
    </div>
@endsection
