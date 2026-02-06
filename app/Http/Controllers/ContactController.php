<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

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

        $request->merge([
            'email' => filled($request->email) ? strtolower(trim($request->email)) : null,
            'phone' => filled($request->phone) ? preg_replace('/\s+/', '', trim($request->phone)) : null,
        ]);


        $data = $request->validate([
            'company_id' => ['required','integer'],
            'name'  => ['required','string','max:190'],

            'email' => [
                'nullable','email','max:190',
                Rule::unique('contacts', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],

            'phone' => [
                'nullable','string','max:50',
                Rule::unique('contacts', 'phone')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],

            'notes' => ['nullable','string'],
            'lifecycle_stage' => ['required','in:qualified,customer'],
        ]);

        // ensure company belongs to tenant
        Company::where('tenant_id', $tenant->id)->findOrFail($data['company_id']);

        try {
            Contact::create($data + [
                'tenant_id' => $tenant->id,
                'lead_stage' => 'converted',
            ]);
        } catch (QueryException $e) {
            // MySQL duplicate key = 1062
            if (($e->errorInfo[1] ?? null) == 1062) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'A contact with this email or phone already exists in your workspace.']);
            }
            throw $e;
        }

        return redirect()->to(tenant_route('tenant.contacts.index'))
            ->with('success', 'Contact created.');
    }

    public function show(string $tenantKey, Contact $contact)
    {
        $tenant = app('tenant');

        abort_unless($contact->tenant_id === $tenant->id, 404);

        $contact->load([
            'company',
            'activities' => fn ($q) => $q->latest(),
        ]);

        return view('tenant.contacts.show', compact('contact'));
    }


    public function edit(string $tenantKey, Contact $contact)
    {
        $tenant = app('tenant');

        abort_unless($contact->tenant_id === $tenant->id, 404);

        $companies = Company::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('tenant.contacts.edit', compact('contact', 'companies'));
    }

    public function update(Request $request, string $tenantKey, Contact $contact)
    {
        $tenant = app('tenant');

        abort_unless($contact->tenant_id === $tenant->id, 404);

        $request->merge([
            'email' => filled($request->email) ? strtolower(trim($request->email)) : null,
            'phone' => filled($request->phone) ? preg_replace('/\s+/', '', trim($request->phone)) : null,
        ]);

        $data = $request->validate([
            'company_id' => ['required','integer'],
            'name'  => ['required','string','max:190'],

            'email' => [
                'nullable','email','max:190',
                Rule::unique('contacts', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($contact->id),
            ],

            'phone' => [
                'nullable','string','max:50',
                Rule::unique('contacts', 'phone')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($contact->id),
            ],

            'notes' => ['nullable','string'],
            'lifecycle_stage' => ['required','in:qualified,customer'],
        ]);

        Company::where('tenant_id', $tenant->id)->findOrFail($data['company_id']);

        $contact->update($data);

        return redirect()->to(tenant_route('tenant.contacts.index'))
            ->with('success', 'Contact updated.');
    }

    public function destroy(string $tenantKey, Contact $contact)
    {
        $tenant = app('tenant');

        abort_unless($contact->tenant_id === $tenant->id, 404);

        if ($contact->activities()->exists()) {
            return back()->with(
                'error',
                'Cannot delete this contact because it has activities logged. Remove/transfer the activities first.'
            );
        }

        $contact->delete();

        return redirect()->to(tenant_route('tenant.contacts.index'))
            ->with('success', 'Contact deleted.');
    }

}


