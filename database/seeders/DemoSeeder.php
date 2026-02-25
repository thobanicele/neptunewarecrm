<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (local only)
        $super = User::firstOrCreate(
            ['email' => 'super@admin.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password')]
        );
        if (method_exists($super, 'assignRole')) {
            $super->assignRole('super_admin');
        }

        // Tenant 1
        $tenant1 = Tenant::firstOrCreate(
            ['subdomain' => 'tenant1'],
            ['name' => 'Tenant One', 'plan' => 'free']
        );

        Subscription::firstOrCreate(
            ['tenant_id' => $tenant1->id, 'plan' => 'free'],
            ['expires_at' => now()->addMonth()]
        );

        $t1Admin = User::firstOrCreate(
            ['email' => 'admin1@tenant.com'],
            ['name' => 'Tenant1 Admin', 'password' => Hash::make('password'), 'tenant_id' => $tenant1->id]
        );
        if (method_exists($t1Admin, 'assignRole')) {
            $t1Admin->assignRole('tenant_admin');
        }

        // Tenant 2
        $tenant2 = Tenant::firstOrCreate(
            ['subdomain' => 'tenant2'],
            ['name' => 'Tenant Two', 'plan' => 'premium']
        );

        Subscription::firstOrCreate(
            ['tenant_id' => $tenant2->id, 'plan' => 'premium'],
            ['expires_at' => now()->addMonth()]
        );

        $t2Admin = User::firstOrCreate(
            ['email' => 'admin2@tenant.com'],
            ['name' => 'Tenant2 Admin', 'password' => Hash::make('password'), 'tenant_id' => $tenant2->id]
        );
        if (method_exists($t2Admin, 'assignRole')) {
            $t2Admin->assignRole('tenant_admin');
        }
    }
}
