<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'unit_rate',
        'unit',
        'is_active',
        'currency',
        'tax_type_id',
    ];

    protected $casts = [
        'unit_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function taxType()
    {
        return $this->belongsTo(\App\Models\TaxType::class, 'tax_type_id');
    }

}




