<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadLifecycleController extends Controller
{
    public function updateStage(Request $request, Tenant $tenant, Contact $contact)
    {
        $tenant = app('tenant');

        // ✅ pass the model instance
        $this->authorize('leadsStage', $contact);

        abort_unless((int) $contact->tenant_id === (int) $tenant->id, 404);
        abort_unless($contact->lifecycle_stage === 'lead', 404);

        $data = $request->validate([
            'lead_stage' => ['required', Rule::in(Contact::leadStages())],
        ]);

        $contact->update(['lead_stage' => $data['lead_stage']]);

        return response()->json(['ok' => true]);
    }

    public function qualify(Request $request, Tenant $tenant, Contact $contact)
    {
        $tenant = app('tenant');
        $this->authorize('leadsQualify', $contact);

        abort_unless((int) $contact->tenant_id === (int) $tenant->id, 404);
        abort_unless($contact->lifecycle_stage === 'lead', 404);

        // ✅ Prevent duplicates: if another contact exists with same email/phone
        $dup = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('id', '!=', $contact->id)
            ->where(function ($q) use ($contact) {
                if (!empty($contact->email)) $q->orWhere('email', $contact->email);
                if (!empty($contact->phone)) $q->orWhere('phone', $contact->phone);
            })
            ->first();

        if ($dup) {
            return back()->withErrors([
                'duplicate' => 'A contact with this email/phone already exists. Please use that existing contact instead of qualifying this lead.',
            ])->withInput();
        }

        $data = $request->validate([
            'company_mode' => ['required', Rule::in(['create','attach'])],

            'company_name' => ['nullable','string','max:190'],
            'company_id'   => ['nullable','integer'],

            'create_deal'  => ['nullable','boolean'],
            'deal_title'   => ['nullable','string','max:190', 'required_if:create_deal,1'],
            'deal_value'   => ['nullable','numeric','min:0', 'required_if:create_deal,1'],
        ]);

        // ✅ Resolve company (create or attach, tenant-safe)
        $company = null;

        if ($data['company_mode'] === 'attach') {
            $request->validate([
                'company_id' => [
                    'required',
                    Rule::exists('companies', 'id')->where(fn($q) => $q->where('tenant_id', $tenant->id)),
                ],
            ]);

            $company = Company::where('tenant_id', $tenant->id)->findOrFail($data['company_id']);
        } else {
            // Create new company OR reuse by name (basic duplicate prevention)
            $name = trim((string) ($data['company_name'] ?? ''));

            if ($name === '') {
                // fallback: make a company from lead name
                $name = $contact->name . ' (Individual)';
            }

            $company = Company::query()
                ->where('tenant_id', $tenant->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if (!$company) {
                $company = Company::create([
                    'tenant_id' => $tenant->id,
                    'name'      => $name,
                ]);
            }
        }

        // ✅ Convert lead into a contact (and attach company)
        $contact->update([
            'company_id'       => $company?->id,
            'lead_stage'       => 'converted',
            'lifecycle_stage'  => 'contact',  // important: removes from leads kanban
        ]);

        // ✅ Optional: create deal
        if (!empty($data['create_deal'])) {
            $pipeline = Pipeline::where('tenant_id', $tenant->id)->orderByDesc('is_default')->first();

            if (!$pipeline) {
                return back()->withErrors(['pipeline' => 'No pipeline found for this tenant. Create a pipeline first.']);
            }

            $firstStage = $pipeline->stages()->orderBy('position')->first();

            if (!$firstStage) {
                return back()->withErrors(['stage' => 'Pipeline has no stages. Add stages first.']);
            }

            $deal = Deal::create([
                'tenant_id'           => $tenant->id,
                'pipeline_id'         => $pipeline->id,
                'stage_id'            => $firstStage->id,
                'title'               => $data['deal_title'],
                'amount'              => $data['deal_value'],
                'company_id'          => $company?->id,
                'primary_contact_id'  => $contact->id,
            ]);

            return redirect()->to(tenant_route('tenant.deals.show', ['deal' => $deal->id]))
                ->with('success', 'Lead qualified and deal created.');
        }

        return redirect()->to(tenant_route('tenant.leads.index'))
            ->with('success', 'Lead qualified (converted to contact).');
    }
}





