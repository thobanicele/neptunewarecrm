<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleController extends Controller
{
    public function index(string $tenant)
    {
        $tenantModel = app('tenant');

        $roles = Role::query()
            ->where('tenant_id', $tenantModel->id)
            ->orderByRaw("FIELD(name,'tenant_owner','tenant_admin','sales','finance','viewer') DESC")
            ->orderBy('name')
            ->get();

        return view('tenant.settings.roles.index', compact('tenantModel', 'roles'));
    }

    public function create(string $tenant)
    {
        $tenantModel = app('tenant');

        $matrix = $this->permissionMatrix();
        $role = null;
        $selected = collect();

        return view('tenant.settings.roles.edit', compact('tenantModel', 'role', 'matrix', 'selected'));
    }

    public function store(Request $request, string $tenant)
    {
        $tenantModel = app('tenant');

        $data = $request->validate([
            'name' => ['required','string','max:80'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        // protect reserved role names if you want
        $reserved = ['super_admin'];
        abort_if(in_array($data['name'], $reserved, true), 403);

        // team scope
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantModel->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'tenant_id'  => $tenantModel->id,
            'name'       => $data['name'],
            'guard_name' => 'web',
        ]);

        $allowedPerms = $this->allPermissionNames();
        $perms = collect($data['permissions'] ?? [])
            ->filter(fn ($p) => $allowedPerms->contains($p))
            ->values()
            ->all();

        $role->syncPermissions($perms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('tenant.settings.roles.index', ['tenant' => $tenantModel->subdomain])
            ->with('success', 'Role created.');
    }

    public function edit(string $tenant, Role $role)
    {
        $tenantModel = app('tenant');
        abort_unless((int)$role->tenant_id === (int)$tenantModel->id, 404);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantModel->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $matrix = $this->permissionMatrix();
        $selected = $role->permissions()->pluck('name');

        return view('tenant.settings.roles.edit', compact('tenantModel', 'role', 'matrix', 'selected'));
    }

    public function update(Request $request, string $tenant, Role $role)
    {
        $tenantModel = app('tenant');
        abort_unless((int)$role->tenant_id === (int)$tenantModel->id, 404);

        // prevent editing tenant_owner permissions (optional safety)
        if ($role->name === 'tenant_owner') {
            return back()->with('error', 'tenant_owner cannot be edited.');
        }

        $data = $request->validate([
            'name' => ['required','string','max:80'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantModel->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // keep name unique per tenant (you already have unique index)
        $role->forceFill(['name' => $data['name']])->save();

        $allowedPerms = $this->allPermissionNames();
        $perms = collect($data['permissions'] ?? [])
            ->filter(fn ($p) => $allowedPerms->contains($p))
            ->values()
            ->all();

        $role->syncPermissions($perms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('tenant.settings.roles.index', ['tenant' => $tenantModel->subdomain])
            ->with('success', 'Role updated.');
    }

    public function destroy(string $tenant, Role $role)
    {
        $tenantModel = app('tenant');
        abort_unless((int)$role->tenant_id === (int)$tenantModel->id, 404);

        // protect system roles
        if (in_array($role->name, ['tenant_owner','tenant_admin','sales','finance','viewer'], true)) {
            return back()->with('error', 'System roles cannot be deleted.');
        }

        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', 'Role deleted.');
    }

    private function permissionMatrix(): array
    {
        // expected: config/tenant_permissions.php => ['modules' => ['deals' => ['view','create'...]]]
        return (array) config('tenant_permissions.modules', []);
    }

    private function allPermissionNames()
    {
        $modules = (array) config('tenant_permissions.modules', []);

        return collect($modules)
            ->flatMap(fn ($actions, $module) => collect((array)$actions)->map(fn ($a) => "{$module}.{$a}"))
            ->unique()
            ->values();
    }
}

