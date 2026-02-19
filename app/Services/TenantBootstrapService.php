<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantBootstrapService
{
    /**
     * Create tenant-scoped roles + permissions for a tenant.
     * Uses config if present, otherwise falls back to safe defaults.
     */
    public function seedRolesForTenant(int $tenantId): void
    {
        // Team-scoped permissions/roles
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // -------------------------
        // 1) Fallback permissions/modules (if config missing)
        // -------------------------
        $permModules = config('tenant_permissions.modules');

        if (!is_array($permModules) || empty($permModules)) {
            $permModules = [
                'dashboard' => ['view'],
                'leads'     => ['view','create','update','delete','export'],
                'contacts'  => ['view','create','update','delete','export'],
                'companies' => ['view','create','update','delete','export'],
                'deals'     => ['view','create','update','delete','export'],
                'activities'=> ['view','create','update','delete','export'],
                'quotes'    => ['view','create','update','delete','export','pdf'],
                'invoices'  => ['view','create','update','delete','export','pdf','email'],
                'payments'  => ['view','create','update','delete','export'],
                'credit_notes' => ['view','create','update','delete','export','pdf','refund'],
                'settings'  => ['view','update'],
                'users'     => ['view','invite','role','deactivate','remove'],
            ];
        }

        // Build explicit permission names: module.action
        $allPerms = collect($permModules)
            ->flatMap(fn ($actions, $module) => collect($actions)->map(fn ($a) => "{$module}.{$a}"))
            ->unique()
            ->values();

        // Create permissions (global permissions are OK, roles are tenant-scoped)
        foreach ($allPerms as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        // -------------------------
        // 2) Fallback roles (if config missing)
        // -------------------------
        $rolesConfig = config('tenant_roles.roles');

        if (!is_array($rolesConfig) || empty($rolesConfig)) {
            $rolesConfig = [
                'tenant_owner' => ['*'],
                'tenant_admin' => [
                    'dashboard.*',
                    'leads.*','contacts.*','companies.*','deals.*','activities.*',
                    'quotes.*','invoices.*','payments.*','credit_notes.*',
                    'settings.view','settings.update',
                    'users.view','users.invite','users.role','users.deactivate','users.remove',
                ],
                'sales' => [
                    'dashboard.view',
                    'leads.*','contacts.*','companies.*','deals.*','activities.*',
                    'quotes.*','invoices.view','invoices.create','invoices.update','invoices.pdf',
                ],
                'finance' => [
                    'dashboard.view',
                    'companies.view','contacts.view',
                    'invoices.*','payments.*','credit_notes.*','quotes.view','quotes.pdf',
                ],
                'viewer' => [
                    'dashboard.view',
                    'leads.view','contacts.view','companies.view','deals.view','activities.view',
                    'quotes.view','quotes.pdf',
                    'invoices.view','invoices.pdf',
                    'payments.view','credit_notes.view','credit_notes.pdf',
                ],
            ];
        }

        // Expand any "module.*" wildcards to explicit perms
        $expand = function (array $rolePerms) use ($allPerms) {
            $out = collect();

            foreach ($rolePerms as $p) {
                if ($p === '*') {
                    $out->push('*');
                    continue;
                }

                if (str_ends_with($p, '.*')) {
                    $prefix = substr($p, 0, -2);
                    $out = $out->merge($allPerms->filter(fn ($x) => str_starts_with($x, $prefix . '.')));
                    continue;
                }

                if ($allPerms->contains($p)) {
                    $out->push($p);
                }
            }

            return $out->unique()->values();
        };

        foreach ($rolesConfig as $roleName => $rolePerms) {
            $role = Role::firstOrCreate([
                'tenant_id'  => $tenantId,
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            if (in_array('*', $rolePerms, true)) {
                $role->syncPermissions($allPerms->all());
            } else {
                $role->syncPermissions($expand($rolePerms)->all());
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}


