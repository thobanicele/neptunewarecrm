<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ SAFE CORE DATA (OK to run in production)
        $this->call([
            RoleSeeder::class,
            CountriesSeeder::class,
            SouthAfricaProvincesSeeder::class,
            TenantRolesSeeder::class,
            TenantTaxTypeSeeder::class,
            PipelineSeeder::class,
            // Add these if you have them:
            // PipelineSeeder::class,
            // PlatformOwnerSeeder::class,
        ]);

        // ❌ DEMO DATA (local only)
        if (app()->environment('local')) {
            $this->call([
                DemoSeeder::class, // we’ll create this next
            ]);
        }
    }
}



