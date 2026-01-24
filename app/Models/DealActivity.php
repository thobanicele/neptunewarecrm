<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealActivity extends Model
{
    protected $fillable = [
        'tenant_id','deal_id','user_id','type','meta','note'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function deal() { return $this->belongsTo(Deal::class); }
    public function user() { return $this->belongsTo(User::class); }
}

