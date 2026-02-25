@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">New Quote</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>
            <a href="{{ tenant_route('tenant.quotes.index') }}" class="btn btn-light">Back</a>
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

        <form method="POST" action="{{ tenant_route('tenant.quotes.store') }}">
            @csrf

            {{-- TOP: Proposal Header + Customer Details --}}
            <div class="card mb-3">
                <div class="card-body">

                    {{-- Header row: Logo + quote meta --}}
                    <div class="row g-3 align-items-start">
                        <div class="col-12 col-lg-6">
                            <div class="col-12 col-lg-6">
                                @include('tenant.partials.transaction-header-brand', [
                                    'tenant' => $tenant,
                                    // optional overrides:
                                    // 'logoHeight' => 56,
                                    // 'logoMaxWidth' => 180,
                                    // 'showAddress' => true,
                                    // 'showMeta' => true,
                                ])
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-muted small">Quote Date</div>
                                        <input type="date" class="form-control" name="issued_at"
                                            value="{{ old('issued_at', now()->toDateString()) }}">
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small">Expiry Date</div>
                                        <input type="date" class="form-control" name="valid_until"
                                            value="{{ old('valid_until') }}">
                                    </div>

                                    <div class="col-6">
                                        <div class="text-muted small">Status</div>
                                        <select class="form-select" name="status">
                                            @foreach (['draft', 'sent', 'accepted', 'declined', 'expired'] as $s)
                                                <option value="{{ $s }}" @selected(old('status', 'draft') === $s)>
                                                    {{ strtoupper($s) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-6">
                                        <div class="text-muted small">Salesperson</div>
                                        <select class="form-select @error('sales_person_user_id') is-invalid @enderror"
                                            name="sales_person_user_id" required>
                                            @foreach ($salesPeople ?? collect() as $u)
                                                <option value="{{ data_get($u, 'id') }}" @selected((string) old('sales_person_user_id', $prefillSalesPersonId ?? auth()->id()) === (string) data_get($u, 'id'))>
                                                    {{ data_get($u, 'name') }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('sales_person_user_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <div class="text-muted small">Associate Deal (optional)</div>
                                        <select class="form-select" name="deal_id" id="dealSelect">
                                            <option value="">— none —</option>
                                            @foreach ($deals ?? collect() as $d)
                                                <option value="{{ data_get($d, 'id') }}" @selected((string) old('deal_id') === (string) data_get($d, 'id'))>
                                                    #{{ data_get($d, 'id') }} • {{ data_get($d, 'title') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <div class="text-muted small">Customer Reference #</div>
                                        <input type="text" class="form-control" name="customer_reference"
                                            value="{{ old('customer_reference') }}" placeholder="e.g. PO-12345 / Ref-9876">
                                        <div class="form-text">Customer can type their internal reference / PO number.</div>
                                    </div>

                                </div>
                            </div>
                            <div class="form-text">Owner is who creates it; Salesperson gets credit.</div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Customer select --}}
                    <div class="row g-3">
                        <div class="col-12 col-lg-8">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <select class="form-select" name="company_id" id="companySelect"
                                data-contacts-url-template="{{ tenant_route('tenant.companies.contacts.index', ['company' => '__ID__']) }}"
                                data-prefill-contact-id="{{ (string) old('contact_id', $prefillContactId ?? '') }}"
                                data-prefill-company-id="{{ (string) old('company_id', $prefillCompanyId ?? '') }}"
                                required>
                                <option value="">— select company —</option>
                                @foreach ($companies ?? collect() as $c)
                                    <option value="{{ data_get($c, 'id') }}" @selected((string) old('company_id', $prefillCompanyId ?? null) === (string) data_get($c, 'id'))>
                                        {{ data_get($c, 'name') }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Company details show below after selecting.</div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Contact (optional)</label>
                            <select class="form-select" name="contact_id" id="contactSelect">
                                <option value="">— none —</option>
                                {{-- will be loaded by AJAX based on company --}}
                            </select>
                        </div>
                    </div>

                    {{-- Quote To / Ship To --}}
                    <div class="row g-3 mt-2">
                        <div class="col-12 col-lg-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="text-muted small fw-semibold">QUOTE TO (BILLING)</div>
                                <div class="text-muted small" id="vatTreatmentLine"></div>
                            </div>
                            <div class="border rounded p-3" id="billingBox">
                                <div class="text-muted small">Select a company to view billing address.</div>
                            </div>

                            <div class="mt-2">
                                <div class="text-muted small">Payment Terms</div>
                                <div class="fw-semibold" id="paymentTermsLine">—</div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="text-muted small fw-semibold mb-1">SHIP TO (DELIVERY)</div>
                            <div class="border rounded p-3" id="shippingBox">
                                <div class="text-muted small">Select a company to view shipping address.</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Default VAT (for new rows) --}}
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Default VAT</label>
                            <select class="form-select" name="tax_type_id" id="defaultTaxType">
                                @foreach ($taxTypes ?? collect() as $t)
                                    <option value="{{ data_get($t, 'id') }}" @selected((string) old('tax_type_id', $defaultTaxTypeId ?? null) === (string) data_get($t, 'id'))>
                                        {{ data_get($t, 'name') }}
                                        ({{ number_format((float) data_get($t, 'rate', 0), 2) }}%)
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">New rows will use this VAT by default.</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ITEMS TABLE --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">Items</div>
                    <div class="text-muted small">Type to search products. Description only shows after selection.</div>
                </div>

                <div class="card-body">

                    <div class="table-responsive nw-overflow-visible">
                        <table class="table align-middle table-sm nw-quote-table">
                            <thead>
                                <tr>
                                    <th style="width: 42%;">ITEM</th>
                                    <th style="width: 10%;">SKU</th>
                                    <th style="width: 10%;">QTY</th>
                                    <th style="width: 12%;">RATE</th>
                                    <th style="width: 10%;">DISC %</th>
                                    <th style="width: 14%;">VAT</th>
                                    <th class="text-end nw-amount-col">LINE</th>
                                    <th class="text-end nw-amount-col">INCL</th>
                                    <th style="width: 2%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm" id="addItemBtn">+ Add New Row</button>

                    <div class="row mt-4">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Customer Notes</label>
                            <textarea class="form-control" name="notes" rows="4" placeholder="Will be displayed on the quote">{{ old('notes') }}</textarea>

                            <div class="mt-3">
                                <label class="form-label">Terms</label>
                                <textarea class="form-control" name="terms" rows="4">{{ old('terms') }}</textarea>
                            </div>

                            <div class="mt-4 border rounded p-3">
                                <div class="fw-semibold mb-2">Signatures</div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="text-muted small">Prepared by</div>
                                        <div class="border-bottom" style="height:24px;"></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="text-muted small">Approved by</div>
                                        <div class="border-bottom" style="height:24px;"></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="text-muted small">Signature</div>
                                        <div class="border-bottom" style="height:24px;"></div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="text-muted small">Date</div>
                                        <div class="border-bottom" style="height:24px;"></div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-12 col-lg-6 d-flex justify-content-end">
                            <div class="border rounded p-4" style="min-width: 360px;">
                                <div class="d-flex justify-content-between">
                                    <div class="fw-semibold">Subtotal</div>
                                    <div class="fw-semibold">R <span id="subtotal">0.00</span></div>
                                </div>

                                <div class="d-flex justify-content-between mt-2">
                                    <div class="text-muted">Discount</div>
                                    <div class="fw-semibold">- R <span id="discountTotal">0.00</span></div>
                                </div>

                                <div class="d-flex justify-content-between mt-2">
                                    <div class="text-muted">VAT Total</div>
                                    <div class="fw-semibold">R <span id="taxAmount">0.00</span></div>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between">
                                    <div class="fw-semibold">Grand Total</div>
                                    <div class="fw-semibold">R <span id="grandTotal">0.00</span></div>
                                </div>

                                <div class="text-muted small mt-2">
                                    Totals are calculated from Qty × Rate − Discount + VAT (per line).
                                </div>

                                @if (!empty($tenant->bank_details))
                                    <hr class="my-3">
                                    <div class="fw-semibold mb-1">Bank Details</div>
                                    <div class="text-muted small" style="white-space: pre-wrap;">
                                        {{ $tenant->bank_details }}</div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary" type="submit">Create Quote</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.quotes.index') }}">Cancel</a>
                    </div>

                </div>
            </div>

        </form>
    </div>
@endsection

@push('styles')
    <style>
        /* Smaller + tighter */
        .nw-quote-table,
        .nw-quote-table td,
        .nw-quote-table th {
            font-size: 0.78rem;
        }

        .nw-quote-table .form-control,
        .nw-quote-table .form-select {
            font-size: 0.78rem;
            padding-top: .18rem;
            padding-bottom: .18rem;
        }

        .nw-amount-col {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        /* Let dropdown escape */
        .table-responsive.nw-overflow-visible {
            overflow: visible !important;
        }

        /* Picker */
        .nw-picker {
            position: relative;
        }

        .nw-picked {
            display: none;
        }

        .nw-picked .form-control[readonly] {
            background: #f8f9fa;
        }

        .nw-desc-wrap {
            display: none;
            margin-top: .35rem;
        }

        /* Global suggest dropdown (appended to body) */
        .nw-suggest {
            position: fixed;
            z-index: 99999;
            background: var(--bs-body-bg);
            border: 1px solid rgba(0, 0, 0, .125);
            border-radius: .5rem;
            box-shadow: 0 10px 24px rgba(0, 0, 0, .12);
            max-height: 260px;
            overflow: auto;
            display: none;
        }

        .nw-suggest .nw-opt {
            padding: .45rem .6rem;
            cursor: pointer;
            border-bottom: 1px solid rgba(0, 0, 0, .06);
        }

        .nw-suggest .nw-opt:last-child {
            border-bottom: 0;
        }

        .nw-suggest .nw-opt:hover,
        .nw-suggest .nw-opt.active {
            background: rgba(13, 110, 253, .08);
        }

        .nw-opt .nw-name {
            font-weight: 600;
            line-height: 1.2;
        }

        .nw-opt .nw-sub {
            font-size: .72rem;
            color: #6c757d;
            line-height: 1.2;
            margin-top: .1rem;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // ============================
        // Global Data
        // ============================
        window.NW_PRODUCTS = @json(($products ?? collect())->keyBy('id'));
        window.NW_TAXTYPES = @json(($taxTypes ?? collect())->keyBy('id'));
        window.NW_OLD_ITEMS = @json(old('items', []));
        window.NW_COMPANIES = @json($companiesJson ?? ($companies ?? collect())->keyBy('id'));

        document.addEventListener('DOMContentLoaded', function() {
            // ============================================================
            // A) CONTACTS (company -> contacts)
            // ============================================================
            (function initContactsLoader() {
                const companySelect = document.getElementById('companySelect');
                const contactSelect = document.getElementById('contactSelect');
                if (!companySelect || !contactSelect) return;

                const urlTemplate = companySelect.getAttribute('data-contacts-url-template') || '';
                const prefillContactId = String(companySelect.getAttribute('data-prefill-contact-id') || '');

                function setContactOptions(contacts, selectedId) {
                    const current = String(selectedId || '');
                    contactSelect.innerHTML = `<option value="">— none —</option>`;

                    (contacts || []).forEach(c => {
                        const id = String(c.id ?? '');
                        const label = c.name || ('Contact #' + id);
                        const opt = document.createElement('option');
                        opt.value = id;
                        opt.textContent = label;
                        if (current && id === current) opt.selected = true;
                        contactSelect.appendChild(opt);
                    });
                }

                async function loadContactsForCompany(companyId, selectedId = '') {
                    const cid = String(companyId || '').trim();
                    if (!cid) {
                        setContactOptions([], '');
                        return;
                    }

                    const url = urlTemplate.replace('__ID__', encodeURIComponent(cid));
                    if (!url || url.includes('__ID__')) {
                        setContactOptions([], '');
                        return;
                    }

                    const keepSelected = String(selectedId || contactSelect.value || prefillContactId ||
                        '');

                    contactSelect.innerHTML = `<option value="">Loading…</option>`;

                    try {
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) throw new Error('HTTP ' + res.status);

                        const data = await res.json();
                        const contacts = Array.isArray(data.contacts) ? data.contacts : [];

                        let finalSelected = keepSelected;
                        if (finalSelected && !contacts.some(c => String(c.id) === String(finalSelected))) {
                            finalSelected = contacts[0]?.id ? String(contacts[0].id) : '';
                        }

                        setContactOptions(contacts, finalSelected);
                    } catch (e) {
                        console.error('[NW] Failed loading contacts', e);
                        setContactOptions([], '');
                    }
                }

                companySelect.addEventListener('change', () => loadContactsForCompany(companySelect.value));
                if (String(companySelect.value || '').trim()) loadContactsForCompany(companySelect.value,
                    prefillContactId);
                else setContactOptions([], '');
            })();

            // ============================================================
            // B) COMPANY BLOCKS (billing/shipping/vat/payment terms)
            // ============================================================
            (function initCompanyBlocks() {
                const companySelect = document.getElementById('companySelect');
                const billingBox = document.getElementById('billingBox');
                const shippingBox = document.getElementById('shippingBox');
                const vatTreatmentLine = document.getElementById('vatTreatmentLine');
                const paymentTermsLine = document.getElementById('paymentTermsLine');

                function nl2br(s) {
                    return (s || '').replace(/\n/g, '<br>');
                }

                function refreshCompanyBlocks() {
                    const id = companySelect?.value;
                    const c = (window.NW_COMPANIES || {})[id];

                    if (!c) {
                        if (billingBox) billingBox.innerHTML =
                            `<div class="text-muted small">Select a company to view billing address.</div>`;
                        if (shippingBox) shippingBox.innerHTML =
                            `<div class="text-muted small">Select a company to view shipping address.</div>`;
                        if (vatTreatmentLine) vatTreatmentLine.textContent = '';
                        if (paymentTermsLine) paymentTermsLine.textContent = '—';
                        return;
                    }

                    const billing = c.billing_address || c.address || '';
                    const shipping = c.shipping_address || c.address || '';

                    if (billingBox) billingBox.innerHTML = billing.trim() ? nl2br(billing.trim()) :
                        `<span class="text-muted small">—</span>`;
                    if (shippingBox) shippingBox.innerHTML = shipping.trim() ? nl2br(shipping.trim()) :
                        `<span class="text-muted small">—</span>`;

                    const vt = c.vat_treatment || '';
                    const vn = c.vat_number || '';
                    if (vatTreatmentLine) vatTreatmentLine.textContent = (vt || vn) ?
                        `VAT: ${vt}${vn ? ' • ' + vn : ''}` : '';
                    if (paymentTermsLine) paymentTermsLine.textContent = (c.payment_terms || '').trim() ? c
                        .payment_terms : '—';
                }

                companySelect?.addEventListener('change', refreshCompanyBlocks);
                refreshCompanyBlocks();
            })();

            // ============================================================
            // C) PRODUCTS PICKER / ITEMS TABLE (WORKING VERSION)
            // ============================================================
            (function initQuoteItems() {
                const itemsBody = document.getElementById('itemsBody');
                const addBtn = document.getElementById('addItemBtn');
                const defaultTaxType = document.getElementById('defaultTaxType');

                if (!itemsBody) {
                    console.error('[NW Quotes] #itemsBody not found');
                    return;
                }

                const RAW_PRODUCTS_OBJ = window.NW_PRODUCTS || {};
                const PRODUCTS = Object.values(RAW_PRODUCTS_OBJ); // array
                const TAXTYPES = window.NW_TAXTYPES || {};

                // If this logs 0, your backend didn't pass products (controller issue)
                console.log('[NW Quotes] products:', PRODUCTS.length, 'taxTypes:', Object.keys(TAXTYPES)
                    .length);

                // ===== Utils =====
                function money(n) {
                    const x = Number(n || 0);
                    return x.toLocaleString('en-ZA', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                function clamp(n, min, max) {
                    const x = Number(n);
                    if (Number.isNaN(x)) return min;
                    return Math.min(max, Math.max(min, x));
                }

                function normalizeRate(p) {
                    return (p.unit_rate ?? p.unit_price ?? p.price ?? p.selling_price ?? 0);
                }

                // ===== Global dropdown (ONE TIME) =====
                const globalSuggest = document.createElement('div');
                globalSuggest.className = 'nw-suggest';
                document.body.appendChild(globalSuggest);

                let activeRow = null;
                let activeInput = null;

                function openSuggest(inputEl, rowEl, html) {
                    const r = inputEl.getBoundingClientRect();
                    globalSuggest.innerHTML = html;
                    globalSuggest.style.left = r.left + 'px';
                    globalSuggest.style.top = (r.bottom + 6) + 'px';
                    globalSuggest.style.width = r.width + 'px';
                    globalSuggest.style.display = 'block';
                    activeRow = rowEl;
                    activeInput = inputEl;
                }

                function closeSuggest() {
                    globalSuggest.style.display = 'none';
                    globalSuggest.innerHTML = '';
                    activeRow = null;
                    activeInput = null;
                }

                window.addEventListener('scroll', () => {
                    if (globalSuggest.style.display === 'block') closeSuggest();
                }, true);
                window.addEventListener('resize', () => {
                    if (globalSuggest.style.display === 'block') closeSuggest();
                });

                document.addEventListener('click', (e) => {
                    if (e.target.closest('.nw-picker')) return;
                    if (e.target.closest('.nw-suggest')) return;
                    closeSuggest();
                });

                // ===== Search scoring =====
                function scoreMatch(p, term) {
                    const t = (term || '').toLowerCase().trim();
                    if (!t) return -1;

                    const sku = String(p.sku || '').toLowerCase();
                    const name = String(p.name || '').toLowerCase();
                    const desc = String(p.description || '').toLowerCase();

                    let score = -1;
                    if (sku.startsWith(t)) score = Math.max(score, 120);
                    if (name.startsWith(t)) score = Math.max(score, 110);
                    if (sku.includes(t)) score = Math.max(score, 90);
                    if (name.includes(t)) score = Math.max(score, 80);
                    if (desc.includes(t)) score = Math.max(score, 50);
                    return score;
                }

                function findMatches(term, limit = 12) {
                    const t = (term || '').trim();
                    if (t.length < 1) return [];

                    const ranked = [];
                    for (const p of PRODUCTS) {
                        const s = scoreMatch(p, t);
                        if (s >= 0) ranked.push({
                            p,
                            s
                        });
                    }
                    ranked.sort((a, b) => b.s - a.s);
                    return ranked.slice(0, limit).map(x => x.p);
                }

                function taxOptions(selectedId) {
                    let html = `<option value="">—</option>`;
                    Object.values(TAXTYPES).forEach(t => {
                        const sel = String(t.id) === String(selectedId) ? 'selected' : '';
                        html +=
                            `<option value="${t.id}" ${sel}>${t.name} (${money(parseFloat(t.rate || 0))}%)</option>`;
                    });
                    return html;
                }

                function setSelectedUI(row, selected) {
                    const searchWrap = row.querySelector('.nw-search-wrap');
                    const pickedWrap = row.querySelector('.nw-picked');
                    const descWrap = row.querySelector('.nw-desc-wrap');

                    if (searchWrap) searchWrap.style.display = selected ? 'none' : 'block';
                    if (pickedWrap) pickedWrap.style.display = selected ? 'block' : 'none';
                    if (descWrap) descWrap.style.display = selected ? 'block' : 'none';
                }

                function applyProduct(row, productId) {
                    const map = (window.NW_PRODUCTS || {});
                    const p = map[productId] || map[String(productId)];
                    if (!p) return;

                    row.querySelector('.product_id').value = p.id;
                    row.querySelector('.sku').value = p.sku ?? '';
                    row.querySelector('.pickedText').value = `${p.name ?? ''}${p.sku ? ' • ' + p.sku : ''}`;
                    row.querySelector('.itemName').value = p.name ?? '';
                    row.querySelector('.itemDesc').value = p.description ?? '';
                    row.querySelector('.unit_price').value = normalizeRate(p);

                    setSelectedUI(row, true);
                    recalc();
                }

                function clearProduct(row) {
                    row.querySelector('.product_id').value = '';
                    row.querySelector('.sku').value = '';
                    row.querySelector('.pickedText').value = '';
                    row.querySelector('.itemName').value = '';
                    row.querySelector('.itemDesc').value = '';
                    row.querySelector('.unit_price').value = 0;

                    const inp = row.querySelector('.nw-search');
                    if (inp) inp.value = '';
                    setSelectedUI(row, false);
                    closeSuggest();
                    row.querySelector('.nw-search')?.focus();
                    recalc();
                }

                function recalc() {
                    let subtotal = 0,
                        discountTotal = 0,
                        taxTotal = 0;

                    itemsBody.querySelectorAll('.quote-item-row').forEach(row => {
                        const qty = parseFloat(row.querySelector('.qty')?.value || '0');
                        const rate = parseFloat(row.querySelector('.unit_price')?.value || '0');

                        const gross = qty * rate;
                        const discPct = clamp(row.querySelector('.discount_pct')?.value || 0, 0, 100);
                        const discAmt = gross * (discPct / 100);
                        const net = gross - discAmt;

                        const taxTypeId = row.querySelector('.taxType')?.value || '';
                        const t = (window.NW_TAXTYPES || {})[taxTypeId] || (window.NW_TAXTYPES || {})[
                            String(taxTypeId)];
                        const vatRate = t ? parseFloat(t.rate || 0) : 0;

                        const vat = net * (vatRate / 100);
                        const incl = net + vat;

                        subtotal += gross;
                        discountTotal += discAmt;
                        taxTotal += vat;

                        row.querySelector('.lineExcl').textContent = money(net);
                        row.querySelector('.lineIncl').textContent = money(incl);
                    });

                    document.getElementById('subtotal').textContent = money(subtotal);
                    document.getElementById('discountTotal').textContent = money(discountTotal);
                    document.getElementById('taxAmount').textContent = money(taxTotal);
                    document.getElementById('grandTotal').textContent = money((subtotal - discountTotal) +
                        taxTotal);
                }

                function reindex() {
                    itemsBody.querySelectorAll('.quote-item-row').forEach((row, idx) => {
                        row.querySelectorAll('input,select,textarea').forEach(el => {
                            if (!el.name) return;
                            el.name = el.name.replace(/items\[\d+\]/, `items[${idx}]`);
                        });
                    });
                }

                function addRow(prefill = {}) {
                    const idx = itemsBody.querySelectorAll('.quote-item-row').length;
                    const defaultTaxId = prefill.tax_type_id ?? (defaultTaxType?.value || '');

                    const tr = document.createElement('tr');
                    tr.className = 'quote-item-row';

                    tr.innerHTML = `
                    <td>
                        <div class="nw-picker">
                            <div class="nw-search-wrap">
                                <input type="text" class="form-control form-control-sm nw-search"
                                       placeholder="Search SKU or name…" autocomplete="off">
                            </div>

                            <div class="nw-picked">
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control pickedText" readonly value="">
                                    <button type="button" class="btn btn-outline-danger clearProductBtn" title="Clear">✕</button>
                                </div>
                            </div>

                            <input type="hidden" class="product_id" name="items[${idx}][product_id]" value="${prefill.product_id ?? ''}">
                            <input type="hidden" class="itemName" name="items[${idx}][name]" value="${String(prefill.name ?? '').replace(/"/g,'&quot;')}">

                            <div class="nw-desc-wrap">
                                <textarea class="form-control form-control-sm itemDesc"
                                          name="items[${idx}][description]" rows="2"
                                          placeholder="Description…">${String(prefill.description ?? '')}</textarea>
                            </div>
                        </div>
                    </td>

                    <td><input class="form-control form-control-sm sku" name="items[${idx}][sku]" value="${prefill.sku ?? ''}" readonly></td>

                    <td><input class="form-control form-control-sm qty" type="number" step="0.01" min="0.01"
                               name="items[${idx}][qty]" value="${prefill.qty ?? 1}" required></td>

                    <td><input class="form-control form-control-sm unit_price" type="number" step="0.01" min="0"
                               name="items[${idx}][unit_price]" value="${prefill.unit_price ?? 0}" required></td>

                    <td><input class="form-control form-control-sm discount_pct" type="number" step="0.01" min="0" max="100"
                               name="items[${idx}][discount_pct]" value="${prefill.discount_pct ?? 0}"></td>

                    <td>
                        <select class="form-select form-select-sm taxType" name="items[${idx}][tax_type_id]">
                            ${taxOptions(defaultTaxId)}
                        </select>
                    </td>

                    <td class="text-end nw-amount-col">R <span class="lineExcl">0.00</span></td>
                    <td class="text-end nw-amount-col">R <span class="lineIncl">0.00</span></td>

                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger removeItemBtn">×</button>
                    </td>
                `;

                    itemsBody.appendChild(tr);

                    const input = tr.querySelector('.nw-search');

                    function renderDropdown(term) {
                        const matches = findMatches(term, 12);

                        if (!matches.length) {
                            openSuggest(input, tr,
                                `<div class="nw-opt"><div class="nw-sub">No matches</div></div>`);
                            return;
                        }

                        const html = matches.map(p => {
                            const sub = [p.sku ? `SKU: ${p.sku}` : null, (p.description || '').trim()]
                                .filter(Boolean).join(' • ');

                            return `
                            <div class="nw-opt" data-id="${p.id}">
                                <div class="nw-name">${p.name ?? '—'}</div>
                                <div class="nw-sub">${(sub || '').substring(0, 130)}</div>
                            </div>
                        `;
                        }).join('');

                        openSuggest(input, tr, html);
                    }

                    input.addEventListener('focus', () => (input.value || '').trim() && renderDropdown(input
                        .value));
                    input.addEventListener('input', () => renderDropdown(input.value));

                    // Prefill product if exists
                    if (prefill.product_id) {
                        applyProduct(tr, prefill.product_id);
                        setSelectedUI(tr, true);
                    } else {
                        setSelectedUI(tr, false);
                    }

                    recalc();
                }

                // ✅ ONE click handler for dropdown (NOT inside addRow)
                globalSuggest.addEventListener('mousedown', (e) => {
                    const opt = e.target.closest('.nw-opt[data-id]');
                    if (!opt) return;
                    e.preventDefault();
                    if (!activeRow) return;

                    applyProduct(activeRow, opt.getAttribute('data-id'));
                    closeSuggest();
                });

                // Boot rows (✅ keep old values!)
                const old = window.NW_OLD_ITEMS || [];
                if (old.length) old.forEach(it => addRow(it));
                else addRow();

                // Events
                itemsBody.addEventListener('change', (e) => {
                    const row = e.target.closest('.quote-item-row');
                    if (!row) return;
                    if (e.target.classList.contains('taxType')) recalc();
                });

                itemsBody.addEventListener('input', (e) => {
                    if (
                        e.target.classList.contains('qty') ||
                        e.target.classList.contains('unit_price') ||
                        e.target.classList.contains('discount_pct')
                    ) recalc();
                });

                itemsBody.addEventListener('click', (e) => {
                    const row = e.target.closest('.quote-item-row');
                    if (!row) return;

                    if (e.target.classList.contains('clearProductBtn')) return clearProduct(row);

                    if (e.target.classList.contains('removeItemBtn')) {
                        if (itemsBody.querySelectorAll('.quote-item-row').length <= 1) {
                            clearProduct(row);
                            row.querySelector('.qty').value = 1;
                            row.querySelector('.unit_price').value = 0;
                            row.querySelector('.discount_pct').value = 0;
                            row.querySelector('.taxType').value = defaultTaxType?.value || '';
                            recalc();
                            return;
                        }
                        row.remove();
                        reindex();
                        recalc();
                    }
                });

                addBtn?.addEventListener('click', () => addRow());

                defaultTaxType?.addEventListener('change', () => {
                    itemsBody.querySelectorAll('.quote-item-row .taxType').forEach(sel => {
                        if (!sel.value) sel.value = defaultTaxType.value;
                    });
                    recalc();
                });

                recalc();
            })();
        });
    </script>
@endpush
