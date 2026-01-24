{{-- ✅ IMPORTANT: Modal must be inside a stack/section (prevents layout/footer gap + weird rendering) --}}
<div class="modal fade" id="qualifyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="qualifyForm" method="POST" action="{{ tenant_route('tenant.leads.qualify', ['contact' => 0]) }}"
            class="modal-content">
            @csrf
            <input type="hidden" name="contact_id" id="qualifyContactId" value="">
            <div class="modal-header">
                <h5 class="modal-title">Qualify Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" name="contact_id" id="qualifyContactId" value="">
                @if ($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="text-muted small mb-3" id="qualifyLeadName"></div>

                <div class="mb-2">
                    <label class="form-label">Company</label>
                    <select class="form-select" name="company_mode" id="companyMode">
                        <option value="attach">Attach existing company</option>
                        <option value="create">Create new company</option>
                    </select>
                </div>

                <div class="mb-2" id="attachCompanyWrap">
                    <label class="form-label">Select company</label>
                    <select class="form-select" name="company_id">
                        <option value="">— Select —</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-2 d-none" id="createCompanyWrap">
                    <label class="form-label">Company name</label>
                    <input class="form-control" name="company_name" placeholder="e.g. Example Pty LTD">
                </div>

                <hr>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" value="1" id="createDeal" name="create_deal">
                    <label class="form-check-label" for="createDeal">Create a Deal automatically</label>
                </div>

                <div id="dealFields" class="d-none">
                    <div class="mb-2">
                        <label class="form-label">Deal title</label>
                        <input class="form-control" name="deal_title" placeholder="Deal title">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Deal value</label>
                        <input class="form-control" name="deal_value" type="number" step="0.01" min="0"
                            placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit">Qualify</button>
            </div>
        </form>
    </div>
</div>
@if ($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('qualifyModal');
            if (el && window.bootstrap?.Modal) bootstrap.Modal.getOrCreateInstance(el).show();
        });
    </script>
@endif
