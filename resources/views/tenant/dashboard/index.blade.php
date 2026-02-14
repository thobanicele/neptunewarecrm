@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Dashboard</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ tenant_route('tenant.deals.index') }}" class="btn btn-outline-secondary">Deals</a>
                <a href="{{ tenant_route('tenant.invoices.index') }}" class="btn btn-outline-secondary">Invoices</a>
                <a href="{{ tenant_route('tenant.payments.index') }}" class="btn btn-outline-secondary">Payments</a>
                <a href="{{ tenant_route('tenant.activities.followups') }}" class="btn btn-outline-secondary">Follow-ups</a>
            </div>
        </div>

        {{-- ROW 1: KPI cards --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-muted small">Open Deals</div>
                            <span class="badge bg-light text-dark">Pipeline</span>
                        </div>
                        <div class="display-6 fw-semibold mb-1">{{ $openDeals ?? 0 }}</div>
                        <div class="text-muted small">Active deals not won/lost</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-muted small">Pipeline Value</div>
                            <span class="badge bg-light text-dark">Value</span>
                        </div>
                        <div class="display-6 fw-semibold mb-1">R {{ number_format((float) ($pipelineValue ?? 0), 2) }}</div>
                        <div class="text-muted small">Sum of open deals value</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-muted small">Invoices Outstanding</div>
                            <span class="badge bg-warning text-dark">Chase</span>
                        </div>
                        <div class="display-6 fw-semibold mb-1">R {{ number_format((float) ($invoicesOutstanding ?? 0), 2) }}
                        </div>
                        <div class="text-muted small">Issued + unpaid/partial</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-muted small">Cash Collected (MTD)</div>
                            <span class="badge bg-success">MTD</span>
                        </div>
                        <div class="display-6 fw-semibold mb-1">R {{ number_format((float) ($cashCollectedMtd ?? 0), 2) }}
                        </div>
                        <div class="text-muted small">From allocations this month</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 2: Charts --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">Quotes by Sales Person</div>
                                <div class="text-muted small">Last 12 months • Value (Sub Total)</div>
                            </div>
                            <span class="badge bg-light text-dark">Stacked</span>
                        </div>

                        <div style="height: 320px;">
                            <canvas id="quotesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">Invoices by Sales Person</div>
                                <div class="text-muted small">Last 12 months • Value (Total)</div>
                            </div>
                            <span class="badge bg-light text-dark">Stacked</span>
                        </div>

                        <div style="height: 320px;">
                            <canvas id="invoicesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 3: Attention tables --}}
        <div class="row g-3">
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Attention: Follow-ups</div>
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.activities.followups') }}">View
                            all</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Subject</th>
                                    <th>Due</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($followups ?? collect()) as $a)
                                    @php
                                        $isOverdue = $a->due_at && $a->due_at->isPast();
                                        $typePill = match ($a->type) {
                                            'call' => 'primary',
                                            'meeting' => 'info',
                                            'email' => 'warning',
                                            'note' => 'secondary',
                                            default => 'dark',
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            <span
                                                class="badge rounded-pill text-bg-{{ $typePill }}">{{ strtoupper($a->type) }}</span>
                                            @if ($isOverdue)
                                                <span class="badge rounded-pill text-bg-danger ms-1">OVERDUE</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">
                                            {{ class_basename($a->subject_type ?? '') ?: '—' }}
                                        </td>
                                        <td>
                                            @if ($a->due_at)
                                                <div class="{{ $isOverdue ? 'text-danger fw-semibold' : '' }}">
                                                    {{ $a->due_at->format('Y-m-d H:i') }}
                                                </div>
                                                <div class="text-muted small">{{ $a->due_at->diffForHumans() }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <form method="POST"
                                                action="{{ tenant_route('tenant.activities.toggle', ['activity' => $a->id]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-secondary"
                                                    type="submit">Done</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-muted px-3 py-4">No overdue / upcoming activities.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Attention: Invoices to chase</div>
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.invoices.index') }}">View
                            all</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th class="text-end">Outstanding</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($invoicesToChase ?? collect()) as $inv)
                                    <tr>
                                        <td class="fw-semibold">
                                            <a
                                                href="{{ tenant_route('tenant.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a>
                                        </td>
                                        <td class="text-muted">
                                            <a href="{{ tenant_route('tenant.companies.show', $inv->company) }}"
                                                class="text-decoration-none">
                                                {{ $inv->company?->name ?? '—' }}
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            {{-- If you don't have outstanding per invoice computed, show total for now --}}
                                            R {{ number_format((float) ($inv->total ?? 0), 2) }}
                                        </td>
                                        <td>
                                            <span
                                                class="badge rounded-pill text-bg-{{ $inv->payment_status === 'paid' ? 'success' : ($inv->payment_status === 'partial' ? 'warning' : 'danger') }}">
                                                {{ strtoupper($inv->payment_status ?? '—') }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ tenant_route('tenant.invoices.show', $inv) }}">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted px-3 py-4">No outstanding invoices found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        // -----------------------------
        // Data from controller
        // -----------------------------
        const months = @json(collect($months ?? [])->values());
        const quotesSeries = @json($quotesSeries ?? []);
        const invoicesSeries = @json($invoicesSeries ?? []);

        function formatCurrencyZAR(value) {
            const n = Number(value || 0);
            return 'R ' + n.toLocaleString('en-ZA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function makeStackedDatasets(series) {
            // Chart.js will auto color if no color provided,
            // but we give subtle transparency for stacking.
            // (No hard-coded theme colors; Chart.js will choose distinct defaults)
            return (series || []).map(s => ({
                label: s.label,
                data: s.data,
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }));
        }

        function buildStackedChart(elId, datasets, valueLabel) {
            const el = document.getElementById(elId);
            if (!el) return;

            return new Chart(el, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: datasets
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 10,
                                boxHeight: 10,
                                usePointStyle: true,
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    const v = ctx.raw ?? 0;
                                    return `${ctx.dataset.label}: ${formatCurrencyZAR(v)}`;
                                },
                                footer: (items) => {
                                    const sum = items.reduce((acc, it) => acc + (Number(it.raw || 0)), 0);
                                    return `Total: ${formatCurrencyZAR(sum)}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            stacked: true,
                            ticks: {
                                callback: (v) => formatCurrencyZAR(v)
                            }
                        }
                    }
                }
            });
        }

        // -----------------------------
        // Render charts
        // -----------------------------
        const quotesDatasets = makeStackedDatasets(quotesSeries);
        const invoicesDatasets = makeStackedDatasets(invoicesSeries);

        buildStackedChart('quotesChart', quotesDatasets, 'Quotes');
        buildStackedChart('invoicesChart', invoicesDatasets, 'Invoices');
    </script>
@endpush
