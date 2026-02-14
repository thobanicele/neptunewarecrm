@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Workspace Settings</h1>
            <a class="btn btn-outline-secondary" href="{{ tenant_route('tenant.dashboard', ['tenant' => $tenant]) }}">Back</a>
        </div>

        {{-- Flash --}}
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
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    }, 3500);
                </script>
            @endpush
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="fw-semibold mb-2">Please fix the following:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @php
            $planKey = $tenant->plan ?? config('plans.default_plan', 'free');
            $plans = config('plans.plans', []);
            $plan = $plans[$planKey] ?? ($plans['free'] ?? []);
            $premium = $plans['premium'] ?? [];

            $trialEnabled = (bool) data_get(config('plans.trial', []), 'enabled', false);
            $trialDays = (int) data_get(config('plans.trial', []), 'days', 14);

            // Optional: only if controller passes $sub (Subscription)
            $trialEndsAt = $sub->trial_ends_at ?? null;
            $trialDaysLeft = isset($trialDaysLeft)
                ? $trialDaysLeft
                : ($trialEndsAt
                    ? max(0, now()->diffInDays($trialEndsAt, false))
                    : null);

            $pricingMonthly = data_get(config('plans.billing.pricing'), 'premium.monthly');
            $pricingYearly = data_get(config('plans.billing.pricing'), 'premium.yearly');

            $features = (array) data_get($plan, 'features', []);
            $limits = [
                'Deals' => data_get($plan, 'deals.max'),
                'Users' => data_get($plan, 'users.max'),
                'Pipelines' => data_get($plan, 'pipelines.max'),
                'Storage (MB)' => data_get($plan, 'storage_mb.max'),
                'Invoices / month' => data_get($plan, 'invoices.max_per_month'),
            ];

            $prettyLimit = function ($v) {
                if (is_null($v)) {
                    return 'Unlimited';
                }
                if ($v === '') {
                    return '—';
                }
                return (string) $v;
            };

            $boolPill = function ($v) {
                return $v ? 'success' : 'secondary';
            };
        @endphp

        {{-- ✅ PLAN + UPGRADE AREA --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="text-muted small">Current plan</div>
                                <div class="fs-5 fw-semibold text-capitalize">{{ $planKey }}</div>

                                @if (!is_null($trialDaysLeft))
                                    <div class="text-muted small mt-1">
                                        Trial: <b>{{ $trialDaysLeft }}</b> day{{ $trialDaysLeft === 1 ? '' : 's' }} left
                                    </div>
                                @elseif($trialEnabled)
                                    <div class="text-muted small mt-1">
                                        Trial available: <b>{{ $trialDays }} days</b> (first time only)
                                    </div>
                                @endif
                            </div>

                            @if ($planKey === 'premium')
                                <span class="badge rounded-pill text-bg-success">PREMIUM</span>
                            @else
                                <span class="badge rounded-pill text-bg-secondary">FREE</span>
                            @endif
                        </div>

                        <hr>

                        <div class="row g-2">
                            @foreach ($limits as $label => $value)
                                <div class="col-6">
                                    <div class="text-muted small">{{ $label }}</div>
                                    <div class="fw-semibold">{{ $prettyLimit($value) }}</div>
                                </div>
                            @endforeach
                        </div>

                        <hr>

                        <div class="fw-semibold mb-2">Features</div>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ($features as $k => $enabled)
                                <span class="badge rounded-pill text-bg-{{ $boolPill($enabled) }}">
                                    {{ str_replace('_', ' ', ucwords($k)) }}: {{ $enabled ? 'On' : 'Off' }}
                                </span>
                            @endforeach
                        </div>

                        @if ($planKey === 'premium')
                            <div class="mt-3">
                                <a href="{{ tenant_route('tenant.billing.upgrade') }}" class="btn btn-outline-primary">
                                    Manage billing
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Upgrade cards --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="fw-semibold">Upgrade</div>
                                <div class="text-muted small">Unlock exports, dashboards, statements and more.</div>
                            </div>
                            <a href="{{ tenant_route('tenant.billing.upgrade') }}" class="small text-decoration-none">
                                Full billing page →
                            </a>
                        </div>

                        <hr>

                        @if ($planKey === 'premium')
                            <div class="alert alert-success mb-0">
                                You’re already on <b>Premium</b>.
                            </div>
                        @else
                            <div class="row g-3">
                                {{-- Monthly --}}
                                <div class="col-12 col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-semibold">
                                                    {{ data_get($pricingMonthly, 'label', 'Premium Monthly') }}</div>
                                                <div class="text-muted small">
                                                    R{{ number_format((float) data_get($pricingMonthly, 'amount', 0), 2) }}
                                                    /
                                                    month
                                                </div>
                                            </div>
                                            <span class="badge text-bg-primary">MONTHLY</span>
                                        </div>

                                        <ul class="mt-3 mb-3 small text-muted">
                                            <li>Exports (Excel)</li>
                                            <li>Reports & dashboards</li>
                                            <li>Statements</li>
                                        </ul>

                                        <form method="POST"
                                            action="{{ tenant_route('tenant.billing.paystack.initialize') }}">
                                            @csrf
                                            <input type="hidden" name="cycle" value="monthly">
                                            <button class="btn btn-primary w-100">Upgrade Monthly</button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Yearly --}}
                                <div class="col-12 col-md-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-semibold">
                                                    {{ data_get($pricingYearly, 'label', 'Premium Yearly') }}</div>
                                                <div class="text-muted small">
                                                    R{{ number_format((float) data_get($pricingYearly, 'amount', 0), 2) }}
                                                    /
                                                    year
                                                </div>
                                            </div>
                                            <span class="badge text-bg-dark">YEARLY</span>
                                        </div>

                                        <ul class="mt-3 mb-3 small text-muted">
                                            <li>Everything in Monthly</li>
                                            <li>Best value</li>
                                            <li>Less admin work</li>
                                        </ul>

                                        <form method="POST"
                                            action="{{ tenant_route('tenant.billing.paystack.initialize') }}">
                                            @csrf
                                            <input type="hidden" name="cycle" value="yearly">
                                            <button class="btn btn-outline-primary w-100">Upgrade Yearly</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="text-muted small mt-3">
                                You’ll be redirected to Paystack to complete billing.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick links --}}
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <div class="fw-semibold">Quick links</div>
                    <div class="text-muted small">Manage workspace configuration and related setup.</div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ tenant_route('tenant.tax-types.index') }}"
                        class="btn btn-outline-primary {{ request()->routeIs('tenant.tax-types.*') ? 'active' : '' }}">
                        <i class="fe fe-percent me-2"></i> Tax Types (VAT)
                    </a>
                </div>
            </div>
        </div>

        {{-- Settings form --}}
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.settings.update', ['tenant' => $tenant]) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Workspace name</label>
                            <input type="text" name="name" class="form-control"
                                value="{{ old('name', $tenant->name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Subdomain</label>
                            <div class="input-group">
                                <span class="input-group-text">/t/</span>
                                <input type="text" name="subdomain" class="form-control"
                                    value="{{ old('subdomain', $tenant->subdomain) }}" required>
                            </div>
                            <div class="form-text">
                                Lowercase letters/numbers + hyphens only. Changing this changes your workspace URL.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">

                            @if ($tenant->logo_path)
                                <div class="mt-2 d-flex align-items-center gap-3">
                                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Logo"
                                        style="height:48px;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1"
                                            name="remove_logo" id="remove_logo">
                                        <label class="form-check-label" for="remove_logo">Remove logo</label>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Bank details --}}
                        <div class="col-md-12">
                            <label class="form-label">Bank Details (for Quotes / Invoices)</label>
                            <textarea name="bank_details" class="form-control" rows="6"
                                placeholder="Account Name&#10;Bank&#10;Account Number&#10;Branch Code&#10;Swift (optional)&#10;Reference">{{ old('bank_details', $tenant->bank_details) }}</textarea>
                            <div class="form-text">This prints on your Quote PDF under “Bank Details”.</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary">Save changes</button>
                    </div>

                </form>
            </div>
        </div>

    </div>
@endsection
