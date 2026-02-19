@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="container-fluid">

        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h1 class="h3 mb-1">Users</h1>
                <div class="text-muted">Manage access for this workspace.</div>
            </div>

            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inviteUserModal">
                + Invite User
            </button>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

        @php
            $me = auth()->user();
            $tab = request('tab', 'users'); // users | invites

            $invites = $invites ?? collect();
            $invitesCount = $invitesCount ?? ($invites->count() ?? 0);

            // ✅ tenant_owner must never be selectable in UI
            $selectableRoles = collect($roles ?? collect())
                ->filter(fn($r) => (is_string($r) ? $r : $r->name) !== 'tenant_owner')
                ->values();
        @endphp

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link @if ($tab === 'users') active @endif"
                    href="{{ tenant_route('tenant.settings.users.index', ['tab' => 'users']) }}">
                    Users
                    <span class="badge bg-light text-dark ms-1">{{ $users->count() }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link @if ($tab === 'invites') active @endif"
                    href="{{ tenant_route('tenant.settings.users.index', ['tab' => 'invites']) }}">
                    Invites
                    <span class="badge bg-warning text-dark ms-1">{{ $invitesCount }}</span>
                </a>
            </li>
        </ul>

        @if ($tab === 'invites')
            {{-- INVITES TAB --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div>
                            <div class="fw-bold">Active Invites</div>
                            <div class="text-muted small">Pending invitations for this workspace.</div>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#inviteUserModal">
                            + New Invite
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th style="width: 220px;">Role</th>
                                    <th style="width: 220px;">Expires</th>
                                    <th style="width: 220px;">Invited</th>
                                    <th class="text-end" style="width: 160px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invites as $invite)
                                    <tr>
                                        <td class="fw-semibold">{{ $invite->email }}</td>
                                        <td><span class="badge bg-info text-dark">{{ $invite->role }}</span></td>
                                        <td class="text-muted">{{ optional($invite->expires_at)->format('Y-m-d H:i') }}</td>
                                        <td class="text-muted">{{ optional($invite->created_at)->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-warning text-dark">Pending</span>
                                            <form method="POST"
                                                action="{{ tenant_route('tenant.settings.users.invites.resend', ['invite' => $invite->id, 'tab' => 'invites']) }}"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    Resend
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No active invites found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="small text-muted mt-3">
                        <div class="fw-semibold mb-1">Notes</div>
                        <ul class="mb-0">
                            <li>Invites expire automatically.</li>
                            <li><code>tenant_owner</code> is assigned only during onboarding.</li>
                        </ul>
                    </div>
                </div>
            </div>
        @else
            {{-- USERS TAB --}}
            <div class="card">
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th style="width: 240px;">Current Role</th>
                                    <th class="text-end" style="width: 220px;">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($users as $u)
                                    @php
                                        $currentRole = optional($u->roles->first())->name;
                                        $isOwner = method_exists($u, 'hasRole') && $u->hasRole('tenant_owner');
                                        $isLastOwner = $isOwner && (int) ($ownerCount ?? 0) <= 1;
                                        $isMe = (string) $me->id === (string) $u->id;
                                        $canOpen = !($isMe && $isOwner);
                                    @endphp

                                    <tr>
                                        <td class="fw-semibold">
                                            {{ $u->name }}
                                            @if ($isMe)
                                                <span class="badge bg-light text-dark ms-1">You</span>
                                            @endif
                                        </td>

                                        <td class="text-muted">{{ $u->email }}</td>

                                        <td>
                                            @if ($currentRole)
                                                <span class="badge bg-info text-dark">{{ $currentRole }}</span>
                                            @else
                                                <span class="badge bg-light text-dark">none</span>
                                            @endif

                                            @if ($isLastOwner)
                                                <span class="badge bg-warning text-dark ms-2">Last owner</span>
                                            @endif
                                        </td>

                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                data-bs-target="#roleModal{{ $u->id }}" @disabled(!$canOpen)
                                                @if (!$canOpen) title="You can’t change your own tenant_owner role." @endif>
                                                Edit role
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Role Modal --}}
                                    <div class="modal fade" id="roleModal{{ $u->id }}" tabindex="-1"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">

                                                <div class="modal-header">
                                                    <div>
                                                        <h5 class="modal-title mb-0">Change role</h5>
                                                        <div class="text-muted small">{{ $u->email }}</div>
                                                    </div>
                                                    <button type="button" class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                                </div>

                                                <form method="POST"
                                                    action="{{ tenant_route('tenant.settings.users.role', ['user' => $u->id]) }}">
                                                    @csrf
                                                    @method('PATCH')

                                                    <input type="hidden" name="modal_user_id" value="{{ $u->id }}">

                                                    <div class="modal-body">

                                                        @if ($isMe && $isOwner)
                                                            <div class="alert alert-warning mb-3">
                                                                You can’t change your own <code>tenant_owner</code> role.
                                                            </div>
                                                        @endif

                                                        @if ($isLastOwner)
                                                            <div class="alert alert-warning mb-3">
                                                                This user is the <strong>last workspace owner</strong>.
                                                                You must assign another owner before downgrading.
                                                            </div>
                                                        @endif

                                                        <label class="form-label fw-semibold">Role</label>

                                                        {{-- If target is tenant_owner, disable UI (owner managed elsewhere) --}}
                                                        <select name="role" class="form-select"
                                                            @disabled(($isMe && $isOwner) || $isOwner)>
                                                            @if ($isOwner)
                                                                <option value="tenant_owner" selected>
                                                                    tenant_owner (managed on onboarding)
                                                                </option>
                                                            @else
                                                                <option value="" disabled
                                                                    @selected(!$currentRole)>
                                                                    — select role —
                                                                </option>

                                                                @foreach ($selectableRoles as $r)
                                                                    @php
                                                                        $roleName = is_string($r) ? $r : $r->name;
                                                                        $selected =
                                                                            old('role', $currentRole) === $roleName;
                                                                    @endphp
                                                                    <option value="{{ $roleName }}"
                                                                        @selected($selected)>
                                                                        {{ $roleName }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>

                                                        <div class="form-text">
                                                            Roles are tenant-scoped. <code>tenant_owner</code> is assigned
                                                            only during onboarding.
                                                        </div>

                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary"
                                                            data-bs-dismiss="modal">
                                                            Cancel
                                                        </button>

                                                        <button type="submit" class="btn btn-primary"
                                                            @disabled(($isMe && $isOwner) || $isOwner)>
                                                            Save
                                                        </button>
                                                    </div>

                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            No users found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>
        @endif

        {{-- Invite Modal --}}
        <div class="modal fade" id="inviteUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Invite user</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <form method="POST" action="{{ tenant_route('tenant.settings.users.invite') }}">
                        @csrf
                        <div class="modal-body">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                    placeholder="name@company.com" required>
                                <div class="form-text">An invite email will be sent.</div>
                            </div>

                            {{-- ✅ Role selection for invite (no tenant_owner) --}}
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Role</label>

                                <select name="invite_role" class="form-select" required>
                                    <option value="" disabled @selected(!old('invite_role'))>— select role —</option>

                                    @foreach ($selectableRoles as $r)
                                        @php
                                            $roleName = is_string($r) ? $r : $r->name;
                                            $selected = old('invite_role') === $roleName;
                                        @endphp
                                        <option value="{{ $roleName }}" @selected($selected)>
                                            {{ $roleName }}
                                        </option>
                                    @endforeach
                                </select>

                                <div class="form-text">
                                    <code>tenant_owner</code> is assigned only during onboarding.
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send invite</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    </div>
@endsection

{{-- ✅ Auto-open the correct role modal on validation error --}}
@php $openUserId = old('modal_user_id'); @endphp
@if ($openUserId)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const el = document.getElementById('roleModal{{ $openUserId }}');
                if (el && window.bootstrap) {
                    new bootstrap.Modal(el).show();
                }
            });
        </script>
    @endpush
@endif
