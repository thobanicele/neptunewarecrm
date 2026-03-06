<?php

namespace App\Http\Controllers;

use App\Models\PaymentTerm;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentTermsController extends Controller
{
    public function index(Tenant $tenant)
    {
        $tenant = app('tenant');

        $terms = PaymentTerm::query()
            ->where('tenant_id', $tenant->id)
            ->withCount('companies')
            ->orderBy('sort_order')
            ->orderBy('days')
            ->orderBy('name')
            ->get();

        return view('tenant.settings.payment_terms.index', compact('terms', 'tenant'));
    }

    public function create(Tenant $tenant)
    {
        $tenant = app('tenant');
        return view('tenant.settings.payment_terms.create', compact('tenant'));
    }

    public function store(Request $request, Tenant $tenant)
    {
        $tenant = app('tenant');

        $data = $this->validated($request, (int) $tenant->id);

        PaymentTerm::create($data + ['tenant_id' => $tenant->id]);

        return redirect()->to(tenant_route('tenant.settings.payment_terms.index'))
            ->with('success', 'Payment term created.');
    }

    public function edit(Tenant $tenant, PaymentTerm $paymentTerm)
    {
        $tenant = app('tenant');
        abort_unless((int) $paymentTerm->tenant_id === (int) $tenant->id, 404);

        return view('tenant.settings.payment_terms.edit', compact('paymentTerm', 'tenant'));
    }

    public function update(Request $request, Tenant $tenant, PaymentTerm $paymentTerm)
    {
        $tenant = app('tenant');
        abort_unless((int) $paymentTerm->tenant_id === (int) $tenant->id, 404);

        $data = $this->validated($request, (int) $tenant->id, (int) $paymentTerm->id);

        $paymentTerm->update($data);

        return redirect()->to(tenant_route('tenant.settings.payment_terms.index'))
            ->with('success', 'Payment term updated.');
    }

    public function toggle(Tenant $tenant, PaymentTerm $paymentTerm)
    {
        $tenant = app('tenant');
        abort_unless((int) $paymentTerm->tenant_id === (int) $tenant->id, 404);

        $paymentTerm->is_active = ! (bool) $paymentTerm->is_active;
        $paymentTerm->save();

        return back()->with('success', $paymentTerm->is_active ? 'Payment term activated.' : 'Payment term deactivated.');
    }

    public function destroy(Tenant $tenant, PaymentTerm $paymentTerm)
    {
        $tenant = app('tenant');
        abort_unless((int) $paymentTerm->tenant_id === (int) $tenant->id, 404);

        $usedCount = $paymentTerm->companies()->count();

        if ($usedCount > 0) {
            return back()->with('error', "You can't delete this payment term because it is assigned to {$usedCount} compan" . ($usedCount === 1 ? 'y' : 'ies') . ". Deactivate it instead.");
        }

        $paymentTerm->delete();

        return back()->with('success', 'Payment term deleted.');
    }

    private function validated(Request $request, int $tenantId, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('payment_terms', 'name_normalized')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($ignoreId),
            ],
            'days' => [
                'required', 'integer', 'min:0', 'max:3650',
                Rule::unique('payment_terms', 'days')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($ignoreId),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}

