@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0" style="max-width:900px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">Add Company</h1>
                <div class="text-muted small">Create a company + optional billing/shipping addresses.</div>
            </div>
            <a class="btn btn-light" href="{{ tenant_route('tenant.companies.index') }}">Back</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix the errors below:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.companies.store') }}">
                    @csrf

                    {{-- Company details --}}
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type">
                                @foreach (['prospect', 'customer', 'individual'] as $t)
                                    <option value="{{ $t }}" @selected(old('type', 'prospect') === $t)>{{ $t }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email" value="{{ old('email') }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" value="{{ old('phone') }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" class="form-control" name="payment_terms"
                                value="{{ old('payment_terms') }}" placeholder="e.g. COD / 30 Days / 50% Deposit">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">VAT Number</label>
                            <input class="form-control" name="vat_number" value="{{ old('vat_number') }}"
                                placeholder="e.g. 49 2030 8527">
                            <div class="form-text">Optional. Used on quotes/invoices.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">VAT Treatment</label>
                            <select class="form-select" name="vat_treatment">
                                <option value="">—</option>
                                @foreach (['registered', 'non_registered', 'exempt', 'reverse_charge'] as $vt)
                                    <option value="{{ $vt }}" @selected(old('vat_treatment') === $vt)>
                                        {{ str_replace('_', ' ', $vt) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Optional. Helps automation later.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Addresses --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">Addresses</div>
                                <div class="text-muted small">Add now or later in the Company profile.</div>
                            </div>
                        </div>

                        {{-- Billing --}}
                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Billing Address</div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="billing[make_default]"
                                            value="1" @checked(old('billing.make_default', true)) id="billDefault">
                                        <label class="form-check-label small" for="billDefault">Default billing</label>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Label</label>
                                    <input class="form-control" name="billing[label]"
                                        value="{{ old('billing.label', 'Head Office') }}">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Attention</label>
                                    <input class="form-control" name="billing[attention]"
                                        value="{{ old('billing.attention') }}">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control" name="billing[phone]" value="{{ old('billing.phone') }}">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Address Line 1</label>
                                    <input class="form-control" name="billing[line1]" value="{{ old('billing.line1') }}">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Address Line 2</label>
                                    <input class="form-control" name="billing[line2]" value="{{ old('billing.line2') }}">
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">City</label>
                                        <input class="form-control" name="billing[city]"
                                            value="{{ old('billing.city') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Postal Code</label>
                                        <input class="form-control" name="billing[postal_code]"
                                            value="{{ old('billing.postal_code') }}">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Country</label>
                                        <select class="form-select js-country" name="billing[country_iso2]"
                                            data-target="billing">
                                            <option value="">— select —</option>
                                            @foreach ($countries ?? collect() as $c)
                                                <option value="{{ $c->iso2 }}" @selected(old('billing.country_iso2', 'ZA') === $c->iso2)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Province/State</label>
                                        <select class="form-select js-subdivision" name="billing[subdivision_id]"
                                            data-target="billing">
                                            <option value="">— select —</option>
                                        </select>
                                        <input class="form-control mt-2 d-none js-subdivision-text"
                                            name="billing[subdivision_text]" data-target="billing"
                                            value="{{ old('billing.subdivision_text') }}"
                                            placeholder="Type province/state (if not in list)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Shipping --}}
                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Shipping Address</div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="shipping[make_default]"
                                            value="1" @checked(old('shipping.make_default', true)) id="shipDefault">
                                        <label class="form-check-label small" for="shipDefault">Default shipping</label>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Label</label>
                                    <input class="form-control" name="shipping[label]"
                                        value="{{ old('shipping.label', 'Delivery') }}">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Attention</label>
                                    <input class="form-control" name="shipping[attention]"
                                        value="{{ old('shipping.attention') }}">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control" name="shipping[phone]"
                                        value="{{ old('shipping.phone') }}">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Address Line 1</label>
                                    <input class="form-control" name="shipping[line1]"
                                        value="{{ old('shipping.line1') }}">
                                </div>

                                <div class="mb-2">
                                    <label class="form-label">Address Line 2</label>
                                    <input class="form-control" name="shipping[line2]"
                                        value="{{ old('shipping.line2') }}">
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">City</label>
                                        <input class="form-control" name="shipping[city]"
                                            value="{{ old('shipping.city') }}">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Postal Code</label>
                                        <input class="form-control" name="shipping[postal_code]"
                                            value="{{ old('shipping.postal_code') }}">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Country</label>
                                        <select class="form-select js-country" name="shipping[country_iso2]"
                                            data-target="shipping">
                                            <option value="">— select —</option>
                                            @foreach ($countries ?? collect() as $c)
                                                <option value="{{ $c->iso2 }}" @selected(old('shipping.country_iso2', 'ZA') === $c->iso2)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-2">
                                        <label class="form-label">Province/State</label>
                                        <select class="form-select js-subdivision" name="shipping[subdivision_id]"
                                            data-target="shipping">
                                            <option value="">— select —</option>
                                        </select>
                                        <input class="form-control mt-2 d-none js-subdivision-text"
                                            name="shipping[subdivision_text]" data-target="shipping"
                                            value="{{ old('shipping.subdivision_text') }}"
                                            placeholder="Type province/state (if not in list)">
                                    </div>
                                </div>

                                <div class="form-text mt-2">
                                    Tip: If shipping = billing, you can fill billing only and add shipping later.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary">Create</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.companies.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const SUBDIV_URL_TEMPLATE = @json(tenant_route('tenant.geo.subdivisions', ['countryIso2' => '__ISO2__']));

            function subdivUrl(iso2) {
                return SUBDIV_URL_TEMPLATE.replace('__ISO2__', encodeURIComponent((iso2 || '').toUpperCase()));
            }

            async function loadSubdivisions(countryIso2) {
                if (!countryIso2) return [];
                const res = await fetch(subdivUrl(countryIso2), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return [];
                return await res.json();
            }

            function fillSubdivisions(target, list, selectedId) {
                const sel = document.querySelector(`.js-subdivision[data-target="${target}"]`);
                const txt = document.querySelector(`.js-subdivision-text[data-target="${target}"]`);
                if (!sel || !txt) return;

                sel.innerHTML = `<option value="">— select —</option>`;

                if (!list.length) {
                    sel.classList.add('d-none');
                    txt.classList.remove('d-none');
                    return;
                }

                sel.classList.remove('d-none');
                txt.classList.add('d-none');

                list.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.name;
                    if (String(s.id) === String(selectedId)) opt.selected = true;
                    sel.appendChild(opt);
                });
            }

            async function handleCountryChange(e) {
                const target = e.target.getAttribute('data-target');
                const iso2 = e.target.value;
                const list = await loadSubdivisions(iso2);
                fillSubdivisions(target, list, null);
            }

            document.querySelectorAll('.js-country').forEach(el => {
                el.addEventListener('change', handleCountryChange);
            });

            async function bootOne(target, iso2, selectedSubdivisionId) {
                const list = await loadSubdivisions(iso2);
                fillSubdivisions(target, list, selectedSubdivisionId);
            }

            bootOne('billing', document.querySelector('.js-country[data-target="billing"]')?.value || 'ZA',
                @json(old('billing.subdivision_id')));
            bootOne('shipping', document.querySelector('.js-country[data-target="shipping"]')?.value || 'ZA',
                @json(old('shipping.subdivision_id')));
        })();
    </script>
@endpush
