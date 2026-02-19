@extends('layouts.frontend.main')

@php
    $currency = config('plans.billing.currency', 'ZAR');
    $trialEnabled = (bool) config('plans.trial.enabled', false);
    $trialDays = (int) config('plans.trial.days', 0);

    $plans = config('plans.plans', []);
    $pricing = config('plans.billing.pricing', []);

    // Define display order (and keep naming consistent)
    $order = ['free', 'premium', 'business'];

    // Friendly feature labels (you can add more anytime)
    $featureLabels = [
        'kanban' => 'Kanban boards',
        'export' => 'Exports (CSV/PDF)',
        'custom_branding' => 'Custom branding',
        'invoicing_manual' => 'Manual invoicing',
        'invoicing_convert_from_quote' => 'Convert quote → invoice',
        'invoice_email_send' => 'Send invoice emails',
        'statements' => 'Statements & reports',
        'invoice_pdf_watermark' => 'Invoice PDF watermark',
        'sales_forecasting' => 'Sales forecasting',
        'sales_analytics' => 'Sales analytics',
        'priority_support' => 'Priority support',
        'dedicated_account_manager' => 'Dedicated account manager',
        'sales_orders' => 'Sales orders',
        'purchase_orders' => 'Purchase orders',
        'vender_management' => 'Vendor management',
        'expense_tracking' => 'Expense tracking',
        'custom_reporting' => 'Custom reporting',
        'workflow_automation' => 'Workflow automation',
    ];

    // Order for compare table (feel free to tweak)
    $compareFeatureKeys = [
        'kanban',
        'export',
        'statements',
        'invoicing_convert_from_quote',
        'invoice_email_send',
        'invoice_pdf_watermark',
        'custom_branding',
        'sales_orders',
        'purchase_orders',
        'sales_forecasting',
        'sales_analytics',
        'priority_support',
        'custom_reporting',
        'dedicated_account_manager',
        'vender_management',
        'expense_tracking',
        'workflow_automation',
    ];

    $fmtMoney = function ($amount) use ($currency) {
        if ($amount === null) {
            return '—';
        }
        // ZAR formatting, no decimals for display
        return $currency === 'ZAR'
            ? 'R' . number_format((float) $amount, 0, '.', ' ')
            : $currency . ' ' . number_format((float) $amount, 0, '.', ' ');
    };

    $fmtLimit = function ($val) {
        if ($val === null) {
            return 'Unlimited';
        }
        return (string) $val;
    };
@endphp

