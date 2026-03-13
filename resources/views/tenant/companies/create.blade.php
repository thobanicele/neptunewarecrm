@extends('layouts.app')

@section('content')
    @php
        $paymentTermOptions = ($paymentTerms ?? collect())
            ->mapWithKeys(fn($pt) => [$pt->id => $pt->name . ' (' . $pt->days . ' days)'])
            ->all();
    @endphp

    <div class="container-fluid px-2 px-md-3" style="max-width:1200px;">
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

            .select2-container--default .select2-selection--single {
                border-color: #ced4da !important;
                box-shadow: none !important;
                min-height: calc(2.25rem + 2px);
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: calc(2.25rem);
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: calc(2.25rem + 2px);
            }

            .select2-container--default .select2-search--dropdown .select2-search__field {
                border: 1px solid #ced4da !important;
                border-radius: .375rem;
                box-shadow: none !important;
                outline: none !important;
            }

            .select2-container--default .select2-search--dropdown .select2-search__field:focus {
                border-color: #86b7fe !important;
                box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25) !important;
            }

            .select2-container--default.select2-container--focus .select2-selection--single {
                border-color: #86b7fe !important;
                box-shadow: 0 0 0 .25rem rgba(13, 110, 253, .25) !important;
            }
        </style>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.companies.store') }}">
                    @csrf

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="name" value="{{ old('name') }}" required>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select js-select2" name="type" data-placeholder="Select type">
                                <option value=""></option>
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
                            <x-select2 name="payment_term_id" label="Payment Terms" :options="$paymentTermOptions" :value="old('payment_term_id')"
                                placeholder="Select payment terms" :allowClear="true" />
                            <div class="form-text">
                                Manage payment terms in
                                <a href="{{ tenant_route('tenant.settings.payment_terms.index') }}">Settings</a>.
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">VAT Number</label>
                            <input class="form-control" name="vat_number" value="{{ old('vat_number') }}"
                                placeholder="e.g. 49 2030 8527">
                            <div class="form-text">Optional. Used on quotes/invoices.</div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">VAT Treatment</label>
                            <select class="form-select js-select2" name="vat_treatment"
                                data-placeholder="Select VAT treatment" data-allow-clear="1">
                                <option value=""></option>
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

                    <div class="row g-4">
                        <div class="col-12">
                            <div>
                                <div class="fw-semibold">Addresses</div>
                                <div class="text-muted small">Add now or later in the Company profile.</div>
                            </div>
                        </div>

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
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">Attention</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="billing[attention]"
                                            value="{{ old('billing.attention') }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">Country/Region</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <select class="form-select js-country js-select2" name="billing[country_iso2]"
                                            data-target="billing" data-placeholder="Select country" data-allow-clear="1">
                                            <option value=""></option>
                                            @foreach ($countries ?? collect() as $c)
                                                <option value="{{ $c->iso2 }}" @selected(old('billing.country_iso2', 'ZA') === $c->iso2)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 addr-field">
                                    <div class="addr-label-col pt-1">
                                        <label class="form-label mb-0">Address</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <textarea class="form-control mb-2" name="billing[line1]" rows="2" placeholder="Street 1">{{ old('billing.line1') }}</textarea>
                                        <textarea class="form-control" name="billing[line2]" rows="2" placeholder="Street 2">{{ old('billing.line2') }}</textarea>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">City</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="billing[city]"
                                            value="{{ old('billing.city') }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">ZIP Code</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="billing[postal_code]"
                                            value="{{ old('billing.postal_code') }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 addr-field">
                                    <div class="addr-label-col pt-1">
                                        <label class="form-label mb-0">State</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <select class="form-select js-subdivision js-select2"
                                            name="billing[subdivision_id]" data-target="billing"
                                            data-placeholder="Select state" data-allow-clear="1">
                                            <option value=""></option>
                                        </select>

                                        <input class="form-control mt-2 d-none js-subdivision-text"
                                            name="billing[subdivision_text]" data-target="billing"
                                            value="{{ old('billing.subdivision_text') }}"
                                            placeholder="Type province/state (if not in list)">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field mb-0">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">Phone</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="billing[phone]"
                                            value="{{ old('billing.phone') }}" placeholder="Phone number">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded-3 p-3 p-md-4 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="fw-semibold">
                                        Shipping Address
                                        <button type="button" class="btn btn-link btn-sm text-decoration-none ps-1"
                                            id="copyBillingBtn">
                                            ↓ Copy billing address
                                        </button>
                                    </div>

                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="shipping[make_default]"
                                            value="1" @checked(old('shipping.make_default', true)) id="shipDefault">
                                        <label class="form-check-label small" for="shipDefault">Default</label>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">Attention</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="shipping[attention]"
                                            value="{{ old('shipping.attention') }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">Country/Region</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <select class="form-select js-country js-select2" name="shipping[country_iso2]"
                                            data-target="shipping" data-placeholder="Select country"
                                            data-allow-clear="1">
                                            <option value=""></option>
                                            @foreach ($countries ?? collect() as $c)
                                                <option value="{{ $c->iso2 }}" @selected(old('shipping.country_iso2', 'ZA') === $c->iso2)>
                                                    {{ $c->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 addr-field">
                                    <div class="addr-label-col pt-1">
                                        <label class="form-label mb-0">Address</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <textarea class="form-control mb-2" name="shipping[line1]" rows="2" placeholder="Street 1">{{ old('shipping.line1') }}</textarea>
                                        <textarea class="form-control" name="shipping[line2]" rows="2" placeholder="Street 2">{{ old('shipping.line2') }}</textarea>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">City</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="shipping[city]"
                                            value="{{ old('shipping.city') }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">ZIP Code</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="shipping[postal_code]"
                                            value="{{ old('shipping.postal_code') }}">
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 addr-field">
                                    <div class="addr-label-col pt-1">
                                        <label class="form-label mb-0">State</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <select class="form-select js-subdivision js-select2"
                                            name="shipping[subdivision_id]" data-target="shipping"
                                            data-placeholder="Select state" data-allow-clear="1">
                                            <option value=""></option>
                                        </select>

                                        <input class="form-control mt-2 d-none js-subdivision-text"
                                            name="shipping[subdivision_text]" data-target="shipping"
                                            value="{{ old('shipping.subdivision_text') }}"
                                            placeholder="Type province/state (if not in list)">
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-3 addr-field mb-0">
                                    <div class="addr-label-col">
                                        <label class="form-label mb-0">Phone</label>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input class="form-control" name="shipping[phone]"
                                            value="{{ old('shipping.phone') }}" placeholder="Phone number">
                                    </div>
                                </div>

                                <div class="form-text mt-2">
                                    Tip: If shipping = billing, fill billing only and copy it across.
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
            document.addEventListener('DOMContentLoaded', function() {
                if (window.initSelect2) window.initSelect2(document);
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

            function destroySelect2(el) {
                if (!window.jQuery) return;
                const $el = window.jQuery(el);
                if ($el.data('select2')) {
                    try {
                        $el.select2('destroy');
                    } catch (e) {}
                }
            }

            function initSelect2On(el) {
                if (el.classList.contains('d-none')) return;
                if (!window.jQuery || !window.jQuery.fn.select2) return;

                const $el = window.jQuery(el);
                if ($el.data('select2')) return;

                $el.select2({
                    width: '100%',
                    placeholder: el.getAttribute('data-placeholder') || '',
                    allowClear: el.getAttribute('data-allow-clear') === '1'
                });
            }

            function fillSubdivisions(target, list, selectedId) {
                const sel = document.querySelector(`.js-subdivision[data-target="${target}"]`);
                const txt = document.querySelector(`.js-subdivision-text[data-target="${target}"]`);
                if (!sel || !txt) return;

                destroySelect2(sel);

                txt.value = '';
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
                    if (String(s.id) === String(selectedId)) opt.selected = true;
                    sel.appendChild(opt);
                });

                initSelect2On(sel);

                if (selectedId != null && window.jQuery) {
                    window.jQuery(sel).val(String(selectedId)).trigger('change');
                }
            }

            async function bootOne(target, iso2, selectedSubdivisionId) {
                const list = await loadSubdivisions(iso2);
                fillSubdivisions(target, list, selectedSubdivisionId);
            }

            document.addEventListener('DOMContentLoaded', function() {
                if (window.jQuery) {
                    window.jQuery(document).on('change', '.js-country', async function() {
                        const target = this.getAttribute('data-target');
                        const iso2 = this.value;

                        fillSubdivisions(target, [], null);
                        const list = await loadSubdivisions(iso2);
                        fillSubdivisions(target, list, null);
                    });
                } else {
                    document.querySelectorAll('.js-country').forEach(el => {
                        el.addEventListener('change', async function(e) {
                            const target = e.target.getAttribute('data-target');
                            const iso2 = e.target.value;

                            fillSubdivisions(target, [], null);
                            const list = await loadSubdivisions(iso2);
                            fillSubdivisions(target, list, null);
                        });
                    });
                }

                bootOne(
                    'billing',
                    document.querySelector('.js-country[data-target="billing"]')?.value || 'ZA',
                    @json(old('billing.subdivision_id'))
                );

                bootOne(
                    'shipping',
                    document.querySelector('.js-country[data-target="shipping"]')?.value || 'ZA',
                    @json(old('shipping.subdivision_id'))
                );
            });

            const copyBtn = document.getElementById('copyBillingBtn');

            function setVal(name, val) {
                const el = document.querySelector(`[name="${name}"]`);
                if (!el) return;

                el.value = val ?? '';
                el.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
                el.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
            }

            async function copyBillingToShipping() {
                setVal('shipping[attention]', document.querySelector('[name="billing[attention]"]')?.value);
                setVal('shipping[line1]', document.querySelector('[name="billing[line1]"]')?.value);
                setVal('shipping[line2]', document.querySelector('[name="billing[line2]"]')?.value);
                setVal('shipping[city]', document.querySelector('[name="billing[city]"]')?.value);
                setVal('shipping[postal_code]', document.querySelector('[name="billing[postal_code]"]')?.value);
                setVal('shipping[phone]', document.querySelector('[name="billing[phone]"]')?.value);

                const billCountry = document.querySelector('[name="billing[country_iso2]"]')?.value || '';
                setVal('shipping[country_iso2]', billCountry);

                const billSubId = document.querySelector('[name="billing[subdivision_id]"]')?.value || '';
                const billSubText = document.querySelector('[name="billing[subdivision_text]"]')?.value || '';

                const list = await loadSubdivisions(billCountry);
                fillSubdivisions('shipping', list, billSubId);

                const shippingSubSel = document.querySelector('.js-subdivision[data-target="shipping"]');
                const shippingSubTxt = document.querySelector('.js-subdivision-text[data-target="shipping"]');

                if (shippingSubSel?.classList.contains('d-none') && shippingSubTxt && billSubText) {
                    shippingSubTxt.value = billSubText;
                }
            }

            if (copyBtn) copyBtn.addEventListener('click', copyBillingToShipping);
        })();
    </script>
@endpush
