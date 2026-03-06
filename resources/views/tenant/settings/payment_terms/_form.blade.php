@php
    $term = $term ?? null;
@endphp

{{-- Validation errors --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="fw-semibold mb-2">Please fix the following:</div>
        <ul class="mb-0">
            @foreach ($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-3">

    {{-- Term name --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Term name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
            value="{{ old('name', $term?->name) }}" placeholder="e.g. Due on Receipt / Net 30" required>

        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="form-text">
            Must be unique in your workspace.
        </div>
    </div>

    {{-- Days --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Number of days <span class="text-danger">*</span></label>
        <input type="number" min="0" max="3650" class="form-control @error('days') is-invalid @enderror"
            name="days" value="{{ old('days', $term?->days) }}" placeholder="e.g. 0 / 7 / 14 / 30" required>

        @error('days')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="form-text">
            Use <span class="fw-semibold">0</span> for “Due on Receipt”. Days must be unique.
        </div>
    </div>

    {{-- Sort order --}}
    <div class="col-12 col-md-6">
        <label class="form-label">Sort order</label>
        <input type="number" min="0" max="9999"
            class="form-control @error('sort_order') is-invalid @enderror" name="sort_order"
            value="{{ old('sort_order', $term?->sort_order ?? 0) }}" placeholder="0">

        @error('sort_order')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        <div class="form-text">
            Lower numbers appear first in dropdowns.
        </div>
    </div>

    {{-- Active --}}
    <div class="col-12 col-md-6 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                @checked(old('is_active', $term?->is_active ?? true))>
            <label class="form-check-label" for="is_active">
                Active
            </label>
            <div class="form-text">
                Inactive terms won’t show in company/quote/invoice dropdowns.
            </div>
        </div>
    </div>

</div>

{{-- Small info box (matches "settings" vibe) --}}
<div class="border rounded p-3 mt-3 bg-light">
    <div class="fw-semibold mb-1">Rules</div>
    <div class="text-muted small">
        • Term names must be unique<br>
        • Number of days must be unique<br>
        • Use “Due on Receipt” as your only 0-day term
    </div>
</div>
