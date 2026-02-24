@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

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

        @if (session('error'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert" id="flash-error">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @push('scripts')
                <script>
                    setTimeout(() => {
                        const el = document.getElementById('flash-error');
                        if (!el) return;
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    }, 4000);
                </script>
            @endpush
        @endif

        @php
            use App\Support\TenantPlan;

            $plans = (array) config('plans.plans', []);
            $pricing = (array) config('plans.billing.pricing', []);
            $currency = (string) config('plans.billing.currency', 'ZAR');
            $fmt = fn($amount) => $currency . ' ' . number_format((float) $amount, 2);

            // ✅ trial-aware effective plan key
            $effectivePlanKey = $tenant
                ? TenantPlan::effectivePlan($tenant)
                : $tenant->plan ?? config('plans.default_plan', 'free');

            // Exclude internal plans
            $publicPlans = collect($plans)
                ->reject(fn($cfg, $key) => str_starts_with((string) $key, 'internal_'))
                ->all();

            // ✅ Trial UI (clean integer days; no decimals)
            $trialEnabled = (bool) data_get(config('plans.trial'), 'enabled', false);
            $trialDays = (int) data_get(config('plans.trial'), 'days', 14);

            $trialEndsAt = $sub?->trial_ends_at ?? null;

            $trialState = null; // active | ended | eligible
            $trialDaysLeft = null;
            $trialEndsDate = null;

            if ($trialEnabled && $trialEndsAt) {
                $trialEndsDate = optional($trialEndsAt)->format('Y-m-d');

                if (now()->lt($trialEndsAt)) {
                    $trialState = 'active';

                    // ✅ whole integer days left, friendly for end-of-day
                    $trialDaysLeft = now()->startOfDay()->diffInDays($trialEndsAt->startOfDay());
                    $trialDaysLeft = max(1, (int) $trialDaysLeft);
                } else {
                    $trialState = 'ended';
                }
            } elseif ($trialEnabled) {
                $trialState = 'eligible';
            }

            $currentPlanLabel = data_get(
                $plans,
                "{$effectivePlanKey}.label",
                ucfirst(str_replace('_', ' ', $effectivePlanKey)),
            );

            // Paystack codes (premium + business)
            $psPremiumMonthly = (string) data_get(config('plans.billing.paystack'), 'premium_monthly_plan_code', '');
            $psPremiumYearly = (string) data_get(config('plans.billing.paystack'), 'premium_yearly_plan_code', '');
            $psBusinessMonthly = (string) data_get(config('plans.billing.paystack'), 'business_monthly_plan_code', '');
            $psBusinessYearly = (string) data_get(config('plans.billing.paystack'), 'business_yearly_plan_code', '');

            $paystackConfigured =
                !empty($psPremiumMonthly) ||
                !empty($psPremiumYearly) ||
                !empty($psBusinessMonthly) ||
                !empty($psBusinessYearly);
        @endphp

        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Upgrade Plan</h3>
                <div class="text-muted small">
                    Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})
                </div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary"
                    href="{{ tenant_route('tenant.dashboard', ['tenant' => $tenant->subdomain]) }}">
                    Back to dashboard
                </a>
            </div>
        </div>

        <div class="row g-3">

            {{-- Current plan --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="text-muted small">Current plan</div>
                            <div class="fs-5 fw-semibold text-capitalize">{{ $currentPlanLabel }}</div>

                            {{-- ✅ Trial badge (clean) --}}
                            @if ($trialState === 'active')
                                <div class="mt-2">
                                    <span class="badge bg-warning text-dark border">
                                        Trial ends {{ $trialEndsDate }} • {{ $trialDaysLeft }}
                                        day{{ $trialDaysLeft === 1 ? '' : 's' }} left
                                    </span>
                                </div>
                            @elseif ($trialState === 'ended')
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark border">
                                        Trial ended {{ $trialEndsDate }}
                                    </span>
                                </div>
                            @elseif ($trialState === 'eligible')
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark border">
                                        Includes {{ $trialDays }}-day trial (if eligible)
                                    </span>
                                </div>
                            @endif

                            @if ($effectivePlanKey === 'free')
                                <div class="text-muted small mt-1">
                                    Free plan limits deals to {{ (int) data_get($plans, 'free.deals.max', 25) }}.
                                </div>
                            @else
                                <div class="text-muted small mt-1">
                                    You’re on {{ $currentPlanLabel }} — enjoy the feature set.
                                </div>
                            @endif
                        </div>

                        <span
                            class="badge rounded-pill {{ $effectivePlanKey === 'free' ? 'text-bg-secondary' : 'text-bg-success' }}">
                            {{ strtoupper($effectivePlanKey) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Plans list (all public plans) --}}
            @foreach ($publicPlans as $planKey => $cfg)
                @php
                    $label = data_get($cfg, 'label', ucfirst(str_replace('_', ' ', $planKey)));

                    $pMonthly = data_get($pricing, "{$planKey}.monthly", null);
                    $pYearly = data_get($pricing, "{$planKey}.yearly", null);

                    $isCurrent = (string) $effectivePlanKey === (string) $planKey;

                    // ✅ Feature list (prefer features_ui if you add it; fallback to enabled feature flags)
                    $features = (array) data_get($cfg, 'features_ui', []);
                    if (empty($features)) {
                        $enabled = collect((array) data_get($cfg, 'features', []))
                            ->filter(fn($v) => (bool) $v)
                            ->keys()
                            ->map(fn($k) => ucfirst(str_replace('_', ' ', $k)))
                            ->values()
                            ->all();

                        $features = array_slice($enabled, 0, 10);
                    }

                    // ✅ Purchasable plans: Premium + Business
                    $canBuy = in_array($planKey, ['premium', 'business'], true);
                @endphp

                <div class="col-12 col-lg-6">
                    <div class="card h-100 {{ $isCurrent ? 'border-dark' : '' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1">{{ $label }}</h5>
                                    <div class="text-muted small">
                                        @if ($isCurrent && $trialState === 'active')
                                            Trial ends {{ $trialEndsDate }} • {{ $trialDaysLeft }}
                                            day{{ $trialDaysLeft === 1 ? '' : 's' }} left
                                        @else
                                            Plan: <code>{{ $planKey }}</code>
                                        @endif
                                    </div>
                                </div>

                                @if ($isCurrent)
                                    <span class="badge text-bg-success">CURRENT</span>
                                @else
                                    <span class="badge text-bg-light text-dark border">{{ strtoupper($planKey) }}</span>
                                @endif
                            </div>

                            <div class="mt-3">
                                <div class="d-flex gap-3 flex-wrap">
                                    <div>
                                        <div class="text-muted small">Monthly</div>
                                        <div class="fs-5 fw-semibold">
                                            {{ $pMonthly ? $fmt(data_get($pMonthly, 'amount')) : '—' }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Yearly</div>
                                        <div class="fs-5 fw-semibold">
                                            {{ $pYearly ? $fmt(data_get($pYearly, 'amount')) : '—' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            {{-- ✅ Feature list --}}
                            @if (!empty($features))
                                <ul class="mb-4">
                                    @foreach ($features as $f)
                                        <li>{{ $f }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-muted small mb-4">No feature list configured.</div>
                            @endif

                            @if ($isCurrent)
                                <button class="btn btn-secondary w-100" disabled>You’re on this plan</button>
                            @else
                                @if ($canBuy)
                                    <div class="d-grid gap-2">
                                        {{-- NOTE: add 'plan' field; controller must accept it --}}
                                        <form method="POST"
                                            action="{{ tenant_route('tenant.billing.paystack.initialize') }}">
                                            @csrf
                                            <input type="hidden" name="plan" value="{{ $planKey }}">
                                            <input type="hidden" name="cycle" value="monthly">
                                            <button class="btn btn-primary w-100">Upgrade (Monthly)</button>
                                        </form>

                                        <form method="POST"
                                            action="{{ tenant_route('tenant.billing.paystack.initialize') }}">
                                            @csrf
                                            <input type="hidden" name="plan" value="{{ $planKey }}">
                                            <input type="hidden" name="cycle" value="yearly">
                                            <button class="btn btn-outline-primary w-100">Upgrade (Yearly)</button>
                                        </form>

                                        <div class="text-muted small">
                                            You’ll be redirected to Paystack to complete payment.
                                        </div>
                                    </div>
                                @else
                                    <button class="btn btn-outline-secondary w-100" disabled>Not purchasable yet</button>
                                    <div class="text-muted small mt-2">
                                        This plan is currently assigned manually.
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Notes --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold mb-1">Notes</div>
                        <ul class="mb-0 text-muted">
                            @if ($trialEnabled)
                                <li>Trial length: <b>{{ $trialDays }} days</b> (only if you haven’t used a trial
                                    before).</li>
                            @endif
                            <li>Billing is handled via <b>Paystack</b> recurring subscriptions.</li>
                            <li>You can cancel anytime; access continues until the end of the paid period.</li>
                        </ul>

                        @if ($paystackConfigured)
                            <div class="text-muted small mt-3">
                                <span class="badge bg-light text-dark border">Paystack plans configured</span>
                            </div>
                        @else
                            <div class="text-muted small mt-3">
                                <span class="badge bg-warning text-dark">Paystack plan codes missing</span>
                                <span class="ms-2">Add plan codes in your env/config so recurring subscriptions
                                    work.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
