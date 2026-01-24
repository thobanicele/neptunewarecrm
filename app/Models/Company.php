<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'tenant_id','name','type','email','phone','website','industry','address','billing_address',
        'shipping_address',
        'vat_treatment',
        'vat_number',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function addresses()
    {
        return $this->hasMany(\App\Models\CompanyAddress::class);
    }

    public function defaultBillingAddress()
    {
        return $this->hasOne(\App\Models\CompanyAddress::class)->where('is_default_billing', true);
    }

    public function defaultShippingAddress()
    {
        return $this->hasOne(\App\Models\CompanyAddress::class)->where('is_default_shipping', true);
    }

}

