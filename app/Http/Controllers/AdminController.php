<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;

class AdminController extends Controller
{
    public function dashboard()
    {
        $tenants = Tenant::all();
        return view('admin.dashboard', compact('tenants'));
    }

}
