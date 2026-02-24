@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Dashboard </h3>
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
            {{-- Overdue Invoices --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-muted small">Overdue Invoices</div>
                            <span class="badge bg-danger">Overdue</span>
                        </div>
                        <div class="display-6 fw-semibold mb-1">
                            R {{ number_format((float) ($overdueInvoicesTotal ?? 0), 2) }}
                        </div>
                        <div class="text-muted small">
                            {{ (int) ($overdueInvoicesCount ?? 0) }}
                            invoice{{ (int) ($overdueInvoicesCount ?? 0) === 1 ? '' : 's' }}
                            past due
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pipeline Value --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-muted small">Pipeline Value</div>
                            <span class="badge bg-light text-dark">Value</span>
                        </div>
                        <div class="display-6 fw-semibold mb-1">
                            R {{ number_format((float) ($pipelineValue ?? 0), 2) }}
                        </div>
                        <div class="text-muted small">Sum of open deals value</div>
                    </div>
                </div>
            </div>

            {{-- Cash Collected (MTD) --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-muted small">Cash Collected (MTD)</div>
                            <span class="badge bg-success">MTD</span>
                        </div>
                        <div class="display-6 fw-semibold mb-1">
                            R {{ number_format((float) ($cashCollectedMtd ?? 0), 2) }}
                        </div>
                        <div class="text-muted small">From allocations this month</div>
                    </div>
                </div>
            </div>

            {{-- SO Pending Fulfillment --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="text-muted small">SO Pending Fulfillment</div>
                            <span class="badge bg-light text-dark">Ops</span>
                        </div>
                        <div class="display-6 fw-semibold mb-1">{{ (int) ($soPendingCount ?? 0) }}</div>
                        <div class="text-muted small">Issued / active sales orders not fulfilled</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 2: Pipeline by stage + Cash trend --}}
        <div class="row g-3 mb-3">
            {{-- Pipeline by stage --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">Pipeline by Stage</div>
                                <div class="text-muted small">Open deals • Count + Value</div>
                            </div>
                            <span class="badge bg-light text-dark">Stages</span>
                        </div>

                        @php
                            $stageRows = collect($pipelineByStage ?? []);
                        @endphp

                        @if ($stageRows->isEmpty())
                            <div class="text-muted small">No pipeline data yet.</div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Stage</th>
                                            <th class="text-end" style="width:120px;">Deals</th>
                                            <th class="text-end" style="width:220px;">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($stageRows as $r)
                                            <tr>
                                                <td class="fw-semibold">{{ data_get($r, 'stage', '—') }}</td>
                                                <td class="text-end">{{ (int) data_get($r, 'count', 0) }}</td>
                                                <td class="text-end">R
                                                    {{ number_format((float) data_get($r, 'value', 0), 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-muted small mt-2">
                                Tip: this shows where deals are stuck so sales can focus.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Cash trend --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="fw-semibold">Cash Trend</div>
                                <div class="text-muted small">Last 30 days • Collected (allocations)</div>
                            </div>
                            <span class="badge bg-light text-dark">Trend</span>
                        </div>

                        <div style="height: 320px;">
                            <canvas id="cashTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ROW 3: (keep your existing stacked charts row) --}}
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

        {{-- ROW 4: Attention / Work queues --}}
        <div class="row g-3">
            {{-- Follow-ups --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Attention: Follow-ups</div>
                        <a class="small text-decoration-none"
                            href="{{ tenant_route('tenant.activities.followups') }}">View all</a>
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
                                        <td class="text-muted">{{ class_basename($a->subject_type ?? '') ?: '—' }}</td>
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

            {{-- Invoices to chase --}}
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
                                            R
                                            {{ number_format((float) ($inv->outstanding_amount ?? ($inv->total ?? 0)), 2) }}
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

            {{-- Quotes expiring --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Attention: Quotes expiring soon</div>
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.quotes.index') }}">View
                            all</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Quote</th>
                                    <th>Customer</th>
                                    <th class="text-end">Value</th>
                                    <th>Expiry</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($quotesExpiring ?? collect()) as $qte)
                                    @php
                                        $exp = $qte->expires_at ?? ($qte->valid_until ?? null);
                                        $isSoon = $exp
                                            ? \Illuminate\Support\Carbon::parse($exp)->diffInDays(now(), false) <= 7
                                            : false;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            <a
                                                href="{{ tenant_route('tenant.quotes.show', $qte) }}">{{ $qte->quote_number ?? 'Quote #' . $qte->id }}</a>
                                        </td>
                                        <td class="text-muted">{{ $qte->company?->name ?? '—' }}</td>
                                        <td class="text-end">R
                                            {{ number_format((float) ($qte->subtotal ?? ($qte->total ?? 0)), 2) }}</td>
                                        <td>
                                            @if ($exp)
                                                <div class="{{ $isSoon ? 'text-danger fw-semibold' : '' }}">
                                                    {{ \Illuminate\Support\Carbon::parse($exp)->format('Y-m-d') }}
                                                </div>
                                                <div class="text-muted small">
                                                    {{ \Illuminate\Support\Carbon::parse($exp)->diffForHumans() }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ tenant_route('tenant.quotes.show', $qte) }}">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted px-3 py-4">No quotes expiring soon.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            {{-- SO pending table --}}
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body pb-0 d-flex justify-content-between align-items-center">
                        <div class="fw-semibold">Attention: Sales Orders pending</div>
                        <a class="small text-decoration-none" href="{{ tenant_route('tenant.sales-orders.index') }}">View
                            all</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>SO</th>
                                    <th>Customer</th>
                                    <th class="text-end">Total</th>
                                    <th>Age</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($soPending ?? collect()) as $so)
                                    @php
                                        $issued = $so->issued_at
                                            ? \Illuminate\Support\Carbon::parse($so->issued_at)
                                            : null;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">
                                            <a
                                                href="{{ tenant_route('tenant.sales-orders.show', $so) }}">{{ $so->sales_order_number ?? 'SO #' . $so->id }}</a>
                                        </td>
                                        <td class="text-muted">{{ $so->company?->name ?? '—' }}</td>
                                        <td class="text-end">R {{ number_format((float) ($so->total ?? 0), 2) }}</td>
                                        <td>
                                            @if ($issued)
                                                <div>{{ $issued->format('Y-m-d') }}</div>
                                                <div class="text-muted small">{{ $issued->diffForHumans() }}</div>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ tenant_route('tenant.sales-orders.show', $so) }}">View</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted px-3 py-4">No pending sales orders.</td>
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
        // Existing stacked chart data
        const months = @json(collect($months ?? [])->values());
        const quotesSeries = @json($quotesSeries ?? []);
        const invoicesSeries = @json($invoicesSeries ?? []);

        // Cash trend (last 30 days)
        const cashTrendLabels = @json(collect($cashTrendLabels ?? [])->values());
        const cashTrendData = @json(collect($cashTrendData ?? [])->values());

        function formatCurrencyZAR(value) {
            const n = Number(value || 0);
            return 'R ' + n.toLocaleString('en-ZA', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function makeStackedDatasets(series) {
            return (series || []).map(s => ({
                label: s.label,
                data: s.data,
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }));
        }

        function buildStackedChart(elId, datasets) {
            const el = document.getElementById(elId);
            if (!el) return;

            return new Chart(el, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets
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
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${ctx.dataset.label}: ${formatCurrencyZAR(ctx.raw ?? 0)}`,
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

        // Cash trend chart (simple line)
        (function buildCashTrend() {
            const el = document.getElementById('cashTrendChart');
            if (!el) return;

            new Chart(el, {
                type: 'line',
                data: {
                    labels: cashTrendLabels,
                    datasets: [{
                        label: 'Cash collected',
                        data: cashTrendData,
                        tension: 0.25,
                        borderWidth: 2,
                        pointRadius: 2
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => formatCurrencyZAR(ctx.raw ?? 0)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            ticks: {
                                callback: (v) => formatCurrencyZAR(v)
                            }
                        }
                    }
                }
            });
        })();

        // Render existing charts
        buildStackedChart('quotesChart', makeStackedDatasets(quotesSeries));
        buildStackedChart('invoicesChart', makeStackedDatasets(invoicesSeries));
    </script>
@endpush
