@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Edit Quote</h3>
                <div class="text-muted small">
                    {{ $quote->quote_number }} • Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})
                </div>
            </div>
            <a href="{{ tenant_route('tenant.quotes.show', ['quote' => $quote->id]) }}" class="btn btn-light">Back</a>
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

        <form method="POST" action="{{ tenant_route('tenant.quotes.update', ['quote' => $quote->id]) }}">
            @csrf
            @method('PUT')

            {{-- TOP: same layout as create --}}
            <div class="card mb-3">
                <div class="card-body">

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
                                            value="{{ old('issued_at', optional($quote->issued_at)->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small">Expiry Date</div>
                                        <input type="date" class="form-control" name="valid_until"
                                            value="{{ old('valid_until', optional($quote->valid_until)->format('Y-m-d')) }}">
                                    </div>

                                    <div class="col-6">
                                        <div class="text-muted small">Status</div>
                                        <select class="form-select" name="status">
                                            @foreach (['draft', 'sent', 'accepted', 'declined', 'expired'] as $s)
                                                <option value="{{ $s }}" @selected(old('status', $quote->status) === $s)>
                                                    {{ strtoupper($s) }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-6">
                                        <div class="text-muted small">Salesperson</div>
                                        <select class="form-select @error('sales_person_user_id') is-invalid @enderror"
                                            name="sales_person_user_id" required>
                                            @foreach ($salesPeople ?? collect() as $u)
                                                <option value="{{ $u->id }}" @selected((string) old('sales_person_user_id', $quote->sales_person_user_id) === (string) $u->id)>
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
                                                <option value="{{ $d->id }}" @selected((string) old('deal_id', $quote->deal_id) === (string) $d->id)>
                                                    #{{ $d->id }} • {{ $d->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>
                            </div>
                            <div class="form-text">Owner: {{ $quote->owner?->name ?? '—' }}</div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- Customer + Contact --}}
                    <div class="row g-3">
                        <div class="col-12 col-lg-8">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <select class="form-select" name="company_id" id="companySelect" required>
                                <option value="">— select company —</option>
                                @foreach ($companies ?? collect() as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('company_id', $quote->company_id) === (string) $c->id)>
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
                                    <option value="{{ $p->id }}" @selected((string) old('contact_id', $quote->contact_id) === (string) $p->id)>
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

                    {{-- Default VAT (for blank tax rows) --}}
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
                            <div class="form-text">New/empty VAT rows will use this by default.</div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ITEMS TABLE --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">Items</div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">+ Add New Row</button>
                </div>

                <div class="card-body">

                    @php
                        // old() takes priority; otherwise use existing snapshots from quote items
                        $oldItems = old('items');
                        if (!$oldItems) {
                            $oldItems = $quote->items
                                ->sortBy('position')
                                ->map(function ($it) {
                                    return [
                                        'product_id' => $it->product_id,
                                        'tax_type_id' => $it->tax_type_id,
                                        'name' => $it->name,
                                        'description' => $it->description,
                                        'qty' => (float) $it->qty,
                                        'unit_price' => (float) $it->unit_price,
                                        'sku' => $it->sku ?? null,
                                        'unit' => $it->unit ?? null,
                                    ];
                                })
                                ->values()
                                ->all();
                        }
                        if (!$oldItems) {
                            $oldItems = [
                                [
                                    'product_id' => null,
                                    'tax_type_id' => null,
                                    'name' => '',
                                    'description' => '',
                                    'qty' => 1,
                                    'unit_price' => 0,
                                    'sku' => '',
                                    'unit' => '',
                                ],
                            ];
                        }
                    @endphp

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 34%;">ITEM</th>
                                    <th style="width: 10%;">SKU</th>
                                    <th style="width: 8%;">UNIT</th>
                                    <th style="width: 8%;">QTY</th>
                                    <th style="width: 10%;">RATE</th>
                                    <th style="width: 14%;">VAT</th>
                                    <th class="text-end" style="width: 8%;">VAT AMT</th>
                                    <th class="text-end" style="width: 8%;">LINE</th>
                                    <th class="text-end" style="width: 8%;">INCL</th>
                                    <th style="width: 2%;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Customer Notes</label>
                            <textarea class="form-control" name="notes" rows="4" placeholder="Will be displayed on the quote">{{ old('notes', $quote->notes) }}</textarea>

                            <div class="mt-3">
                                <label class="form-label">Terms</label>
                                <textarea class="form-control" name="terms" rows="4">{{ old('terms', $quote->terms) }}</textarea>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6 d-flex justify-content-end">
                            <div class="border rounded p-4" style="min-width: 360px;">
                                <div class="d-flex justify-content-between">
                                    <div class="fw-semibold">Subtotal</div>
                                    <div class="fw-semibold">R <span id="subtotal">0.00</span></div>
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
                                    Totals are calculated from Qty × Rate + VAT (per line).
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
                        <button class="btn btn-primary" type="submit">Save changes</button>
                        <a class="btn btn-light"
                            href="{{ tenant_route('tenant.quotes.show', ['quote' => $quote->id]) }}">Cancel</a>
                    </div>

                </div>
            </div>

        </form>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <style>
        .nw-item-display {
            display: none;
        }

        .nw-item-display .form-control[readonly] {
            background: #f8f9fa;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        window.NW_PRODUCTS = @json(($products ?? collect())->keyBy('id'));
        window.NW_TAXTYPES = @json(($taxTypes ?? collect())->keyBy('id'));
        window.NW_COMPANIES = @json(($companies ?? collect())->keyBy('id'));
        window.NW_SEED_ITEMS = @json($oldItems);

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
                        const hay = [p?.sku, p?.name, p?.description, p?.unit].filter(Boolean).join(' ')
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
                row.querySelector('.unit_text').value = p.unit ?? '';
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
                row.querySelector('.unit_text').value = '';
                row.querySelector('.itemDisplayText').value = '';
                row.querySelector('.itemName').value = '';
                row.querySelector('.itemDesc').value = '';

                setSelectedUI(row, false);

                if (sel && window.jQuery) jQuery(sel).select2('open');
                recalc();
            }

            function recalc() {
                let subtotal = 0;
                let taxTotal = 0;

                itemsBody.querySelectorAll('.quote-item-row').forEach(row => {
                    const qty = parseFloat(row.querySelector('.qty')?.value || '0');
                    const rate = parseFloat(row.querySelector('.unit_price')?.value || '0');
                    const line = qty * rate;

                    const taxTypeId = row.querySelector('.taxType')?.value || '';
                    const t = (window.NW_TAXTYPES || {})[taxTypeId];
                    const vatRate = t ? parseFloat(t.rate) : 0;

                    const vat = line * (vatRate / 100);
                    const incl = line + vat;

                    subtotal += line;
                    taxTotal += vat;

                    row.querySelector('.lineVat').textContent = money(vat);
                    row.querySelector('.lineExcl').textContent = money(line);
                    row.querySelector('.lineIncl').textContent = money(incl);
                });

                document.getElementById('subtotal').textContent = money(subtotal);
                document.getElementById('taxAmount').textContent = money(taxTotal);
                document.getElementById('grandTotal').textContent = money(subtotal + taxTotal);
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
            <td><input class="form-control unit_text" name="items[${idx}][unit]" value="${prefill.unit ?? ''}" readonly></td>

            <td><input class="form-control qty" type="number" step="0.01" min="0.01" name="items[${idx}][qty]" value="${prefill.qty ?? 1}" required></td>
            <td><input class="form-control unit_price" type="number" step="0.01" min="0" name="items[${idx}][unit_price]" value="${prefill.unit_price ?? 0}" required></td>

            <td>
                <select class="form-select taxType" name="items[${idx}][tax_type_id]">
                    ${taxOptions(defaultTaxId)}
                </select>
            </td>

            <td class="text-end">R <span class="lineVat">0.00</span></td>
            <td class="text-end">R <span class="lineExcl">0.00</span></td>
            <td class="text-end">R <span class="lineIncl">0.00</span></td>

            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger removeItemBtn">×</button>
            </td>
        `;

                itemsBody.appendChild(tr);

                const sel = tr.querySelector('.productSelect');
                initSelect2(sel);

                // seed/prefill behavior
                if (prefill.product_id) {
                    // If product exists in catalog, prefer catalog to re-sync sku/unit/unit_rate/desc
                    if ((window.NW_PRODUCTS || {})[prefill.product_id]) {
                        applyProduct(tr, prefill.product_id);
                        // keep qty, vat selection, and unit_price if user edited it historically?
                        tr.querySelector('.qty').value = prefill.qty ?? 1;
                        tr.querySelector('.unit_price').value = prefill.unit_price ?? (window.NW_PRODUCTS[prefill
                            .product_id]?.unit_rate ?? 0);
                        if (prefill.tax_type_id) tr.querySelector('.taxType').value = prefill.tax_type_id;
                    } else {
                        // fallback: use snapshots
                        setSelectedUI(tr, true);
                        tr.querySelector('.itemDisplayText').value =
                            `${prefill.name ?? ''}${prefill.sku ? ' • ' + prefill.sku : ''}`;
                        tr.querySelector('.sku').value = prefill.sku ?? '';
                        tr.querySelector('.unit_text').value = prefill.unit ?? '';
                        tr.querySelector('.itemName').value = prefill.name ?? '';
                        tr.querySelector('.itemDesc').value = prefill.description ?? '';
                        tr.querySelector('.qty').value = prefill.qty ?? 1;
                        tr.querySelector('.unit_price').value = prefill.unit_price ?? 0;
                        if (prefill.tax_type_id) tr.querySelector('.taxType').value = prefill.tax_type_id;
                    }
                } else {
                    setSelectedUI(tr, false);
                }

                recalc();
            }

            // Boot
            const seed = window.NW_SEED_ITEMS || [];
            if (seed.length) seed.forEach(it => addRow(it));
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
                if (e.target.classList.contains('qty') || e.target.classList.contains('unit_price')) recalc();
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
