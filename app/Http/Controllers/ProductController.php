<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\TaxType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request, \App\Models\Tenant $tenant)
    {
        $tenant = app('tenant');

        $q = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->where('tenant_id', $tenant->id)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('tenant.products.index', compact('tenant', 'products', 'q'));
    }

    public function create(\App\Models\Tenant $tenant)
    {
        $tenant = app('tenant');

        $taxTypes = TaxType::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'rate', 'is_default']);

        return view('tenant.products.create', compact('tenant', 'taxTypes'));
    }

    public function store(Request $request, \App\Models\Tenant $tenant)
    {
        $tenant = app('tenant');

        $data = $request->validate([
            'sku' => [
                'required', 'string', 'max:80',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'name' => [
                'required', 'string', 'max:190',
                Rule::unique('products', 'name')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'unit_rate' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'currency' => ['nullable', 'string', 'max:10'],
            'tax_type_id' => ['nullable', 'integer'],
            'is_active' => ['nullable'], // checkbox
        ]);

        // Tenant safety for tax_type_id
        if (!empty($data['tax_type_id'])) {
            TaxType::where('tenant_id', $tenant->id)->findOrFail((int) $data['tax_type_id']);
        }

        Product::create([
            'tenant_id' => $tenant->id,
            'sku' => strtoupper(trim($data['sku'])),
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'unit_rate' => (float) $data['unit_rate'],
            'unit' => $data['unit'] ?? null,
            'currency' => $data['currency'] ?? null,
            'tax_type_id' => $data['tax_type_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->to(tenant_route('tenant.products.index'))
            ->with('success', 'Product created.');
    }

    public function show(\App\Models\Tenant $tenant, Product $product)
    {
        $tenant = app('tenant');
        abort_unless((int) $product->tenant_id === (int) $tenant->id, 404);

        $product->load('taxType');

        return view('tenant.products.show', compact('tenant', 'product'));
    }

    public function edit(\App\Models\Tenant $tenant, Product $product)
    {
        $tenant = app('tenant');
        abort_unless((int) $product->tenant_id === (int) $tenant->id, 404);

        $taxTypes = TaxType::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'rate', 'is_default']);

        return view('tenant.products.edit', compact('tenant', 'product', 'taxTypes'));
    }

    public function update(Request $request, \App\Models\Tenant $tenant, Product $product)
    {
        $tenant = app('tenant');
        abort_unless((int) $product->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'sku' => [
                'required', 'string', 'max:80',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($product->id),
            ],
            'name' => [
                'required', 'string', 'max:190',
                Rule::unique('products', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($product->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'unit_rate' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'currency' => ['nullable', 'string', 'max:10'],
            'tax_type_id' => ['nullable', 'integer'],
            'is_active' => ['nullable'],
        ]);

        // Tenant safety for tax_type_id
        if (!empty($data['tax_type_id'])) {
            TaxType::where('tenant_id', $tenant->id)->findOrFail((int) $data['tax_type_id']);
        }

        $product->update([
            'sku' => strtoupper(trim($data['sku'])),
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'unit_rate' => (float) $data['unit_rate'],
            'unit' => $data['unit'] ?? null,
            'currency' => $data['currency'] ?? null,
            'tax_type_id' => $data['tax_type_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->to(tenant_route('tenant.products.index'))
            ->with('success', 'Product updated.');
    }

    public function destroy(\App\Models\Tenant $tenant, Product $product)
    {
        $tenant = app('tenant');
        abort_unless((int) $product->tenant_id === (int) $tenant->id, 404);

        $product->delete();

        return redirect()
            ->to(tenant_route('tenant.products.index'))
            ->with('success', 'Product deleted.');
    }
}




