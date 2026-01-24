<?php
namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;

class GeoController extends Controller
{
    public function subdivisions(string $countryIso2)
    {
        $country = Country::where('iso2', strtoupper($countryIso2))->firstOrFail();

        $subs = $country->subdivisions()
            ->where('level', 1)
            ->orderBy('name')
            ->get(['id','name','code','iso_code']);

        return response()->json($subs);
    }
}

