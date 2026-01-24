<?php
namespace App\Http\Controllers;

class BillingController extends Controller
{
    public function upgrade()
    {
        $tenant = app('tenant');
        return view('tenant.billing.upgrade', compact('tenant'));
    }
}
