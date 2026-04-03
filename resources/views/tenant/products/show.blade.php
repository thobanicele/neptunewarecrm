@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">{{ $product->name }}</h3>
                <div class="text-muted small">
                    SKU: <span class="fw-semibold">{{ $product->sku }}</span>
                    <span class="mx-2">•</span>
                    Tenant: {{ $tenant->name }} ({{ $tenant->subdomain }})
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.products.index') }}" class="btn btn-light">Back</a>
                <a href="{{ tenant_route('tenant.products.edit', ['product' => $product->id]) }}"
                    class="btn btn-outline-primary">Edit</a>

                <form method="POST" action="{{ tenant_route('tenant.products.destroy', ['product' => $product->id]) }}"
                    onsubmit="return confirm('Delete this product?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Delete</button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @php
            $taxType = $product->taxType ?? null;
            $brand = $product->brand ?? null;
            $category = $product->category ?? null;

            $taxLabel = '—';
            if ($taxType) {
                $taxLabel = trim(($taxType->code ? $taxType->code . ' — ' : '') . ($taxType->name ?? ''));
                if ($taxLabel === '') {
                    $taxLabel = 'Tax #' . $taxType->id;
                }
            }
        @endphp

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-header fw-semibold">Product Details</div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if ($product->image_url)
                                <div class="col-12">
                                    <div class="text-muted small mb-2">Product Image</div>
                                    <div class="border rounded p-3 bg-light">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="img-fluid rounded" style="max-height: 280px; object-fit: contain;">
                                    </div>
                                </div>
                            @endif

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">SKU</div>
                                <div class="fw-semibold">{{ $product->sku }}</div>
                            </div>

                            <div class="col-12 col-md-8">
                                <div class="text-muted small">Name</div>
                                <div class="fw-semibold">{{ $product->name }}</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="text-muted small">Slug</div>
                                <div class="fw-semibold">
                                    <code>{{ $product->slug ?: '—' }}</code>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="text-muted small">Storefront Image Path</div>
                                <div class="fw-semibold">{{ $product->image_path ?: '—' }}</div>
                            </div>

                            <div class="col-12">
                                <div class="text-muted small">Description</div>
                                <div>{{ $product->description ?: '—' }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Unit Rate</div>
                                <div class="fw-semibold">{{ number_format((float) $product->unit_rate, 2) }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Unit</div>
                                <div class="fw-semibold">{{ $product->unit ?: '—' }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Currency</div>
                                <div class="fw-semibold">{{ $product->currency ?: '—' }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Brand</div>
                                <div class="fw-semibold">{{ $brand?->name ?: '—' }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Category</div>
                                <div class="fw-semibold">{{ $category?->name ?: '—' }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Tax Type</div>
                                <div class="fw-semibold">{{ $taxLabel }}</div>
                                @if ($taxType?->rate !== null)
                                    <div class="text-muted small">Rate: {{ number_format((float) $taxType->rate, 2) }}%
                                    </div>
                                @endif
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Status</div>
                                @if ($product->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                @endif
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Storefront</div>
                                @if ($product->is_storefront_visible)
                                    <span class="badge bg-dark-subtle text-dark">Visible on Storefront</span>
                                @else
                                    <span class="badge bg-light text-muted border">Hidden from Storefront</span>
                                @endif
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Featured</div>
                                @if ($product->is_featured)
                                    <span class="badge bg-primary-subtle text-primary">Featured</span>
                                @else
                                    <span class="badge bg-light text-muted border">No</span>
                                @endif
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Created</div>
                                <div class="fw-semibold">{{ $product->created_at?->format('Y-m-d H:i') }}</div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="text-muted small">Last Updated</div>
                                <div class="fw-semibold">{{ $product->updated_at?->format('Y-m-d H:i') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-header fw-semibold">Quick Actions</div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ tenant_route('tenant.products.edit', ['product' => $product->id]) }}"
                            class="btn btn-outline-primary">
                            Edit Product
                        </a>

                        <form method="POST"
                            action="{{ tenant_route('tenant.products.destroy', ['product' => $product->id]) }}"
                            onsubmit="return confirm('Delete this product?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger" type="submit">Delete Product</button>
                        </form>

                        <a href="{{ tenant_route('tenant.quotes.create') }}" class="btn btn-outline-secondary">
                            Create Quote
                        </a>

                        @if ($product->is_storefront_visible)
                            <div class="alert alert-info mb-0 mt-2">
                                This product is currently published to the storefront.
                            </div>
                        @else
                            <div class="alert alert-secondary mb-0 mt-2">
                                This product is currently hidden from the storefront.
                            </div>
                        @endif

                        <div class="text-muted small mt-2">
                            Tip: Products are snapshotted into quote items. Updating a product later won’t change old
                            quotes.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
