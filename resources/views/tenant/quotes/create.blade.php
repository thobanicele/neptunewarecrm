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
                            <div class="d-flex align-items-center gap-3">
                                @if ($tenant->logo_path)
                                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="Logo"
                                        style="height:56px;">
                                @else
                                    <div class="rounded bg-light border d-flex align-items-center justify-content-center"
                                        style="height:56px; width:56px;">
                                        <span
                                            class="text-muted fw-semibold">{{ strtoupper(substr($tenant->name, 0, 1)) }}</span>
                                    </div>
                                @endif

                                <div>
                                    <div class="fw-semibold" style="font-size: 18px;">{{ $tenant->name }}</div>
                                    <div class="text-muted small">Quote</div>
                                </div>
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
                                                <option value="{{ $u->id }}" @selected((string) old('sales_person_user_id', $prefillSalesPersonId ?? auth()->id()) === (string) $u->id)>
                                                    {{ $u->name }}
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
                                                <option value="{{ $d->id }}" @selected((string) old('deal_id', $deal?->id) === (string) $d->id)>
                                                    #{{ $d->id }} • {{ $d->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- ✅ NEW: Customer Reference --}}
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
                            <select class="form-select" name="company_id" id="companySelect" required>
                                <option value="">— select company —</option>
                                @foreach ($companies ?? collect() as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('company_id', $prefillCompanyId ?? null) === (string) $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Company details show below after selecting.</div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Contact (optional)</label>
                            <select class="form-select" name="contact_id" id="contactSelect">
                                <option value="">— none —</option>
                                @foreach ($contacts ?? collect() as $p)
                                    <option value="{{ $p->id }}" @selected((string) old('contact_id', $prefillContactId ?? null) === (string) $p->id)>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
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
                                    <option value="{{ $t->id }}" @selected((string) old('tax_type_id', $defaultTaxTypeId ?? null) === (string) $t->id)>
                                        {{ $t->name }} ({{ number_format((float) $t->rate, 2) }}%)
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
                    <div class="text-muted small">Pick products, adjust qty/rate, discount per line, choose VAT per line.
                    </div>
                </div>

                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table align-middle table-sm nw-quote-table">
                            <thead>
                                <tr>
                                    <th style="width: 38%;">ITEM</th>
                                    <th style="width: 12%;">SKU</th>
                                    <th style="width: 10%;">QTY</th>
                                    <th style="width: 12%;">RATE</th>
                                    <th style="width: 10%;">DISC %</th>
                                    <th style="width: 16%;">VAT</th>
                                    {{-- ✅ widths removed for amount columns (auto adjust) --}}
                                    <th class="text-end nw-amount-col">LINE</th>
                                    <th class="text-end nw-amount-col">INCL</th>
                                    <th style="width: 2%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>

                    <button type="button" class="btn btn-outline-primary" id="addItemBtn">+ Add New Row</button>

                    <div class="row mt-4">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Customer Notes</label>
                            <textarea class="form-control" name="notes" rows="4" placeholder="Will be displayed on the quote">{{ old('notes') }}</textarea>

                            <div class="mt-3">
                                <label class="form-label">Terms</label>
                                <textarea class="form-control" name="terms" rows="4">{{ old('terms') }}</textarea>
                            </div>

                            {{-- Signature lines --}}
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
        .nw-item-display {
            display: none;
        }

        .nw-item-display .form-control[readonly] {
            background: #f8f9fa;
        }

        /* ✅ Make table text smaller + tighter */
        .nw-quote-table,
        .nw-quote-table td,
        .nw-quote-table th {
            font-size: 0.875rem;
            /* smaller than default */
        }

        /* ✅ Ensure amount columns don't spill (keeps figures on one line & allows horizontal scroll if needed) */
        .nw-amount-col {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        /* Inputs inside table a bit smaller and tighter */
        .nw-quote-table .form-control,
        .nw-quote-table .form-select {
            font-size: 0.875rem;
            padding-top: .25rem;
            padding-bottom: .25rem;
        }

        /* If a number still gets huge, allow table to scroll instead of breaking layout */
        .table-responsive {
            overflow-x: auto;
        }
    </style>
@endpush

@push('scripts')
    <script>
        window.NW_PRODUCTS = @json(($products ?? collect())->keyBy('id'));
        window.NW_TAXTYPES = @json(($taxTypes ?? collect())->keyBy('id'));
        window.NW_OLD_ITEMS = @json(old('items', []));
        window.NW_COMPANIES = @json(($companies ?? collect())->keyBy('id'));

        (function() {
            const companySelect = document.getElementById('companySelect');
            const billingBox = document.getElementById('billingBox');
            const shippingBox = document.getElementById('shippingBox');
            const vatTreatmentLine = document.getElementById('vatTreatmentLine');
            const paymentTermsLine = document.getElementById('paymentTermsLine');

            const itemsBody = document.getElementById('itemsBody');
            const addBtn = document.getElementById('addItemBtn');
            const defaultTaxType = document.getElementById('defaultTaxType');

            function money(n) {
                const x = Number(n || 0);
                return x.toLocaleString('en-ZA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function nl2br(s) {
                return (s || '').replace(/\n/g, '<br>');
            }

            // Company blocks
            function refreshCompanyBlocks() {
                const id = companySelect?.value;
                const c = (window.NW_COMPANIES || {})[id];

                if (!c) {
                    billingBox.innerHTML =
                        `<div class="text-muted small">Select a company to view billing address.</div>`;
                    shippingBox.innerHTML =
                        `<div class="text-muted small">Select a company to view shipping address.</div>`;
                    vatTreatmentLine.textContent = '';
                    paymentTermsLine.textContent = '—';
                    return;
                }

                const billing = c.billing_address || c.address || '';
                const shipping = c.shipping_address || c.address || '';

                billingBox.innerHTML = billing.trim() ? nl2br(billing.trim()) :
                    `<span class="text-muted small">—</span>`;
                shippingBox.innerHTML = shipping.trim() ? nl2br(shipping.trim()) :
                    `<span class="text-muted small">—</span>`;

                const vt = c.vat_treatment || '';
                const vn = c.vat_number || '';
                vatTreatmentLine.textContent = (vt || vn) ? `VAT: ${vt}${vn ? ' • ' + vn : ''}` : '';
                paymentTermsLine.textContent = (c.payment_terms || '').trim() ? c.payment_terms : '—';
            }
            companySelect?.addEventListener('change', refreshCompanyBlocks);
            refreshCompanyBlocks();

            // Options builders
            function taxOptions(selectedId) {
                const types = window.NW_TAXTYPES || {};
                let html = `<option value="">—</option>`;
                Object.values(types).forEach(t => {
                    const sel = String(t.id) === String(selectedId) ? 'selected' : '';
                    html += `<option value="${t.id}" ${sel}>${t.name} (${money(parseFloat(t.rate))}%)</option>`;
                });
                return html;
            }

            function productOptions(selectedId) {
                const products = window.NW_PRODUCTS || {};
                let html = `<option value="">Search SKU or name…</option>`;
                Object.values(products).forEach(p => {
                    const sel = String(p.id) === String(selectedId) ? 'selected' : '';
                    const label = `${p.sku ? p.sku+' — ' : ''}${p.name}`;
                    html += `<option value="${p.id}" ${sel}>${label}</option>`;
                });
                return html;
            }

            function initSelect2(selectEl) {
                if (!window.jQuery || !jQuery.fn.select2) return;
                const $el = jQuery(selectEl);
                if ($el.data('select2')) $el.select2('destroy');

                $el.select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Search SKU or name…',
                    allowClear: true,
                    matcher: function(params, data) {
                        if (jQuery.trim(params.term) === '') return data;
                        if (!data.id) return data;

                        const term = (params.term || '').toLowerCase();
                        const p = (window.NW_PRODUCTS || {})[data.id];
                        const hay = [p?.sku, p?.name, p?.description].filter(Boolean).join(' ')
                        .toLowerCase();
                        return hay.includes(term) ? data : null;
                    }
                });
            }

            function setSelectedUI(row, selected) {
                const selectWrap = row.querySelector('.nw-item-select');
                const displayWrap = row.querySelector('.nw-item-display');
                const desc = row.querySelector('.itemDesc');

                if (selected) {
                    selectWrap.style.display = 'none';
                    displayWrap.style.display = 'block';
                    desc.readOnly = true;
                } else {
                    selectWrap.style.display = 'block';
                    displayWrap.style.display = 'none';
                    desc.readOnly = false;
                }
            }

            function applyProduct(row, productId) {
                const p = (window.NW_PRODUCTS || {})[productId];
                if (!p) return;

                row.querySelector('.sku').value = p.sku ?? '';
                row.querySelector('.itemDisplayText').value = `${p.name ?? ''}${p.sku ? ' • ' + p.sku : ''}`;

                row.querySelector('.itemName').value = p.name ?? '';
                row.querySelector('.itemDesc').value = p.description ?? '';

                row.querySelector('.unit_price').value = p.unit_rate ?? 0;

                setSelectedUI(row, true);
                recalc();
            }

            function clearProduct(row) {
                const sel = row.querySelector('.productSelect');
                if (sel && window.jQuery) jQuery(sel).val('').trigger('change.select2');

                row.querySelector('.sku').value = '';
                row.querySelector('.itemDisplayText').value = '';
                row.querySelector('.itemName').value = '';
                row.querySelector('.itemDesc').value = '';
                setSelectedUI(row, false);

                if (sel && window.jQuery) jQuery(sel).select2('open');
                recalc();
            }

            function clamp(n, min, max) {
                const x = Number(n);
                if (Number.isNaN(x)) return min;
                return Math.min(max, Math.max(min, x));
            }

            function recalc() {
                let subtotal = 0; // qty * rate (before discount)
                let discountTotal = 0; // discount amount total
                let taxTotal = 0;

                itemsBody.querySelectorAll('.quote-item-row').forEach(row => {
                    const qty = parseFloat(row.querySelector('.qty')?.value || '0');
                    const rate = parseFloat(row.querySelector('.unit_price')?.value || '0');

                    const gross = qty * rate;
                    const discPct = clamp(row.querySelector('.discount_pct')?.value || 0, 0, 100);
                    const discAmt = gross * (discPct / 100);

                    const net = gross - discAmt;

                    const taxTypeId = row.querySelector('.taxType')?.value || '';
                    const t = (window.NW_TAXTYPES || {})[taxTypeId];
                    const vatRate = t ? parseFloat(t.rate) : 0;

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
                document.getElementById('grandTotal').textContent = money((subtotal - discountTotal) + taxTotal);
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
                    <div class="nw-item-select">
                        <select class="form-select productSelect" name="items[${idx}][product_id]">
                            ${productOptions(prefill.product_id ?? '')}
                        </select>
                    </div>

                    <div class="nw-item-display">
                        <div class="input-group">
                            <input type="text" class="form-control itemDisplayText" value="" readonly>
                            <button type="button" class="btn btn-outline-danger clearProductBtn">✕</button>
                        </div>
                    </div>

                    <input type="hidden" class="itemName" name="items[${idx}][name]" value="${prefill.name ?? ''}">
                    <textarea class="form-control form-control-sm mt-2 itemDesc"
                              name="items[${idx}][description]" rows="2"
                              placeholder="Description…">${prefill.description ?? ''}</textarea>
                </td>

                <td><input class="form-control sku" name="items[${idx}][sku]" value="${prefill.sku ?? ''}" readonly></td>

                <td><input class="form-control qty" type="number" step="0.01" min="0.01"
                           name="items[${idx}][qty]" value="${prefill.qty ?? 1}" required></td>

                <td><input class="form-control unit_price" type="number" step="0.01" min="0"
                           name="items[${idx}][unit_price]" value="${prefill.unit_price ?? 0}" required></td>

                <td><input class="form-control discount_pct" type="number" step="0.01" min="0" max="100"
                           name="items[${idx}][discount_pct]" value="${prefill.discount_pct ?? 0}"></td>

                <td>
                    <select class="form-select taxType" name="items[${idx}][tax_type_id]">
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

                const sel = tr.querySelector('.productSelect');
                initSelect2(sel);

                if (prefill.product_id) {
                    setSelectedUI(tr, true);
                    tr.querySelector('.itemDisplayText').value =
                        `${prefill.name ?? ''}${prefill.sku ? ' • ' + prefill.sku : ''}`;
                    tr.querySelector('.itemDesc').readOnly = true;
                } else {
                    setSelectedUI(tr, false);
                }

                recalc();
            }

            // Boot rows
            const old = window.NW_OLD_ITEMS || [];
            if (old.length) old.forEach(it => addRow(it));
            else addRow();

            // Events
            itemsBody.addEventListener('change', (e) => {
                const row = e.target.closest('.quote-item-row');
                if (!row) return;

                if (e.target.classList.contains('productSelect')) {
                    const pid = e.target.value;
                    if (pid) applyProduct(row, pid);
                    else setSelectedUI(row, false);
                }

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

                if (e.target.classList.contains('clearProductBtn')) {
                    clearProduct(row);
                    return;
                }

                if (e.target.classList.contains('removeItemBtn')) {
                    const sel = row.querySelector('.productSelect');
                    if (sel && window.jQuery && jQuery(sel).data('select2')) jQuery(sel).select2('destroy');

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
    </script>
@endpush
