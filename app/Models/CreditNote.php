<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CreditNote extends Model
{
   protected $fillable = [
        'tenant_id',
        'company_id',
        'contact_id',
        'invoice_id',
        'credit_note_number',
        'issued_at',
        'amount',
        'reason',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'amount'    => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(CreditNoteItem::class);
    }

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
        return max(0, (float) $this->amount - $this->allocatedTotal() - $this->refundedTotal());
    }

}

