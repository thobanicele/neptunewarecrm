<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (Brand $brand) {
            if (!$brand->tenant_id && tenant()) {
                $brand->tenant_id = tenant()->id;
            }

            if (blank($brand->slug) && filled($brand->name)) {
                $base = Str::slug($brand->name);
                $slug = $base ?: Str::random(8);

                // Ensure unique per tenant
                $i = 2;
                while (static::where('tenant_id', $brand->tenant_id)
                    ->where('slug', $slug)
                    ->when($brand->exists, fn($q) => $q->where('id', '!=', $brand->id))
                    ->exists()
                ) {
                    $slug = $base . '-' . $i++;
                }

                $brand->slug = $slug;
            }
        });
    }

    public function scopeForTenant($q, $tenantId)
    {
        return $q->where('tenant_id', (int) $tenantId);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
