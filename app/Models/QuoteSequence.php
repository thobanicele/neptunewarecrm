<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteSequence extends Model
{
    protected $table = 'quote_sequences';

    protected $fillable = [
        'tenant_id',
        'next_number',
        'prefix',
        'padding',
    ];

    public $timestamps = true;

    protected $casts = [
        'tenant_id'    => 'integer',
        'next_number'  => 'integer',
        'padding'      => 'integer',
    ];
}

