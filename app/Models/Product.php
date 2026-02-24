<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'tenant_id',
        'brand_id',
        'category_id',
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

    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class, 'brand_id');
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id');
    }
}




