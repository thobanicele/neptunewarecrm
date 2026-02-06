<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Contact extends Model
{

    protected $fillable = [
        'tenant_id','company_id','name','email','phone','lifecycle_stage','lead_stage','notes'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public static function leadStages(): array
    {
        // keep these aligned with your validation + UI
        return ['new', 'contacted', 'qualified', 'converted', 'lost'];
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    public function setEmailAttribute($value): void
    {
        $value = is_string($value) ? trim($value) : $value;
        $this->attributes['email'] = ($value === '' || $value === null) ? null : mb_strtolower($value);
    }

    public function setPhoneAttribute($value): void
    {
        $value = is_string($value) ? trim($value) : $value;
        $value = ($value === '' || $value === null) ? null : preg_replace('/\s+/', '', $value);
        $this->attributes['phone'] = $value;
    }



}