@section('content')
    {{-- HERO --}}
    <section class="nw-hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="nw-kicker mb-3">
                        <span class="nw-dot"></span>
                        NeptuneWare CRM • Pricing
                    </div>

                    <h1 class="display-5 fw-bold nw-hero-title mb-3">
                        Simple pricing that scales with your workspace.
                    </h1>

                    <p class="lead text-muted mb-0">
                        Start free and upgrade when you need more users, exports, statements, and advanced features.
                        @if ($trialEnabled && $trialDays > 0)
                            <span class="fw-semibold">Includes {{ $trialDays }}-day trial.</span>
                        @endif
                    </p>
                </div>

                <div class="col-lg-4">
                    <div class="card nw-card">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">What’s included</div>
                            <div class="text-muted small mb-3">All plans are multi-tenant with tenant-scoped roles.</div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge nw-badge-neutral">/t/{tenant}</span>
                                <span class="badge nw-badge-neutral">Spatie Teams</span>
                                <span class="badge nw-badge-neutral">Quotes</span>
                                <span class="badge nw-badge-neutral">Invoices</span>
                                <span class="badge nw-badge-neutral">Payments</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- PRICING CARDS --}}
    <section class="py-5">
        <div class="container">
            <div class="row g-3 align-items-stretch">

                @foreach ($order as $key)
                    @php
                        $p = $plans[$key] ?? null;
                        if (!$p) {
                            continue;
                        }

                        $label = data_get($p, 'label', ucfirst($key));

                        $monthly = data_get($pricing, "$key.monthly.amount");
                        $yearly = data_get($pricing, "$key.yearly.amount");

                        $isFree = $key === 'free';
                        $isPopular = $key === 'premium';

                        $usersMax = data_get($p, 'users.max');
                        $dealsMax = data_get($p, 'deals.max');
                        $pipesMax = data_get($p, 'pipelines.max');
                        $storageMax = data_get($p, 'storage_mb.max');
                        $invPerMonth = data_get($p, 'invoices.max_per_month');

                        $features = data_get($p, 'features', []);
                        $hasExport = (bool) data_get($features, 'export', false);
                        $hasStatements = (bool) data_get($features, 'statements', false);

                        // Button / action link
                        $ctaText = $isFree ? 'Start free' : "Choose {$label}";
                        $ctaHrefGuest = route('register');
                        // Auth: if tenant exists -> upgrade page; else onboarding
                        $tenant = auth()->check() ? auth()->user()->tenant : null;
                        $ctaHrefAuth = $tenant
                            ? url('/t/' . $tenant->subdomain . '/billing/upgrade')
                            : route('tenant.onboarding.create');
                    @endphp

                    <div class="col-12 col-lg-4">
                        <div class="card nw-card h-100 {{ $isPopular ? 'border-primary position-relative' : '' }}">
                            @if ($isPopular)
                                <div class="position-absolute top-0 start-50 translate-middle">
                                    <span class="badge bg-primary px-3 py-2">Most popular</span>
                                </div>
                            @endif

                            <div class="card-body d-flex flex-column {{ $isPopular ? 'pt-4' : '' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold fs-4">{{ $label }}</div>
                                        <div class="text-muted small">
                                            @if ($isFree)
                                                For getting started
                                            @elseif($key === 'premium')
                                                For growing teams
                                            @else
                                                For serious operations
                                            @endif
                                        </div>
                                    </div>

                                    @if ($isFree)
                                        <span class="badge nw-badge-neutral">Starter</span>
                                    @elseif($key === 'premium')
                                        <span class="badge nw-badge-info">Recommended</span>
                                    @else
                                        <span class="badge nw-badge-warn">Advanced</span>
                                    @endif
                                </div>

                                <div class="my-3">
                                    @if ($isFree)
                                        <div class="display-6 fw-bold mb-0">R0</div>
                                        <div class="text-muted small">per workspace / month</div>
                                    @else
                                        <div class="d-flex align-items-end gap-2">
                                            <div class="display-6 fw-bold mb-0">{{ $fmtMoney($monthly) }}</div>
                                            <div class="text-muted small mb-2">/ month</div>
                                        </div>
                                        <div class="text-muted small">
                                            or {{ $fmtMoney($yearly) }} / year
                                        </div>
                                    @endif
                                </div>

                                <div class="nw-price-limits mb-3">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="nw-stat p-3">
                                                <div class="small text-muted">Users</div>
                                                <div class="fw-bold">{{ $fmtLimit($usersMax) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="nw-stat p-3">
                                                <div class="small text-muted">Deals</div>
                                                <div class="fw-bold">{{ $fmtLimit($dealsMax) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="nw-stat p-3">
                                                <div class="small text-muted">Pipelines</div>
                                                <div class="fw-bold">{{ $fmtLimit($pipesMax) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="nw-stat p-3">
                                                <div class="small text-muted">File Storage</div>
                                                <div class="fw-bold">{{ $fmtLimit($storageMax) }} MB</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <ul class="list-unstyled small mb-4">
                                    <li class="d-flex gap-2 mb-2"><span class="text-success">✓</span> Tenant isolation
                                        (/t/{tenant})
                                    </li>
                                    <li class="d-flex gap-2 mb-2"><span class="text-success">✓</span> Tenant-scoped roles
                                        (Spatie Teams)</li>
                                    <li class="d-flex gap-2 mb-2"><span class="text-success">✓</span> Leads, companies,
                                        contacts, deals</li>
                                    <li class="d-flex gap-2 mb-2"><span class="text-success">✓</span> Quotes & invoices
                                        (PDF)</li>
                                    <li class="d-flex gap-2 mb-2"><span class="text-success">✓</span> Payments & credit
                                        notes</li>

                                    <li class="d-flex gap-2 mb-2">
                                        <span
                                            class="{{ $hasExport ? 'text-success' : 'text-muted' }}">{{ $hasExport ? '✓' : '—' }}</span>
                                        <span class="{{ $hasExport ? '' : 'text-muted' }}">Exports (CSV/PDF)</span>
                                    </li>
                                    <li class="d-flex gap-2 mb-2">
                                        <span
                                            class="{{ $hasStatements ? 'text-success' : 'text-muted' }}">{{ $hasStatements ? '✓' : '—' }}</span>
                                        <span class="{{ $hasStatements ? '' : 'text-muted' }}">Statements & reports</span>
                                    </li>

                                    @if (!is_null($invPerMonth))
                                        <li class="d-flex gap-2 mb-2">
                                            <span class="text-success">✓</span>
                                            <span>{{ $invPerMonth }} invoices / month</span>
                                        </li>
                                    @else
                                        <li class="d-flex gap-2 mb-2"><span class="text-success">✓</span> Unlimited invoices
                                        </li>
                                    @endif
                                </ul>

                                <div class="mt-auto">
                                    @guest
                                        <a href="{{ $ctaHrefGuest }}"
                                            class="btn {{ $isPopular ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                            {{ $ctaText }}
                                        </a>
                                    @endguest

                                    @auth
                                        <a href="{{ $ctaHrefAuth }}"
                                            class="btn {{ $isPopular ? 'btn-primary' : ($isFree ? 'btn-outline-primary' : 'btn-outline-primary') }} w-100">
                                            {{ $isFree ? 'Go to workspace' : 'Upgrade' }}
                                        </a>
                                        <div class="text-muted small text-center mt-2">Cancel anytime</div>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>

            {{-- COMPARE TABLE --}}
            <div class="mt-5">
                <div class="text-center mb-4">
                    <h2 class="h2 fw-bold mb-2">Compare features</h2>
                    <p class="text-muted mb-0">See what each plan includes at a glance.</p>
                </div>

                <div class="card nw-card">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 320px;">Feature</th>

                                    @foreach ($order as $key)
                                        @php $p = $plans[$key] ?? null; @endphp
                                        @if ($p)
                                            @php $isPopularCol = $key === 'premium'; @endphp
                                            <th class="text-center {{ $isPopularCol ? 'bg-primary bg-opacity-10' : '' }}"
                                                style="min-width: 160px;">
                                                <div class="fw-bold">{{ data_get($p, 'label', ucfirst($key)) }}</div>
                                                @if ($isPopularCol)
                                                    <div class="small text-primary fw-semibold">Recommended</div>
                                                @endif
                                            </th>
                                        @endif
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                {{-- LIMITS --}}
                                <tr>
                                    <td class="fw-semibold text-muted">Users</td>
                                    @foreach ($order as $key)
                                        @php $p = $plans[$key] ?? null; @endphp
                                        @if ($p)
                                            <td class="text-center">
                                                <span
                                                    class="fw-semibold">{{ $fmtLimit(data_get($p, 'users.max')) }}</span>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>

                                <tr>
                                    <td class="fw-semibold text-muted">Deals</td>
                                    @foreach ($order as $key)
                                        @php $p = $plans[$key] ?? null; @endphp
                                        @if ($p)
                                            <td class="text-center">
                                                <span
                                                    class="fw-semibold">{{ $fmtLimit(data_get($p, 'deals.max')) }}</span>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>

                                <tr>
                                    <td class="fw-semibold text-muted">Pipelines</td>
                                    @foreach ($order as $key)
                                        @php $p = $plans[$key] ?? null; @endphp
                                        @if ($p)
                                            <td class="text-center">
                                                <span
                                                    class="fw-semibold">{{ $fmtLimit(data_get($p, 'pipelines.max')) }}</span>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>

                                <tr>
                                    <td class="fw-semibold text-muted">File Storage</td>
                                    @foreach ($order as $key)
                                        @php $p = $plans[$key] ?? null; @endphp
                                        @if ($p)
                                            <td class="text-center">
                                                <span class="fw-semibold">{{ $fmtLimit(data_get($p, 'storage_mb.max')) }}
                                                    MB</span>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>

                                <tr>
                                    <td class="fw-semibold text-muted">Invoices per month</td>
                                    @foreach ($order as $key)
                                        @php $p = $plans[$key] ?? null; @endphp
                                        @if ($p)
                                            @php $v = data_get($p, 'invoices.max_per_month'); @endphp
                                            <td class="text-center">
                                                <span class="fw-semibold">{{ $v === null ? 'Unlimited' : $v }}</span>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>

                                {{-- Divider --}}
                                <tr>
                                    <td colspan="{{ 1 + count($order) }}" class="p-0">
                                        <div class="border-top"></div>
                                    </td>
                                </tr>

                                {{-- FEATURE FLAGS --}}
                                @foreach ($compareFeatureKeys as $fKey)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $featureLabels[$fKey] ?? $fKey }}</div>
                                            @if ($fKey === 'invoice_pdf_watermark')
                                                <div class="text-muted small">Premium & Business remove the watermark.
                                                </div>
                                            @endif
                                        </td>

                                        @foreach ($order as $key)
                                            @php
                                                $p = $plans[$key] ?? null;
                                                if (!$p) {
                                                    continue;
                                                }

                                                // normal boolean features
                                                $enabled = (bool) data_get($p, "features.$fKey", false);
                                                $display = $enabled ? '✓' : '—';

                                                // special case: watermark flag is inverted logic
                                                if ($fKey === 'invoice_pdf_watermark') {
                                                    $val = data_get($p, "features.$fKey", true); // default true on free
                                                    $removed = $val === false; // false = NO watermark
                                                    $enabled = $removed;

                                                    $display = $removed ? 'No watermark' : 'Watermarked';
                                                }
                                            @endphp

                                            <td class="text-center">
                                                @if ($fKey === 'invoice_pdf_watermark')
                                                    <span class="badge {{ $enabled ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $display }}
                                                    </span>
                                                @else
                                                    <span class="{{ $enabled ? 'text-success fw-bold' : 'text-muted' }}">
                                                        {{ $display }}
                                                    </span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            {{-- FAQ --}}
            <div class="mt-5">
                <div class="text-center mb-4">
                    <h2 class="h2 fw-bold mb-2">FAQ</h2>
                    <p class="text-muted mb-0">Quick answers before you start.</p>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card nw-card h-100">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">Do I need a credit card?</div>
                                <div class="text-muted">No. Start on Free and upgrade any time.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card nw-card h-100">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">Is my data isolated per tenant?</div>
                                <div class="text-muted">Yes. Tenant routes + Spatie Teams ensure workspace isolation.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card nw-card h-100">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">Can I invite my team?</div>
                                <div class="text-muted">Yes. Roles allowed via invites: tenant_admin, sales, finance,
                                    viewer.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card nw-card h-100">
                            <div class="card-body">
                                <div class="fw-semibold mb-2">Can I cancel?</div>
                                <div class="text-muted">Yes. Cancel any time — your workspace remains accessible based on
                                    plan rules.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- CTA --}}
    <section class="py-5 nw-cta">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <h2 class="h1 fw-bold mb-2 text-white">Start free today</h2>
                    <p class="mb-0 text-white-50">Create your workspace and invite your team in minutes.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-light btn-lg">Start free</a>
                    @endguest

                    @auth
                        @php $tenant = auth()->user()->tenant; @endphp
                        @if ($tenant)
                            <a href="{{ url('/t/' . $tenant->subdomain . '/billing/upgrade') }}"
                                class="btn btn-light btn-lg">
                                Upgrade
                            </a>
                        @else
                            <a href="{{ route('tenant.onboarding.create') }}" class="btn btn-light btn-lg">
                                Create workspace
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </section>
@endsection
