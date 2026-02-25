<nav id="frontendNavbar" class="navbar navbar-expand-lg navbar-light bg-white border-bottom py-2">
    <div class="container-fluid">

        <a class="navbar-brand d-flex align-items-center py-0" href="{{ url('/') }}">
            <img src="{{ asset('asset/img/Raster_1024x1024_Transparent.png') }}" alt="NeptuneWare"
                style="height:34px;width:auto;display:block;">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">

            {{-- Left links --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link py-1 {{ request()->routeIs('pricing') ? 'active' : '' }}"
                        href="{{ route('home.pricing') }}">
                        Pricing
                    </a>
                </li>

                {{-- <li class="nav-item">
                    <a class="nav-link py-1 {{ request()->routeIs('shop.*') ? 'active' : '' }}"
                        href="#">
                        Shop
                    </a>
                </li> --}}

                {{-- Other Products dropdown --}}
                {{-- <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-1 {{ request()->routeIs('products.*') ? 'active' : '' }}"
                        href="#" id="otherProductsDropdown" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Other Products
                    </a>

                    <ul class="dropdown-menu" aria-labelledby="otherProductsDropdown">
                        <li>
                            <a class="dropdown-item" href="#">
                                Smart Lighting
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                Inventory & Procurement
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                Billing & Invoicing Suite
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item" href="#">
                                View all products
                            </a>
                        </li>
                    </ul>
                </li> --}}
            </ul>

            {{-- Right links --}}
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2 mb-2 mb-lg-0">

                @auth
                    @php $tenant = auth()->user()->tenant; @endphp

                    @if ($tenant)
                        <li class="nav-item">
                            <a class="nav-link py-1" href="{{ url('/t/' . $tenant->subdomain . '/dashboard') }}">
                                Dashboard
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link py-1" href="{{ url('/') }}">Dashboard</a>
                        </li>
                    @endif

                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary ms-lg-2">
                                Logout
                            </button>
                        </form>
                    </li>
                @endauth

                @guest
                    <li class="nav-item">
                        <a class="nav-link py-1" href="{{ route('login') }}">Sign In</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-primary ms-lg-2" href="{{ route('register') }}">
                            Sign Up
                        </a>
                    </li>
                @endguest

            </ul>

        </div>
    </div>
</nav>
