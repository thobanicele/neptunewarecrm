<?php

namespace App\Http\Controllers;

use App\Mail\TenantInviteMail;
use App\Models\TenantInvite;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class TenantUserController extends Controller
{
    public function index(Request $request, string $tenant)
    {
        $tenant = app('tenant');

        // Ensure Spatie teams context is set (usually already via middleware, but safe here too)
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->with(['roles' => function ($q) use ($tenant) {
                // extra safety: only roles for this tenant
                $q->where('roles.tenant_id', $tenant->id);
            }])
            ->orderBy('name')
            ->get();

        $roles = Role::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        // ✅ Active invites (pending + not expired)
        $invites = TenantInvite::query()
            ->where('tenant_id', $tenant->id)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        $invitesCount = $invites->count();

        // Count owners once (tenant-scoped)
        $ownerCount = User::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('roles', function ($q) use ($tenant) {
                $q->where('roles.name', 'tenant_owner')
                    ->where('roles.tenant_id', $tenant->id);
            })
            ->count();

        $me = $request->user();
        $meIsSuper = method_exists($me, 'hasRole') && $me->hasRole('super_admin');
        $meIsOwner = method_exists($me, 'hasRole') && $me->hasRole('tenant_owner');
        $canAssignOwner = $meIsSuper || $meIsOwner;

        return view('tenant.settings.users.index', compact(
            'users',
            'roles',
            'ownerCount',
            'canAssignOwner',
            'invites',
            'invitesCount'
        ));
    }

    public function invite(Request $request, string $tenant)
    {
        $tenant = app('tenant');

        // Ensure teams context (role checks happen in tenant context)
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ✅ Tenant roles are source of truth, but NEVER allow inviting tenant_owner
        $tenantRoles = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', '!=', 'tenant_owner')
            ->pluck('name')
            ->all();

        $data = $request->validate([
            'email'       => ['required', 'email', 'max:255'],
            // ✅ blade sends invite_role
            'invite_role' => ['required', 'string', 'in:' . implode(',', $tenantRoles)],
        ]);

        // If user already exists + already belongs to this tenant
        $existingUser = User::where('email', $data['email'])->first();
        if ($existingUser && (int) $existingUser->tenant_id === (int) $tenant->id) {
            return back()->with('error', 'This user is already a member of your workspace.');
        }

        // If user already belongs to another tenant (your policy decision)
        if ($existingUser && $existingUser->tenant_id && (int) $existingUser->tenant_id !== (int) $tenant->id) {
            return back()->with('error', 'This user already belongs to another workspace.');
        }

        // create token (store hash)
        $rawToken = Str::random(48);
        $tokenHash = hash('sha256', $rawToken);

        $expiresDays = (int) config('plans.users.invites.expires_days', 7);

        // Create-or-update invite row (resend friendly)
        $invite = TenantInvite::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $data['email']],
            [
                'role'       => $data['invite_role'],
                'token_hash' => $tokenHash,
                'expires_at' => now()->addDays($expiresDays),
                'accepted_at'=> null,
                'invited_by' => auth()->id(),
            ]
        );

        // Accept URL (public)
        $acceptUrl = route('tenant.invites.accept', [
            'tenant' => $tenant->subdomain,
            'token'  => $rawToken,
        ]);

        // If your Mailable expects tenant relation
        $invite->loadMissing('tenant');

        Mail::to($invite->email)->send(new TenantInviteMail($invite, $acceptUrl));

        return back()->with('success', 'Invite sent to ' . $invite->email);
    }

    /**
     * Accept invite (guest or logged-in).
     * Route is public; it will force login if needed.
     */
    public function accept(Tenant $tenant, string $token)
    {
        $tokenHash = hash('sha256', $token);

        $invite = TenantInvite::query()
            ->where('tenant_id', $tenant->id) // ✅ lock to tenant from URL
            ->where('token_hash', $tokenHash)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invite->expires_at && now()->greaterThan($invite->expires_at)) {
            abort(410, 'Invite link expired.');
        }

        // ✅ Owner is onboarding-only
        abort_if($invite->role === 'tenant_owner', 403, 'Owner role cannot be assigned via invite.');

        $user = User::firstOrCreate(
            ['email' => $invite->email],
            [
                'name'     => $invite->name ?: $invite->email,
                'password' => bcrypt(Str::random(48)),
            ]
        );

        $user->tenant_id = $tenant->id;
        $user->is_active = true;
        $user->save();

        // Set Spatie team context BEFORE role assignment
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ✅ Do NOT auto-create roles. Role must exist for this tenant.
        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', $invite->role)
            ->first();

        abort_unless($role, 422, 'Invite role no longer exists for this workspace.');

        $user->syncRoles([$role->name]);

        $invite->accepted_at = now();
        $invite->accepted_by_user_id = $user->id; // ✅ you already added this column
        $invite->save();

        auth()->login($user, true);
        session()->put('invite_tenant_subdomain', $tenant->subdomain);
        session()->forget('url.intended');

        return redirect()->route('password.setup.show');
    }

    public function updateRole(Request $request, string $tenant, User $user)
    {
        $tenant = app('tenant');
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 404);

        $me = $request->user();

        // Scope spatie teams to this tenant
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ✅ Tenant roles (source of truth) — EXCLUDE tenant_owner (onboarding-only)
        $tenantRoles = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', '!=', 'tenant_owner')
            ->pluck('name')
            ->all();

        $data = $request->validate([
            'role' => ['required', 'string', 'in:' . implode(',', $tenantRoles)],
        ]);

        $newRole = $data['role'];

        // 1) Prevent changing your own tenant_owner role (safety)
        if ((string) $user->id === (string) $me->id && $user->hasRole('tenant_owner')) {
            return back()->with('error', 'You cannot change your own owner role.');
        }

        // 2) Prevent changing the LAST tenant_owner (owner is protected)
        if ($user->hasRole('tenant_owner')) {
            $ownerCount = User::query()
                ->where('tenant_id', $tenant->id)
                ->whereHas('roles', function ($q) use ($tenant) {
                    $q->where('roles.name', 'tenant_owner')
                        ->where('roles.tenant_id', $tenant->id);
                })
                ->count();

            if ($ownerCount <= 1) {
                return back()->with('error', 'You cannot change the last workspace owner.');
            }
        }

        // ✅ Assign role (do NOT create roles here)
        $user->syncRoles([$newRole]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role updated for ' . $user->email);
    }

    public function resendInvite(Request $request, string $tenant, TenantInvite $invite)
    {
        $tenant = app('tenant');

        // Ensure this invite belongs to the current tenant
        abort_unless((int) $invite->tenant_id === (int) $tenant->id, 404);

        // already accepted? don't resend
        if ($invite->accepted_at) {
            return back()->with('error', 'This invite has already been accepted.');
        }

        // expired invites can be resent (we refresh expiry)
        $rawToken  = Str::random(48);
        $tokenHash = hash('sha256', $rawToken);

        $expiresDays = (int) config('plans.users.invites.expires_days', 7);

        // ✅ Enforce: no tenant_owner via invites (even if an old row exists)
        if ($invite->role === 'tenant_owner') {
            return back()->with('error', 'Owner role cannot be assigned via invite.');
        }

        // ✅ Enforce: role must still exist for this tenant (no auto-create)
        $roleExists = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', $invite->role)
            ->where('name', '!=', 'tenant_owner')
            ->exists();

        if (!$roleExists) {
            return back()->with('error', 'This invite role no longer exists. Please create/select a new role and invite again.');
        }

        $invite->forceFill([
            'token_hash'  => $tokenHash,
            'expires_at'  => now()->addDays($expiresDays),
            'accepted_at' => null,
            'invited_by'  => auth()->id(),
        ])->save();

        $acceptUrl = route('tenant.invites.accept', [
            'tenant' => $tenant->subdomain,
            'token'  => $rawToken,
        ]);

        $invite->loadMissing('tenant');

        Mail::to($invite->email)->send(new TenantInviteMail($invite, $acceptUrl));

        return back()->with('success', 'Invite resent to ' . $invite->email);
    }


    public function deactivate(string $tenant, User $user)
    {
        $tenant = app('tenant');
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 404);

        if ((string) $user->id === (string) auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->forceFill(['is_active' => !(bool) $user->is_active])->save();

        return back()->with('success', ($user->is_active ? 'Activated ' : 'Deactivated ') . $user->email);
    }

    public function destroy(string $tenant, User $user)
    {
        $tenant = app('tenant');
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 404);

        if ((string) $user->id === (string) auth()->id()) {
            return back()->with('error', 'You cannot remove yourself.');
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user->syncRoles([]);
        $user->forceFill([
            'tenant_id' => null,
            'is_active' => true,
        ])->save();

        return back()->with('success', 'Removed ' . $user->email . ' from workspace.');
    }
}
