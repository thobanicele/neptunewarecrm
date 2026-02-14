<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'tenant_id','quote_id',
        'invoice_number','quote_number','reference',
        'deal_id','company_id','contact_id',
        'owner_user_id','sales_person_user_id',
        'status','issued_at','due_at','paid_at','voided_at',
        'tax_type_id','currency',
        'subtotal','discount_amount','tax_rate','tax_amount','total',
        'notes','terms',
        'billing_address_id','shipping_address_id',
        'billing_address_snapshot','shipping_address_snapshot',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at'    => 'date',
        'paid_at'   => 'datetime',
        'voided_at' => 'datetime',

        // money/percent fields
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    // Optional convenience helpers
    public function isDraft(): bool  { return $this->status === 'draft'; }
    public function isIssued(): bool { return $this->status === 'issued'; }
    public function isPaid(): bool   { return $this->status === 'paid'; }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'sales_person_user_id');
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function deal()
    {
        return $this->belongsTo(\App\Models\Deal::class);
    }

    // Invoice.php
    public function allocations()
    {
        return $this->hasMany(TransactionAllocation::class, 'invoice_id');
    }

    public function paymentsAllocatedTotal(): float
    {
        return (float) $this->allocations()
            ->whereNotNull('payment_id')
            ->sum('amount_applied');
    }

    public function creditsAllocatedTotal(): float
    {
        return (float) $this->allocations()
            ->whereNotNull('credit_note_id')
            ->sum('amount_applied');
    }

    public function balanceDue(): float
    {
        return max(0, (float) $this->total - $this->paymentsAllocatedTotal() - $this->creditsAllocatedTotal());
    }



    public function getAllocatedAmountAttribute(): float
    {
        // If eager-loaded, this avoids extra queries
        $sum = $this->relationLoaded('allocations')
            ? $this->allocations->sum('amount_applied')
            : $this->allocations()->sum('amount_applied');

        return round((float) $sum, 2);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return round(((float) $this->total) - $this->allocated_amount, 2);
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->outstanding_amount <= 0) return 'paid';
        if ($this->allocated_amount > 0) return 'partial';
        return 'unpaid';
    }

}



