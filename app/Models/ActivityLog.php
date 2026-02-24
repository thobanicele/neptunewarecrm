<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant;
use App\Models\User;

class ActivityLog extends Model
{
    protected $fillable = [
        'tenant_id','user_id','action','subject_type','subject_id','meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function subject()
    {
        return $this->morphTo();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
