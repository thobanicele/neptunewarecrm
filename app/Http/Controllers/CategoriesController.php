<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Tenant; 
use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(\Illuminate\Http\Request $request, string $tenant)
    {
        $tenant = app('tenant');

        $q = trim((string) $request->query('q', ''));

        // filters
        $status = (string) $request->query('status', ''); // active | inactive | ''

        // sorting
        $sort = (string) $request->query('sort', 'sort_order');
        $dir  = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['sort_order', 'name', 'slug', 'is_active', 'updated_at', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'sort_order';
        }

        $query = \App\Models\Category::query()
            ->with(['parent:id,name']) // ✅ avoid N+1
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
            ->orderBy($sort, $dir);

        // ✅ stable ordering without fighting the chosen sort
        if ($sort !== 'name') {
            $query->orderBy('name', 'asc');
        }

        $categories = $query
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('tenant.categories.index', compact(
            'tenant',
            'categories',
            'q',
            'status',
            'sort',
            'dir'
        ));
    }

    public function create(string $tenant)
    {
        $parents = Category::where('tenant_id', tenant()->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('tenant.categories.create', compact('parents'));
    }

    public function store(Request $request, string $tenant)
    {
        $t = tenant();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160'],
            'parent_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['tenant_id'] = $t->id;
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        // Ensure parent belongs to tenant
        if (!empty($data['parent_id'])) {
            $parentOk = Category::where('tenant_id', $t->id)->where('id', $data['parent_id'])->exists();
            abort_unless($parentOk, 422);
        }

        Category::create($data);

        return redirect()->to(tenant_route('tenant.categories.index'))
            ->with('success', 'Category created.');
    }

    public function edit(string $tenant, Category $category)
    {
        abort_unless((int)$category->tenant_id === (int)tenant()->id, 404);

        $parents = Category::where('tenant_id', tenant()->id)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return view('tenant.categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, string $tenant, Category $category)
    {
        abort_unless((int)$category->tenant_id === (int)tenant()->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:160'],
            'parent_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if (!empty($data['parent_id'])) {
            abort_unless((int)$data['parent_id'] !== (int)$category->id, 422);
            $parentOk = Category::where('tenant_id', tenant()->id)->where('id', $data['parent_id'])->exists();
            abort_unless($parentOk, 422);
        }

        $category->update($data);

        return redirect()->to(tenant_route('tenant.categories.index'))
            ->with('success', 'Category updated.');
    }

    public function destroy(string $tenant, Category $category)
    {
        abort_unless((int)$category->tenant_id === (int)tenant()->id, 404);

        // Optional: prevent delete if has children
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete a category that has sub-categories.');
        }

        $category->delete();

        return redirect()->to(tenant_route('tenant.categories.index'))
            ->with('success', 'Category deleted.');
    }
}
