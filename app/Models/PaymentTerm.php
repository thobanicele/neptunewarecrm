<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTerm extends Model
{
    protected $fillable = [
        'tenant_id','name','name_normalized','days','is_active','sort_order',
    ];

    protected $casts = [
        'days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (PaymentTerm $term) {
            if (!$term->tenant_id && tenant()) {
                $term->tenant_id = tenant()->id;
            }

            $term->name = trim((string) $term->name);
            $term->name_normalized = mb_strtolower(trim((string) $term->name));
        });
    }

    public function scopeForTenant($q, $tenantId)
    {
        return $q->where('tenant_id', (int) $tenantId);
    }

    public function companies()
    {
        return $this->hasMany(\App\Models\Company::class, 'payment_term_id');
    }
}