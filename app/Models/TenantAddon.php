<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantAddon extends Model
{
    protected $fillable = [
        'tenant_id',
        'key',
        'is_enabled',
        'enabled_at',
        'enabled_by_user_id',
        'meta',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'meta' => 'array',
    ];
}