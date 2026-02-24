@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">New Product</h3>
                <div class="text-muted small">Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})</div>
            </div>
            <a href="{{ tenant_route('tenant.products.index') }}" class="btn btn-light">Back</a>
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

        <form method="POST" action="{{ tenant_route('tenant.products.store') }}">
            @csrf

            <div class="card">
                <div class="card-header fw-semibold">Product Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">SKU</label>
                            <input class="form-control @error('sku') is-invalid @enderror" name="sku"
                                value="{{ old('sku') }}" required>
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-8">
                            <label class="form-label">Name</label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ✅ Brand dropdown + quick add --}}
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Brand (optional)</label>
                            <div class="input-group">
                                <select id="brandSelect" class="form-select @error('brand_id') is-invalid @enderror"
                                    name="brand_id">
                                    <option value="">— none —</option>
                                    @foreach ($brands ?? collect() as $b)
                                        <option value="{{ $b->id }}" @selected((string) old('brand_id') === (string) $b->id)>
                                            {{ $b->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#quickAddBrandModal">
                                    + Add
                                </button>

                                @error('brand_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">Used for ecommerce-ready product grouping.</div>
                        </div>

                        {{-- ✅ Category dropdown + quick add --}}
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Category (optional)</label>
                            <div class="input-group">
                                <select id="categorySelect" class="form-select @error('category_id') is-invalid @enderror"
                                    name="category_id">
                                    <option value="">— none —</option>
                                    @foreach ($categories ?? collect() as $c)
                                        <option value="{{ $c->id }}" @selected((string) old('category_id') === (string) $c->id)>
                                            {{ $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#quickAddCategoryModal">
                                    + Add
                                </button>

                                @error('category_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">Used for ecommerce-ready product grouping.</div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Unit Rate</label>
                            <input class="form-control @error('unit_rate') is-invalid @enderror" type="number"
                                step="0.01" min="0" name="unit_rate" value="{{ old('unit_rate', 0) }}" required>
                            @error('unit_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input class="form-control @error('description') is-invalid @enderror" name="description"
                                value="{{ old('description') }}">
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Unit (optional)</label>
                            <input class="form-control @error('unit') is-invalid @enderror" name="unit"
                                value="{{ old('unit') }}" placeholder="each / box / m">
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Currency (optional)</label>
                            <input class="form-control @error('currency') is-invalid @enderror" name="currency"
                                value="{{ old('currency', 'ZAR') }}">
                            @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ✅ Tax Type dropdown (optional) --}}
                        <div class="col-12 col-lg-4">
                            <label class="form-label">Tax Type (optional)</label>
                            <select class="form-select @error('tax_type_id') is-invalid @enderror" name="tax_type_id">
                                <option value="">— none —</option>
                                @foreach ($taxTypes ?? collect() as $t)
                                    <option value="{{ $t->id }}" @selected((string) old('tax_type_id') === (string) $t->id)>
                                        {{ $t->name }} ({{ number_format((float) $t->rate, 2) }}%)
                                        @if ($t->is_default)
                                            • Default
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('tax_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">If set, this will be the default VAT for this product on quotes.</div>
                        </div>

                        <div class="col-12 col-lg-4 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                    @checked(old('is_active', true))>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary" type="submit">Save Product</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.products.index') }}">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- =======================
         Quick Add Brand Modal
         ======================= --}}
    <div class="modal fade" id="quickAddBrandModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Brand</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="brandQuickAddError" class="alert alert-danger d-none"></div>

                    <label class="form-label">Brand name</label>
                    <input type="text" id="brandQuickAddName" class="form-control" placeholder="e.g. Philips">
                    <div class="form-text">This will create an active brand for this tenant.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="brandQuickAddSave" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ==========================
         Quick Add Category Modal
         ========================== --}}
    <div class="modal fade" id="quickAddCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="categoryQuickAddError" class="alert alert-danger d-none"></div>

                    <label class="form-label">Category name</label>
                    <input type="text" id="categoryQuickAddName" class="form-control" placeholder="e.g. LED Bulbs">
                    <div class="form-text">This will create an active top-level category for this tenant.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="categoryQuickAddSave" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                '{{ csrf_token() }}';

            const brandUrl = @json(tenant_route('tenant.quick-add.brands.store'));
            const categoryUrl = @json(tenant_route('tenant.quick-add.categories.store'));

            function showErr(el, msg) {
                el.classList.remove('d-none');
                el.textContent = msg;
            }

            function hideErr(el) {
                el.classList.add('d-none');
                el.textContent = '';
            }

            async function postJson(url, payload) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    // Laravel validation: { errors: { field: ["msg"] } }
                    const first = data?.message ||
                        (data?.errors ? Object.values(data.errors).flat()[0] : null) ||
                        'Request failed.';
                    throw new Error(first);
                }

                return data;
            }

            // ---- Brand quick add ----
            const brandSelect = document.getElementById('brandSelect');
            const brandName = document.getElementById('brandQuickAddName');
            const brandBtn = document.getElementById('brandQuickAddSave');
            const brandErr = document.getElementById('brandQuickAddError');
            const brandModalEl = document.getElementById('quickAddBrandModal');
            const brandModal = brandModalEl ? bootstrap.Modal.getOrCreateInstance(brandModalEl) : null;

            brandBtn?.addEventListener('click', async () => {
                hideErr(brandErr);
                const name = (brandName?.value || '').trim();
                if (!name) return showErr(brandErr, 'Please enter a brand name.');

                brandBtn.disabled = true;
                try {
                    const data = await postJson(brandUrl, {
                        name
                    });
                    const b = data.brand;

                    // insert & select
                    const opt = document.createElement('option');
                    opt.value = b.id;
                    opt.textContent = b.name;
                    brandSelect.appendChild(opt);
                    brandSelect.value = String(b.id);

                    // close + reset
                    brandName.value = '';
                    brandModal?.hide();
                } catch (e) {
                    showErr(brandErr, e.message || 'Failed to create brand.');
                } finally {
                    brandBtn.disabled = false;
                }
            });

            brandModalEl?.addEventListener('shown.bs.modal', () => {
                hideErr(brandErr);
                setTimeout(() => brandName?.focus(), 50);
            });

            // ---- Category quick add ----
            const categorySelect = document.getElementById('categorySelect');
            const categoryName = document.getElementById('categoryQuickAddName');
            const categoryBtn = document.getElementById('categoryQuickAddSave');
            const categoryErr = document.getElementById('categoryQuickAddError');
            const categoryModalEl = document.getElementById('quickAddCategoryModal');
            const categoryModal = categoryModalEl ? bootstrap.Modal.getOrCreateInstance(categoryModalEl) : null;

            categoryBtn?.addEventListener('click', async () => {
                hideErr(categoryErr);
                const name = (categoryName?.value || '').trim();
                if (!name) return showErr(categoryErr, 'Please enter a category name.');

                categoryBtn.disabled = true;
                try {
                    const data = await postJson(categoryUrl, {
                        name
                    });
                    const c = data.category;

                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    categorySelect.appendChild(opt);
                    categorySelect.value = String(c.id);

                    categoryName.value = '';
                    categoryModal?.hide();
                } catch (e) {
                    showErr(categoryErr, e.message || 'Failed to create category.');
                } finally {
                    categoryBtn.disabled = false;
                }
            });

            categoryModalEl?.addEventListener('shown.bs.modal', () => {
                hideErr(categoryErr);
                setTimeout(() => categoryName?.focus(), 50);
            });
        })();
    </script>
@endpush
