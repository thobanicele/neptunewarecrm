@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        @php
            $dashUrl = url('/t/' . $tenant->subdomain . '/dashboard');
            $settingsUrl = url('/t/' . $tenant->subdomain . '/settings');
            $billingUrl = url('/t/' . $tenant->subdomain . '/billing/upgrade');

            $planKey = strtolower((string) ($effectivePlanKey ?? ($tenant->plan ?? 'free')));
            $planCls = match ($planKey) {
                'premium' => 'text-bg-success',
                'business' => 'text-bg-warning text-dark',
                'internal_neptuneware' => 'text-bg-dark',
                default => 'text-bg-secondary',
            };

            $prettyLimit = function ($v) {
                if (is_null($v)) {
                    return 'Unlimited';
                }
                if ($v === '') {
                    return '—';
                }
                return (string) $v;
            };

            $pct = function ($used, $limit) {
                $u = (int) ($used ?? 0);
                if (is_null($limit) || (int) $limit <= 0) {
                    return null;
                } // unlimited or invalid
                return min(100, (int) round(($u / (int) $limit) * 100));
            };

            $usageRows = [
                [
                    'label' => 'Users',
                    'used' => $usage['users'] ?? 0,
                    'limit' => $limits['users'] ?? null,
                    'help' => 'Total users in this tenant.',
                ],
                [
                    'label' => 'Deals',
                    'used' => $usage['deals'] ?? 0,
                    'limit' => $limits['deals'] ?? null,
                    'help' => 'Total deals in this tenant.',
                ],
                [
                    'label' => 'Pipelines',
                    'used' => $usage['pipelines'] ?? 0,
                    'limit' => $limits['pipelines'] ?? null,
                    'help' => 'Total pipelines configured.',
                ],
                [
                    'label' => 'Invoices (MTD)',
                    'used' => $usage['invoices_mtd'] ?? 0,
                    'limit' => $limits['invoices_per_month'] ?? null,
                    'help' => 'Invoices created this month.',
                ],
                [
                    'label' => 'Sales Orders (MTD)',
                    'used' => $usage['sales_orders_mtd'] ?? 0,
                    'limit' => $limits['sales_orders_per_month'] ?? null,
                    'help' => 'Sales orders created this month.',
                ],
            ];
        @endphp

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <div>
                <div class="text-muted small mb-1">
                    <a href="{{ route('admin.tenants.index') }}" class="text-decoration-none">Tenants</a>
                    <span class="mx-2">/</span>
                    <span class="text-muted">{{ $tenant->subdomain }}</span>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h3 class="mb-0">{{ $tenant->name }}</h3>
                    <span class="badge {{ $planCls }}">{{ strtoupper($planKey) }}</span>
                    <span class="badge bg-light text-dark border">{{ $planLabel ?? strtoupper($planKey) }}</span>

                    @if (!empty($tenant->status))
                        <span class="badge bg-light text-dark border text-capitalize">{{ $tenant->status }}</span>
                    @endif
                </div>

                <div class="text-muted small mt-1">
                    Registered {{ optional($tenant->created_at)->format('Y-m-d H:i') }}
                    @if ($tenant->last_seen_at)
                        • Last seen {{ $tenant->last_seen_at->diffForHumans() }}
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-primary" href="{{ $dashUrl }}" target="_blank">Open Dashboard</a>
                <a class="btn btn-outline-secondary" href="{{ $settingsUrl }}" target="_blank">Open Settings</a>
                <a class="btn btn-outline-secondary" href="{{ $billingUrl }}" target="_blank">Billing</a>
                <a class="btn btn-light" href="{{ route('admin.tenants.index') }}">Back</a>
            </div>
        </div>

        {{-- Trial status strip --}}
        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Trial Status</div>
                    <div class="text-muted small">
                        @if ($trialState === 'active')
                            <span class="badge bg-warning text-dark border">
                                Active • ends {{ optional($trialEndsAt)->format('Y-m-d') }} • {{ $trialDaysLeft }}
                                day{{ ($trialDaysLeft ?? 0) === 1 ? '' : 's' }} left
                            </span>
                        @elseif($trialState === 'ended')
                            <span class="badge bg-light text-dark border">
                                Ended • ended {{ optional($trialEndsAt)->format('Y-m-d') }}
                            </span>
                        @elseif($trialState === 'eligible')
                            <span class="badge bg-light text-dark border">
                                Eligible • {{ $trialDays }}-day trial (if started)
                            </span>
                        @else
                            <span class="badge bg-light text-dark border">Not enabled</span>
                        @endif
                    </div>
                </div>

                <div class="text-muted small">
                    Provider: {{ $sub?->provider ?? '—' }} • Cycle: {{ $sub?->cycle ?? '—' }}
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Left: Tenant + Owner --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="fw-semibold mb-3">Tenant Info</div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="text-muted small">Subdomain</div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="fw-semibold">{{ $tenant->subdomain }}</div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="navigator.clipboard.writeText('{{ $tenant->subdomain }}')">Copy</button>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="text-muted small">Dashboard URL</div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="{{ $dashUrl }}" target="_blank"
                                        class="text-decoration-none small">{{ $dashUrl }}</a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="navigator.clipboard.writeText('{{ $dashUrl }}')">Copy</button>
                                </div>
                            </div>

                            <div class="col-6 mt-2">
                                <div class="text-muted small">Registered</div>
                                <div class="fw-semibold">{{ optional($tenant->created_at)->format('Y-m-d H:i') }}</div>
                            </div>

                            <div class="col-6 mt-2">
                                <div class="text-muted small">Last seen</div>
                                <div class="fw-semibold">
                                    {{ $tenant->last_seen_at ? $tenant->last_seen_at->diffForHumans() : '—' }}</div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="fw-semibold mb-2">Owner</div>
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded bg-light border d-flex align-items-center justify-content-center"
                                style="width:44px; height:44px;">
                                <span class="fw-semibold text-muted">
                                    {{ strtoupper(substr($tenant->owner?->name ?? '—', 0, 1)) }}
                                </span>
                            </div>

                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $tenant->owner?->name ?? '—' }}</div>
                                <div class="text-muted small d-flex align-items-center gap-2">
                                    <span>{{ $tenant->owner?->email ?? '—' }}</span>
                                    @if ($tenant->owner?->email)
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="navigator.clipboard.writeText('{{ $tenant->owner->email }}')">Copy</button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="text-muted small">
                            Effective plan is <b>{{ $planLabel }}</b> (trial-aware). Limits below are based on this.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Usage vs Plan Limits --}}
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="fw-semibold mb-3">Usage vs Plan Limits</div>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($usageRows as $row)
                                @php
                                    $used = (int) $row['used'];
                                    $limit = $row['limit'];
                                    $percent = $pct($used, $limit);
                                @endphp

                                <div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="fw-semibold">{{ $row['label'] }}</div>
                                        <div class="text-muted small">
                                            {{ $used }} / {{ $prettyLimit($limit) }}
                                        </div>
                                    </div>

                                    @if (!is_null($percent))
                                        <div class="progress mt-2" style="height: 10px;">
                                            <div class="progress-bar" role="progressbar"
                                                style="width: {{ $percent }}%;" aria-valuenow="{{ $percent }}"
                                                aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                        <div class="text-muted small mt-1">{{ $row['help'] }}</div>
                                    @else
                                        <div class="text-muted small mt-1">{{ $row['help'] }} • <b>Unlimited</b></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <hr class="my-3">

                        <div class="text-muted small">
                            Month-to-date counts reset on the 1st of each month.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Company/PDF Details --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="fw-semibold mb-3">Company / PDF Details</div>

                        <div class="row g-3">
                            <div class="col-12 col-lg-4">
                                <div class="text-muted small">VAT Number</div>
                                <div class="fw-semibold">{{ $tenant->vat_number ?: '—' }}</div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="text-muted small">Registration Number</div>
                                <div class="fw-semibold">{{ $tenant->registration_number ?: '—' }}</div>
                            </div>

                            <div class="col-12 col-lg-4">
                                <div class="text-muted small">Plan Storage Limit</div>
                                <div class="fw-semibold">{{ $prettyLimit($limits['storage_mb'] ?? null) }} MB</div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="text-muted small">Company Address</div>
                                <div class="border rounded p-3 small" style="white-space: pre-line;">
                                    {{ $tenant->company_address ?: '—' }}
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="text-muted small">Bank Details</div>
                                <div class="border rounded p-3 small" style="white-space: pre-line;">
                                    {{ $tenant->bank_details ?: '—' }}
                                </div>
                            </div>
                        </div>

                        <div class="text-muted small mt-3">
                            Next upgrades: revenue, MTD cash, overdue invoices, feature usage, audit logs.
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
@endsection
