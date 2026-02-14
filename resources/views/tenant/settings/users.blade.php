{{-- resources/views/tenant/settings/users/index.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container-fluid p-0">

        {{-- Flash --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert" id="flash-success">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @push('scripts')
                <script>
                    setTimeout(() => {
                        const el = document.getElementById('flash-success');
                        if (!el) return;
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    }, 3500);
                </script>
            @endpush
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="flash-error">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @push('scripts')
                <script>
                    setTimeout(() => {
                        const el = document.getElementById('flash-error');
                        if (!el) return;
                        bootstrap.Alert.getOrCreateInstance(el).close();
                    }, 4500);
                </script>
            @endpush
        @endif

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

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-0 d-flex align-items-center gap-2">
                    Tenant Users
                    <span class="badge bg-light text-dark">
                        Total:
                        {{ isset($users) ? (method_exists($users, 'total') ? $users->total() : $users->count()) : 0 }}
                    </span>
                </h1>
                <div class="text-muted small">
                    Tenant: {{ $tenant->name ?? '—' }} ({{ $tenant->subdomain ?? '—' }})
                </div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary"
                    href="{{ tenant_route('tenant.settings.edit', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                    Settings
                </a>

                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inviteModal">
                    Invite user
                </button>
            </div>
        </div>

        @php
            $me = auth()->user();
            $meId = $me?->id;

            // Expect these from controller:
            // $users: collection/paginator of users in tenant
            // $roles: array/collection of role names, e.g. ['tenant_owner','tenant_admin','tenant_staff']
            // $invites (optional): collection/paginator of pending invites
            $roleList = $roles ?? ['tenant_owner', 'tenant_admin', 'tenant_staff'];
            $hasInvites = isset($invites) && (method_exists($invites, 'count') ? $invites->count() : count($invites));
        @endphp

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMembers" type="button">
                    Members
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabInvites" type="button">
                    Pending invites
                    @if ($hasInvites)
                        <span
                            class="badge bg-light text-dark ms-1">{{ method_exists($invites, 'total') ? $invites->total() : $invites->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="tab-content">

            {{-- MEMBERS --}}
            <div class="tab-pane fade show active" id="tabMembers">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th class="text-end" style="width: 260px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($users ?? []) as $u)
                                        @php
                                            // Role display: if user has spatie roles, prefer that.
                                            $roleName = null;
                                            if (method_exists($u, 'getRoleNames')) {
                                                $roleName = $u->getRoleNames()->first();
                                            }
                                            $roleName = $roleName ?: $u->role ?? 'tenant_staff';

                                            // Active state: expect is_active boolean on users table.
                                            $isActive = data_get($u, 'is_active', true);
                                            $isMe = (string) $u->id === (string) $meId;
                                        @endphp

                                        <tr>
                                            <td class="fw-semibold">
                                                {{ $u->name ?? '—' }}
                                                @if ($isMe)
                                                    <span class="badge bg-light text-dark ms-1">You</span>
                                                @endif
                                            </td>
                                            <td class="text-muted">{{ $u->email ?? '—' }}</td>

                                            <td>
                                                <span class="badge bg-light text-dark text-capitalize">
                                                    {{ str_replace('_', ' ', $roleName) }}
                                                </span>
                                            </td>

                                            <td>
                                                @if ($isActive)
                                                    <span class="badge rounded-pill text-bg-success">ACTIVE</span>
                                                @else
                                                    <span class="badge rounded-pill text-bg-secondary">INACTIVE</span>
                                                @endif
                                            </td>

                                            <td class="text-end">
                                                <div class="btn-group" role="group" aria-label="User actions">

                                                    {{-- Change role (dropdown) --}}
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-primary dropdown-toggle"
                                                        data-bs-toggle="dropdown" aria-expanded="false"
                                                        @disabled($isMe && $roleName === 'tenant_owner')>
                                                        Change role
                                                    </button>

                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @foreach ($roleList as $r)
                                                            @php $selected = ((string)$r === (string)$roleName); @endphp
                                                            <li>
                                                                <form method="POST"
                                                                    action="{{ tenant_route('tenant.settings.users.role', ['tenant' => $tenant->subdomain ?? $tenant, 'user' => $u->id]) }}">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <input type="hidden" name="role"
                                                                        value="{{ $r }}">
                                                                    <button type="submit"
                                                                        class="dropdown-item {{ $selected ? 'active' : '' }}"
                                                                        @disabled($selected)
                                                                        onclick="return confirm('Assign role: {{ str_replace('_', ' ', $r) }} to {{ addslashes($u->email ?? '') }}?');">
                                                                        {{ str_replace('_', ' ', ucwords($r)) }}
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endforeach
                                                    </ul>

                                                    {{-- Deactivate/Activate --}}
                                                    <form method="POST"
                                                        action="{{ tenant_route('tenant.settings.users.deactivate', ['tenant' => $tenant->subdomain ?? $tenant, 'user' => $u->id]) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                            @disabled($isMe)
                                                            onclick="return confirm('{{ $isActive ? 'Deactivate' : 'Re-activate' }} this user?');">
                                                            {{ $isActive ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>

                                                    {{-- Remove --}}
                                                    <form method="POST"
                                                        action="{{ tenant_route('tenant.settings.users.destroy', ['tenant' => $tenant->subdomain ?? $tenant, 'user' => $u->id]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            @disabled($isMe)
                                                            onclick="return confirm('Remove this user from the workspace? This cannot be undone.');">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>

                                                @if ($isMe)
                                                    <div class="text-muted small mt-1">You can’t deactivate/remove yourself.
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                No users found for this tenant.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if (isset($users) && method_exists($users, 'links'))
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="text-muted small">
                                    Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of
                                    {{ $users->total() }}
                                    <span class="ms-2 badge bg-light text-dark">
                                        Page {{ $users->currentPage() }} / {{ $users->lastPage() }}
                                    </span>
                                </div>
                                <div>
                                    {{ $users->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- INVITES --}}
            <div class="tab-pane fade" id="tabInvites">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0 table-hover table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Invited</th>
                                        <th>Expires</th>
                                        <th class="text-end" style="width: 240px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($invites ?? []) as $inv)
                                        @php
                                            // Expect invite model fields (typical):
                                            // email, role, created_at, expires_at, accepted_at
                                            $invRole = data_get($inv, 'role', 'tenant_staff');
                                            $expiresAt = data_get($inv, 'expires_at');
                                            $isExpired = $expiresAt
                                                ? \Illuminate\Support\Carbon::parse($expiresAt)->isPast()
                                                : false;
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ data_get($inv, 'email', '—') }}</td>

                                            <td>
                                                <span class="badge bg-light text-dark text-capitalize">
                                                    {{ str_replace('_', ' ', $invRole) }}
                                                </span>
                                            </td>

                                            <td class="text-muted">
                                                {{ optional(data_get($inv, 'created_at'))->format('Y-m-d H:i') ?? '—' }}
                                            </td>

                                            <td>
                                                @if ($expiresAt)
                                                    <span
                                                        class="{{ $isExpired ? 'text-danger fw-semibold' : 'text-muted' }}">
                                                        {{ \Illuminate\Support\Carbon::parse($expiresAt)->format('Y-m-d H:i') }}
                                                    </span>
                                                @else
                                                    —
                                                @endif
                                            </td>

                                            <td class="text-end">
                                                <div class="btn-group" role="group">
                                                    {{-- Resend invite --}}
                                                    <form method="POST"
                                                        action="{{ tenant_route('tenant.settings.users.invite', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                                                        @csrf
                                                        <input type="hidden" name="email"
                                                            value="{{ data_get($inv, 'email') }}">
                                                        <input type="hidden" name="role"
                                                            value="{{ $invRole }}">
                                                        <input type="hidden" name="resend" value="1">
                                                        <button class="btn btn-sm btn-outline-primary" type="submit"
                                                            onclick="return confirm('Resend invite email to {{ addslashes((string) data_get($inv, 'email')) }}?');">
                                                            Resend
                                                        </button>
                                                    </form>

                                                    {{-- Cancel invite (optional route - only show if you implement it) --}}
                                                    @if (Route::has('tenant.settings.users.invites.destroy'))
                                                        <form method="POST"
                                                            action="{{ tenant_route('tenant.settings.users.invites.destroy', ['tenant' => $tenant->subdomain ?? $tenant, 'invite' => $inv->id]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit"
                                                                onclick="return confirm('Cancel this invite?');">
                                                                Cancel
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>

                                                @if ($isExpired)
                                                    <div class="text-muted small mt-1">Expired — resend to issue a new
                                                        link.</div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                No pending invites.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if (isset($invites) && method_exists($invites, 'links'))
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="text-muted small">
                                    Showing {{ $invites->firstItem() ?? 0 }}–{{ $invites->lastItem() ?? 0 }} of
                                    {{ $invites->total() }}
                                    <span class="ms-2 badge bg-light text-dark">
                                        Page {{ $invites->currentPage() }} / {{ $invites->lastPage() }}
                                    </span>
                                </div>
                                <div>
                                    {{ $invites->links() }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="text-muted small mt-2">
                    Invites send an email with an <b>accept link</b>. The user joins the tenant only after accepting.
                </div>
            </div>

        </div>

        {{-- Invite Modal --}}
        <div class="modal fade" id="inviteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Invite user</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form method="POST"
                        action="{{ tenant_route('tenant.settings.users.invite', ['tenant' => $tenant->subdomain ?? $tenant]) }}">
                        @csrf
                        <div class="modal-body">

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}"
                                    placeholder="user@company.com" required>
                                <div class="form-text">We’ll email them an invite link to accept.</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role" required>
                                    @foreach ($roleList as $r)
                                        <option value="{{ $r }}" @selected(old('role', 'tenant_staff') === $r)>
                                            {{ str_replace('_', ' ', ucwords($r)) }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">You can change their role later.</div>
                            </div>

                            <div class="alert alert-info small mb-0">
                                The invite will expire automatically (recommended: 7 days). If it expires, just resend.
                            </div>

                        </div>

                        <div class="modal-footer">
                            <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary" type="submit">Send invite</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

    </div>
@endsection
