@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4" style="max-width:1100px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="mb-0">Roles & Permissions</h3>
                <div class="text-muted small">Tenant: {{ $tenantModel->name }} ({{ $tenantModel->subdomain }})</div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ tenant_route('tenant.settings.users.index') }}" class="btn btn-light">Users</a>
                <a href="{{ tenant_route('tenant.settings.roles.create') }}" class="btn btn-primary">+ New Role</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Permissions</th>
                                <th class="text-end" style="width:220px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $r)
                                <tr>
                                    <td class="fw-semibold">{{ $r->name }}</td>
                                    <td class="text-muted small">
                                        {{ $r->permissions()->count() }} permissions
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a class="btn btn-sm btn-outline-primary"
                                                href="{{ tenant_route('tenant.settings.roles.edit', $r) }}">
                                                Edit
                                            </a>

                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="visually-hidden">Toggle Dropdown</span>
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <form method="POST"
                                                        action="{{ tenant_route('tenant.settings.roles.destroy', $r) }}"
                                                        onsubmit="return confirm('Delete this role?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="dropdown-item text-danger"
                                                            type="submit">Delete</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted px-3 py-4">No roles found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
