<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\Request;

class StorefrontProductController extends Controller
{
    public function index(Request $request, string $tenant)
    {
        $tenantModel = Tenant::where('subdomain', $tenant)->firstOrFail();

        $sort = (string) $request->input('sort', 'latest');

        $query = Product::query()
            ->with(['brand', 'category'])
            ->where('tenant_id', $tenantModel->id)
            ->where('is_active', true);

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->string('category')->toString());
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query = match ($sort) {
            'price_asc' => $query->orderBy('unit_rate', 'asc')->orderByDesc('id'),
            'price_desc' => $query->orderBy('unit_rate', 'desc')->orderByDesc('id'),
            'name_asc' => $query->orderBy('name', 'asc')->orderByDesc('id'),
            'name_desc' => $query->orderBy('name', 'desc')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $products = $query->paginate((int) $request->input('limit', 12));

        return response()->json([
            'data' => $products->getCollection()->map(fn ($product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => (float) $product->unit_rate,
                'currency' => $product->currency ?: 'ZAR',
                'image' => $product->image_url,
                'is_featured' => (bool) $product->is_featured,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                    'slug' => $product->brand->slug,
                ] : null,
            ])->values(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(Request $request, string $tenant, string $slug)
    {
        $tenantModel = Tenant::where('subdomain', $tenant)->firstOrFail();

        $product = Product::query()
            ->with(['brand', 'category'])
            ->where('tenant_id', $tenantModel->id)
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => (float) $product->unit_rate,
                'currency' => $product->currency ?: 'ZAR',
                'image' => $product->image_url,
                'is_featured' => (bool) $product->is_featured,
                'default_variant_id' => null,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                    'slug' => $product->brand->slug,
                ] : null,
            ],
        ]);
    }
}
