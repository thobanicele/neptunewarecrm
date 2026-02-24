@extends('layouts.app')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-0">Edit Category</h4>
                <div class="text-muted">{{ $category->name }}</div>
            </div>
            <a class="btn btn-outline-secondary" href="{{ tenant_route('tenant.categories.index') }}">Back</a>
        </div>

        <form class="card" method="POST"
            action="{{ tenant_route('tenant.categories.update', ['category' => $category->id]) }}">
            @csrf @method('PUT')
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Name *</label>
                    <input class="form-control" name="name" value="{{ old('name', $category->name) }}" required>
                    @error('name')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug</label>
                    <input class="form-control" name="slug" value="{{ old('slug', $category->slug) }}">
                    @error('slug')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Parent</label>
                    <select class="form-select" name="parent_id">
                        <option value="">— none —</option>
                        @foreach ($parents as $p)
                            <option value="{{ $p->id }}" @selected((string) old('parent_id', $category->parent_id) === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-2">
                    <div class="col-12 col-lg-4">
                        <label class="form-label">Sort order</label>
                        <input type="number" class="form-control" name="sort_order"
                            value="{{ old('sort_order', $category->sort_order) }}" min="0">
                        @error('sort_order')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                        @checked(old('is_active', $category->is_active))>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="card-footer text-end">
                <button class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
@endsection
