@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="h3 mb-1">{{ $tenant->name }} <span class="text-muted">/ Dashboard</span></h1>
                <div class="text-muted">
                    Plan: <strong class="text-capitalize">{{ $plan }}</strong>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.deals.create', ['tenant' => $tenant]) }}" class="btn btn-primary">
                    + Add Deal
                </a>

                <a class="btn btn-outline-secondary" href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant]) }}">
                    <i class="align-middle" data-feather="zap"></i>
                    <span class="align-middle ms-1">Upgrade</span>
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @php
            $dealCountSafe = (int) ($dealCount ?? 0);

            // null = unlimited, int = capped
            $maxDealsSafe = isset($maxDeals) ? (is_null($maxDeals) ? null : (int) $maxDeals) : null;

            $totalPipelineAmount = isset($stats) ? collect($stats)->sum('total_amount') : 0;

            $progressPct =
                !is_null($maxDealsSafe) && $maxDealsSafe > 0
                    ? min(100, round(($dealCountSafe / $maxDealsSafe) * 100))
                    : null;

            // bar color: <70 success, 70-89 warning, >=90 danger
            $barClass = 'bg-success';
            if (!is_null($progressPct)) {
                if ($progressPct >= 90) {
                    $barClass = 'bg-danger';
                } elseif ($progressPct >= 70) {
                    $barClass = 'bg-warning';
                }
            }
        @endphp

        {{-- KPI cards --}}
        <div class="row">
            {{-- Deals Used --}}
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">Deals Used</h5>
                                <div class="text-muted small">Current usage for your plan</div>
                            </div>
                            <div class="text-muted">
                                <i class="align-middle" data-feather="briefcase"></i>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="h2 mb-1">
                                {{ $dealCountSafe }}
                                @if (!is_null($maxDealsSafe))
                                    <span class="text-muted fs-6">/ {{ $maxDealsSafe }}</span>
                                @else
                                    <span class="text-muted fs-6">/ Unlimited</span>
                                @endif
                            </div>

                            @if (!is_null($maxDealsSafe))
                                <div class="progress mt-2" style="height: 10px;">
                                    <div class="progress-bar {{ $barClass }}" role="progressbar"
                                        style="width: {{ $progressPct }}%;" aria-valuenow="{{ $progressPct }}"
                                        aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <div class="d-flex justify-content-between small mt-2">
                                    <span class="text-muted">{{ $dealCountSafe }} / {{ $maxDealsSafe }} used</span>
                                    <span class="text-muted">{{ $progressPct }}%</span>
                                </div>

                                @if ($progressPct >= 90)
                                    <div class="small mt-2">
                                        <span class="badge text-bg-danger">Limit almost reached</span>
                                        <a class="ms-2"
                                            href="{{ tenant_route('tenant.billing.upgrade', ['tenant' => $tenant]) }}">Upgrade</a>
                                    </div>
                                @elseif($progressPct >= 70)
                                    <div class="small mt-2">
                                        <span class="badge text-bg-warning">Getting close</span>
                                    </div>
                                @endif
                            @else
                                <div class="text-muted small mt-2">No deal limit on this plan.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pipeline Total --}}
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">Pipeline Total</h5>
                                <div class="text-muted small">Sum of deal amounts across stages</div>
                            </div>
                            <div class="text-muted">
                                <i class="align-middle" data-feather="bar-chart-2"></i>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="h2 mb-0">
                                R{{ number_format((float) $totalPipelineAmount, 2) }}
                            </div>
                            <div class="text-muted small mt-2">All stages combined</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Links --}}
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">Quick Links</h5>
                                <div class="text-muted small">Jump to common actions</div>
                            </div>
                            <div class="text-muted">
                                <i class="align-middle" data-feather="link"></i>
                            </div>
                        </div>

                        <div class="mt-3 d-grid gap-2">
                            <a class="btn btn-outline-primary"
                                href="{{ tenant_route('tenant.deals.index', ['tenant' => $tenant]) }}">
                                <i class="align-middle" data-feather="list"></i>
                                <span class="align-middle ms-1">View Deals</span>
                            </a>

                            <a class="btn btn-outline-secondary"
                                href="{{ tenant_route('tenant.deals.create', ['tenant' => $tenant]) }}">
                                <i class="align-middle" data-feather="plus-circle"></i>
                                <span class="align-middle ms-1">Create Deal</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pipeline breakdown --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Pipeline Breakdown</h5>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Stage</th>
                                <th class="text-end">Deals</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stages as $stage)
                                @php
                                    $cnt = $stats[$stage->id]->cnt ?? 0;
                                    $sum = $stats[$stage->id]->total_amount ?? 0;
                                @endphp
                                <tr>
                                    <td><span class="fw-semibold">{{ $stage->name }}</span></td>
                                    <td class="text-end">{{ $cnt }}</td>
                                    <td class="text-end">R{{ number_format((float) $sum, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted">No pipeline stages found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <th>Total</th>
                                <th class="text-end">{{ isset($stats) ? collect($stats)->sum('cnt') : 0 }}</th>
                                <th class="text-end">R{{ number_format((float) $totalPipelineAmount, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
