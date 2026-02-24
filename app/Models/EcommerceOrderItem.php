<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceOrderItem extends Model
{
    protected $fillable = [
        'tenant_id',
        'ecommerce_order_id',
        'external_item_id',
        'position',
        'sku',
        'name',
        'qty',
        'unit_price',
        'tax_total',
        'discount_total',
        'line_total',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrder::class, 'ecommerce_order_id');
    }
}
