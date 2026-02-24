<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SalesOrder extends Model
{
    protected $fillable = [
        'tenant_id','quote_id',
        'sales_order_number','quote_number','reference',
        'deal_id','company_id','contact_id',
        'owner_user_id','sales_person_user_id',
        'status','issued_at','due_at',
        'tax_type_id','currency',
        'subtotal','discount_amount','tax_rate','tax_amount','total',
        'notes','terms',
        'billing_address_id','shipping_address_id',
        'billing_address_snapshot','shipping_address_snapshot',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at'    => 'date',

        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate'        => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(SalesOrderItem::class)->orderBy('position');
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

    public function deal()
    {
        return $this->belongsTo(Deal::class);
    }

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
        return $this->belongsTo(Tenant::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'sales_order_id');
    }

    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }
}

