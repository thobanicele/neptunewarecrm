@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Credit Note #{{ $creditNote->id }}</h3>
                <div class="text-muted small">{{ $creditNote->company?->name }}</div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light" href="{{ tenant_route('credit-notes.index') }}">Back</a>

                @if (($remaining ?? 0) > 0)
                    <a class="btn btn-outline-primary"
                        href="{{ tenant_route('tenant.credit_notes.refund.create', ['creditNote' => $creditNote->id]) }}">
                        Refund
                    </a>
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row g-3">

            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Summary</div>

                        <div class="d-flex justify-content-between">
                            <div class="text-muted">Issued</div>
                            <div class="fw-semibold">
                                {{ optional($creditNote->issued_at)->toDateString() ?? $creditNote->issued_at }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Amount</div>
                            <div class="fw-semibold">R {{ number_format((float) $creditNote->amount, 2) }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Allocated</div>
                            <div class="fw-semibold">R {{ number_format((float) ($allocated ?? 0), 2) }}</div>
                        </div>

                        <div class="d-flex justify-content-between mt-2">
                            <div class="text-muted">Refunded</div>
                            <div class="fw-semibold">R {{ number_format((float) ($refunded ?? 0), 2) }}</div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between">
                            <div class="text-muted">Remaining</div>
                            <div class="fw-bold">R {{ number_format((float) ($remaining ?? 0), 2) }}</div>
                        </div>

                        @if (!empty($creditNote->reason))
                            <hr class="my-3">
                            <div class="text-muted small">Reason</div>
                            <div class="fw-semibold">{{ $creditNote->reason }}</div>
                        @endif

                        @if (!empty($creditNote->notes))
                            <div class="text-muted small mt-2">Notes</div>
                            <div>{{ $creditNote->notes }}</div>
                        @endif

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Allocations (applied to invoices)</div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th class="text-end">Amount Applied</th>
                                        <th class="text-muted">Applied At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($alloc ?? collect()) as $a)
                                        <tr>
                                            <td class="text-muted">INV #{{ $a->invoice_id }}</td>
                                            <td class="text-end">R {{ number_format((float) $a->amount_applied, 2) }}</td>
                                            <td class="text-muted">{{ $a->applied_at }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-muted">No allocations yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="form-text mt-2">
                            Allocations are created automatically when the credit note is issued.
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <div class="fw-semibold mb-2">Refunds</div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($refunds ?? collect()) as $r)
                                        <tr>
                                            <td>{{ $r->refunded_at }}</td>
                                            <td class="text-muted">{{ $r->method }}</td>
                                            <td class="text-muted">{{ $r->reference }}</td>
                                            <td class="text-end">R {{ number_format((float) $r->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-muted">No refunds recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if (($remaining ?? 0) > 0)
                            <div class="mt-3">
                                <a class="btn btn-outline-primary"
                                    href="{{ tenant_route('tenant.credit_notes.refund.create', ['creditNote' => $creditNote->id]) }}">
                                    Refund remaining credit
                                </a>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

        </div>

    </div>
@endsection
