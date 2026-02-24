<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Database\Seeders\TenantTaxTypeSeeder;
use App\Services\TenantBootstrapService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Tenant extends Model
{
    protected $fillable = [
        'name',
        'subdomain',
        'plan',
        'status',
        'logo_path',

        // PDF/company info
        'bank_details',
        'company_address',
        'vat_number',
        'registration_number',

        // platform meta
        'owner_user_id',
        'last_seen_at',
    ];

    protected static function booted()
    {
        static::created(function (Tenant $tenant) {

            // Seed default tax types
            TenantTaxTypeSeeder::seedForTenant((int) $tenant->id);

            // ✅ Seed tenant roles + permissions
            app(TenantBootstrapService::class)->seedRolesForTenant((int) $tenant->id);
        });
    }
    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

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

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Models\Invoice::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_user_id');
    }
    

}



