<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class TenantRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['tenant_owner', 'tenant_admin', 'sales', 'finance', 'viewer'];

        foreach (Tenant::query()->pluck('id') as $tenantId) {
            foreach ($roles as $name) {
                Role::firstOrCreate([
                    'tenant_id'  => (int) $tenantId,
                    'name'       => $name,
                    'guard_name' => 'web',
                ]);
            }
        }
    }
}

