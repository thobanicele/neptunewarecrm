@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Edit Product</h3>
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

        <form method="POST" action="{{ tenant_route('tenant.products.update', ['product' => $product->id]) }}">
            @csrf
            @method('PUT')

            <div class="card">
                <div class="card-header fw-semibold">Product Details</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-4">
                            <label class="form-label">SKU</label>
                            <input class="form-control @error('sku') is-invalid @enderror" name="sku"
                                value="{{ old('sku', $product->sku) }}" required>
                            @error('sku')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-8">
                            <label class="form-label">Name</label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name', $product->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <input class="form-control @error('description') is-invalid @enderror" name="description"
                                value="{{ old('description', $product->description) }}">
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Unit Rate</label>
                            <input class="form-control @error('unit_rate') is-invalid @enderror" type="number"
                                step="0.01" min="0" name="unit_rate"
                                value="{{ old('unit_rate', (float) $product->unit_rate) }}" required>
                            @error('unit_rate')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Unit (optional)</label>
                            <input class="form-control @error('unit') is-invalid @enderror" name="unit"
                                value="{{ old('unit', $product->unit) }}">
                            @error('unit')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-lg-4">
                            <label class="form-label">Currency (optional)</label>
                            <input class="form-control @error('currency') is-invalid @enderror" name="currency"
                                value="{{ old('currency', $product->currency) }}">
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
                                    @php
                                        $label = trim(($t->code ? $t->code . ' — ' : '') . ($t->name ?? ''));
                                    @endphp
                                    <option value="{{ $t->id }}" @selected((string) old('tax_type_id', $product->tax_type_id) === (string) $t->id)>
                                        {{ $label ?: 'Tax #' . $t->id }}
                                    </option>
                                @endforeach
                            </select>

                            @error('tax_type_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-text">
                                Optional. If set, this tax type can be auto-selected as the default on quotes.
                            </div>
                        </div>

                        <div class="col-12 col-lg-4 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                    @checked(old('is_active', $product->is_active))>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button class="btn btn-primary" type="submit">Update Product</button>
                        <a class="btn btn-light" href="{{ tenant_route('tenant.products.index') }}">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
