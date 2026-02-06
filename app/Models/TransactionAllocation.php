<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionAllocation extends Model
{
    protected $fillable = [
        'tenant_id','invoice_id','payment_id','credit_note_id',
        'amount_applied','applied_at',
    ];

    protected $casts = [
        'applied_at'     => 'date',
        'amount_applied' => 'decimal:2',
    ];

    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function payment() { return $this->belongsTo(Payment::class); }
    public function creditNote() { return $this->belongsTo(CreditNote::class); }
}

