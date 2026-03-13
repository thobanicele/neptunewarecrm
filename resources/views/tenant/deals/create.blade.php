@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0" style="max-width: 900px;">
        <h1 class="h3 mb-3">Create Deal</h1>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ tenant_route('tenant.deals.store') }}">
                    @csrf

                    @if ($lead)
                        <input type="hidden" name="lead_contact_id" value="{{ $lead->id }}">
                        <div class="alert alert-info">
                            Creating deal from lead: <strong>{{ $lead->name }}</strong>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input class="form-control" name="title" required
                            value="{{ old('title', $lead ? 'Deal for ' . $lead->name : '') }}">
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Amount</label>
                            <input class="form-control" name="amount" type="number" step="0.01"
                                value="{{ old('amount', 0) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expected close date</label>
                            <input class="form-control" name="expected_close_date" type="date"
                                value="{{ old('expected_close_date') }}">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pipeline</label>
                            <select class="form-select js-select2" name="pipeline_id" id="pipeline_id"
                                data-placeholder="Select pipeline" required>
                                @foreach ($pipelines as $p)
                                    <option value="{{ $p->id }}" @selected((string) old('pipeline_id', $pipelines->first()?->id) === (string) $p->id)>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stage</label>
                            <select class="form-select js-select2" name="stage_id" id="stage_id"
                                data-placeholder="Select stage" required>
                                @php
                                    $firstPipelineId = old('pipeline_id', $pipelines->first()?->id);

                                    $stages = $firstPipelineId
                                        ? \App\Models\PipelineStage::where('pipeline_id', $firstPipelineId)
                                            ->orderBy('position')
                                            ->get()
                                        : collect();
                                @endphp

                                @foreach ($stages as $s)
                                    <option value="{{ $s->id }}" @selected((string) old('stage_id') === (string) $s->id)>
                                        {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if (!$lead)
                        <div class="row g-2">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company</label>
                                <select class="form-select js-select2" name="company_id" id="company_id"
                                    data-placeholder="Select company"
                                    data-contacts-url-template="{{ tenant_route('tenant.companies.contacts.index', ['company' => '__ID__']) }}">
                                    <option value=""></option>
                                    @foreach ($companies as $c)
                                        <option value="{{ $c->id }}" @selected((string) old('company_id') === (string) $c->id)>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Required unless creating from a lead.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Primary Contact (optional)</label>
                                <select class="form-select js-select2" name="primary_contact_id" id="primary_contact_id"
                                    data-placeholder="Select contact">
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>
                    @endif

                    <button class="btn btn-primary">Create Deal</button>
                    <a href="{{ tenant_route('tenant.deals.index') }}" class="btn btn-light">Back</a>
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
            const oldContactId = @json((string) old('primary_contact_id', ''));

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
