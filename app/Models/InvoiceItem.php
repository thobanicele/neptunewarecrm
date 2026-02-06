<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'tenant_id','invoice_id',
        'product_id','sku','unit','tax_type_id','position',
        'name','description','qty','unit_price',
        'discount_pct','discount_amount',
        'tax_rate','tax_name',
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
        'position'       => 'integer',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}



