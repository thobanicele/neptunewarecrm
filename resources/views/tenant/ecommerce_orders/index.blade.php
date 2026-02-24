@extends('layouts.app')

@section('content')
    <div class="container-fluid py-3">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-0">Ecommerce Orders</h4>
                <div class="text-muted">Online orders submitted via storefront / integrations</div>
            </div>
        </div>

        <form method="GET" class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Search</label>
                        <input type="text" name="q" value="{{ $q }}" class="form-control"
                            placeholder="Order ID, name, email, phone">
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label">Status</label>
                        <input type="text" name="status" value="{{ $status }}" class="form-control"
                            placeholder="pending/paid...">
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label">Payment</label>
                        <input type="text" name="payment_status" value="{{ $pay }}" class="form-control"
                            placeholder="pending/paid...">
                    </div>

                    <div class="col-6 col-lg-2">
                        <label class="form-label">Fulfillment</label>
                        <input type="text" name="fulfillment_status" value="{{ $ful }}" class="form-control"
                            placeholder="unfulfilled/fulfilled">
                    </div>

                    <div class="col-6 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                        <a class="btn btn-outline-secondary w-100"
                            href="{{ tenant_route('tenant.ecommerce-orders.index') }}">Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:160px;">Order</th>
                            <th>Customer</th>
                            <th style="width:140px;">Placed</th>
                            <th style="width:120px;">Payment</th>
                            <th style="width:140px;">Fulfillment</th>
                            <th style="width:140px;" class="text-end">Total</th>
                            <th style="width:120px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $o)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $o->external_order_id }}</div>
                                    <div class="text-muted small">{{ $o->source ?? 'storefront' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $o->customer_name ?? '—' }}</div>
                                    <div class="text-muted small">
                                        {{ $o->customer_email ?? '' }}
                                        @if ($o->customer_phone)
                                            • {{ $o->customer_phone }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>{{ optional($o->placed_at)->format('Y-m-d H:i') ?? '—' }}</div>
                                    <div class="text-muted small">{{ $o->status }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $o->payment_status === 'paid' ? 'success' : 'secondary' }}">
                                        {{ $o->payment_status }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-{{ $o->fulfillment_status === 'fulfilled' ? 'success' : 'warning' }}">
                                        {{ $o->fulfillment_status }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">
                                    {{ $o->currency ?? 'ZAR' }} {{ number_format((float) $o->grand_total, 2) }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ tenant_route('tenant.ecommerce-orders.show', ['ecommerceOrder' => $o->id]) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No ecommerce orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="card-body">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
