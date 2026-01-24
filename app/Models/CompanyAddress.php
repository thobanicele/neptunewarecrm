<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAddress extends Model
{
    protected $fillable = [
        'tenant_id','company_id','type','label','attention','phone',
        'line1','line2','city','postal_code','country_id','subdivision_id','subdivision_text',
        'is_default_billing','is_default_shipping',
    ];

    public function country()     { return $this->belongsTo(Country::class); }
    public function subdivision() { return $this->belongsTo(CountrySubdivision::class, 'subdivision_id'); }
    public function company()     { return $this->belongsTo(Company::class); }

    public function toSnapshotString(): string
    {
        $parts = array_filter([
            $this->label ? $this->label : null,
            $this->attention ? 'Att: '.$this->attention : null,
            $this->line1,
            $this->line2,
            $this->city,
            $this->subdivision?->name ?: $this->subdivision_text,
            $this->postal_code,
            $this->country?->name,
            $this->phone ? 'Tel: '.$this->phone : null,
        ]);

        return implode("\n", $parts);
    }
}
