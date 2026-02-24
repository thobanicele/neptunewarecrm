<div class="sidebar-content js-simplebar">
    <a class="sidebar-brand" href="#">
        <span class="align-middle">
            <img src="{{ asset('asset/img/icons/large_icon.png') }}" alt="NeptuneWare CRM" width="28" height="28">
            NeptuneWare CRM
        </span>
    </a>

    @php
        // Detect tenant context safely
        $hasTenant = app()->bound('tenant');
        $t = $hasTenant ? app()->make('tenant') : null;

        // For platform owner "back to my workspace"
        $myTenantSub = auth()->user()?->tenant?->subdomain;

        // Route-state helpers
        $isPlatformOwner = (bool) (auth()->user()?->is_platform_owner);

        // Dropdown open states (tenant routes only)
        $openItems = $hasTenant && (
            request()->routeIs('tenant.products.*') ||
            request()->routeIs('tenant.brands.*') ||
            request()->routeIs('tenant.categories.*')
        );

        $openSales = $hasTenant && (
            request()->routeIs('tenant.quotes.*') ||
            request()->routeIs('tenant.sales-orders.*') ||
            request()->routeIs('tenant.invoices.*') ||
            request()->routeIs('tenant.credit-notes.*') ||
            request()->routeIs('tenant.payments.*')
        );

        // Ecommerce visibility (tenant only)
        $showEcommerce = false;
        $internalOnly = (bool) config('ecommerce_internal.only', true);

        if ($hasTenant && $t) {
            $isAllowed = tenant_is_internal_allowed($t);

            $hasEcommerceModule = tenant_feature($t, 'ecommerce_module', false);
            $hasEcommerceInbound = tenant_feature($t, 'ecommerce_inbound_api', false);

            $showEcommerce = $hasEcommerceModule && $hasEcommerceInbound && (!$internalOnly || $isAllowed);
        }
    @endphp

    <ul class="sidebar-nav">

        {{-- =========================================================
            PLATFORM ADMIN SIDEBAR (no tenant in URL)
        ========================================================== --}}
        @if (!$hasTenant)
            @if ($isPlatformOwner)
                <li class="sidebar-item {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('admin.tenants.index') }}">
                        <i class="align-middle" data-feather="shield"></i>
                        <span class="align-middle">Platform: Tenants</span>
                    </a>
                </li>
            @endif

            @if ($myTenantSub)
                <li class="sidebar-item">
                    <a class="sidebar-link" href="{{ url('/t/'.$myTenantSub.'/dashboard') }}">
                        <i class="align-middle" data-feather="arrow-left"></i>
                        <span class="align-middle">Back to Workspace</span>
                    </a>
                </li>
            @endif

            <li class="sidebar-item">
                <a class="sidebar-link" href="{{ route('app.home') }}">
                    <i class="align-middle" data-feather="home"></i>
                    <span class="align-middle">App Home</span>
                </a>
            </li>

        {{-- =========================================================
            TENANT SIDEBAR (normal app)
        ========================================================== --}}
        @else
            <li class="sidebar-item {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ tenant_route('tenant.dashboard') }}">
                    <i class="align-middle" data-feather="home"></i> <span class="align-middle">Home</span>
                </a>
            </li>

            <li class="sidebar-item {{ request()->routeIs('tenant.leads.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ tenant_route('tenant.leads.index') }}">
                    <i class="align-middle" data-feather="target"></i> <span class="align-middle">Leads</span>
                </a>
            </li>

            <li class="sidebar-item {{ request()->routeIs('tenant.contacts.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ tenant_route('tenant.contacts.index') }}">
                    <i class="align-middle" data-feather="user"></i> <span class="align-middle">Contacts</span>
                </a>
            </li>

            <li class="sidebar-item {{ request()->routeIs('tenant.companies.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ tenant_route('tenant.companies.index') }}">
                    <i class="align-middle" data-feather="layers"></i> <span class="align-middle">Companies</span>
                </a>
            </li>

            <li class="sidebar-item {{ request()->routeIs('tenant.deals.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ tenant_route('tenant.deals.index') }}">
                    <i class="align-middle" data-feather="credit-card"></i>
                    <span class="align-middle">Deals</span>
                </a>
            </li>

            {{-- Activities --}}
            <li class="sidebar-item {{ request()->routeIs('tenant.activities.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ tenant_route('tenant.activities.followups') }}">
                    <i class="align-middle" data-feather="list"></i> <span class="align-middle">Activities</span>
                    @if (($overdueFollowupsCount ?? 0) > 0)
                        <span class="badge rounded-pill bg-danger ms-auto">
                            {{ $overdueFollowupsCount }}
                        </span>
                    @endif
                </a>
            </li>

            {{-- ITEMS (DROPDOWN) --}}
            <li class="sidebar-item {{ $openItems ? 'active' : '' }}">
                <a data-bs-target="#itemsMenu" data-bs-toggle="collapse"
                    class="sidebar-link d-flex align-items-center {{ $openItems ? '' : 'collapsed' }}" href="#">
                    <span class="nw-caret-left me-2">›</span>
                    <i class="align-middle" data-feather="package"></i>
                    <span class="align-middle ms-2">Items</span>
                </a>
                <ul id="itemsMenu" class="sidebar-dropdown list-unstyled collapse {{ $openItems ? 'show' : '' }}">
                    <li class="sidebar-item {{ request()->routeIs('tenant.products.*') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ tenant_route('tenant.products.index') }}">
                            <span class="align-middle">Products</span>
                        </a>
                    </li>

                    <li class="sidebar-item {{ request()->routeIs('tenant.brands.*') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ tenant_route('tenant.brands.index') }}">
                            <span class="align-middle">Brands</span>
                        </a>
                    </li>

                    <li class="sidebar-item {{ request()->routeIs('tenant.categories.*') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ tenant_route('tenant.categories.index') }}">
                            <span class="align-middle">Categories</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- SALES (DROPDOWN) --}}
            <li class="sidebar-item {{ $openSales ? 'active' : '' }}">
                <a data-bs-target="#salesMenu" data-bs-toggle="collapse"
                    class="sidebar-link d-flex align-items-center {{ $openSales ? '' : 'collapsed' }}" href="#">
                    <span class="nw-caret-left me-2">›</span>
                    <i class="align-middle" data-feather="shopping-cart"></i>
                    <span class="align-middle ms-2">Sales</span>
                </a>
                <ul id="salesMenu" class="sidebar-dropdown list-unstyled collapse {{ $openSales ? 'show' : '' }}">
                    <li class="sidebar-item {{ request()->routeIs('tenant.quotes.*') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ tenant_route('tenant.quotes.index') }}">
                            <span class="align-middle">Quotes</span>
                        </a>
                    </li>

                    <li class="sidebar-item {{ request()->routeIs('tenant.sales-orders.*') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ tenant_route('tenant.sales-orders.index') }}">
                            <span class="align-middle">Sales Orders</span>
                        </a>
                    </li>

                    <li class="sidebar-item {{ request()->routeIs('tenant.invoices.*') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ tenant_route('tenant.invoices.index') }}">
                            <span class="align-middle">Invoices</span>
                        </a>
                    </li>

                    <li class="sidebar-item {{ request()->routeIs('tenant.credit-notes.*') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ tenant_route('tenant.credit-notes.index') }}">
                            <span class="align-middle">Credit Notes</span>
                        </a>
                    </li>

                    <li class="sidebar-item {{ request()->routeIs('tenant.payments.*') ? 'active' : '' }}">
                        <a class="sidebar-link" href="{{ tenant_route('tenant.payments.index') }}">
                            <span class="align-middle">Payments</span>
                        </a>
                    </li>
                </ul>
            </li>

            {{-- Ecommerce Orders --}}
            @if ($showEcommerce)
                <li class="sidebar-item {{ request()->routeIs('tenant.ecommerce-orders.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ tenant_route('tenant.ecommerce-orders.index') }}">
                        <i class="align-middle" data-feather="shopping-cart"></i>
                        <span class="align-middle">Ecommerce Orders</span>
                        @if ($internalOnly)
                            <span class="badge rounded-pill bg-warning text-dark ms-auto">Internal</span>
                        @endif
                    </a>
                </li>
            @endif

            {{-- Settings --}}
            <li class="sidebar-item {{ request()->routeIs('tenant.settings.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ tenant_route('tenant.settings.index') }}">
                    <i class="align-middle" data-feather="settings"></i>
                    <span class="align-middle">Settings</span>
                </a>
            </li>

            {{-- Platform owner quick link even inside tenant --}}
            @if ($isPlatformOwner)
                <li class="sidebar-item {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}">
                    <a class="sidebar-link" href="{{ route('admin.tenants.index') }}">
                        <i class="align-middle" data-feather="shield"></i>
                        <span class="align-middle">Platform: Tenants</span>
                    </a>
                </li>
            @endif
        @endif
    </ul>
</div>