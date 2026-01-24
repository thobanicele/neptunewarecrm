{{-- ✅ Proper: modal rendered via stack so it sits under <body>, not inside content --}}
    <div class="modal fade" id="qualifyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" id="qualifyForm" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Qualify Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2 text-muted small">Lead: <span class="fw-semibold" id="qualifyLeadName">—</span></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Company mode</label>
                            <select class="form-select" name="company_mode" id="companyMode">
                                <option value="create">Create new company</option>
                                <option value="attach">Attach to existing</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="companyCreateWrap">
                            <label class="form-label">New company name</label>
                            <input type="text" class="form-control" name="company_name" placeholder="e.g. ABSA">
                        </div>

                        <div class="col-md-6 d-none" id="companyAttachWrap">
                            <label class="form-label">Select company</label>
                            <select class="form-select" name="company_id">
                                <option value="">-- choose --</option>
                                @foreach ($companies as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="createDeal"
                                    name="create_deal">
                                <label class="form-check-label" for="createDeal">
                                    Also create a Deal
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6 d-none" id="dealTitleWrap">
                            <label class="form-label">Deal title</label>
                            <input type="text" class="form-control" name="deal_title"
                                placeholder="e.g. Lighting upgrade">
                        </div>

                        <div class="col-md-6 d-none" id="dealValueWrap">
                            <label class="form-label">Deal value</label>
                            <input type="number" step="0.01" class="form-control" name="deal_value"
                                placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Qualify</button>
                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>