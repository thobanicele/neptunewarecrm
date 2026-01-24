<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\Country;
use Illuminate\Http\Request;

class CompanyAddressController extends Controller
{
    public function index(Company $company)
    {
        // Ensure tenant scoping if needed:
        // abort_if($company->tenant_id !== tenant()->id, 404);

        $addresses = $company->addresses()
            ->with(['country','subdivision'])
            ->orderByDesc('is_default_billing')
            ->orderByDesc('is_default_shipping')
            ->latest()
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'type' => $a->type,
                    'label' => $a->label,
                    'is_default_billing' => (bool) $a->is_default_billing,
                    'is_default_shipping' => (bool) $a->is_default_shipping,
                    'snapshot' => $a->toSnapshotString(),
                ];
            });

        return response()->json($addresses);
    }

    public function store(Request $request, Company $company)
    {
        $data = $request->validate([
            'type' => ['required','in:billing,shipping,other'],
            'label' => ['nullable','string','max:120'],
            'attention' => ['nullable','string','max:120'],
            'phone' => ['nullable','string','max:40'],
            'line1' => ['nullable','string','max:255'],
            'line2' => ['nullable','string','max:255'],
            'city' => ['nullable','string','max:120'],
            'postal_code' => ['nullable','string','max:30'],

            'country_iso2' => ['required','string','size:2'],
            'subdivision_id' => ['nullable','integer','exists:country_subdivisions,id'],
            'subdivision_text' => ['nullable','string','max:120'],

            'make_default_billing' => ['nullable','boolean'],
            'make_default_shipping' => ['nullable','boolean'],
        ]);

        $country = Country::where('iso2', strtoupper($data['country_iso2']))->firstOrFail();

        $addr = new CompanyAddress();
        $addr->tenant_id = tenant()->id;
        $addr->company_id = $company->id;

        $addr->type = $data['type'];
        $addr->label = $data['label'] ?? null;
        $addr->attention = $data['attention'] ?? null;
        $addr->phone = $data['phone'] ?? null;
        $addr->line1 = $data['line1'] ?? null;
        $addr->line2 = $data['line2'] ?? null;
        $addr->city = $data['city'] ?? null;
        $addr->postal_code = $data['postal_code'] ?? null;

        $addr->country_id = $country->id;
        $addr->subdivision_id = $data['subdivision_id'] ?? null;
        $addr->subdivision_text = $data['subdivision_text'] ?? null;

        $addr->save();

        // Defaults
        if (!empty($data['make_default_billing'])) {
            $this->applyDefault($company, 'billing', $addr->id);
        }
        if (!empty($data['make_default_shipping'])) {
            $this->applyDefault($company, 'shipping', $addr->id);
        }

        $addr->load(['country','subdivision']);

        return response()->json([
            'id' => $addr->id,
            'snapshot' => $addr->toSnapshotString(),
        ], 201);
    }

    public function setDefault(Request $request, Company $company, CompanyAddress $address)
    {
        $data = $request->validate([
            'kind' => ['required','in:billing,shipping'],
        ]);

        $this->applyDefault($company, $data['kind'], $address->id);

        return response()->json(['ok' => true]);
    }

    private function applyDefault(Company $company, string $kind, int $addressId): void
    {
        if ($kind === 'billing') {
            $company->addresses()->update(['is_default_billing' => false]);
            $company->addresses()->whereKey($addressId)->update(['is_default_billing' => true]);
        } else {
            $company->addresses()->update(['is_default_shipping' => false]);
            $company->addresses()->whereKey($addressId)->update(['is_default_shipping' => true]);
        }
    }
}

