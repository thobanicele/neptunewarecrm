<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['iso2','iso3','name','numeric_code','currency_code'];

    public function subdivisions()
    {
        return $this->hasMany(CountrySubdivision::class);
    }
}

