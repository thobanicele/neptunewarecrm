<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxType extends Model
{
    protected $fillable = [
        'tenant_id','name','rate','is_default','is_active'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function taxType()
    {
        return $this->belongsTo(\App\Models\TaxType::class);
    }

}

