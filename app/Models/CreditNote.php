<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
    protected $fillable = [
        'tenant_id','company_id','invoice_id',
        'credit_note_number','issued_at','amount','reason','notes',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'amount'    => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }

    // CreditNote.php

    public function allocations()
    {
        return $this->hasMany(TransactionAllocation::class, 'credit_note_id');
    }

    public function refunds()
    {
        return $this->hasMany(CreditNoteRefund::class, 'credit_note_id');
    }

    public function allocatedTotal(): float
    {
        return (float) $this->allocations()->sum('amount_applied');
    }

    public function refundedTotal(): float
    {
        return (float) $this->refunds()->sum('amount');
    }

    public function availableTotal(): float
    {
        // credit remaining that can still be used or refunded
        return max(0, (float) $this->total - $this->allocatedTotal() - $this->refundedTotal());
    }

}

