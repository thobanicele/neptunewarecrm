@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-1">Workspace Settings</h1>
                <div class="text-muted small">
                    Manage your workspace configuration and modules.
                </div>
            </div>

            <div class="d-flex gap-2">
                {{-- ✅ Platform Admin (platform owner only) --}}
                @if (auth()->user()?->is_platform_owner)
                    <a class="btn btn-outline-primary" href="{{ route('admin.tenants.index') }}">
                        <i class="fa-solid fa-shield-halved me-2"></i> Platform Admin
                    </a>
                @endif

                <a class="btn btn-outline-secondary"
                    href="{{ tenant_route('tenant.dashboard', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                    Back
                </a>
            </div>
        </div>

        @php
            $t = tenant();

            // Internal-only ecommerce rollout controls
            $internalOnly = (bool) config('ecommerce_internal.only', true);
            $allowed = array_values(
                array_filter(array_map('trim', explode(',', (string) config('ecommerce_internal.allowed', '')))),
            );

            $isAllowed = $t
                ? in_array((string) $t->id, $allowed, true) ||
                    (!empty($t->subdomain) && in_array((string) $t->subdomain, $allowed, true))
                : false;

            // Feature flags (plan/add-on)
            $hasEcommerceModule = $t ? tenant_feature($t, 'ecommerce_module', false) : false;
            $hasEcommerceInbound = $t ? tenant_feature($t, 'ecommerce_inbound_api', false) : false;
            $canEnableEcommAddon =
                auth()
                    ->user()
                    ?->hasAnyRole(['super_admin', 'tenant_owner', 'tenant_admin']) &&
                (!$internalOnly || $isAllowed);
            // Final visibility (feature + allowlist safety switch)
            $showEcommerce = $hasEcommerceModule && $hasEcommerceInbound && (!$internalOnly || $isAllowed);

            // Explain why ecommerce is hidden (for admin clarity)
            $ecommReason = null;
            if (!$hasEcommerceModule || !$hasEcommerceInbound) {
                $ecommReason = 'Not enabled';
            } elseif ($internalOnly && !$isAllowed) {
                $ecommReason = 'Internal only';
            }

            // Subscription label (optional)
            $planLabel = $t?->plan ?? 'free';
        @endphp

        {{-- Organization Settings --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="fw-semibold mb-3">Organization Settings</div>

                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Organization" icon="fa-building" tone="success">
                            <a class="nw-link"
                                href="{{ tenant_route('tenant.settings.edit', ['tenant' => $tenant->subdomain ?? $tenant]) }}">Profile</a>

                            <a class="nw-link"
                                href="{{ tenant_route('tenant.settings.branding', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                                Branding
                            </a>

                            <a class="nw-link" href="#">
                                Custom Domain
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>

                            <a class="nw-link" href="#">
                                Branches
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>

                            <a class="nw-link" href="{{ tenant_route('tenant.billing.upgrade') }}">
                                Manage Subscription
                                <span
                                    class="badge bg-light text-dark border ms-auto text-capitalize">{{ $planLabel }}</span>
                            </a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Users & Roles" icon="fa-users" tone="danger">
                            <a class="nw-link"
                                href="{{ tenant_route('tenant.settings.users.index', ['tenant' => $tenant->subdomain ?? $tenant]) }}">Users</a>

                            @hasanyrole('super_admin|tenant_owner|tenant_admin')
                                <a class="nw-link"
                                    href="{{ tenant_route('tenant.settings.roles.index', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                                    Roles & Permissions
                                </a>
                            @else
                                <a class="nw-link" href="javascript:void(0)" onclick="return false;" style="opacity:.6">
                                    Roles & Permissions
                                    <span class="badge bg-light text-muted ms-auto">Admin</span>
                                </a>
                            @endhasanyrole

                            <a class="nw-link" href="#">
                                User Preferences
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Taxes & Compliance" icon="fa-receipt" tone="primary">
                            <a class="nw-link" href="{{ tenant_route('tenant.tax-types.index') }}">Tax Types (VAT)</a>
                            <a class="nw-link" href="#">
                                Taxes
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Setup & Configurations" icon="fa-gear" tone="warning">
                            <a class="nw-link" href="#">
                                General
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>
                            <a class="nw-link" href="#">
                                Currencies
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>
                            <a class="nw-link" href="#">
                                Reminders
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>

                            {{-- ✅ Ecommerce API belongs in Settings/Config --}}
                            @if (!$showEcommerce)
                                <div class="mt-2">
                                    <div class="text-muted small">
                                        Status:
                                        <span class="badge bg-light text-muted ms-1">
                                            {{ $ecommReason ?? 'Locked' }}
                                        </span>
                                    </div>

                                    @if ($canEnableEcommAddon)
                                        <form class="mt-2" method="POST"
                                            action="{{ tenant_route('tenant.settings.addons.enable') }}">
                                            @csrf
                                            <input type="hidden" name="addon" value="ecommerce">
                                            <button class="btn btn-sm btn-primary w-100">
                                                Enable Ecommerce Add-on
                                            </button>
                                        </form>
                                        <div class="text-muted small mt-2">
                                            Enables Ecommerce Orders + Inbound API for this workspace.
                                        </div>
                                    @else
                                        <div class="text-muted small mt-2">
                                            You can’t enable this yet
                                            ({{ $internalOnly ? 'internal rollout' : 'not available' }}).
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </x-tenant.settings.tile>
                    </div>

                    {{-- ✅ Platform Owner tile (platform owner only) --}}
                    @if (auth()->user()?->is_platform_owner)
                        <div class="col-12 col-md-6 col-xl-3">
                            <x-tenant.settings.tile title="Platform Owner" icon="fa-shield-halved" tone="secondary">
                                <a class="nw-link" href="{{ route('admin.tenants.index') }}">
                                    Tenants (All Workspaces)
                                    <span class="badge bg-light text-dark border ms-auto">Admin</span>
                                </a>

                                <a class="nw-link" href="{{ route('admin.tenants.index', ['plan' => 'free']) }}">
                                    Free Plan Tenants
                                    <span class="badge bg-light text-muted ms-auto">Filter</span>
                                </a>

                                <a class="nw-link" href="{{ route('admin.tenants.index', ['plan' => 'premium']) }}">
                                    Premium Tenants
                                    <span class="badge bg-light text-muted ms-auto">Filter</span>
                                </a>

                                <a class="nw-link" href="{{ route('admin.tenants.index', ['plan' => 'business']) }}">
                                    Business Tenants
                                    <span class="badge bg-light text-muted ms-auto">Filter</span>
                                </a>
                            </x-tenant.settings.tile>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Module Settings --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="fw-semibold mb-3">Module Settings</div>

                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Sales" icon="fa-cart-shopping" tone="success">
                            <a class="nw-link" href="{{ tenant_route('tenant.quotes.index') }}">Quotes</a>
                            <a class="nw-link" href="{{ tenant_route('tenant.sales-orders.index') }}">Sales Orders</a>
                            <a class="nw-link" href="{{ tenant_route('tenant.invoices.index') }}">Invoices</a>
                            <a class="nw-link" href="{{ tenant_route('tenant.payments.index') }}">Payments</a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Items / Catalog" icon="fa-box" tone="info">
                            <a class="nw-link" href="{{ tenant_route('tenant.products.index') }}">Products</a>
                            <a class="nw-link" href="{{ tenant_route('tenant.brands.index') }}">Brands</a>
                            <a class="nw-link" href="{{ tenant_route('tenant.categories.index') }}">Categories</a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Customization" icon="fa-palette" tone="secondary">
                            <a class="nw-link" href="#">
                                PDF Templates
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>
                            <a class="nw-link" href="#">
                                Email Notifications
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="E-Commerce" icon="fa-store" tone="primary">

                            @if ($showEcommerce)
                                <a class="nw-link" href="{{ tenant_route('tenant.ecommerce-orders.index') }}">
                                    Ecommerce Orders
                                    @if ($internalOnly)
                                        <span class="badge bg-warning text-dark ms-auto">Internal</span>
                                    @endif
                                </a>
                            @else
                                <a class="nw-link" href="javascript:void(0)" onclick="return false;" style="opacity:.6">
                                    Ecommerce Orders
                                    <span class="badge bg-light text-muted ms-auto">{{ $ecommReason ?? 'Locked' }}</span>
                                </a>
                            @endif

                            <a class="nw-link" href="#">
                                Storefront (Front-end)
                                <span class="badge bg-light text-muted ms-auto">Soon</span>
                            </a>
                        </x-tenant.settings.tile>
                    </div>

                </div>
            </div>
        </div>

    </div>
@endsection

@push('styles')
    <style>
        .nw-tile {
            border: 1px solid rgba(0, 0, 0, .06);
            border-radius: 14px;
            padding: 14px;
            background: #fff;
            transition: box-shadow .15s ease, transform .15s ease;
            height: 100%;
        }

        .nw-tile:hover {
            box-shadow: 0 8px 22px rgba(0, 0, 0, .08);
            transform: translateY(-1px);
        }

        .nw-tile__head {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .nw-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .nw-links {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-top: 8px;
        }

        .nw-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            text-decoration: none;
            color: inherit;
        }

        .nw-link:hover {
            background: rgba(0, 0, 0, .04);
        }
    </style>
@endpush
