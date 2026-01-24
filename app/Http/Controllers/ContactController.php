<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');

        $contacts = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('lifecycle_stage', '!=', 'lead')
            ->with('company')
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.contacts.index', compact('contacts'));
    }

    public function create()
    {
        $tenant = app('tenant');

        $companies = Company::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('tenant.contacts.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'company_id' => ['required','integer'],
            'name'  => ['required','string','max:190'],
            'email' => ['nullable','email','max:190'],
            'phone' => ['nullable','string','max:50'],
            'notes' => ['nullable','string'],
            'lifecycle_stage' => ['required','in:qualified,customer'], // contacts module = not lead
        ]);

        // ensure company belongs to tenant
        Company::where('tenant_id', $tenant->id)->findOrFail($data['company_id']);

        Contact::create($data + [
            'tenant_id' => $tenant->id,
            'lead_stage' => 'converted', // since it's not a lead
        ]);

        return redirect()->to(tenant_route('tenant.contacts.index'))
            ->with('success', 'Contact created.');
    }
}


