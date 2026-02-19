@extends('layouts.frontend.main')

@section('content')
    {{-- HERO --}}
    <section class="nw-hero">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <div class="nw-kicker mb-3">
                        <span class="nw-dot"></span>
                        NeptuneWare CRM • Multi-tenant
                    </div>

                    <h1 class="display-5 fw-bold nw-hero-title mb-3">
                        Close deals faster.<br class="d-none d-lg-block">
                        Stay on top of every customer.
                    </h1>

                    <p class="lead text-muted mb-4">
                        A modern, multi-tenant CRM built for teams that need leads, deals, quotes, invoices and payments in
                        one place.
                    </p>

                    <div class="d-flex flex-wrap gap-2">
                        @guest
                            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                                Start free
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">
                                Sign in
                            </a>
                        @endguest

                        @auth
                            @php $tenant = auth()->user()->tenant; @endphp
                            @if ($tenant)
                                <a href="{{ url('/t/' . $tenant->subdomain . '/dashboard') }}" class="btn btn-primary btn-lg">
                                    Go to dashboard
                                </a>
                            @else
                                <a href="{{ route('tenant.onboarding.create') }}" class="btn btn-primary btn-lg">
                                    Create workspace
                                </a>
                            @endif
                        @endauth
                    </div>

                    <div class="mt-4 small text-muted">
                        No credit card needed • Quick onboarding • Tenant-isolated workspaces
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card shadow-sm nw-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="fw-semibold">Sales pipeline</div>
                                        <span class="badge nw-badge-success">Live</span>
                                    </div>
                                    <div class="text-muted small mb-3">Track leads → deals → quotes with confidence.</div>

                                    <div class="row g-2">
                                        <div class="col-4">
                                            <div class="p-3 nw-stat">
                                                <div class="small text-muted">New</div>
                                                <div class="fw-bold">18</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="p-3 nw-stat">
                                                <div class="small text-muted">In progress</div>
                                                <div class="fw-bold">9</div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="p-3 nw-stat">
                                                <div class="small text-muted">Won</div>
                                                <div class="fw-bold">5</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card shadow-sm nw-card">
                                <div class="card-body">
                                    <div class="fw-semibold mb-2">Quotes & invoices</div>
                                    <div class="text-muted small mb-3">
                                        Generate professional PDFs, manage payments, and keep statements clean.
                                    </div>

                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge nw-badge-info">PDF</span>
                                        <span class="badge nw-badge-warn">VAT-ready</span>
                                        <span class="badge nw-badge-neutral">Exports</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> {{-- /row --}}
                </div>
            </div>
        </div>
    </section>

    {{-- TRUST / METRICS --}}
    <section class="py-5">
        <div class="container">
            <div class="row g-3 text-center">
                <div class="col-6 col-lg-3">
                    <div class="card h-100 nw-card nw-metric">
                        <div class="card-body">
                            <div class="h2 fw-bold mb-1">Leads</div>
                            <div class="text-muted">Capture & qualify</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card h-100 nw-card nw-metric">
                        <div class="card-body">
                            <div class="h2 fw-bold mb-1">Deals</div>
                            <div class="text-muted">Pipeline management</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card h-100 nw-card nw-metric">
                        <div class="card-body">
                            <div class="h2 fw-bold mb-1">Invoices</div>
                            <div class="text-muted">Bill & track payments</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card h-100 nw-card nw-metric">
                        <div class="card-body">
                            <div class="h2 fw-bold mb-1">Tenants</div>
                            <div class="text-muted">Isolated workspaces</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FEATURES --}}
    <section class="py-5 nw-section-soft">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="h1 fw-bold mb-2">Everything you need in one CRM</h2>
                <p class="text-muted mb-0">Designed for speed, clarity, and real business workflows.</p>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 nw-card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Multi-tenant workspaces</div>
                            <div class="text-muted">
                                Each company has its own isolated workspace under <code>/t/{tenant}</code>.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 nw-card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Roles & permissions</div>
                            <div class="text-muted">
                                Tenant-scoped roles using Spatie Teams. Keep access controlled per workspace.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 nw-card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Quotes → invoices</div>
                            <div class="text-muted">
                                Convert quotes to invoices, generate PDFs, and track billing.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 nw-card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Activities & follow-ups</div>
                            <div class="text-muted">
                                Log calls, meetings, and reminders so nothing slips through.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 nw-card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Reports & statements</div>
                            <div class="text-muted">
                                Tenant-wide statements, company statements, exports and PDFs.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 nw-card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Billing ready</div>
                            <div class="text-muted">
                                Upgrade flows and payment integrations (e.g., Paystack) built into the platform.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section class="py-5">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-6">
                    <h2 class="h1 fw-bold mb-3">How it works</h2>

                    <div class="d-flex gap-3 mb-3">
                        <div class="nw-step">1</div>
                        <div>
                            <div class="fw-semibold">Create a workspace</div>
                            <div class="text-muted">Your tenant is created and isolated automatically.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <div class="nw-step">2</div>
                        <div>
                            <div class="fw-semibold">Invite your team</div>
                            <div class="text-muted">Assign tenant roles like admin, sales, finance, viewer.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="nw-step">3</div>
                        <div>
                            <div class="fw-semibold">Start selling</div>
                            <div class="text-muted">Track leads and deals, send quotes, invoice customers, get paid.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card nw-card">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Built for real workflows</div>
                            <div class="text-muted">
                                Leads → Contacts → Companies → Deals → Quotes → Invoices → Payments → Credits.
                                Everything stays linked and tenant-safe.
                            </div>

                            <hr>

                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge nw-badge-neutral">Fast UI</span>
                                <span class="badge nw-badge-neutral">Tenant-safe</span>
                                <span class="badge nw-badge-neutral">Export-ready</span>
                                <span class="badge nw-badge-neutral">PDF-ready</span>
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
                    <h2 class="h1 fw-bold mb-2 text-white">Ready to launch your workspace?</h2>
                    <p class="mb-0 text-white-50">Get started now and invite your team in minutes.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-light btn-lg">
                            Start free
                        </a>
                    @endguest

                    @auth
                        @php $tenant = auth()->user()->tenant; @endphp
                        @if ($tenant)
                            <a href="{{ url('/t/' . $tenant->subdomain . '/dashboard') }}" class="btn btn-light btn-lg">
                                Go to dashboard
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
