@extends('layouts.app')

@section('content')
    <div class="container py-4" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Edit Deal</h3>
            <a href="{{ tenant_route('tenant.deals.show', ['tenant' => $tenant, 'deal' => $deal]) }}"
                class="btn btn-outline-secondary">Back</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form method="POST"
                    action="{{ tenant_route('tenant.deals.update', ['tenant' => $tenant, 'deal' => $deal]) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input class="form-control" name="title" value="{{ old('title', $deal->title) }}" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Amount</label>
                            <input class="form-control" name="amount" type="number" step="0.01"
                                value="{{ old('amount', $deal->amount) }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Stage</label>
                            <select class="form-select js-select2" name="stage_id" data-placeholder="Select stage" required>
                                @foreach ($stages as $s)
                                    <option value="{{ $s->id }}" @selected((string) old('stage_id', $deal->stage_id) === (string) $s->id)>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Expected close date</label>
                            <input class="form-control" name="expected_close_date" type="date"
                                value="{{ old('expected_close_date', optional($deal->expected_close_date)->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <select class="form-select js-select2" name="company_id" id="company_id"
                                data-placeholder="Select company"
                                data-contacts-url-template="{{ tenant_route('tenant.companies.contacts.index', ['company' => '__ID__']) }}">
                                <option value=""></option>
                                @foreach ($companies ?? collect() as $c)
                                    <option value="{{ $c->id }}" @selected((string) old('company_id', $deal->company_id) === (string) $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Primary Contact (optional)</label>
                            <select class="form-select js-select2" name="primary_contact_id" id="primary_contact_id"
                                data-placeholder="Select contact">
                                <option value=""></option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="4">{{ old('notes', $deal->notes) }}</textarea>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary">Save Changes</button>
                        <a href="{{ tenant_route('tenant.deals.show', ['tenant' => $tenant, 'deal' => $deal]) }}"
                            class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initSelect2) {
                window.initSelect2(document);
            } else if (window.jQuery && window.jQuery.fn.select2) {
                window.jQuery('.js-select2').select2({
                    width: '100%'
                });
            }

            const companySelect = document.getElementById('company_id');
            const contactSelect = document.getElementById('primary_contact_id');

            if (!companySelect || !contactSelect) return;

            const contactsUrlTemplate = companySelect.getAttribute('data-contacts-url-template') || '';
            const oldContactId = @json((string) old('primary_contact_id', $deal->primary_contact_id));

            function refreshContactSelect() {
                if (window.jQuery && window.jQuery.fn.select2) {
                    const $contact = window.jQuery(contactSelect);

                    if ($contact.data('select2')) {
                        $contact.trigger('change.select2');
                    } else {
                        $contact.select2({
                            width: '100%',
                            placeholder: contactSelect.getAttribute('data-placeholder') || 'Select contact',
                            allowClear: true
                        });
                    }
                }
            }

            function setContactOptions(contacts, selectedId = '') {
                contactSelect.innerHTML = '<option value=""></option>';

                (contacts || []).forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = String(c.id);
                    opt.textContent = c.name || ('Contact #' + c.id);

                    if (String(selectedId) === String(c.id)) {
                        opt.selected = true;
                    }

                    contactSelect.appendChild(opt);
                });

                refreshContactSelect();
            }

            async function loadContacts(companyId, selectedId = '') {
                const id = String(companyId || '').trim();

                if (!id || !contactsUrlTemplate) {
                    setContactOptions([], '');
                    return;
                }

                const url = contactsUrlTemplate.replace('__ID__', encodeURIComponent(id));

                try {
                    const res = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!res.ok) {
                        setContactOptions([], '');
                        return;
                    }

                    const data = await res.json();
                    const contacts = Array.isArray(data.contacts) ? data.contacts : [];

                    let finalSelected = selectedId || '';
                    if (finalSelected && !contacts.some(c => String(c.id) === String(finalSelected))) {
                        finalSelected = '';
                    }

                    setContactOptions(contacts, finalSelected);
                } catch (e) {
                    setContactOptions([], '');
                }
            }

            companySelect.addEventListener('change', function() {
                loadContacts(this.value, '');
            });

            if (companySelect.value) {
                loadContacts(companySelect.value, oldContactId);
            } else {
                setContactOptions([], '');
            }
        });
    </script>
@endpush
