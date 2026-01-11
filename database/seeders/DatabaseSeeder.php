<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Seed roles first (very important)
        $this->call([
            RoleSeeder::class,
            // PermissionSeeder::class, // if you have one
        ]);

        // Super Admin (no tenant)
        $super = User::create([
            'name' => 'Super Admin',
            'email' => 'super@admin.com',
            'password' => Hash::make('password'),
        ]);
        $super->assignRole('super_admin');

        // Tenant 1
        $tenant1 = Tenant::create([
            'name' => 'Tenant One',
            'subdomain' => 'tenant1',
            'plan' => 'free',
        ]);

        Subscription::create([
            'tenant_id' => $tenant1->id,
            'plan' => 'free',
            'expires_at' => now()->addMonth(),
        ]);

        $t1Admin = User::create([
            'name' => 'Tenant1 Admin',
            'email' => 'admin1@tenant.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant1->id,
        ]);
        $t1Admin->assignRole('tenant_admin'); // or tenant_owner if you want

        $t1User = User::create([
            'name' => 'Tenant1 User',
            'email' => 'user1@tenant.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant1->id,
        ]);
        $t1User->assignRole('tenant_staff');

        // Tenant 2
        $tenant2 = Tenant::create([
            'name' => 'Tenant Two',
            'subdomain' => 'tenant2',
            'plan' => 'premium',
        ]);

        Subscription::create([
            'tenant_id' => $tenant2->id,
            'plan' => 'premium',
            'expires_at' => now()->addMonth(),
        ]);

        $t2Admin = User::create([
            'name' => 'Tenant2 Admin',
            'email' => 'admin2@tenant.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant2->id,
        ]);
        $t2Admin->assignRole('tenant_admin');
    }
}



