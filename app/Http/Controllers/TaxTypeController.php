<?php

namespace App\Http\Controllers;

use App\Models\TaxType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaxTypeController extends Controller
{
    public function index(\App\Models\Tenant $tenant)
    {
        $tenant = app('tenant');

        $taxTypes = TaxType::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('tenant.tax-types.index', compact('tenant', 'taxTypes'));
    }

    public function create(\App\Models\Tenant $tenant)
    {
        $tenant = app('tenant');
        return view('tenant.tax-types.create', compact('tenant'));
    }

    public function store(Request $request, \App\Models\Tenant $tenant)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'name' => [
                'required','string','max:100',
                Rule::unique('tax_types', 'name')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'rate' => ['required','numeric','min:0','max:100'],
            'is_default' => ['nullable'],
            'is_active' => ['nullable'],
        ]);

        $makeDefault = $request->boolean('is_default');

        if ($makeDefault) {
            TaxType::where('tenant_id', $tenant->id)->update(['is_default' => false]);
        }

        TaxType::create([
            'tenant_id' => $tenant->id,
            'name' => trim($data['name']),
            'rate' => (float) $data['rate'],
            'is_default' => $makeDefault,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Ensure at least one default exists
        if (!TaxType::where('tenant_id', $tenant->id)->where('is_default', true)->exists()) {
            $firstActive = TaxType::where('tenant_id', $tenant->id)->where('is_active', true)->first();
            if ($firstActive) {
                $firstActive->update(['is_default' => true]);
            }
        }

        return redirect()
            ->to(tenant_route('tenant.tax-types.index'))
            ->with('success', 'Tax type created.');
    }

    public function edit(\App\Models\Tenant $tenant, TaxType $taxType)
    {
        $tenant = app('tenant');
        abort_unless((int) $taxType->tenant_id === (int) $tenant->id, 404);

        return view('tenant.tax-types.edit', compact('tenant', 'taxType'));
    }

    public function update(Request $request, \App\Models\Tenant $tenant, TaxType $taxType)
    {
        $tenant = app('tenant');
        abort_unless((int) $taxType->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'name' => [
                'required','string','max:100',
                Rule::unique('tax_types', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($taxType->id),
            ],
            'rate' => ['required','numeric','min:0','max:100'],
            'is_default' => ['nullable'],
            'is_active' => ['nullable'],
        ]);

        $makeDefault = $request->boolean('is_default');
        $active = $request->boolean('is_active', true);

        // Prevent turning OFF the last active tax type
        if (!$active) {
            $activeCount = TaxType::where('tenant_id', $tenant->id)->where('is_active', true)->count();
            if ($activeCount <= 1 && $taxType->is_active) {
                return back()->withErrors(['is_active' => 'You must have at least one active tax type.'])->withInput();
            }
        }

        // If making default, clear other defaults
        if ($makeDefault) {
            TaxType::where('tenant_id', $tenant->id)->update(['is_default' => false]);
        }

        $taxType->update([
            'name' => trim($data['name']),
            'rate' => (float) $data['rate'],
            'is_default' => $makeDefault,
            'is_active' => $active,
        ]);

        // If default got deactivated, pick another default
        if (!$taxType->is_active && $taxType->is_default) {
            $taxType->update(['is_default' => false]);
            $firstActive = TaxType::where('tenant_id', $tenant->id)->where('is_active', true)->first();
            if ($firstActive) $firstActive->update(['is_default' => true]);
        }

        // Ensure at least one default exists
        if (!TaxType::where('tenant_id', $tenant->id)->where('is_default', true)->exists()) {
            $firstActive = TaxType::where('tenant_id', $tenant->id)->where('is_active', true)->first();
            if ($firstActive) $firstActive->update(['is_default' => true]);
        }

        return redirect()
            ->to(tenant_route('tenant.tax-types.index'))
            ->with('success', 'Tax type updated.');
    }

    public function makeDefault(\App\Models\Tenant $tenant, TaxType $taxType)
    {
        $tenant = app('tenant');
        abort_unless((int) $taxType->tenant_id === (int) $tenant->id, 404);

        if (!$taxType->is_active) {
            return back()->withErrors(['default' => 'You cannot set an inactive tax type as default.']);
        }

        TaxType::where('tenant_id', $tenant->id)->update(['is_default' => false]);
        $taxType->update(['is_default' => true]);

        return back()->with('success', 'Default tax type updated.');
    }

    public function toggleActive(\App\Models\Tenant $tenant, TaxType $taxType)
    {
        $tenant = app('tenant');
        abort_unless((int) $taxType->tenant_id === (int) $tenant->id, 404);

        // Prevent disabling last active
        if ($taxType->is_active) {
            $activeCount = TaxType::where('tenant_id', $tenant->id)->where('is_active', true)->count();
            if ($activeCount <= 1) {
                return back()->withErrors(['toggle' => 'You must have at least one active tax type.']);
            }
        }

        $taxType->update(['is_active' => !$taxType->is_active]);

        // If we deactivated the default, pick a new default
        if (!$taxType->is_active && $taxType->is_default) {
            $taxType->update(['is_default' => false]);
            $firstActive = TaxType::where('tenant_id', $tenant->id)->where('is_active', true)->first();
            if ($firstActive) $firstActive->update(['is_default' => true]);
        }

        return back()->with('success', 'Tax type updated.');
    }
}

