<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Seed roles first (very important)
        $this->call([
            RoleSeeder::class,
            CountriesSeeder::class,
            SouthAfricaProvincesSeeder::class,
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

        $countryRepo = new CountryRepository();
        $subRepo = new SubdivisionRepository();

        DB::transaction(function () use ($countryRepo, $subRepo) {
            // 1) Countries
            $countries = $countryRepo->getAll(); // country objects :contentReference[oaicite:4]{index=4}
            foreach ($countries as $c) {
                DB::table('countries')->updateOrInsert(
                    ['iso2' => $c->getCountryCode()],
                    [
                        'name' => $c->getName(),
                        'iso3' => $c->getThreeLetterCode(),
                        'numeric_code' => $c->getNumericCode(),
                        'currency_code' => $c->getCurrencyCode(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            // 2) Subdivisions (only for countries where the library has them)
            // Because subdivisions exist for ~60 countries, we try/catch per country. :contentReference[oaicite:5]{index=5}
            foreach ($countries as $c) {
                $cc = $c->getCountryCode();

                try {
                    $level1 = $subRepo->getAll([$cc]); // first-level admin areas :contentReference[oaicite:6]{index=6}
                } catch (\Throwable $e) {
                    continue; // no subdivision dataset for this country
                }

                foreach ($level1 as $sub) {
                    $subCode = $sub->getCode();      // e.g. 'GP' or 'ZA-GP' depending on dataset
                    $isoCode = method_exists($sub, 'getIsoCode') ? $sub->getIsoCode() : null; // admin areas
                    $name = $sub->getName();

                    DB::table('country_subdivisions')->updateOrInsert(
                        [
                            'country_iso2' => $cc,
                            'code' => $subCode,
                            'level' => 1,
                        ],
                        [
                            'iso_code' => $isoCode,
                            'name' => $name,
                            'parent_code' => null,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            }
        });
    }
}



