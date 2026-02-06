<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantCounter extends Model
{
    protected $fillable = ['tenant_id', 'key', 'value'];
}

