<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantBootstrapService;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class TenantRolesBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $bootstrap = app(TenantBootstrapService::class);

        Tenant::query()->orderBy('id')->chunk(100, function ($tenants) use ($bootstrap) {
            foreach ($tenants as $tenant) {

                // 1) Ensure roles exist for tenant
                $bootstrap->seedRolesForTenant((int) $tenant->id);

                // 2) If tenant has users but no role assignments in pivot, assign owner to first user
                app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                $hasPivot = DB::table('model_has_roles')
                    ->where('tenant_id', $tenant->id)
                    ->count() > 0;

                if (!$hasPivot) {
                    $u = User::query()
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('id')
                        ->first();

                    if ($u) {
                        $u->syncRoles(['tenant_owner']);
                    }
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

