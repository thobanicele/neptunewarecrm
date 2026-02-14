<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan',
        'provider',
        'paystack_plan_code',
        'paystack_subscription_code',
        'paystack_email_token',
        'paystack_customer_code',
        'paystack_authorization_code',
        'last_payment_ref',
        'cycle',
        'expires_at',
        'trial_ends_at',
        'canceled_at',
    ];

    protected $casts = [
        'expires_at'    => 'date',
        'trial_ends_at' => 'datetime',
        'canceled_at'   => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    // Helpers
    public function onTrial(): bool
    {
        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    public function active(): bool
    {
        // active if not canceled AND not expired (or expires_at null)
        if ($this->canceled_at) return false;
        if ($this->expires_at && now()->toDateString() > $this->expires_at->toDateString()) return false;
        return true;
    }
}

