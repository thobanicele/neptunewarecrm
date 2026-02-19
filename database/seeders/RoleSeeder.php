<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Global role ONLY (no tenant scope)
        Role::firstOrCreate([
            'tenant_id'   => null,
            'name'        => 'super_admin',
            'guard_name'  => 'web',
        ]);
    }
}




