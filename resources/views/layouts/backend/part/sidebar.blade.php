<div class="sidebar-content js-simplebar">
    <a class="sidebar-brand" href="#">
        <span class="align-middle">
            <img src="{{ asset('asset/img/icons/large_icon.png') }}" alt="NeptuneWare CRM" width="28" height="28">
            NeptuneWare CRM
        </span>
    </a>

    <ul class="sidebar-nav">
        <li class="sidebar-item active">
            <a class="sidebar-link" href="{{ tenant_route('tenant.dashboard') }}">
                <i class="align-middle" data-feather="home"></i> <span class="align-middle">Home</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.leads.index') }}">
                <i class="align-middle" data-feather="target"></i> <span class="align-middle">Leads</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.contacts.index') }}">
                <i class="align-middle" data-feather="user"></i> <span class="align-middle">Contacts</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.companies.index') }}">
                <i class="align-middle" data-feather="layers"></i> <span class="align-middle">Companies</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.deals.index') }}">
                <i class="align-middle" data-feather="credit-card"></i>
                <span class="align-middle">Deals</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.deals.kanban') }}">
                <i class="align-middle" data-feather="columns"></i> <span class="align-middle">Deals Kanban</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.products.index') }}">
                <i class="align-middle" data-feather="shopping-bag"></i> <span class="align-middle">Products</span>
            </a>
        </li>

        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.quotes.index') }}">
                <i class="align-middle" data-feather="file-text"></i> <span class="align-middle">Quotes</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.invoices.index') }}">
                <i class="align-middle" data-feather="file-text"></i> <span class="align-middle">Invoices</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="#">
                <i class="align-middle" data-feather="file-text"></i> <span class="align-middle">Credit Notes</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="#">
                <i class="align-middle" data-feather="dollar-sign"></i> <span class="align-middle">Payments</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.activities.followups') }}">
                <i class="align-middle" data-feather="list"></i> <span class="align-middle">Activities</span>
                @if (($overdueFollowupsCount ?? 0) > 0)
                    <span class="badge rounded-pill bg-danger ms-auto">
                        {{ $overdueFollowupsCount }}
                    </span>
                @endif
            </a>
        </li>
        <li class="sidebar-item">
            <a class="sidebar-link" href="{{ tenant_route('tenant.settings.edit') }}">
                <i class="align-middle" data-feather="settings"></i>
                <span class="align-middle">Settings</span>
            </a>
        </li>
    </ul>
</div>
