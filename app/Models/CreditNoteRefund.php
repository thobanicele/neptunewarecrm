<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteRefund extends Model
{
    protected $fillable = [
        'tenant_id','company_id','credit_note_id',
        'refunded_at','amount','method','reference','notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'refunded_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creditNote()
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}


