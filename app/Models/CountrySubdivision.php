<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountrySubdivision extends Model
{
    protected $fillable = ['country_id','code','iso_code','name','level','parent_code'];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
