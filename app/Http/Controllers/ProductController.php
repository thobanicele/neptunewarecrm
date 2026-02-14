<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\TaxType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(string $tenantKey, Request $request)
    {
        $tenant = app('tenant');

        $q = trim((string) $request->query('q', ''));

        // filters
        $status = $request->query('status', ''); // active | inactive | ''
        $unit = $request->query('unit', '');     // e.g. "pcs" etc.

        // sorting
        $sort = (string) $request->query('sort', 'name');
        $dir  = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['sku','name','unit_rate','unit','is_active','updated_at','created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'name';

        $query = Product::query()
            ->where('tenant_id', $tenant->id)
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($x) use ($q) {
                    $x->where('sku', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->when($status !== '', function ($qry) use ($status) {
                if ($status === 'active') $qry->where('is_active', 1);
                if ($status === 'inactive') $qry->where('is_active', 0);
            })
            ->when($unit !== '', fn($qry) => $qry->where('unit', $unit))
            ->orderBy($sort, $dir)
            ->orderByDesc('id');

        $products = $query
            ->paginate(15)
            ->withQueryString();

        // filter dropdown options (units)
        $units = Product::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('unit')
            ->where('unit', '<>', '')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit');

        $canExport = tenant_feature($tenant, 'export'); // matches your config key

        return view('tenant.products.index', compact(
            'tenant','products','units','q','status','unit','sort','dir','canExport'
        ));
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

    public function export(string $tenantKey, Request $request): StreamedResponse
    {
        $tenant = app('tenant');

        if (!tenant_feature($tenant, 'export')) {
            return back()->with('error', 'Export to Excel is available on the Premium plan.');
        }

        // same filters/sort as index
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');
        $unit = $request->query('unit', '');

        $sort = (string) $request->query('sort', 'name');
        $dir  = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['sku','name','unit_rate','unit','is_active','updated_at','created_at'];
        if (!in_array($sort, $allowedSorts, true)) $sort = 'name';

        $rows = Product::query()
            ->where('tenant_id', $tenant->id)
            ->when($q !== '', fn($qry) => $qry->where(fn($x) =>
                $x->where('sku', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")
            ))
            ->when($status !== '', function ($qry) use ($status) {
                if ($status === 'active') $qry->where('is_active', 1);
                if ($status === 'inactive') $qry->where('is_active', 0);
            })
            ->when($unit !== '', fn($qry) => $qry->where('unit', $unit))
            ->orderBy($sort, $dir)
            ->orderByDesc('id')
            ->get(['sku','name','description','unit_rate','unit','is_active','created_at','updated_at']);

        $filename = 'products-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU','Name','Description','Rate','Unit','Status','Created','Updated']);

            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->sku,
                    $p->name,
                    $p->description,
                    number_format((float)$p->unit_rate, 2, '.', ''),
                    $p->unit,
                    $p->is_active ? 'Active' : 'Inactive',
                    optional($p->created_at)->format('Y-m-d H:i'),
                    optional($p->updated_at)->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}




