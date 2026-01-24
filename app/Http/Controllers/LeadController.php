<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Tenant;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request, Tenant $tenant)
    {
        // If you prefer your middleware-bound tenant, keep this line:
        $tenant = app('tenant');

        $q = trim((string) $request->query('q', ''));
        $stage = (string) $request->query('stage', '');

        $leadStages = Contact::leadStages();

        $leads = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('lifecycle_stage', 'lead')
            ->when($stage, fn ($qq) => $qq->where('lead_stage', $stage))
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $companies = Company::where('tenant_id', $tenant->id)->orderBy('name')->get();

        return view('tenant.leads.index', compact('tenant','leads','leadStages','stage','q','companies'));
    }

    public function kanban(Tenant $tenant)
    {
        $tenant = app('tenant');

        $leadStages = Contact::leadStages();

        $allLeads = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('lifecycle_stage', 'lead')
            ->withMin([
                'activities as next_followup_at' => fn ($q) =>
                    $q->whereNull('done_at')->whereNotNull('due_at')
            ], 'due_at')
            ->orderBy('name')
            ->get();
        $leads = $allLeads->groupBy('lead_stage'); // so $leads[$stage] works

        $companies = Company::where('tenant_id', $tenant->id)->orderBy('name')->get();

        return view('tenant.leads.kanban', compact('tenant','leadStages','leads','companies'));
    }

    public function create(Tenant $tenant)
    {
        $tenant = app('tenant');
        return view('tenant.leads.create', compact('tenant'));
    }

    public function store(Request $request, Tenant $tenant)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'name'  => ['required','string','max:190'],
            'email' => ['nullable','email','max:190'],
            'phone' => ['nullable','string','max:50'],
            'notes' => ['nullable','string'],
        ]);

        Contact::create($data + [
            'tenant_id' => $tenant->id,
            'company_id' => null,
            'lifecycle_stage' => 'lead',
            'lead_stage' => 'new',
        ]);

        return redirect()->to(tenant_route('tenant.leads.index'))
            ->with('success', 'Lead created.');
    }

    public function edit(Tenant $tenant, Contact $contact)
    {
        $tenant = app('tenant');

        abort_unless((int) $contact->tenant_id === (int) $tenant->id, 404);
        abort_unless($contact->lifecycle_stage === 'lead', 404);

        $leadStages = Contact::leadStages();

        $contact->load(['company', 'activities.user']); // ✅ for header + timeline
        $activities = $contact->activities;

        return view('tenant.leads.edit', compact('tenant', 'contact', 'leadStages', 'activities'));
    }


    public function update(Request $request, Tenant $tenant, Contact $contact)
    {
        $tenant = app('tenant');

        abort_unless((int) $contact->tenant_id === (int) $tenant->id, 404);
        abort_unless($contact->lifecycle_stage === 'lead', 404);

        $rules = [
            'name'  => ['required','string','max:190'],
            'email' => ['nullable','email','max:190'],
            'phone' => ['nullable','string','max:50'],
            'lead_stage' => ['required', 'in:' . implode(',', Contact::leadStages())],
            'notes' => ['nullable','string'],
        ];

        // Only validate/source update if your contacts table actually has this column
        if (\Illuminate\Support\Facades\Schema::hasColumn('contacts', 'source')) {
            $rules['source'] = ['nullable','string','max:190'];
        }

        $data = $request->validate($rules);

        // Light normalization (optional but useful)
        if (isset($data['email'])) {
            $data['email'] = $data['email'] ? strtolower(trim($data['email'])) : null;
        }
        if (isset($data['phone'])) {
            $data['phone'] = $data['phone'] ? trim($data['phone']) : null;
        }

        $contact->update($data);

        return redirect()->to(tenant_route('tenant.leads.index'))
            ->with('success', 'Lead updated.');
    }


    public function destroy(Tenant $tenant, Contact $contact)
    {
        $tenant = app('tenant');

        abort_unless((int) $contact->tenant_id === (int) $tenant->id, 404);
        abort_unless($contact->lifecycle_stage === 'lead', 404);

        $contact->delete();

        return redirect()->to(tenant_route('tenant.leads.index'))
            ->with('success', 'Lead deleted.');
    }
}




