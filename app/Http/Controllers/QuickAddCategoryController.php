<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Http\Request;

class QuickAddCategoryController extends Controller
{
    public function store(Request $request, string $tenant)
    {
        $t = tenant();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $name = trim(preg_replace('/\s+/', ' ', $data['name']));

        $existing = Category::where('tenant_id', $t->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if (!$existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }

            return response()->json([
                'ok' => true,
                'created' => false,
                'category' => ['id' => $existing->id, 'name' => $existing->name],
            ], 200);
        }

        $cat = Category::create([
            'tenant_id' => $t->id,
            'name' => $name,
            'is_active' => true,
            'parent_id' => null,
            'sort_order' => 0,
        ]);

        return response()->json([
            'ok' => true,
            'created' => true,
            'category' => ['id' => $cat->id, 'name' => $cat->name],
        ], 201);
    }
}
