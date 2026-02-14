@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Record Payment</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>
            <a href="{{ tenant_route('tenant.payments.index') }}" class="btn btn-light">Back</a>
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

        <form method="POST" action="{{ tenant_route('tenant.payments.store') }}">
            @csrf

            <div class="card">
                <div class="card-body">

                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label">Company <span class="text-danger">*</span></label>
                            <select class="form-select" name="company_id" id="companySelect"
                                data-open-invoices-url-template="{{ tenant_route('tenant.companies.openInvoices', ['company' => '__ID__']) }}"
                                data-prefill-invoice-id="{{ (string) old('apply_invoice_id', $prefillInvoiceId ?? '') }}"
                                required>
                                <option value="">— select company —</option>
                                @foreach ($companies ?? collect() as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('company_id', $prefillCompanyId ?? '') === (string) $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-6">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" name="paid_at"
                                value="{{ old('paid_at', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="amount"
                                value="{{ old('amount') }}" required>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Method</label>
                            <select class="form-select" name="method" required>
                                @foreach (['eft', 'cash', 'card', 'other'] as $m)
                                    <option value="{{ $m }}" @selected(old('method', 'eft') === $m)>{{ strtoupper($m) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Reference (optional)</label>
                            <input type="text" class="form-control" name="reference" value="{{ old('reference') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Apply to specific invoice (optional)</label>
                            <select class="form-select" name="apply_invoice_id" id="invoiceSelect">
                                <option value="">— auto allocate / not specified —</option>
                                @foreach ($openInvoices ?? collect() as $inv)
                                    <option value="{{ $inv->id }}" @selected((string) old('apply_invoice_id') === (string) $inv->id)>
                                        {{ $inv->invoice_number ?? 'INV-' . $inv->id }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Backend auto-allocates remaining amount to oldest unpaid invoices.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Note (optional)</label>
                            <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary" type="submit">Save Payment</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.payments.index') }}">Cancel</a>
                    </div>

                </div>
            </div>
        </form>

    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const companySelect = document.getElementById('companySelect');
            const invoiceSelect = document.getElementById('invoiceSelect');

            if (!companySelect || !invoiceSelect) return;

            const urlTemplate = companySelect.getAttribute('data-open-invoices-url-template') || '';
            const prefillInvoiceId = (companySelect.getAttribute('data-prefill-invoice-id') || '').toString();

            function money(n) {
                const x = Number(n || 0);
                return x.toLocaleString('en-ZA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function setInvoiceOptions(invoices, selectedId = '') {
                const current = (selectedId || '').toString();
                invoiceSelect.innerHTML = `<option value="">— auto allocate / not specified —</option>`;

                (invoices || []).forEach(inv => {
                    const id = String(inv.id ?? '');
                    const labelNo = inv.invoice_number || ('INV-' + id);
                    const total = money(inv.total);
                    const due = money(inv.outstanding);

                    const opt = document.createElement('option');
                    opt.value = id;
                    opt.textContent = `${labelNo}`;
                    if (current && id === current) opt.selected = true;
                    invoiceSelect.appendChild(opt);
                });

                if (!invoices || invoices.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = '— no outstanding invoices —';
                    opt.disabled = true;
                    invoiceSelect.appendChild(opt);
                }
            }

            async function loadOpenInvoices(companyId, selectedId = '') {
                const cid = String(companyId || '').trim();

                if (!cid) {
                    setInvoiceOptions([], '');
                    return;
                }

                const url = urlTemplate.replace('__ID__', encodeURIComponent(cid));
                if (!url || url.includes('__ID__')) {
                    setInvoiceOptions([], '');
                    return;
                }

                const keepSelected = String(selectedId || invoiceSelect.value || prefillInvoiceId || '');
                invoiceSelect.innerHTML = `<option value="">Loading…</option>`;

                try {
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);

                    const data = await res.json();
                    const invoices = Array.isArray(data.invoices) ? data.invoices : [];

                    // Keep selection if still valid, otherwise clear (don’t “carry” wrong company invoice)
                    let finalSelected = keepSelected;
                    if (finalSelected && !invoices.some(i => String(i.id) === String(finalSelected))) {
                        finalSelected = '';
                    }

                    setInvoiceOptions(invoices, finalSelected);
                } catch (e) {
                    setInvoiceOptions([], '');
                }
            }

            companySelect.addEventListener('change', () => loadOpenInvoices(companySelect.value, ''));

            // Initial load if company preselected (edit/old form)
            if (String(companySelect.value || '').trim()) {
                loadOpenInvoices(companySelect.value, prefillInvoiceId);
            } else {
                setInvoiceOptions([], '');
            }
        });
    </script>
@endpush
