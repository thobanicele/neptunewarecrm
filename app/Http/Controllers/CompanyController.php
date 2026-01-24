<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        $companies = Company::where('tenant_id', $tenant->id)->latest()->paginate(20);
        return view('tenant.companies.index', compact('companies'));
    }

    public function create()
    {
        $tenant = app('tenant');

        $countries = \App\Models\Country::orderBy('name')->get(['id','iso2','name']);

        return view('tenant.companies.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');
        $data = $request->validate([
            'name' => ['required','max:190'],
            'type' => ['required','in:prospect,customer,individual'],
            'email' => ['nullable','email'],
            'phone' => ['nullable','max:50'],
            'payment_terms' => ['nullable','string','max:190'],
        ]);
        

        Company::create($data + ['tenant_id' => $tenant->id]);

        return redirect()->to(
            tenant_route('tenant.companies.index')
        )->with('success', 'Company created successfully.');

    }

    public function show(Tenant $tenant, Company $company)
    {
        // extra safety (should pass automatically with scopeBindings)
        abort_unless($company->tenant_id === $tenant->id, 404);

        $company->load('contacts');

        return view('tenant.companies.show', compact('company'));
    }
}

