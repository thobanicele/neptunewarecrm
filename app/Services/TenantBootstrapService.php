<?php

namespace App\Services;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

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

    public function seedDefaultPipelineForTenant(int $tenantId): void
    {
        $stageTemplate = [
            ['name' => 'New Lead',      'position' => 1, 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Qualified',     'position' => 2, 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Proposal Sent', 'position' => 3, 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Negotiation',   'position' => 4, 'is_won' => 0, 'is_lost' => 0],
            ['name' => 'Won',           'position' => 5, 'is_won' => 1, 'is_lost' => 0],
            ['name' => 'Lost',          'position' => 6, 'is_won' => 0, 'is_lost' => 1],
        ];

        // NOTE: This method may be called inside an outer transaction (onboarding),
        // so we avoid starting a new transaction here.

        // 1) Find existing default pipeline or create one
        $pipelineId = DB::table('pipelines')
            ->where('tenant_id', $tenantId)
            ->where('is_default', 1)
            ->value('id');

        if (!$pipelineId) {
            $pipelineId = DB::table('pipelines')
                ->where('tenant_id', $tenantId)
                ->where('name', 'Sales Pipeline')
                ->value('id');

            if (!$pipelineId) {
                $pipelineId = DB::table('pipelines')->insertGetId([
                    'tenant_id' => $tenantId,
                    'name' => 'Sales Pipeline',
                    'is_default' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('pipelines')->where('id', $pipelineId)->update([
                    'is_default' => 1,
                    'updated_at' => now(),
                ]);
            }
        }

        // 2) Enforce single default for this tenant
        DB::table('pipelines')
            ->where('tenant_id', $tenantId)
            ->where('id', '!=', $pipelineId)
            ->update(['is_default' => 0, 'updated_at' => now()]);

        // 3) Seed stages idempotently
        foreach ($stageTemplate as $s) {
            DB::table('pipeline_stages')->updateOrInsert(
                [
                    'tenant_id' => $tenantId,
                    'pipeline_id' => $pipelineId,
                    'name' => $s['name'],
                ],
                [
                    'position' => $s['position'],
                    'is_won' => $s['is_won'],
                    'is_lost' => $s['is_lost'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}


