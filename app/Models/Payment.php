<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'tenant_id','company_id','contact_id','invoice_id','credit_note_id',
        'reference','direction','amount','paid_at','method','notes',
        ];

    protected $casts = [
        'paid_at' => 'date',
        'amount'  => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function contact() { return $this->belongsTo(Contact::class); }

    public function allocations()
    {
        return $this->hasMany(TransactionAllocation::class, 'source_id')
        ->where('source_type', self::class);
    }

    public function allocatedTotal(): float
    {
        return (float) $this->allocations()->sum('amount_applied');
    }

    public function unallocatedTotal(): float
    {
        return max(0, (float)$this->amount - (float)$this->allocatedTotal());
    }
    
    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }
}
