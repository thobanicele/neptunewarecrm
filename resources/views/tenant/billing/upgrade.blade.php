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
            $currency = $paystack['currency'] ?? 'ZAR';

            $monthly = $pricing['monthly'] ?? null;
            $yearly = $pricing['yearly'] ?? null;

            $isPremium = ($tenant->plan ?? 'free') === 'premium' || ($sub->plan ?? 'free') === 'premium';

            $trialLabel = null;
            if ($trialEnabled) {
                if (!is_null($trialDaysLeft)) {
                    $trialLabel = $trialDaysLeft > 0 ? "Trial: {$trialDaysLeft} days left" : 'Trial ended';
                } else {
                    $trialLabel = "Includes {$trialDays}-day trial (if eligible)";
                }
            }

            $fmt = fn($amount) => $currency . ' ' . number_format((float) $amount, 2);
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
                            <div class="fs-5 fw-semibold text-capitalize">{{ $tenant->plan ?? 'free' }}</div>

                            @if (!$isPremium)
                                <div class="text-muted small mt-1">
                                    Free plan limits deals to {{ data_get(config('plans.plans.free.deals'), 'max', 25) }}.
                                </div>
                            @else
                                <div class="text-muted small mt-1">
                                    You’re on Premium — enjoy the full feature set.
                                </div>
                            @endif

                            @if ($trialEnabled)
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark">{{ $trialLabel }}</span>
                                </div>
                            @endif
                        </div>

                        @if ($isPremium)
                            <span class="badge rounded-pill text-bg-success">PREMIUM</span>
                        @else
                            <span class="badge rounded-pill text-bg-secondary">FREE</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Plans --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">{{ data_get($monthly, 'label', 'Premium Monthly') }}</h5>
                                <div class="text-muted small">
                                    Billed monthly
                                    @if ($trialEnabled)
                                        • {{ $trialLabel }}
                                    @endif
                                </div>
                            </div>
                            <span class="badge text-bg-primary">MONTHLY</span>
                        </div>

                        <div class="mt-3">
                            <div class="display-6 fw-semibold mb-0">
                                {{ $monthly ? $fmt($monthly['amount']) : '—' }}
                            </div>
                            <div class="text-muted small">per month</div>
                        </div>

                        <hr>

                        <ul class="mb-4">
                            <li>Unlimited deals</li>
                            <li>Exports (Excel)</li>
                            <li>Invoice email sending</li>
                            <li>Dashboards & reports</li>
                        </ul>

                        @if ($isPremium)
                            <button class="btn btn-secondary w-100" disabled>
                                You’re already Premium
                            </button>
                        @else
                            <form method="POST" action="{{ tenant_route('tenant.billing.paystack.initialize') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="monthly">
                                <button class="btn btn-primary w-100">
                                    Upgrade (Monthly)
                                </button>
                            </form>
                            <div class="text-muted small mt-2">
                                You’ll be redirected to Paystack to complete payment.
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card h-100 border-dark">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">{{ data_get($yearly, 'label', 'Premium Yearly') }}</h5>
                                <div class="text-muted small">
                                    Billed yearly
                                    @if ($trialEnabled)
                                        • {{ $trialLabel }}
                                    @endif
                                </div>
                            </div>
                            <span class="badge text-bg-dark">YEARLY</span>
                        </div>

                        <div class="mt-3">
                            <div class="display-6 fw-semibold mb-0">
                                {{ $yearly ? $fmt($yearly['amount']) : '—' }}
                            </div>
                            <div class="text-muted small">per year</div>
                        </div>

                        <hr>

                        <ul class="mb-4">
                            <li>Everything in Monthly</li>
                            <li>Best value for teams</li>
                            <li>Less admin work</li>
                        </ul>

                        @if ($isPremium)
                            <button class="btn btn-secondary w-100" disabled>
                                You’re already Premium
                            </button>
                        @else
                            <form method="POST" action="{{ tenant_route('tenant.billing.paystack.initialize') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="yearly">
                                <button class="btn btn-outline-primary w-100">
                                    Upgrade (Yearly)
                                </button>
                            </form>
                            <div class="text-muted small mt-2">
                                You’ll be redirected to Paystack to complete payment.
                            </div>
                        @endif

                    </div>
                </div>
            </div>

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

                        @if (!empty($paystack['monthly_plan_code']) || !empty($paystack['yearly_plan_code']))
                            <div class="text-muted small mt-3">
                                <span class="badge bg-light text-dark">Paystack plans configured</span>
                            </div>
                        @else
                            <div class="text-muted small mt-3">
                                <span class="badge bg-warning text-dark">Paystack plan codes missing</span>
                                <span class="ms-2">Add plan codes in your config/env so recurring subscriptions
                                    work.</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
