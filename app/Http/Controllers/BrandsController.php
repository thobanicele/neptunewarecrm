<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Tenant;
use Illuminate\Support\Str;

class BrandsController extends Controller
{
    public function index(Request $request, string $tenant)
    {
        $tenant = app('tenant'); // or tenant()

        $q = trim((string) $request->query('q', ''));

        // filters
        $status = (string) $request->query('status', ''); // active | inactive | ''

        // sorting
        $sort = (string) $request->query('sort', 'name');
        $dir  = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['name', 'slug', 'is_active', 'updated_at', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $brandsQuery = \App\Models\Brand::query()
            ->where('tenant_id', $tenant->id)
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($x) use ($q) {
                    $x->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->when($status !== '', function ($qry) use ($status) {
                if ($status === 'active') $qry->where('is_active', 1);
                if ($status === 'inactive') $qry->where('is_active', 0);
            })
            ->orderBy($sort, $dir)
            ->orderByDesc('id');

        $brands = $brandsQuery
            ->paginate(25)
            ->withQueryString();

        return view('tenant.brands.index', compact(
            'tenant',
            'brands',
            'q',
            'status',
            'sort',
            'dir'
        ));
    }

    public function create(string $tenant)
    {
        return view('tenant.brands.create');
    }

    public function store(Request $request, string $tenant)
    {
        $t = tenant();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['tenant_id'] = $t->id;
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        Brand::create($data);

        return redirect()->to(tenant_route('tenant.brands.index'))
            ->with('success', 'Brand created.');
    }

    public function edit(string $tenant, Brand $brand)
    {
        abort_unless((int)$brand->tenant_id === (int)tenant()->id, 404);
        return view('tenant.brands.edit', compact('brand'));
    }

    public function update(Request $request, string $tenant, Brand $brand)
    {
        abort_unless((int)$brand->tenant_id === (int)tenant()->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $brand->update($data);

        return redirect()->to(tenant_route('tenant.brands.index'))
            ->with('success', 'Brand updated.');
    }

    public function destroy(string $tenant, Brand $brand)
    {
        abort_unless((int)$brand->tenant_id === (int)tenant()->id, 404);
        $brand->delete();

        return redirect()->to(tenant_route('tenant.brands.index'))
            ->with('success', 'Brand deleted.');
    }
}
