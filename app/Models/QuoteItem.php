<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $fillable = [
    'tenant_id','quote_id','product_id','tax_type_id',
    'position','name','description',
    'qty','unit_price','line_total',
    'discount_pct','discount_amount',
    'tax_name','tax_rate','tax_amount',
    'sku','unit',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'discount_pct' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];

    public function quote() { return $this->belongsTo(Quote::class); }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
