@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4" style="max-width:1100px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">{{ $role ? 'Edit Role' : 'Create Role' }}</h3>
                <div class="text-muted small">Tenant: {{ $tenantModel->name }} ({{ $tenantModel->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.settings.roles.index') }}" class="btn btn-light">Back</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Please fix the errors below.</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
            action="{{ $role ? tenant_route('tenant.settings.roles.update', $role) : tenant_route('tenant.settings.roles.store') }}">
            @csrf
            @if ($role)
                @method('PUT')
            @endif

            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label">Role Name</label>
                    <input class="form-control" name="name" value="{{ old('name', $role->name ?? '') }}" required>
                    <div class="form-text">Example: <code>sales_manager</code></div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Permissions</h5>

                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="checkAll(true)">Select
                                all</button>
                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                onclick="checkAll(false)">Clear</button>
                        </div>
                    </div>

                    @php
                        $selectedSet = collect(old('permissions', $selected ?? []))->flip();
                    @endphp

                    <div class="row g-3">
                        @foreach ($matrix as $module => $actions)
                            <div class="col-12 col-lg-6">
                                <div class="border rounded p-3">
                                    <div class="fw-semibold mb-2">{{ ucfirst(str_replace('_', ' ', $module)) }}</div>

                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ((array) $actions as $a)
                                            @php $perm = $module . '.' . $a; @endphp
                                            <label class="form-check form-check-inline mb-0">
                                                <input class="form-check-input perm-box" type="checkbox"
                                                    name="permissions[]" value="{{ $perm }}"
                                                    @checked($selectedSet->has($perm))>
                                                <span class="form-check-label">{{ $a }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-primary" type="submit">
                            {{ $role ? 'Save Changes' : 'Create Role' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function checkAll(state) {
            document.querySelectorAll('.perm-box').forEach(cb => cb.checked = state);
        }
    </script>
@endpush
