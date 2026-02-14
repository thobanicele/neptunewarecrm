<?php

namespace App\Services;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantBootstrapService
{
    public function bootstrap(int $tenantId): void
    {
        $pipeline = Pipeline::create([
            'tenant_id' => $tenantId,
            'name' => 'Sales Pipeline',
            'is_default' => true,
        ]);

        $stages = [
            ['name' => 'New Lead',        'position' => 1],
            ['name' => 'Qualified',  'position' => 2],
            ['name' => 'Proposal Sent',   'position' => 3],
            ['name' => 'Negotiation','position' => 4],
            ['name' => 'Won',        'position' => 5, 'is_won' => true],
            ['name' => 'Lost',       'position' => 6, 'is_lost' => true],
        ];

        foreach ($stages as $s) {
            PipelineStage::create([
                'tenant_id' => $tenantId,
                'pipeline_id' => $pipeline->id,
                'name' => $s['name'],
                'position' => $s['position'],
                'is_won' => $s['is_won'] ?? false,
                'is_lost' => $s['is_lost'] ?? false,
            ]);
        }
    }

    public function seedRolesForTenant(int $tenantId): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $rolesConfig = config('tenant_roles.roles', []);

        // Create permissions (global list)
        $perms = collect($rolesConfig)
            ->flatten()
            ->unique()
            ->reject(fn ($p) => $p === '*');

        foreach ($perms as $perm) {
            Permission::findOrCreate($perm, 'web');
        }

        foreach ($rolesConfig as $roleName => $rolePerms) {
            $role = Role::firstOrCreate([
                'tenant_id'   => $tenantId,
                'name'        => $roleName,
                'guard_name'  => 'web',
            ]);

            if (in_array('*', $rolePerms, true)) {
                $role->syncPermissions(Permission::all());
            } else {
                $role->syncPermissions($rolePerms);
            }
        }
    }


}

