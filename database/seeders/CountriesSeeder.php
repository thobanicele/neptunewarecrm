<?php
// database/seeders/CountriesSeeder.php
namespace Database\Seeders;

use App\Models\Country;
use App\Models\CountrySubdivision;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountriesSeeder extends Seeder
{
    public function run(): void
    {
        $countryRepo = new CountryRepository();
        $subRepo     = new SubdivisionRepository();

        DB::transaction(function () use ($countryRepo, $subRepo) {
            // optional: keep, but only if you're ok wiping
            // CountrySubdivision::truncate();
            // Country::truncate();

            $countries = $countryRepo->getAll(); // :contentReference[oaicite:3]{index=3}

            foreach ($countries as $c) {
                $country = Country::updateOrCreate(
                    ['iso2' => $c->getCountryCode()],
                    [
                        'name' => $c->getName(),
                        'iso3' => $c->getThreeLetterCode(),
                        'numeric_code' => $c->getNumericCode(),
                        'currency_code' => $c->getCurrencyCode(),
                    ]
                );

                // Try seed level 1 subdivisions (states/provinces) if dataset exists
                try {
                    $level1 = $subRepo->getAll([$country->iso2]); // :contentReference[oaicite:4]{index=4}
                } catch (\Throwable $e) {
                    continue;
                }

                foreach ($level1 as $key => $sub) {
                    $code = (string) $key; // use array key

                    $iso = null;
                    if (method_exists($sub, 'getIsoCode')) {
                        $iso = $sub->getIsoCode();
                    }
                    // fallback if iso is missing
                    if (!$iso && method_exists($sub, 'getCode')) {
                        $iso = $sub->getCode();
                    }

                    CountrySubdivision::updateOrCreate(
                        [
                            'country_id' => $country->id,
                            'level'      => 1,
                            'code'       => $code,
                        ],
                        [
                            'name'        => $sub->getName(),
                            'iso_code'    => $iso,
                            'parent_code' => null,
                        ]
                    );
                }

            }
        });
    }
}
