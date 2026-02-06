@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h1 class="h3 mb-0">{{ $company->name }}</h1>
                <div class="text-muted small">Company profile</div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <a class="btn btn-light" href="{{ tenant_route('tenant.companies.index') }}">Back</a>

                {{-- Quick Statement actions --}}
                <div class="btn-group">
                    <a class="btn btn-outline-secondary" href="{{ tenant_route('tenant.companies.statement', $company) }}">
                        Statement
                    </a>
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="{{ tenant_route('tenant.companies.statement.pdf', $company) }}">
                                Download PDF
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ tenant_route('tenant.companies.statement.csv', $company) }}">
                                Download CSV
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                                href="{{ tenant_route('tenant.companies.statement', $company) }}#emailStatement">
                                Send Email
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- New Transaction dropdown --}}
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        New Transaction
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item"
                                href="{{ tenant_route('tenant.quotes.create') . '?company_id=' . $company->id }}">
                                New Quote
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                                href="{{ tenant_route('tenant.invoices.create') . '?company_id=' . $company->id }}">
                                New Invoice
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item"
                                href="{{ tenant_route('payments.create') . '?company_id=' . $company->id }}">
                                Record Payment
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                                href="{{ tenant_route('credit-notes.create') . '?company_id=' . $company->id }}">
                                New Credit Note
                            </a>
                        </li>
                    </ul>
                </div>

                <a class="btn btn-outline-secondary" href="{{ tenant_route('tenant.companies.edit', $company) }}">Edit</a>
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        {{-- Tabs (Zoho-like) --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tenant.companies.show') ? 'active' : '' }}"
                    href="{{ tenant_route('tenant.companies.show', $company) }}">
                    Overview
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tenant.companies.statement*') ? 'active' : '' }}"
                    href="{{ tenant_route('tenant.companies.statement', $company) }}">
                    Statement
                </a>
            </li>
        </ul>

        <div class="row g-3">
            {{-- Company summary --}}
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><strong>Details</strong></div>
                    <div class="card-body">
                        <div><strong>Type:</strong> {{ $company->type }}</div>
                        <div><strong>Email:</strong> {{ $company->email ?? '—' }}</div>
                        <div><strong>Phone:</strong> {{ $company->phone ?? '—' }}</div>

                        <hr class="my-3">

                        <div><strong>Payment Terms:</strong> {{ $company->payment_terms ?? '—' }}</div>
                        <div><strong>VAT Number:</strong> {{ $company->vat_number ?? '—' }}</div>
                        <div>
                            <strong>VAT Treatment:</strong>
                            {{ $company->vat_treatment ? str_replace('_', ' ', $company->vat_treatment) : '—' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contacts --}}
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>Contacts</strong>
                    </div>
                    <div class="card-body">
                        @forelse($company->contacts as $c)
                            <div class="border rounded p-2 mb-2">
                                <div class="fw-semibold">{{ $c->name }}</div>
                                <div class="small text-muted">{{ $c->email ?? '—' }} • {{ $c->phone ?? '—' }}</div>
                            </div>
                        @empty
                            <div class="text-muted">No contacts yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Addresses --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-header"><strong>Addresses</strong></div>
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Billing --}}
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-semibold">Billing Address</div>
                                        @if ($billing && data_get($billing, 'is_default_billing'))
                                            <span class="badge bg-success">Default</span>
                                        @endif
                                    </div>

                                    @if ($billing)
                                        <div class="small text-muted mb-1">
                                            {{ data_get($billing, 'label') ?: '—' }}
                                        </div>

                                        <div>{{ data_get($billing, 'attention') ?: '' }}</div>
                                        <div class="text-muted small">{{ data_get($billing, 'phone') ?: '' }}</div>

                                        <div class="mt-2">
                                            @php
                                                $lines = array_filter([
                                                    data_get($billing, 'line1'),
                                                    data_get($billing, 'line2'),
                                                    data_get($billing, 'city'),
                                                    data_get($billing, 'postal_code'),
                                                ]);
                                            @endphp

                                            @if (count($lines))
                                                {!! nl2br(e(implode("\n", $lines))) !!}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>

                                        <div class="mt-2 small text-muted">
                                            <div><strong>Country:</strong> {{ optional($billing->country)->iso2 ?? '—' }}
                                            </div>
                                            <div>
                                                <strong>Province/State:</strong>
                                                {{ optional($billing->subdivision)->name ?? ($billing->subdivision_text ?? '—') }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-muted">No billing address yet.</div>
                                    @endif
                                </div>
                            </div>

                            {{-- Shipping --}}
                            <div class="col-lg-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-semibold">Shipping Address</div>
                                        @if ($shipping && data_get($shipping, 'is_default_shipping'))
                                            <span class="badge bg-success">Default</span>
                                        @endif
                                    </div>

                                    @if ($shipping)
                                        <div class="small text-muted mb-1">
                                            {{ data_get($shipping, 'label') ?: '—' }}
                                        </div>

                                        <div>{{ data_get($shipping, 'attention') ?: '' }}</div>
                                        <div class="text-muted small">{{ data_get($shipping, 'phone') ?: '' }}</div>

                                        <div class="mt-2">
                                            @php
                                                $lines = array_filter([
                                                    data_get($shipping, 'line1'),
                                                    data_get($shipping, 'line2'),
                                                    data_get($shipping, 'city'),
                                                    data_get($shipping, 'postal_code'),
                                                ]);
                                            @endphp

                                            @if (count($lines))
                                                {!! nl2br(e(implode("\n", $lines))) !!}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>

                                        <div class="mt-2 small text-muted">
                                            <div><strong>Country:</strong> {{ optional($shipping->country)->iso2 ?? '—' }}
                                            </div>
                                            <div>
                                                <strong>Province/State:</strong>
                                                {{ optional($shipping->subdivision)->name ?? ($shipping->subdivision_text ?? '—') }}
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-muted">No shipping address yet.</div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
