@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <div class="container-fluid">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="h3 mb-1">Edit User</h1>
                <div class="text-muted">Manage role access for this tenant.</div>
            </div>

            <a href="{{ tenant_route('tenant.settings.users.index') }}" class="btn btn-outline-secondary">
                ← Back to Users
            </a>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-bold mb-1">Please fix the following:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            {{-- User details card --}}
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div>
                                <div class="fw-bold fs-5">{{ $user->name }}</div>
                                <div class="text-muted">{{ $user->email }}</div>
                            </div>

                            @if (!empty($isTargetOwner))
                                <span class="badge bg-primary">Owner</span>
                            @else
                                <span class="badge bg-secondary">{{ $userRoleName ?? 'No Role' }}</span>
                            @endif
                        </div>

                        <hr>

                        <div class="small text-muted mb-2">Current role in this tenant</div>
                        <div>
                            @if (!empty($userRoleName))
                                <span class="badge bg-info text-dark">{{ $userRoleName }}</span>
                            @else
                                <span class="badge bg-light text-dark">none</span>
                            @endif
                        </div>

                        @if (!empty($isLastOwner))
                            <div class="alert alert-warning mt-3 mb-0">
                                <div class="fw-bold">Protected user</div>
                                This user is the <strong>last tenant owner</strong>. You can’t remove or downgrade the owner
                                role.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Role assignment card --}}
            <div class="col-12 col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <div class="fw-bold">Role & Permissions</div>
                        <div class="text-muted small">
                            Roles are tenant-scoped. Only roles created for this tenant can be assigned.
                        </div>
                    </div>

                    <div class="card-body">

                        {{-- Hard rule notices --}}
                        @if (empty($canAssignOwner))
                            <div class="alert alert-info">
                                <div class="fw-bold">Owner role is restricted</div>
                                Only a tenant owner (or super admin) can assign the <code>tenant_owner</code> role.
                            </div>
                        @endif

                        @if (empty($canChangeRole))
                            <div class="alert alert-warning">
                                <div class="fw-bold">Role changes are blocked</div>
                                This action is restricted (e.g., last-owner protection or self-owner protection).
                            </div>
                        @endif

                        <form method="POST"
                            action="{{ tenant_route('tenant.settings.users.role', ['user' => $user->id]) }}" class="mt-2">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="role" class="form-label fw-semibold">Assign role</label>

                                <select id="role" name="role" class="form-select" @disabled(empty($canChangeRole))>
                                    <option value="" disabled @selected(!old('role', $userRoleName))>— select role —</option>

                                    @foreach ($roles as $role)
                                        @php
                                            $roleName = is_string($role) ? $role : $role->name;
                                            $isOwnerRole = $roleName === 'tenant_owner';

                                            // UI restrictions (backend still enforces)
                                            $disabled = false;

                                            // Only tenant_owner/super_admin can assign tenant_owner
                                            if ($isOwnerRole && empty($canAssignOwner)) {
                                                $disabled = true;
                                            }

                                            // Last owner cannot be downgraded (only allow tenant_owner option)
                                            if (!empty($isLastOwner) && !$isOwnerRole) {
                                                $disabled = true;
                                            }

                                            $selected = old('role', $userRoleName) === $roleName;
                                        @endphp

                                        <option value="{{ $roleName }}" @selected($selected)
                                            @disabled($disabled)>
                                            {{ $roleName }} @if ($isOwnerRole)
                                                (owner)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>

                                <div class="form-text">
                                    This will sync the user’s role <strong>within this tenant</strong> (Spatie teams /
                                    <code>tenant_id</code>).
                                </div>
                            </div>

                            {{-- Safety: last owner cannot be downgraded --}}
                            @if (!empty($isLastOwner))
                                <div class="alert alert-warning">
                                    <div class="fw-bold">Last-owner protection</div>
                                    You must first assign another user as <code>tenant_owner</code> before changing this
                                    user’s role.
                                </div>
                            @endif

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" @disabled(empty($canChangeRole))>
                                    Save Changes
                                </button>

                                <a href="{{ tenant_route('tenant.settings.users.index') }}"
                                    class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>

                        </form>

                        <hr class="my-4">

                        {{-- Quick info panel --}}
                        <div class="small text-muted">
                            <div class="fw-semibold mb-1">Rules enforced</div>
                            <ul class="mb-0">
                                <li>Only tenant roles are assignable (no auto-create).</li>
                                <li>Owner role assignment is restricted to tenant owners / super admins.</li>
                                <li>Last-owner can’t be downgraded or stripped.</li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
