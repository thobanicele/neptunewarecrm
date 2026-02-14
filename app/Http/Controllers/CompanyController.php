<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use App\Models\Contact;
use App\Models\User;
use App\Models\TaxType;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    public function index(string $tenantKey, Request $request)
    {
        $tenant = app('tenant');
       

        $q = trim((string) $request->query('q', ''));

        // filters
        $type = (string) $request->query('type', ''); // customer | supplier | ...

        // sorting
        $sort = (string) $request->query('sort', 'updated_at');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name','type','email','phone','updated_at','created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'updated_at';

        $query = Company::query()
            ->where('tenant_id', $tenant->id)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($type !== '', fn($qq) => $qq->where('type', $type))
            ->orderBy($sort, $dir)
            ->orderByDesc('id');

        $companies = $query
            ->paginate(20)
            ->withQueryString();

        // dropdown options for type filter (from existing data)
        $types = Company::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('type')
            ->where('type', '<>', '')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $canExport = tenant_feature($tenant, 'export');

        return view('tenant.companies.index', compact(
            'tenant','companies','q','type','types','sort','dir','canExport'
        ));
    }

    public function create()
    {
        $tenant = app('tenant');

        $countries = \App\Models\Country::orderBy('name')->get(['id','iso2','name']);
        $companies = Company::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $contacts  = Contact::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $users     = User::where('tenant_id', $tenant->id)->orderBy('name')->get();
        $taxTypes  = TaxType::where('tenant_id', $tenant->id)->orderBy('name')->get();

        $prefillCompanyId = request()->integer('company_id') ?: null;

        return view('tenant.companies.create', compact('countries', 'companies', 'contacts', 'users', 'taxTypes', 'prefillCompanyId'));
    }

    public function store(Request $request)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'name' => [
                'required',
                'max:190',
                Rule::unique('companies', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'type'          => ['required','in:prospect,customer,individual'],

            // Optional: prevent duplicate email in same tenant (ignore nulls automatically)
            'email' => [
                'nullable',
                'email',
                Rule::unique('companies', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],

            'phone'         => ['nullable','max:50'],
            'payment_terms' => ['nullable','string','max:190'],
            'vat_number'    => ['nullable','string','max:190'],
            'vat_treatment' => ['nullable','in:registered,non_registered,exempt,reverse_charge'],

            // Billing (all optional)
            'billing'                  => ['nullable','array'],
            'billing.is_default_billing'     => ['nullable','boolean'],
            'billing.label'            => ['nullable','string','max:190'],
            'billing.attention'        => ['nullable','string','max:190'],
            'billing.phone'            => ['nullable','string','max:50'],
            'billing.line1'            => ['nullable','string','max:190'],
            'billing.line2'            => ['nullable','string','max:190'],
            'billing.city'             => ['nullable','string','max:190'],
            'billing.postal_code'      => ['nullable','string','max:30'],
            'billing.country_iso2'     => ['nullable','string','size:2'],
            'billing.subdivision_id'   => ['nullable','integer'],
            'billing.subdivision_text' => ['nullable','string','max:190'],

            // Shipping (all optional)
            'shipping'                  => ['nullable','array'],
            'shipping.is_default_shipping'     => ['nullable','boolean'],
            'shipping.label'            => ['nullable','string','max:190'],
            'shipping.attention'        => ['nullable','string','max:190'],
            'shipping.phone'            => ['nullable','string','max:50'],
            'shipping.line1'            => ['nullable','string','max:190'],
            'shipping.line2'            => ['nullable','string','max:190'],
            'shipping.city'             => ['nullable','string','max:190'],
            'shipping.postal_code'      => ['nullable','string','max:30'],
            'shipping.country_iso2'     => ['nullable','string','size:2'],
            'shipping.subdivision_id'   => ['nullable','integer'],
            'shipping.subdivision_text' => ['nullable','string','max:190'],
        ]);

        $company = Company::create($data + ['tenant_id' => $tenant->id]);

        $this->storeAddressIfProvided($company, 'billing', Arr::get($data, 'billing', []));
        $this->storeAddressIfProvided($company, 'shipping', Arr::get($data, 'shipping', []));

        return redirect()->to(tenant_route('tenant.companies.index'))
            ->with('success', 'Company created successfully.');
    }

    /**
     * Create an address row if any meaningful fields are present.
     */
    protected function storeAddressIfProvided(Company $company, string $type, array $payload): void
    {
        if (empty($payload)) return;

        $tenant = app('tenant');

        // detect if user actually filled anything (ignore make_default)
        $meaningfulKeys = ['label','attention','phone','line1','line2','city','postal_code','country_iso2','subdivision_id','subdivision_text'];
        $hasAnything = false;
        foreach ($meaningfulKeys as $k) {
            if (!empty($payload[$k])) { $hasAnything = true; break; }
        }
        if (!$hasAnything) return;

        // subdivision selection wins over text
        if (!empty($payload['subdivision_id'])) {
            $payload['subdivision_text'] = null;
        }

        // ✅ country_id is required in DB
        $iso2 = strtoupper($payload['country_iso2'] ?? '');
        $countryId = null;

        if ($iso2) {
            $countryId = Country::where('iso2', $iso2)->value('id');
        }

        // If country_id is required, block save if missing
        if (!$countryId) {
            // You can also throw ValidationException instead, but this is simple
            abort(422, "Invalid country selected for {$type} address.");
        }

        $company->addresses()->create([
            'tenant_id'        => $tenant->id,
            'company_id'       => $company->id,      // ok even if relation sets it
            'type'             => $type,
            'make_default'     => (bool)($payload['make_default'] ?? true),

            'country_id'       => $countryId,        // ✅ REQUIRED
            'country_iso2'     => $iso2 ?: null,     // if your table has this column, keep it

            'label'            => $payload['label'] ?? null,
            'attention'        => $payload['attention'] ?? null,
            'phone'            => $payload['phone'] ?? null,
            'line1'            => $payload['line1'] ?? null,
            'line2'            => $payload['line2'] ?? null,
            'city'             => $payload['city'] ?? null,
            'postal_code'      => $payload['postal_code'] ?? null,
            'subdivision_id'   => $payload['subdivision_id'] ?? null,
            'subdivision_text' => $payload['subdivision_text'] ?? null,
        ]);
    }

   public function edit(Tenant $tenant, Company $company)
    {
        $countries = Country::orderBy('name')->get(['id','name','iso2']);

        $billing = $company->addresses()
            ->where('type', 'billing')
            ->orderByDesc('is_default_billing')
            ->orderByDesc('id')
            ->first();

        $shipping = $company->addresses()
            ->where('type', 'shipping')
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('id')
            ->first();

        return view('tenant.companies.edit', compact('tenant', 'company', 'countries', 'billing', 'shipping'));
    }


    public function show(Tenant $tenant, Company $company)
    {
        abort_unless($company->tenant_id === $tenant->id, 404);

        $company->load(['contacts', 'addresses.country', 'addresses.subdivision']);

        $billing = $company->addresses()
            ->where('type', 'billing')
            ->orderByDesc('is_default_billing')
            ->orderByDesc('id')
            ->with(['country','subdivision'])
            ->first();

        $shipping = $company->addresses()
            ->where('type', 'shipping')
            ->orderByDesc('is_default_shipping')
            ->orderByDesc('id')
            ->with(['country','subdivision'])
            ->first();

        return view('tenant.companies.show', compact('tenant', 'company', 'billing', 'shipping'));
    }

    
    public function update(Request $request, Tenant $tenant, Company $company)
    {
        // safety: ensure company belongs to tenant from route
        if ((int) $company->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'max:190',
                Rule::unique('companies', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($company->id),
            ],
            'type' => ['required','in:prospect,customer,individual'],

            'email' => [
                'nullable',
                'email',
                Rule::unique('companies', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($company->id),
            ],

            'phone'         => ['nullable','max:50'],
            'payment_terms' => ['nullable','string','max:190'],
            'vat_number'    => ['nullable','string','max:190'],
            'vat_treatment' => ['nullable','in:registered,non_registered,exempt,reverse_charge'],

            // Billing
            'billing'                  => ['nullable','array'],
            'billing.make_default'     => ['nullable','boolean'],
            'billing.label'            => ['nullable','string','max:190'],
            'billing.attention'        => ['nullable','string','max:190'],
            'billing.phone'            => ['nullable','string','max:50'],
            'billing.line1'            => ['nullable','string','max:190'],
            'billing.line2'            => ['nullable','string','max:190'],
            'billing.city'             => ['nullable','string','max:190'],
            'billing.postal_code'      => ['nullable','string','max:30'],
            'billing.country_iso2'     => ['nullable','string','size:2'],
            'billing.subdivision_id'   => ['nullable','integer'],
            'billing.subdivision_text' => ['nullable','string','max:190'],

            // Shipping
            'shipping'                  => ['nullable','array'],
            'shipping.make_default'     => ['nullable','boolean'],
            'shipping.label'            => ['nullable','string','max:190'],
            'shipping.attention'        => ['nullable','string','max:190'],
            'shipping.phone'            => ['nullable','string','max:50'],
            'shipping.line1'            => ['nullable','string','max:190'],
            'shipping.line2'            => ['nullable','string','max:190'],
            'shipping.city'             => ['nullable','string','max:190'],
            'shipping.postal_code'      => ['nullable','string','max:30'],
            'shipping.country_iso2'     => ['nullable','string','size:2'],
            'shipping.subdivision_id'   => ['nullable','integer'],
            'shipping.subdivision_text' => ['nullable','string','max:190'],
        ]);

        // 1) Update company
        $company->update(Arr::only($data, [
            'name','type','email','phone','payment_terms','vat_number','vat_treatment',
        ]));

        // 2) Upsert addresses
        $this->upsertAddress($company, 'billing', Arr::get($data, 'billing', []));
        $this->upsertAddress($company, 'shipping', Arr::get($data, 'shipping', []));

        return redirect()->to(tenant_route('tenant.companies.index'))
            ->with('success', 'Company updated successfully.');
    }

    protected function upsertAddress(Company $company, string $type, array $payload): void
    {
        if (empty($payload)) return;

        $tenant = app('tenant');

        $meaningfulKeys = [
            'label','attention','phone','line1','line2','city','postal_code',
            'country_iso2','subdivision_id','subdivision_text',
        ];

        $hasAnything = false;
        foreach ($meaningfulKeys as $k) {
            if (!empty($payload[$k])) { $hasAnything = true; break; }
        }
        if (!$hasAnything) return;

        if (!empty($payload['subdivision_id'])) {
            $payload['subdivision_text'] = null;
        }

        $iso2 = strtoupper($payload['country_iso2'] ?? '');
        $countryId = $iso2 ? Country::where('iso2', $iso2)->value('id') : null;

        if (!$countryId) {
            abort(422, "Invalid country selected for {$type} address.");
        }

        $makeDefault = (bool)($payload['make_default'] ?? true);

        $values = [
            'tenant_id'        => $tenant->id,
            'type'             => $type,
            'country_id'       => $countryId,
            'country_iso2'     => $iso2 ?: null,
            'label'            => $payload['label'] ?? null,
            'attention'        => $payload['attention'] ?? null,
            'phone'            => $payload['phone'] ?? null,
            'line1'            => $payload['line1'] ?? null,
            'line2'            => $payload['line2'] ?? null,
            'city'             => $payload['city'] ?? null,
            'postal_code'      => $payload['postal_code'] ?? null,
            'subdivision_id'   => $payload['subdivision_id'] ?? null,
            'subdivision_text' => $payload['subdivision_text'] ?? null,
        ];

        if ($type === 'billing') {
            $values['is_default_billing'] = $makeDefault ? 1 : 0;
        } else {
            $values['is_default_shipping'] = $makeDefault ? 1 : 0;
        }

        $query = $company->addresses()->where('type', $type);

        if ($type === 'billing') {
            $query->orderByDesc('is_default_billing');
        } else {
            $query->orderByDesc('is_default_shipping');
        }

        $address = $query->orderByDesc('id')->first();

        if ($address) {
            $address->update($values);
        } else {
            $address = $company->addresses()->create($values); // ✅ assign it
        }

        // Ensure only one default per type
        if ($makeDefault) {
            if ($type === 'billing') {
                $company->addresses()
                    ->where('type', 'billing')
                    ->where('id', '!=', $address->id)
                    ->update(['is_default_billing' => 0]);
            } else {
                $company->addresses()
                    ->where('type', 'shipping')
                    ->where('id', '!=', $address->id)
                    ->update(['is_default_shipping' => 0]);
            }
        }
    }

    public function export(string $tenantKey, Request $request): StreamedResponse
    {
        $tenant = app('tenant');

        if (!tenant_feature($tenant, 'export')) {
            return back()->with('error', 'Export to Excel is available on the Premium plan.');
        }

        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');

        $sort = (string) $request->query('sort', 'updated_at');
        $dir  = strtolower((string) $request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name','type','email','phone','updated_at','created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'updated_at';

        $rows = Company::query()
            ->where('tenant_id', $tenant->id)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($type !== '', fn($qq) => $qq->where('type', $type))
            ->orderBy($sort, $dir)
            ->orderByDesc('id')
            ->get(['name','type','email','phone','created_at','updated_at']);

        $filename = 'companies-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Name','Type','Email','Phone','Created','Updated']);

            foreach ($rows as $c) {
                fputcsv($out, [
                    $c->name,
                    $c->type,
                    $c->email,
                    $c->phone,
                    optional($c->created_at)->format('Y-m-d H:i'),
                    optional($c->updated_at)->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
    
}

