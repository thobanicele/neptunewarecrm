<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPlan extends Model
{
    protected $fillable = ['provider','cycle','amount','currency','interval','plan_code'];
}
