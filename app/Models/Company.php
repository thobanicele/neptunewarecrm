<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\{Quote, Invoice, SalesOrder, Payment, CreditNote, Deal};

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id','name','type','email','phone','payment_term_id','website','industry','address',
        'billing_address','shipping_address','vat_treatment','vat_number',
    ];

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class);
    }

    public function addresses()
    {
        return $this->hasMany(\App\Models\CompanyAddress::class);
    }

    public function defaultBillingAddress()
    {
        return $this->hasOne(\App\Models\CompanyAddress::class)->where('is_default_billing', true);
    }

    public function defaultShippingAddress()
    {
        return $this->hasOne(\App\Models\CompanyAddress::class)->where('is_default_shipping', true);
    }

    public function paymentTerm()
    {
        return $this->belongsTo(\App\Models\PaymentTerm::class, 'payment_term_id');
    }

    public function hasTransactions(): bool
    {
        $tenantId = (int) $this->tenant_id;

        return Quote::where('tenant_id', $tenantId)->where('company_id', $this->id)->exists()
            || Invoice::where('tenant_id', $tenantId)->where('company_id', $this->id)->exists()
            || SalesOrder::where('tenant_id', $tenantId)->where('company_id', $this->id)->exists()
            || Payment::where('tenant_id', $tenantId)->where('company_id', $this->id)->exists()
            || CreditNote::where('tenant_id', $tenantId)->where('company_id', $this->id)->exists()
            || Deal::where('tenant_id', $tenantId)->where('company_id', $this->id)->exists();
    }

}

