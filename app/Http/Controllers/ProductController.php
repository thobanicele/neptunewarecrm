<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\TaxType;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(string $tenantKey, Request $request)
    {
        $tenant = app('tenant');
        $this->authorize('viewAny', Product::class);

        $q = trim((string) $request->query('q', ''));

        $status = $request->query('status', '');
        $unit = $request->query('unit', '');

        $sort = (string) $request->query('sort', 'name');
        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['sku', 'name', 'slug', 'unit_rate', 'unit', 'is_active', 'is_featured', 'updated_at', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $query = Product::query()
            ->with(['brand', 'category', 'taxType'])
            ->where('tenant_id', $tenant->id)
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($x) use ($q) {
                    $x->where('sku', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->when($status !== '', function ($qry) use ($status) {
                if ($status === 'active') {
                    $qry->where('is_active', 1);
                }
                if ($status === 'inactive') {
                    $qry->where('is_active', 0);
                }
            })
            ->when($unit !== '', fn ($qry) => $qry->where('unit', $unit))
            ->orderBy($sort, $dir)
            ->orderByDesc('id');

        $products = $query
            ->paginate(15)
            ->withQueryString();

        $units = Product::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('unit')
            ->where('unit', '<>', '')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit');

        $canExport = tenant_feature($tenant, 'export');

        return view('tenant.products.index', compact(
            'tenant',
            'products',
            'units',
            'q',
            'status',
            'unit',
            'sort',
            'dir',
            'canExport'
        ));
    }

    public function create(Tenant $tenant)
    {
        $tenant = app('tenant');
        $this->authorize('create', Product::class);

        $brands = Brand::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $taxTypes = TaxType::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'rate', 'is_default']);

        return view('tenant.products.create', compact('tenant', 'taxTypes', 'brands', 'categories'));
    }

    public function store(Request $request, Tenant $tenant)
    {
        $tenant = app('tenant');
        $this->authorize('create', Product::class);

        $data = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('products', 'name')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:190',
                Rule::unique('products', 'slug')->where(fn ($q) => $q->where('tenant_id', $tenant->id)),
            ],
            'is_storefront_visible' => $request->boolean('is_storefront_visible'),
            'brand_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'unit_rate' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'currency' => ['nullable', 'string', 'max:10'],
            'tax_type_id' => ['nullable', 'integer'],
            'is_active' => ['nullable'],
            'is_featured' => ['nullable'],
        ]);

        if (! empty($data['tax_type_id'])) {
            TaxType::where('tenant_id', $tenant->id)->findOrFail((int) $data['tax_type_id']);
        }

        if (! empty($data['brand_id'])) {
            Brand::where('tenant_id', $tenant->id)->findOrFail((int) $data['brand_id']);
        }

        if (! empty($data['category_id'])) {
            Category::where('tenant_id', $tenant->id)->findOrFail((int) $data['category_id']);
        }

        $slug = filled($data['slug'] ?? null)
            ? Str::slug((string) $data['slug'])
            : Str::slug(trim((string) $data['name']));

        if ($slug === '') {
            $slug = Str::random(8);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'brand_id' => $data['brand_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'sku' => strtoupper(trim($data['sku'])),
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'image_path' => $imagePath,
            'unit_rate' => (float) $data['unit_rate'],
            'unit' => $data['unit'] ?? null,
            'currency' => $data['currency'] ?? null,
            'tax_type_id' => $data['tax_type_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        $wantsJson =
            $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || $request->isJson()
            || str_contains((string) $request->header('accept'), 'application/json')
            || $request->header('X-Requested-With') === 'XMLHttpRequest';

        if ($wantsJson) {
            $payload = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'description' => $product->description,
                'image_path' => $product->image_path,
                'image_url' => $product->image_url,
                'unit_rate' => (float) $product->unit_rate,
                'unit' => $product->unit,
                'currency' => $product->currency,
                'tax_type_id' => $product->tax_type_id,
                'brand_id' => $product->brand_id,
                'category_id' => $product->category_id,
                'is_active' => (bool) $product->is_active,
                'is_featured' => (bool) $product->is_featured,
                'stock_on_hand' => (int) ($product->stock_on_hand ?? 0),
            ];

            return response()->json([
                'ok' => true,
                'product' => $payload,
                'data' => $payload,
            ]);
        }

        return redirect()
            ->to(tenant_route('tenant.products.index'))
            ->with('success', 'Product created.');
    }

    public function show(Tenant $tenant, Product $product)
    {
        $tenant = app('tenant');
        $this->authorize('view', $product);
        abort_unless((int) $product->tenant_id === (int) $tenant->id, 404);

        $product->load(['taxType', 'brand', 'category']);

        return view('tenant.products.show', compact('tenant', 'product'));
    }

    public function edit(Tenant $tenant, Product $product)
    {
        $tenant = app('tenant');
        $this->authorize('update', $product);
        abort_unless((int) $product->tenant_id === (int) $tenant->id, 404);

        $brands = Brand::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $taxTypes = TaxType::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'rate', 'is_default']);

        return view('tenant.products.edit', compact('tenant', 'product', 'taxTypes', 'brands', 'categories'));
    }

    public function update(Request $request, Tenant $tenant, Product $product)
    {
        $tenant = app('tenant');
        $this->authorize('update', $product);
        abort_unless((int) $product->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('products', 'sku')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($product->id),
            ],
            'name' => [
                'required',
                'string',
                'max:190',
                Rule::unique('products', 'name')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($product->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:190',
                Rule::unique('products', 'slug')
                    ->where(fn ($q) => $q->where('tenant_id', $tenant->id))
                    ->ignore($product->id),
            ],
            'is_storefront_visible' => ['nullable'],
            'brand_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'unit_rate' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'currency' => ['nullable', 'string', 'max:10'],
            'tax_type_id' => ['nullable', 'integer'],
            'is_active' => ['nullable'],
            'is_featured' => ['nullable'],
        ]);

        if (! empty($data['tax_type_id'])) {
            TaxType::where('tenant_id', $tenant->id)->findOrFail((int) $data['tax_type_id']);
        }

        if (! empty($data['brand_id'])) {
            Brand::where('tenant_id', $tenant->id)->findOrFail((int) $data['brand_id']);
        }

        if (! empty($data['category_id'])) {
            Category::where('tenant_id', $tenant->id)->findOrFail((int) $data['category_id']);
        }

        $slug = filled($data['slug'] ?? null)
            ? Str::slug((string) $data['slug'])
            : $product->slug;

        $updateData = [
            'is_storefront_visible' => $request->boolean('is_storefront_visible'),
            'brand_id' => $data['brand_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'sku' => strtoupper(trim($data['sku'])),
            'name' => trim($data['name']),
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'unit_rate' => (float) $data['unit_rate'],
            'unit' => $data['unit'] ?? null,
            'currency' => $data['currency'] ?? null,
            'tax_type_id' => $data['tax_type_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ];

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $updateData['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($updateData);

        return redirect()
            ->to(tenant_route('tenant.products.index'))
            ->with('success', 'Product updated.');
    }

    public function destroy(Tenant $tenant, Product $product)
    {
        $tenant = app('tenant');
        $this->authorize('delete', $product);

        if ((int) $product->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        if ($product->isUsedInTransactions()) {
            $product->update(['is_active' => false]);

            return back()->with('success', 'Product has transactions, so it was deactivated (not deleted).');
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    public function export(string $tenantKey, Request $request): StreamedResponse
    {
        $tenant = app('tenant');
        abort_unless(auth()->user()->can('export.run'), 403);

        if (! tenant_feature($tenant, 'export')) {
            return back()->with('error', 'Export to Excel is available on the Premium plan.');
        }

        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');
        $unit = $request->query('unit', '');

        $sort = (string) $request->query('sort', 'name');
        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['sku', 'name', 'slug', 'unit_rate', 'unit', 'is_active', 'is_featured', 'updated_at', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $rows = Product::query()
            ->where('tenant_id', $tenant->id)
            ->when($q !== '', fn ($qry) => $qry->where(fn ($x) =>
                $x->where('sku', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
            ))
            ->when($status !== '', function ($qry) use ($status) {
                if ($status === 'active') {
                    $qry->where('is_active', 1);
                }
                if ($status === 'inactive') {
                    $qry->where('is_active', 0);
                }
            })
            ->when($unit !== '', fn ($qry) => $qry->where('unit', $unit))
            ->orderBy($sort, $dir)
            ->orderByDesc('id')
            ->get([
                'sku',
                'name',
                'slug',
                'description',
                'unit_rate',
                'unit',
                'is_active',
                'is_featured',
                'created_at',
                'updated_at',
            ]);

        $filename = 'products-' . now()->format('Ymd-Hi') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['SKU', 'Name', 'Slug', 'Description', 'Rate', 'Unit', 'Status', 'Featured', 'Created', 'Updated']);

            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->sku,
                    $p->name,
                    $p->slug,
                    $p->description,
                    number_format((float) $p->unit_rate, 2, '.', ''),
                    $p->unit,
                    $p->is_active ? 'Active' : 'Inactive',
                    $p->is_featured ? 'Yes' : 'No',
                    optional($p->created_at)->format('Y-m-d H:i'),
                    optional($p->updated_at)->format('Y-m-d H:i'),
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}




