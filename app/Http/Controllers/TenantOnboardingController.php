<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTenantRequest;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantOnboardingController extends Controller
{
    public function create()
    {
        return view('tenant.onboarding.create');
    }

    public function store(CreateTenantRequest $request)
    {
        $user = $request->user();

        if ($user->tenant_id) {
            return redirect()->route('tenant.dashboard')
                ->with('error', 'You already belong to a workspace.');
        }

        $tenant = DB::transaction(function () use ($request, $user) {

            $tenant = Tenant::create([
                'name' => $request->name,
                'subdomain' => $request->subdomain,
                'plan' => 'free',
                'status' => 'active', // if you have this column
            ]);

            $user->tenant_id = $tenant->id;
            $user->save();

            // Roles are clean now → assign tenant_admin
            $user->syncRoles(['tenant_admin']);

            return $tenant;
        });

        // Redirect to tenant subdomain dashboard (recommended for your setup)
        // Assumes your base domain is crm.test and you want {subdomain}.crm.test
        return redirect()->to("http://{$tenant->subdomain}.crm.test/tenant/dashboard")
            ->with('success', 'Workspace created successfully!');
    }
}


