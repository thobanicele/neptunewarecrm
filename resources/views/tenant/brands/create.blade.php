@extends('layouts.app')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h4 class="mb-0">New Brand</h4>
                <div class="text-muted">Create a brand for product dropdowns.</div>
            </div>
            <a class="btn btn-outline-secondary" href="{{ tenant_route('tenant.brands.index') }}">Back</a>
        </div>

        <form class="card" method="POST" action="{{ tenant_route('tenant.brands.store') }}">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Name *</label>
                    <input class="form-control" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Slug (optional)</label>
                    <input class="form-control" name="slug" value="{{ old('slug') }}">
                    <div class="text-muted small">Leave empty to auto-generate.</div>
                    @error('slug')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>

            <div class="card-footer text-end">
                <button class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
@endsection
