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
            <a class="btn btn-outline-secondary"
                href="{{ tenant_route('tenant.dashboard', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                Back
            </a>
        </div>

        {{-- Organization Settings --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="fw-semibold mb-3">Organization Settings</div>

                <div class="row g-3">
                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Organization" icon="fa-building" tone="success">
                            <a class="nw-link"
                                href="{{ tenant_route('tenant.settings.edit', ['tenant' => $tenant->subdomain ?? $tenant]) }}">Profile</a>
                            <a class="nw-link" href="#">Branding <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                            <a class="nw-link" href="#">Custom Domain <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                            <a class="nw-link" href="#">Branches <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                            <a class="nw-link" href="{{ tenant_route('tenant.billing.upgrade') }}">Manage Subscription</a>
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
                                    Roles & Permissions <span class="badge bg-light text-muted ms-auto">Admin</span>
                                </a>
                            @endhasanyrole

                            <a class="nw-link" href="#">User Preferences <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Taxes & Compliance" icon="fa-receipt" tone="primary">
                            <a class="nw-link" href="{{ tenant_route('tenant.tax-types.index') }}">Tax Types (VAT)</a>
                            <a class="nw-link" href="#">Taxes <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Setup & Configurations" icon="fa-gear" tone="warning">
                            <a class="nw-link" href="#">General <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                            <a class="nw-link" href="#">Currencies <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                            <a class="nw-link" href="#">Reminders <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                        </x-tenant.settings.tile>
                    </div>
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
                            <a class="nw-link" href="#">Quotes <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                            <a class="nw-link" href="#">Invoices <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Customization" icon="fa-palette" tone="info">
                            <a class="nw-link" href="#">PDF Templates <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                            <a class="nw-link" href="#">Email Notifications <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                        </x-tenant.settings.tile>
                    </div>

                    <div class="col-12 col-md-6 col-xl-3">
                        <x-tenant.settings.tile title="Automation" icon="fa-bolt" tone="danger">
                            <a class="nw-link" href="#">Workflow Rules <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
                            <a class="nw-link" href="#">Workflow Logs <span
                                    class="badge bg-light text-muted ms-auto">Soon</span></a>
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
