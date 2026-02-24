<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceOrder extends Model
{
    protected $fillable = [
        'tenant_id',
        'external_order_id',
        'source',
        'status',
        'currency',
        'subtotal',
        'tax_total',
        'shipping_total',
        'discount_total',
        'grand_total',
        'customer_name',
        'customer_email',
        'customer_phone',
        'billing_address',
        'shipping_address',
        'placed_at',
        'raw_payload',
        'meta',
        'external_updated_at',
        'payment_status',
        'fulfillment_status',
        'paid_at',
        'fulfilled_at',
        'converted_sales_order_id',
        'converted_at',
    ];

    protected $casts = [
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'raw_payload' => 'array',
        'meta' => 'array',
        'placed_at' => 'datetime',
        'external_updated_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(EcommerceOrderItem::class)->orderBy('position');
    }

    public function convertedSalesOrder()
    {
        return $this->belongsTo(\App\Models\SalesOrder::class, 'converted_sales_order_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
    public function activityLogs()
    {
        return $this->morphMany(ActivityLog::class, 'subject');
    }
    
}
