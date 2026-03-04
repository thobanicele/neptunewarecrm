<div class="modal fade" id="nwQuickProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Create Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger d-none" id="nwQpErrors"></div>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nwQpName"
                            placeholder="e.g. LED Floodlight 200W">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">SKU <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nwQpSku" placeholder="e.g. FL200W">
                        <div class="form-text">Saved in uppercase.</div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Unit rate <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="nwQpUnitRate"
                            value="0">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Unit</label>
                        <input type="text" class="form-control" id="nwQpUnit" placeholder="pcs, m, box">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">VAT</label>
                        <select class="form-select" id="nwQpTaxTypeId">
                            <option value="">— none —</option>
                            @foreach ($taxTypes ?? collect() as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->name }} ({{ number_format((float) $t->rate, 2) }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ✅ Brand + Category --}}
                    <div class="col-md-6">
                        <label class="form-label">Brand</label>
                        <select class="form-select" id="nwQpBrandId">
                            <option value="">— none —</option>
                            @foreach ($brands ?? collect() as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="nwQpCategoryId">
                            <option value="">— none —</option>
                            @foreach ($categories ?? collect() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="nwQpDescription" rows="3" placeholder="Optional…"></textarea>
                    </div>

                    <input type="hidden" id="nwQpCurrency" value="ZAR">
                    <input type="hidden" id="nwQpIsActive" value="1">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="nwQpSaveBtn">Save product</button>
            </div>

        </div>
    </div>
</div>
