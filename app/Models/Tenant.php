<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Seeders\TenantTaxTypeSeeder;
use App\Models\Company;

class Tenant extends Model
{
    protected $fillable = [
    'name',
    'subdomain',
    'plan',
    'status',
    'logo_path',
    ];

    protected static function booted()
    {
        static::created(function (Tenant $tenant) {
            TenantTaxTypeSeeder::seedForTenant((int) $tenant->id);
        });
    }



    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function deals()
    {
        return $this->hasMany(\App\Models\Deal::class);
    }

    public function companies()
    {
        return $this->hasMany(Company::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
    public function getRouteKeyName(): string
    {
        return 'subdomain';
    }

}
