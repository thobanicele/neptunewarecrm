<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'tenant_id','sales_order_id',
        'product_id','sku','unit','tax_type_id','position',
        'name','description',
        'qty','unit_price',
        'discount_pct','discount_amount',
        'tax_name','tax_rate',
        'line_total','tax_amount',
    ];

    protected $casts = [
        'qty'            => 'decimal:2',
        'unit_price'     => 'decimal:2',
        'discount_pct'   => 'decimal:2',
        'discount_amount'=> 'decimal:2',
        'tax_rate'       => 'decimal:2',
        'line_total'     => 'decimal:2',
        'tax_amount'     => 'decimal:2',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

