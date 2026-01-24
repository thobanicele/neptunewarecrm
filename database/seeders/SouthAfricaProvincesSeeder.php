<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\CountrySubdivision;

class SouthAfricaProvincesSeeder extends Seeder
{
    public function run(): void
    {
        $za = Country::where('iso2', 'ZA')->first();
        if (!$za) return;

        $provinces = [
            ['code' => 'EC', 'name' => 'Eastern Cape',      'iso_code' => 'ZA-EC'],
            ['code' => 'FS', 'name' => 'Free State',        'iso_code' => 'ZA-FS'],
            ['code' => 'GP', 'name' => 'Gauteng',           'iso_code' => 'ZA-GP'],
            ['code' => 'KZN','name' => 'KwaZulu-Natal',     'iso_code' => 'ZA-KZN'],
            ['code' => 'LP', 'name' => 'Limpopo',           'iso_code' => 'ZA-LP'],
            ['code' => 'MP', 'name' => 'Mpumalanga',        'iso_code' => 'ZA-MP'],
            ['code' => 'NC', 'name' => 'Northern Cape',     'iso_code' => 'ZA-NC'],
            ['code' => 'NW', 'name' => 'North West',        'iso_code' => 'ZA-NW'],
            ['code' => 'WC', 'name' => 'Western Cape',      'iso_code' => 'ZA-WC'],
        ];

        foreach ($provinces as $p) {
            CountrySubdivision::updateOrCreate(
                [
                    'country_id' => $za->id,
                    'level'      => 1,
                    'code'       => $p['code'],
                ],
                [
                    'name'        => $p['name'],
                    'iso_code'    => $p['iso_code'],
                    'parent_code' => null,
                ]
            );
        }
    }
}

