<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteItem extends Model
{
    protected $fillable = [
        'tenant_id','credit_note_id','product_id','tax_type_id',
        'name','sku','description',
        'qty','unit_price','discount_pct',
        'line_subtotal','line_discount','line_tax','line_total','line_total_incl',
    ];

    public function creditNote() { return $this->belongsTo(CreditNote::class); }
    public function product()    { return $this->belongsTo(Product::class); }
    public function taxType()    { return $this->belongsTo(TaxType::class); }
}

