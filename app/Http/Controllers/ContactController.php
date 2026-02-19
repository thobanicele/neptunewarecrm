<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index(string $tenantKey, Request $request)
    {
        $tenant = app('tenant');
        $this->authorize('viewAny', Contact::class);

        $q = trim((string) $request->query('q', ''));
        $stage = (string) $request->query('stage', '');

        // sorting
        $sort = (string) $request->query('sort', 'updated_at');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        // allow only safe sorts (some are contact fields, some are joined/display-only)
        $allowedSorts = ['name','email','phone','lifecycle_stage','updated_at','created_at','company'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'updated_at';

        $query = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('lifecycle_stage', '!=', 'lead')
            ->with('company')
            ->when($stage !== '', fn($qq) => $qq->where('lifecycle_stage', $stage))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$q}%"));
                });
            });

        // sorting logic
        if ($sort === 'company') {
            // sort by related company name (left join)
            $query->leftJoin('companies', function ($join) use ($tenant) {
                    $join->on('companies.id', '=', 'contacts.company_id')
                        ->where('companies.tenant_id', '=', $tenant->id);
                })
                ->select('contacts.*')
                ->orderBy('companies.name', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        $contacts = $query
            ->orderByDesc('contacts.id')
            ->paginate(20)
            ->withQueryString();

        // stage options (use your own if you have a helper)
        $stages = ['contact','customer','vendor','partner','other'];

        $canExport = tenant_feature($tenant, 'export');

        return view('tenant.contacts.index', compact(
            'tenant','contacts','q','stage','sort','dir','stages','canExport'
        ));
    }

    public function create()
    {
        $tenant = app('tenant');
        $this->authorize('create', Contact::class);

        $companies = Company::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('tenant.contacts.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');
        $this->authorize('create', Contact::class);

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
        $this->authorize('view', $contact);

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
        $this->authorize('update', $contact);

        abort_unless($contact->tenant_id === $tenant->id, 404);

        $companies = Company::where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get();

        return view('tenant.contacts.edit', compact('contact', 'companies'));
    }

    public function update(Request $request, string $tenantKey, Contact $contact)
    {
        $tenant = app('tenant');
        $this->authorize('update', $contact);

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
        $this->authorize('delete', $contact);

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

    public function export(string $tenantKey, Request $request): StreamedResponse
    {
        $tenant = app('tenant');
        abort_unless(auth()->user()->can('export.run'), 403);

        if (!tenant_feature($tenant, 'export')) {
            return back()->with('error', 'Export to Excel is available on the Premium plan.');
        }

        $q = trim((string) $request->query('q', ''));
        $stage = (string) $request->query('stage', '');

        $sort = (string) $request->query('sort', 'updated_at');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name','email','phone','lifecycle_stage','updated_at','created_at','company'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'updated_at';

        $query = Contact::query()
            ->where('tenant_id', $tenant->id)
            ->where('lifecycle_stage', '!=', 'lead')
            ->with('company')
            ->when($stage !== '', fn($qq) => $qq->where('lifecycle_stage', $stage))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$q}%"));
                });
            });

        if ($sort === 'company') {
            $query->leftJoin('companies', function ($join) use ($tenant) {
                    $join->on('companies.id', '=', 'contacts.company_id')
                        ->where('companies.tenant_id', '=', $tenant->id);
                })
                ->select('contacts.*')
                ->orderBy('companies.name', $dir);
        } else {
            $query->orderBy($sort, $dir);
        }

        $rows = $query
            ->orderByDesc('contacts.id')
            ->get(['contacts.id','contacts.name','contacts.email','contacts.phone','contacts.lifecycle_stage','contacts.company_id','contacts.created_at','contacts.updated_at']);

        $filename = 'contacts-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Name','Email','Phone','Company','Stage','Created','Updated']);

            foreach ($rows as $c) {
                $c->loadMissing('company');
                fputcsv($out, [
                    $c->name,
                    $c->email,
                    $c->phone,
                    $c->company?->name,
                    $c->lifecycle_stage,
                    optional($c->created_at)->format('Y-m-d H:i'),
                    optional($c->updated_at)->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }


}


