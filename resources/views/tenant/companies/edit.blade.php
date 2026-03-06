@extends('layouts.app')

@section('content')
    <div class="container-fluid px-2 px-md-3" style="max-width:1200px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">Edit Company</h1>
                <div class="text-muted small">Update company details and addresses.</div>
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

        <style>
            .addr-field {
                margin-bottom: .75rem;
            }

            .addr-label-col {
                width: 160px;
            }

            @media (max-width: 576px) {
                .addr-label-col {
                    width: auto;
                }
            }

            textarea.form-control {
                resize: vertical;
            }
        </style>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.companies.update', $company) }}">
                    @csrf
                    @method('PUT')

                    {{-- Company details --}}
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="name" value="{{ old('name', $company->name) }}" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select js-select2" name="type" data-placeholder="Select type">
                                <option value=""></option>
                                @foreach (['prospect', 'customer', 'individual'] as $t)
                                    <option value="{{ $t }}" @selected(old('type', $company->type) === $t)>
                                        {{ $t }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="email" type="email"
                                value="{{ old('email', $company->email) }}">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Phone</label>
                            <input class="form-control" name="phone" value="{{ old('phone', $company->phone) }}">
                        </div>

                        {{-- ✅ Payment terms dropdown (managed via Settings) --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label">Payment Terms</label>
                            <select class="form-select js-select2" name="payment_term_id"
                                data-placeholder="Select payment terms" data-allow-clear="1">
                                <option value=""></option>
                                @foreach ($paymentTerms ?? collect() as $t)
                                    <option value="{{ $t->id }}" @selected((string) old('payment_term_id', $company->payment_term_id) === (string) $t->id)>
                                        {{ $t->name }} ({{ (int) $t->days }} days)
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Manage terms in <a
                                    href="{{ tenant_route('tenant.settings.payment_terms.index') }}">Settings → Payment
                                    Terms</a>.
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">VAT Number</label>
                            <input class="form-control" name="vat_number"
                                value="{{ old('vat_number', $company->vat_number) }}" placeholder="e.g. 49 2030 8527">
                            <div class="form-text">Optional. Used on quotes/invoices.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">VAT Treatment</label>
                            <select class="form-select js-select2" name="vat_treatment"
                                data-placeholder="Select VAT treatment" data-allow-clear="1">
                                <option value=""></option>
                                @foreach (['registered', 'non_registered', 'exempt', 'reverse_charge'] as $vt)
                                    <option value="{{ $vt }}" @selected(old('vat_treatment', $company->vat_treatment) === $vt)>
                                        {{ str_replace('_', ' ', $vt) }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Optional. Helps automation later.</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    {{-- Addresses --}}
                    <div class="row g-4">
                        <div class="col-12">
                            <div>
                                <div class="fw-semibold">Addresses</div>
                                <div class="text-muted small">Update now or later in the Company profile.</div>
                            </div>
                        </div>

                        {{-- Billing --}}
                        <div class="col-12 col-lg-6">
                            <div class="border rounded-3 p-3 p-md-4 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="fw-semibold">Billing Address</div>
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="billing[make_default]"
                                            value="1" @checked(old('billing.make_default', true)) id="billDefault">
                                        <label class="form-check-label small" for="billDefault">Default</label>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col"><label class="form-label mb-0">Attention</label></div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="billing[attention]"
                                            value="{{ old('billing.attention', data_get($billing, 'attention')) }}">
                                    </div>
                                </div>

                                {{-- Country --}}
                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col"><label class="form-label mb-0">Country/Region</label></div>
                                    <div class="flex-grow-1">
                                        <select class="form-select js-country js-select2" name="billing[country_iso2]"
                                            data-target="billing" data-placeholder="Select country" data-allow-clear="1">
                                            <option value=""></option>
                                            @foreach ($countries ?? collect() as $c)
                                                <option value="{{ $c->iso2 }}" @selected(old('billing.country_iso2', data_get($billing, 'country_iso2', 'ZA')) === $c->iso2)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 addr-field">
                                    <div class="addr-label-col pt-1"><label class="form-label mb-0">Address</label></div>
                                    <div class="flex-grow-1">
                                        <textarea class="form-control mb-2" name="billing[line1]" rows="2" placeholder="Street 1">{{ old('billing.line1', data_get($billing, 'line1')) }}</textarea>
                                        <textarea class="form-control" name="billing[line2]" rows="2" placeholder="Street 2">{{ old('billing.line2', data_get($billing, 'line2')) }}</textarea>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col"><label class="form-label mb-0">City</label></div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="billing[city]"
                                            value="{{ old('billing.city', data_get($billing, 'city')) }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col"><label class="form-label mb-0">ZIP Code</label></div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="billing[postal_code]"
                                            value="{{ old('billing.postal_code', data_get($billing, 'postal_code')) }}">
                                    </div>
                                </div>

                                {{-- State --}}
                                <div class="d-flex align-items-start gap-3 addr-field">
                                    <div class="addr-label-col pt-1"><label class="form-label mb-0">State</label></div>
                                    <div class="flex-grow-1">
                                        <select class="form-select js-subdivision js-select2"
                                            name="billing[subdivision_id]" data-target="billing"
                                            data-placeholder="Select state" data-allow-clear="1">
                                            <option value=""></option>
                                        </select>

                                        <input class="form-control mt-2 d-none js-subdivision-text"
                                            name="billing[subdivision_text]" data-target="billing"
                                            value="{{ old('billing.subdivision_text', data_get($billing, 'subdivision_text')) }}"
                                            placeholder="Type province/state (if not in list)">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field mb-0">
                                    <div class="addr-label-col"><label class="form-label mb-0">Phone</label></div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="billing[phone]"
                                            value="{{ old('billing.phone', data_get($billing, 'phone')) }}"
                                            placeholder="Phone number">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Shipping --}}
                        <div class="col-12 col-lg-6">
                            <div class="border rounded-3 p-3 p-md-4 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="fw-semibold">Shipping Address</div>
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="shipping[make_default]"
                                            value="1" @checked(old('shipping.make_default', true)) id="shipDefault">
                                        <label class="form-check-label small" for="shipDefault">Default</label>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col"><label class="form-label mb-0">Attention</label></div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="shipping[attention]"
                                            value="{{ old('shipping.attention', data_get($shipping, 'attention')) }}">
                                    </div>
                                </div>

                                {{-- Country --}}
                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col"><label class="form-label mb-0">Country/Region</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <select class="form-select js-country js-select2" name="shipping[country_iso2]"
                                            data-target="shipping" data-placeholder="Select country"
                                            data-allow-clear="1">
                                            <option value=""></option>
                                            @foreach ($countries ?? collect() as $c)
                                                <option value="{{ $c->iso2 }}" @selected(old('shipping.country_iso2', data_get($shipping, 'country_iso2', 'ZA')) === $c->iso2)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 addr-field">
                                    <div class="addr-label-col pt-1"><label class="form-label mb-0">Address</label></div>
                                    <div class="flex-grow-1">
                                        <textarea class="form-control mb-2" name="shipping[line1]" rows="2" placeholder="Street 1">{{ old('shipping.line1', data_get($shipping, 'line1')) }}</textarea>
                                        <textarea class="form-control" name="shipping[line2]" rows="2" placeholder="Street 2">{{ old('shipping.line2', data_get($shipping, 'line2')) }}</textarea>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col"><label class="form-label mb-0">City</label></div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="shipping[city]"
                                            value="{{ old('shipping.city', data_get($shipping, 'city')) }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col"><label class="form-label mb-0">ZIP Code</label></div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="shipping[postal_code]"
                                            value="{{ old('shipping.postal_code', data_get($shipping, 'postal_code')) }}">
                                    </div>
                                </div>

                                {{-- State --}}
                                <div class="d-flex align-items-start gap-3 addr-field">
                                    <div class="addr-label-col pt-1"><label class="form-label mb-0">State</label></div>
                                    <div class="flex-grow-1">
                                        <select class="form-select js-subdivision js-select2"
                                            name="shipping[subdivision_id]" data-target="shipping"
                                            data-placeholder="Select state" data-allow-clear="1">
                                            <option value=""></option>
                                        </select>

                                        <input class="form-control mt-2 d-none js-subdivision-text"
                                            name="shipping[subdivision_text]" data-target="shipping"
                                            value="{{ old('shipping.subdivision_text', data_get($shipping, 'subdivision_text')) }}"
                                            placeholder="Type province/state (if not in list)">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field mb-0">
                                    <div class="addr-label-col"><label class="form-label mb-0">Phone</label></div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="shipping[phone]"
                                            value="{{ old('shipping.phone', data_get($shipping, 'phone')) }}"
                                            placeholder="Phone number">
                                    </div>
                                </div>

                                <div class="form-text mt-2">
                                    Tip: If shipping = billing, just keep them aligned.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary">Save Changes</button>
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
            // Init Select2 on this page (same as create)
            document.addEventListener('DOMContentLoaded', function() {
                if (window.initSelect2) window.initSelect2(document);
                else if (window.jQuery) window.jQuery('.js-select2').select2({
                    width: '100%'
                });
            });

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

                const selectedText = (txt.value || '').trim();

                sel.innerHTML = `<option value=""></option>`;

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
                    sel.appendChild(opt);
                });

                if (selectedId) sel.value = String(selectedId);

                // Refresh Select2 UI if enabled
                if (window.jQuery) {
                    const $sel = window.jQuery(sel);
                    if ($sel.data('select2')) $sel.trigger('change.select2');
                    else $sel.trigger('change');
                }

                // If user has a text state but no id match, show text input instead
                if (!selectedId && selectedText) {
                    sel.classList.add('d-none');
                    txt.classList.remove('d-none');
                }
            }

            async function handleCountryChange(e) {
                const target = e.target.getAttribute('data-target');
                const iso2 = e.target.value;
                const list = await loadSubdivisions(iso2);

                // clear any selected subdivision id when country changes
                const sel = document.querySelector(`.js-subdivision[data-target="${target}"]`);
                if (sel) sel.value = '';

                fillSubdivisions(target, list, null);
            }

            // Bind country change (Select2 safe)
            if (window.jQuery) {
                window.jQuery(document).on('change', '.js-country', function(e) {
                    handleCountryChange(e);
                });
            } else {
                document.querySelectorAll('.js-country').forEach(el => el.addEventListener('change',
                    handleCountryChange));
            }

            async function bootOne(target, iso2, selectedSubdivisionId) {
                const list = await loadSubdivisions(iso2);
                fillSubdivisions(target, list, selectedSubdivisionId);
            }

            bootOne(
                'billing',
                document.querySelector('.js-country[data-target="billing"]')?.value || 'ZA',
                @json(old('billing.subdivision_id', data_get($billing, 'subdivision_id')))
            );

            bootOne(
                'shipping',
                document.querySelector('.js-country[data-target="shipping"]')?.value || 'ZA',
                @json(old('shipping.subdivision_id', data_get($shipping, 'subdivision_id')))
            );
        })();
    </script>
@endpush
