<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TenantAdminController extends Controller
{
    public function dashboard()
    {
        $tenant = app('currentTenant');
        $users = $tenant->users()->get();
        return view('tenant.dashboard.index', compact('tenant','users'));
    }

}
