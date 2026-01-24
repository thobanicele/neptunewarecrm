<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $fillable = [
        'tenant_id','deal_id','company_id','contact_id','owner_user_id',
        'sales_person_user_id',
        'quote_number','status','issued_at','valid_until','currency',
        'subtotal','tax_rate','tax_amount','total',
        'notes','terms','sent_at','accepted_at','declined_at',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items() { return $this->hasMany(QuoteItem::class)->orderBy('position'); }
    public function deal() { return $this->belongsTo(Deal::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function contact() { return $this->belongsTo(Contact::class); }
    public function owner() { return $this->belongsTo(User::class, 'owner_user_id'); }
    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'sales_person_user_id');
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

}

