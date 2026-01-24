<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $fillable = [
    'tenant_id',
    'pipeline_id',
    'stage_id',
    'title',
    'amount',
    'expected_close_date',
    'notes',
    'company_id',
    'primary_contact_id'
    ];


    protected $casts = [
        'expected_close_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function deal_activities()
    {
        return $this->hasMany(DealActivity::class)->latest();
    }

    public function stage()
    {
        return $this->belongsTo(\App\Models\PipelineStage::class, 'stage_id');
    }
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function primaryContact()
    {
        return $this->belongsTo(\App\Models\Contact::class, 'primary_contact_id');
    }

    public function activities():MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }
}


