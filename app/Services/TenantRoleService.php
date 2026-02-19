<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleService
{
    /**
     * Create default tenant-scoped roles for a tenant.
     */
    public static function seedForTenant(int $tenantId): void
    {
        $roles = ['tenant_owner', 'tenant_admin', 'sales', 'finance', 'viewer'];

        // Ensure Spatie Teams scope is set (tenant_id)
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        foreach ($roles as $name) {
            Role::firstOrCreate([
                'tenant_id'   => $tenantId,
                'name'        => $name,
                'guard_name'  => 'web',
            ]);
        }

        // Clear permission cache (safe here)
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Optional: reset team scope to avoid leaking into other operations
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}
